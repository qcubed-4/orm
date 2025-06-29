<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Database\Mysqli5;

use DateMalformedStringException;
use QCubed\Database\RowBase;
use QCubed\Database\FieldType;
use QCubed\Exception\Caller;
use QCubed\Exception\InvalidCast;
use QCubed\QDateTime;
use QCubed\Type;

/**
 *
 * @package DatabaseAdapters
 */
class MysqliRow extends RowBase
{
    protected array $strColumnArray;

    /**
     * Constructor to initialize the column array.
     *
     * @param array $strColumnArray The array of column values to be assigned.
     *
     * @return void
     */
    public function __construct(array $strColumnArray)
    {
        $this->strColumnArray = $strColumnArray;
    }

    /**
     * Retrieves the value of a column from the internal column array, optionally casting it to a specific type.
     *
     * @param string $strColumnName The name of the column to retrieve.
     * @param string|null $strColumnType The desired type to cast the column value to, if applicable.
     *                                   Supported types include FieldType::BIT, FieldType::BLOB, FieldType::CHAR,
     *                                   FieldType::VAR_CHAR, FieldType::DATE, FieldType::DATE_TIME, FieldType::TIME,
     *                                   FieldType::FLOAT, and FieldType::INTEGER.
     *
     * @return mixed The value of the column, optionally cast to the specified type. Returns null if the column is not found.
     * @throws DateMalformedStringException
     * @throws Caller
     * @throws InvalidCast
     */
    public function getColumn(string $strColumnName, ?string $strColumnType = null): mixed
    {
        if (!isset($this->strColumnArray[$strColumnName])) {
            return null;
        }
        $strColumnValue = $this->strColumnArray[$strColumnName];

        switch ($strColumnType) {
            case FieldType::BIT:
                // Account for single bit value
                $chrBit = $strColumnValue;
                if ((strlen($chrBit) == 1) && (ord($chrBit) == 0)) {
                    return false;
                }

                // Otherwise, use PHP conditional to determine true or false
                return (bool)$strColumnValue;

            case FieldType::BLOB:
            case FieldType::CHAR:
            case FieldType::VAR_CHAR:
                return Type::cast($strColumnValue, Type::STRING);

            case FieldType::DATE:
                return new QDateTime($strColumnValue, null, QDateTime::DATE_ONLY_TYPE);
            case FieldType::DATE_TIME:
                return new QDateTime($strColumnValue, null, QDateTime::DATE_AND_TIME_TYPE);
            case FieldType::TIME:
                return new QDateTime($strColumnValue, null, QDateTime::TIME_ONLY_TYPE);

            case FieldType::FLOAT:
                return Type::cast($strColumnValue, Type::FLOAT);

            case FieldType::INTEGER:
                return Type::cast($strColumnValue, Type::INTEGER);

            default:
                return $strColumnValue;
        }
    }

    /**
     * Checks if a given column exists in the internal column array.
     *
     * @param string $strColumnName The name of the column to check for existence.
     *
     * @return bool True if the column exists, false otherwise.
     */
    public function columnExists(string $strColumnName): bool
    {
        return array_key_exists($strColumnName, $this->strColumnArray);
    }

    /**
     * Retrieves the internal array of column names and their associated values.
     *
     * @return array An associative array where keys represent column names and values represent their corresponding data.
     */
    public function getColumnNameArray(): array
    {
        return $this->strColumnArray;
    }
}