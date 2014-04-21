<?php

namespace Archivist;


interface Exception
{

}



/**
 * The exception that is thrown when the value of an argument is
 * outside the allowable range of values as defined by the invoked method.
 */
class ArgumentOutOfRangeException extends \InvalidArgumentException implements Exception
{

}



/**
 * The exception that is thrown when a method call is invalid for the object's
 * current state, method has been invoked at an illegal or inappropriate time.
 */
class InvalidStateException extends \RuntimeException implements Exception
{

}



/**
 * The exception that is thrown when a requested method or operation is not implemented.
 */
class NotImplementedException extends \LogicException implements Exception
{

}



/**
 * The exception that is thrown when an invoked method is not supported. For scenarios where
 * it is sometimes possible to perform the requested operation, see InvalidStateException.
 */
class NotSupportedException extends \LogicException implements Exception
{

}



/**
 * The exception that is thrown when a requested method or operation is deprecated.
 */
class DeprecatedException extends NotSupportedException implements Exception
{

}



/**
 * The exception that is thrown when accessing a class member (property or method) fails.
 */
class MemberAccessException extends \LogicException implements Exception
{

}



/**
 * The exception that is thrown when an I/O error occurs.
 */
class IOException extends \RuntimeException implements Exception
{

}



/**
 * The exception that is thrown when accessing a file that does not exist on disk.
 */
class FileNotFoundException extends IOException implements Exception
{

}



/**
 * The exception that is thrown when part of a file or directory cannot be found.
 */
class DirectoryNotFoundException extends IOException implements Exception
{

}



/**/
/**
 * The exception that is thrown when an argument does not match with the expected value.
 */
class InvalidArgumentException extends \InvalidArgumentException implements Exception
{

}



/**
 * The exception that is thrown when an illegal index was requested.
 */
class OutOfRangeException extends \OutOfRangeException implements Exception
{

}



/**
 * The exception that is thrown when a value (typically returned by function) does not match with the expected value.
 */
class UnexpectedValueException extends \UnexpectedValueException implements Exception
{

}



/**
 * The exception that is thrown when static class is instantiated.
 */
class StaticClassException extends \LogicException implements Exception
{

}
