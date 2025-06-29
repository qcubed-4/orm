<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Database\Exception;

use Exception;
use QCubed\Exception\Caller;

/**
 * Class to handle exceptions related to database querying
 * @property-read int $ErrorNumber The amount of error provided by the SQL server
 * @property-read string $Query The query caused the error
 * @package DatabaseAdapters
 * @was QDatabaseException
 */
abstract class ExceptionBase extends Caller
{
    /** @var int Error number */
    protected int $intErrorNumber;
    /** @var string Query which produced the error */
    protected string $strQuery;

    /**
     * PHP magic function to get property values
     * @param string $strName
     *
     * @return array|int|mixed
     * @throws Exception
     */
    public function __get(string $strName): mixed
    {
        return match ($strName) {
            "ErrorNumber" => $this->intErrorNumber,
            "Query" => $this->strQuery,
            default => parent::__get($strName),
        };
    }
}
