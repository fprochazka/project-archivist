<?php

use Tracy\Debugger;



function bd($var, $title = NULL)
{
	if (Debugger::$productionMode) {
		return $var;
	}

	$trace = debug_backtrace();
	$traceTitle = (isset($trace[1]['class']) ? htmlspecialchars($trace[1]['class']) . "->" : NULL) .
		(isset($trace[1]['function']) ? htmlspecialchars($trace[1]['function']) . '()' : htmlspecialchars(basename($trace[0]['file']))) .
		':' . $trace[0]['line'];

	if (!is_scalar($title) && $title !== NULL) {
		foreach (func_get_args() as $arg) {
			Debugger::barDump($arg, $traceTitle);
		}

		return $var;
	}

	return Debugger::barDump($var, $title ? : $traceTitle);
}
