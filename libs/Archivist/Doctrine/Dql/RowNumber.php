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
 * @author Filip Procházka <filip@prochazka.su>
 */
class RowNumber extends FunctionNode
{

	/**
	 * @var AggregateExpression[]|FunctionNode[]|InputParameter[]
	 */
	private $values = array();



	public function parse(Parser $parser)
	{
		$parser->match(Lexer::T_IDENTIFIER);
		$parser->match(Lexer::T_OPEN_PARENTHESIS);

		// Do the field.
		$this->values[] = $parser->OrderByItem();

		// todo: while for other columns to sort

		$parser->match(Lexer::T_CLOSE_PARENTHESIS);
	}



	public function getSql(SqlWalker $sqlWalker)
	{
		$platform = $sqlWalker->getConnection()->getDatabasePlatform();
		if ($platform instanceof PostgreSqlPlatform) {
			return $this->getPostgreSql($sqlWalker);
		}

		throw new NotImplementedException;
	}



	private function getPostgreSql(SqlWalker $sqlWalker)
	{
		$query = 'ROW_NUMBER() OVER(ORDER BY ';

		for ($i = 1; $i <= count($this->values); $i++) {
			if ($i > 1) {
				$query .= ', ';
			}

			$query .= $this->values[$i - 1]->dispatch($sqlWalker);
		}

		$query .= ' )';

		return $query;
	}

}
