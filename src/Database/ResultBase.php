<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Database;

use QCubed\Exception\Caller;
use QCubed\Exception\InvalidCast;
use QCubed\ObjectBase;
use QCubed\Type;

/**
 */

/**
 * Class ResultBase
 *
 * Class to handle results sent by a database upon querying
 *
 * @property string[] ColumnAliasArray;
 * @package QCubed\Database
 */
abstract class ResultBase extends ObjectBase
{
    /** @var array The column alias array. This is needed for instantiating cursors. */
    protected array $strColumnAliasArray;

    /**
     * Fetches a result row as an array.
     * The array can contain both numerical and associative indexes, allowing access to row data by column index or column name.
     * @abstract
     * @return mixed
     */
    abstract public function fetchArray(): mixed;

    /**
     * Fetches one row as an enumerated (with numerical indexes) array from the result set
     * @abstract
     * @return mixed
     */
    abstract public function fetchRow(): mixed;

    /**
     * Fetches a single field value from a result set.
     *
     * This method is abstract and should be implemented in a subclass.
     * It is used to retrieve the value of a specific field in a database query result,
     * typically by specifying the column index or name.
     *
     * @return mixed The value of the requested field. The return type depends on the implementation.
     */
    abstract public function fetchField(): mixed;

    /**
     * Retrieves multiple field values from a result set.
     *
     * This abstract method must be implemented in a subclass. It is designed to fetch
     * and return multiple fields or columns from a database query result, usually representing
     * a row of data.
     *
     * @return mixed An array or collection containing the field values. The structure and type
     *               of the returned data depends on the implementation.
     */
    abstract public function fetchFields(): mixed;

    /**
     * Counts the total number of rows in a data set.
     *
     * This abstract method must be implemented in a subclass to determine
     * the total number of rows available, typically in a database query result or collection.
     *
     * @return int The total number of rows.
     */
    abstract public function countRows(): int;

    /**
     * Counts the number of fields in a result set.
     *
     * This abstract method is intended to be implemented by subclasses to provide
     * a mechanism for determining the total number of fields available in a given
     * result set, often used in the context of database queries or data manipulation.
     *
     * @return int The total number of fields in the result set.
     */
    abstract public function countFields(): int;

    /**
     * Retrieves the next row from a result set.
     *
     * This method is abstract and must be implemented in a subclass.
     * It is used to fetch the next row of data from a database query result,
     * typically returning an associative array or similar structure containing row data.
     *
     * @return mixed The next row of data from the result set. The exact return type depends on the implementation.
     */
    abstract public function getNextRow(): mixed;

    /**
     * Retrieves multiple rows from a result set.
     *
     * This method is abstract and must be implemented in a subclass.
     * It is intended to fetch and return an array of rows from a data source,
     * typically as part of processing a query result.
     *
     * @return mixed An array of rows or a format defined by the implementation. The structure and content depend on the implementation.
     */
    abstract public function getRows(): mixed;

    /**
     * Closes the current resource or connection.
     *
     * This method is abstract and must be implemented by subclasses.
     * It is intended to terminate any active resource, connection, or session,
     * ensuring proper cleanup and release of associated resources.
     *
     * @return void
     */
    abstract public function close(): void;

    /**
     * Magic method to retrieve the value of a property dynamically.
     *
     * This method intercepts attempts to access undefined or protected properties
     * and provides values based on the specified property name. It can also defer
     * to parent implementation if applicable.
     *
     * @param string $strName The name of the property to retrieve.
     * @return mixed The value of the requested property. The return type depends on the property logic.
     * @throws Caller If the property is not found or is inaccessible.
     */
    public function __get(string $strName): mixed
    {
        switch ($strName) {
            case 'ColumnAliasArray':
                return $this->strColumnAliasArray;
            default:
                try {
                    return parent::__get($strName);
                } catch (Caller $objExc) {
                    $objExc->incrementOffset();
                    throw $objExc;
                }
        }
    }

    public function __set(string $strName, mixed $mixValue): void
    {
        switch ($strName) {
            case 'ColumnAliasArray':
                try {
                    $this->strColumnAliasArray = Type::cast($mixValue, Type::ARRAY_TYPE);
                } catch (InvalidCast $objExc) {
                    $objExc->incrementOffset();
                    throw $objExc;
                }
                break;
            default:
                try {
                    parent::__set($strName, $mixValue);
                } catch (Caller $objExc) {
                    $objExc->incrementOffset();
                    throw $objExc;
                }
        }
    }
}