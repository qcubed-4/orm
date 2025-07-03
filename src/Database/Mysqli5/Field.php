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
use QCubed\Database\FieldType;

/**
 * Class Field
 * @package QCubed\Database\Mysqli5
 */
class Field extends MysqliField
{
    /**
     * Sets the field type based on the provided MySQL field type and flags.
     *
     * @param int $intMySqlFieldType The MySQL field type identifier.
     * @param int $intFlags Flags associated with the MySQL field.
     * @return void
     * @throws Exception
     */
    protected function setFieldType(int $intMySqlFieldType, int $intFlags): void
    {
        switch ($intMySqlFieldType) {
            case MYSQLI_TYPE_NEWDECIMAL:
                $this->strType = FieldType::VAR_CHAR;
                break;

            case MYSQLI_TYPE_BIT:
                $this->strType = FieldType::BIT;
                break;

            default:
                parent::setFieldType($intMySqlFieldType, $intFlags);
        }
    }
}