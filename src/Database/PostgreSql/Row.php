<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Database\PostgreSql;

use DateMalformedStringException;
use QCubed\Database\RowBase;
use QCubed\Database\FieldType;
use QCubed\Exception\Caller;
use QCubed\Exception\InvalidCast;
use QCubed\QDateTime;
use QCubed\Type;

/**
 * Class for handling a single row from a PostgreSQL database result set
 *
 */
class Row extends RowBase
{
    /** @var string[] Column name value pairs for current result set */
    protected string|array $strColumnArray;

    /**
     * QPostgreSqlDatabaseRow constructor.
     *
     * @param string|array $strColumnArray
     */
    public function __construct(string|array $strColumnArray)
    {
        $this->strColumnArray = $strColumnArray;
    }

    /**
     * Retrieves the value of a specified column from the database data array.
     * The value can optionally be cast to a specific type based on the column type.
     *
     * @param string $strColumnName The name of the column to retrieve.
     * @param string|null $strColumnType Optional. The type of the column, used to cast the value (e.g., BIT, FLOAT, VAR_CHAR, etc.).
     * @return mixed Returns the column value, cast to the specified type when applicable. Returns null if the column does not exist in the array.
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
                // PostgreSQL returns 't' or 'f' for boolean fields
                if ($strColumnValue == 'f') {
                    return false;
                } else {
                    return (bool)$strColumnValue;
                }

            case FieldType::BLOB:
            case FieldType::CHAR:
            case FieldType::VAR_CHAR:
            case FieldType::JSON: // JSON is basically String
                return Type::cast($strColumnValue, Type::STRING);
            case FieldType::DATE:
            case FieldType::DATE_TIME:
            case FieldType::TIME:
                return new QDateTime($strColumnValue);

            case FieldType::FLOAT:
                return Type::cast($strColumnValue, Type::FLOAT);

            case FieldType::INTEGER:
                return Type::cast($strColumnValue, Type::INTEGER);

            default:
                return $strColumnValue;
        }
    }

    /**
     * Checks if a specified column exists in the database data array.
     *
     * @param string $strColumnName The name of the column to check for existence.
     * @return bool Returns true if the column exists in the array, otherwise false.
     */
    public function columnExists(string $strColumnName): bool
    {
        return array_key_exists($strColumnName, $this->strColumnArray);
    }

    /**
     * Retrieves the array of column names from the database data array.
     *
     * @return array Returns an associative array of column names as keys and their corresponding values.
     */
    public function getColumnNameArray(): array
    {
        return $this->strColumnArray;
    }

    /**
     * Resolves the given value into a boolean representation based on specific conditions.
     *
     * @param mixed $mixValue The value to be resolved. Expected to be 't', 'f', or another value.
     * @return bool|null Returns true if the value isn't', false if the value is 'f', and null otherwise.
     */
    public function resolveBooleanValue(mixed $mixValue): ?bool
    {
        if ($mixValue == 'f') {
            return false;
        } elseif ($mixValue == 't') {
            return true;
        } else {
            return null;
        }
    }
}