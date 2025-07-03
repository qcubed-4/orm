<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Database\Mysqli5;

use QCubed\Database\Exception\ExceptionBase;


/**
 * Exception
 */
class MysqliException extends ExceptionBase
{
    /**
     * Constructor initializes the MySqli error message, error number, and query string.
     *
     * @param string $strMessage The error message describing the MySqli issue.
     * @param int $intNumber The error number associated with the MySqli issue.
     * @param string $strQuery The SQL query that caused the error.
     * @return void
     */
    public function __construct(string $strMessage, int $intNumber, string $strQuery)
    {
        parent::__construct(sprintf("MySqli Error: %s", $strMessage), 2);
        $this->intErrorNumber = $intNumber;
        $this->strQuery = $strQuery;
    }
}