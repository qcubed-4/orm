<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Database\Mysqli5;

use Exception;
use mysqli_result;
use QCubed\Database\ResultBase;

/**
 * Class to handle results sent by a database upon querying
 */
class MysqliResult extends ResultBase
{
    protected ?mysqli_result $objMySqliResult;
    protected MysqliDatabase $objDb;

    /**
     * Constructor method for initializing the object with a MySQLi result set and database connection.
     *
     * @param mysqli_result|null $objResult The result set returned from a MySQLi query or null.
     * @param MysqliDatabase $objDb An instance of the database connection object.
     * @return void
     */
    public function __construct(?mysqli_result $objResult, MysqliDatabase $objDb)
    {
        $this->objMySqliResult = $objResult;
        $this->objDb = $objDb;
    }

    /**
     * Fetches a result row as an associative, numeric array, or both, from the MySQLi result set.
     *
     * @return array|null Returns an array representing the fetched row, or null if there are no more rows in the result set.
     */
    public function fetchArray(): ?array
    {
        return $this->objMySqliResult->fetch_array();
    }

    /**
     * Fetches all field definitions from the MySQLi result set and converts them into an array of MysqliField objects.
     *
     * @return MysqliField[] An array of MysqliField objects representing the fields in the result set.
     * @throws Exception
     */
    public function fetchFields(): array
    {
        $objArrayToReturn = array();
        while ($objField = $this->objMySqliResult->fetch_field()) {
            $objArrayToReturn[] = new MysqliField($objField, $this->objDb);
        }
        return $objArrayToReturn;
    }

    /**
     * Fetches the next field information from the MySQLi result object.
     *
     * @return MysqliField|null Returns an instance of MysqliField if a field is available, or null if no more fields are available.
     * @throws Exception
     */
    public function fetchField(): ?MysqliField
    {
        if ($objField = $this->objMySqliResult->fetch_field()) {
            return new MysqliField($objField, $this->objDb);
        }
        return null;
    }

    /**
     * Fetches a single row from the MySQLi result set as a numeric array.
     *
     * @return array|null An array of strings representing the fetched row, or null if no more rows are available.
     */
    public function fetchRow(): ?array
    {
        return $this->objMySqliResult->fetch_row();
    }

    /**
     * Fetches information about the next field in the result set.
     *
     * @return object|false Returns an object containing field definition information, or false if no more fields exist.
     */
    public function mySqlFetchField(): object|false
    {
        return $this->objMySqliResult->fetch_field();
    }

    /**
     * Counts the number of rows in the MySQLi result set.
     *
     * @return int The total number of rows in the result set.
     */
    public function countRows(): int
    {
        return $this->objMySqliResult->num_rows;
    }

    /**
     * Retrieves the count of fields in the current MySQLi result set.
     *
     * @return int The number of fields in the result set.
     */
    public function countFields(): int
    {
        return $this->objMySqliResult->field_count;
    }

    /**
     * Closes the MySQLi result set and frees up the associated resources.
     *
     * @return void
     */
    public function close(): void
    {
        $this->objMySqliResult->free();
    }

    /**
     * Retrieves the next row from the result set as an instance of MysqliRow or null if no rows are available.
     *
     * @return MysqliRow|null Returns an instance of MysqliRow representing the next row in the result set, or null if no rows are available.
     */
    public function getNextRow(): ?MysqliRow
    {
        $strColumnArray = $this->fetchArray();

        if ($strColumnArray) {
            return new MysqliRow($strColumnArray);
        } else {
            return null;
        }
    }

    /**
     * Retrieves all rows from the result set and returns them as an array.
     *
     * @return array An array containing all rows from the result set. Each element of the array represents a row.
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