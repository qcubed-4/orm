<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Database\PostgreSql;

use QCubed\Database\ResultBase;

/**
 * Class to handle results sent by a database upon querying
 * @package DatabaseAdapters
 */
class PostgreResult extends ResultBase
{
    protected mixed $objPgSqlResult;
    protected Database $objDb;

    /**
     * Constructor method to initialize the database result and database instance.
     *
     * @param mixed $objResult The database query result object or resource.
     * @param Database $objDb The database instance.
     * @return void
     */
    public function __construct(mixed $objResult, Database $objDb)
    {
        $this->objPgSqlResult = $objResult;
        $this->objDb = $objDb;
    }

    /**
     * Fetch result (single result) as an array
     *
     * @return array
     */
    public function fetchArray(): array
    {
        return pg_fetch_array($this->objPgSqlResult);
    }

    /**
     * Fetches multiple field values.
     *
     * @return null The field values if available; otherwise, null.
     */
    public function fetchFields(): null
    {
        return null;  // Not implemented
    }

    /**
     * Fetches a single field value.
     *
     * @return null The field value if available; otherwise, null.
     */
    public function fetchField(): null
    {
        return null;  // Not implemented
    }

    /**
     * Fetches a row from the PostgreSQL result resource.
     *
     * @return array|false Returns an array representing a row from the result set, or false if there are no more rows.
     */
    public function fetchRow(): array|false
    {
        return pg_fetch_row($this->objPgSqlResult);
    }

    /**
     * Counts the number of rows in the PostgreSQL result set.
     *
     * @return int The number of rows in the result set.
     */
    public function countRows(): int
    {
        return pg_num_rows($this->objPgSqlResult);
    }

    /**
     * Counts the number of fields in the current PostgreSQL result resource.
     *
     * @return int The number of fields in the result set.
     */
    public function countFields(): int
    {
        return pg_num_fields($this->objPgSqlResult);
    }

    /**
     * Free the memory. Connection closes when a script ends
     */
    public function close(): void
    {
        pg_free_result($this->objPgSqlResult);
    }

    /**
     * Retrieves the next row from the dataset if available.
     *
     * @return Row|null An instance of the Row class if the next row exists; otherwise, null.
     */
    public function getNextRow(): ?Row
    {
        $strColumnArray = $this->fetchArray();

        if ($strColumnArray) {
            return new Row($strColumnArray);
        } else {
            return null;
        }
    }

    /**
     * Retrieves all rows by iterating through available data.
     *
     * @return array An array of database row objects.
     */
    public function getRows(): array
    {
        $objDbRowArray = array();
        while ($objDbRow = $this->getNextRow()) {
            $objDbRowArray[] = $objDbRow;
        }
        return $objDbRowArray;
    }
}