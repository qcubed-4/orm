<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Database;

use QCubed\ObjectBase;

/**
 * Base class for all Database rows. Implemented by Database adapters
 * @package DatabaseAdapters
 */
abstract class RowBase extends ObjectBase
{
    /**
     * Gets the value of a column from a result row returned by the database
     *
     * @param string $strColumnName Name of the column
     * @param string|null $strColumnType Data type
     *
     * @return mixed
     */
    abstract public function getColumn(string $strColumnName, ?string $strColumnType = null): mixed;

    /**
     * Tells whether a particular column exists in a returned database row
     *
     * @param string $strColumnName Name of the column
     *
     * @return bool
     */
    abstract public function columnExists(string $strColumnName): bool;

    /**
     * Retrieves an array containing the names of all columns from a result set.
     *
     * @return array An array of column names.
     */
    abstract public function getColumnNameArray(): array;

    /**
     * Returns the boolean value corresponding to whatever a boolean column returns. Some database types
     * return strings that represent the boolean values. The default is to use a PHP cast.
     * @param mixed $mixValue the value of the BIT column
     * @return bool|null
     */
    public function resolveBooleanValue(mixed $mixValue): ?bool
    {
        if ($mixValue === null) {
            return null;
        }
        return ((bool)$mixValue);
    }
}