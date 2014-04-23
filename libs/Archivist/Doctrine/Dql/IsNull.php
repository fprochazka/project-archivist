<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Doctrine\Dql;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Kdyby;
use Nette;



/**
 * "IsNull" "(" SimpleArithmeticExpression ")"
 *
 * @author Filip Procházka <filip@prochazka.su>
 */
class IsNull extends FunctionNode
{

	public $expression;



	/**
	 * @override
	 */
	public function getSql(SqlWalker $sqlWalker)
	{
		return $sqlWalker->walkArithmeticPrimary($this->expression) . " IS NULL";
	}



	/**
	 * @override
	 */
	public function parse(Parser $parser)
	{
		$parser->match(Lexer::T_IDENTIFIER);
		$parser->match(Lexer::T_OPEN_PARENTHESIS);

		$this->expression = $parser->ArithmeticPrimary();

		$parser->match(Lexer::T_CLOSE_PARENTHESIS);
	}
}
