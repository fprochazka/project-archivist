<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Doctrine\Dql;

use Archivist\NotImplementedException;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\Query\AST\AggregateExpression;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\InputParameter;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;



/**
 * @see https://github.com/beberlei/DoctrineExtensions/blob/5e4ec9c3ec3434151e1c73144b4ab87ae30aefbc/lib/DoctrineExtensions/Query/Mysql/Field.php
 * @author Filip Procházka <filip@prochazka.su>
 * @author  Jeremy Hicks <jeremy.hicks@gmail.com>
 */
class Field extends FunctionNode
{

	/**
	 * @var AggregateExpression|FunctionNode|InputParameter
	 */
	private $field;

	/**
	 * @var AggregateExpression[]|FunctionNode[]|InputParameter[]
	 */
	private $values = array();



	public function parse(Parser $parser)
	{
		$parser->match(Lexer::T_IDENTIFIER);
		$parser->match(Lexer::T_OPEN_PARENTHESIS);

		// Do the field.
		$this->field = $parser->ArithmeticPrimary();

		// Add the strings to the values array. FIELD must
		// be used with at least 1 string not including the field.

		$lexer = $parser->getLexer();

		while (count($this->values) < 1 ||
			$lexer->lookahead['type'] != Lexer::T_CLOSE_PARENTHESIS) {
			$parser->match(Lexer::T_COMMA);
			$this->values[] = $parser->ArithmeticPrimary();
		}

		$parser->match(Lexer::T_CLOSE_PARENTHESIS);
	}



	public function getSql(SqlWalker $sqlWalker)
	{
		$platform = $sqlWalker->getConnection()->getDatabasePlatform();
		if ($platform instanceof PostgreSqlPlatform) {
			return $this->getPostgreSql($sqlWalker);

		} elseif ($platform instanceof MySqlPlatform) {
			return $this->getMysqlSql($sqlWalker);
		}

		throw new NotImplementedException;
	}



	private function getMysqlSql(SqlWalker $sqlWalker)
	{
		$query = 'FIELD(';

		$query .= $this->field->dispatch($sqlWalker);

		$query .= ',';

		for ($i = 0; $i < count($this->values); $i++) {
			if ($i > 0) {
				$query .= ',';
			}

			$query .= $this->values[$i]->dispatch($sqlWalker);
		}

		$query .= ')';

		return $query;
	}



	private function getPostgreSql(SqlWalker $sqlWalker)
	{
		$query = '(CASE';

		for ($i = 1; $i <= count($this->values); $i++) {
			$query .= ' WHEN (' . $this->field->dispatch($sqlWalker) . ') = ' .
				$this->values[$i - 1]->dispatch($sqlWalker) . ' THEN ' . $i;
		}

		$query .= ' END)';

		return $query;
	}

}
