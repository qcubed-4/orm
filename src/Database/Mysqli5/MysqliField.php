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
use QCubed\Database\FieldBase;
use QCubed\Database\FieldType;
use QCubed\Exception\Caller;

/**
 *
 * @package DatabaseAdapters
 */
class MysqliField extends FieldBase
{
    /**
     * Constructor method to initialize the object with field data and optional database connection.
     *
     * @param mixed $mixFieldData Field data object containing details such as name, table, and type.
     * @param MysqliDatabase|null $objDb Optional database connection object for fetching additional field information.
     * @return void
     * @throws Caller
     */
    public function __construct(mixed $mixFieldData, ?MysqliDatabase $objDb = null)
    {
        $this->strName = $mixFieldData->name;
        $this->strOriginalName = $mixFieldData->orgname;
        $this->strTable = $mixFieldData->table;
        $this->strOriginalTable = $mixFieldData->orgtable;
        $this->strDefault = $mixFieldData->def;
        $this->intMaxLength = null;
        $this->strComment = null;

        // Set strOriginalName to Name if it isn't set
        if (!$this->strOriginalName) {
            $this->strOriginalName = $this->strName;
        }

        if ($this->strOriginalTable) {
            $objDescriptionResult = $objDb->query(sprintf("SHOW FULL FIELDS FROM `%s`", $this->strOriginalTable));
            while (($objRow = $objDescriptionResult->fetchArray())) {
                if ($objRow["Field"] == $this->strOriginalName) {

                    $this->strDefault = $objRow["Default"];
                    // Calculate MaxLength of this column (e.g., if it's a varchar, calculate length of varchar
                    // NOTE: $mixFieldData->max_length in the MySQL spec is **DIFFERENT**
                    $strLengthArray = explode("(", $objRow["Type"]);
                    if ((count($strLengthArray) > 1) &&
                        (strtolower($strLengthArray[0]) != 'enum') &&
                        (strtolower($strLengthArray[0]) != 'set')
                    ) {
                        $strLengthArray = explode(")", $strLengthArray[1]);
                        $this->intMaxLength = $strLengthArray[0];

                        // If the length is something like (7,2), then let's pull out just the "7"
                        $intCommaPosition = strpos($this->intMaxLength, ',');
                        if ($intCommaPosition !== false) {
                            $this->intMaxLength = substr($this->intMaxLength, 0, $intCommaPosition);
                            $this->intMaxLength++; // this is a decimal, so max length should include the decimal point too.
                        }

                        if (!is_numeric($this->intMaxLength)) {
                            throw new Exception("Not a valid Column Length: " . $objRow["Type"]);
                        }
                    }

                    // Get the field comment
                    $this->strComment = $objRow["Comment"];
                }
            }
        }

        $this->blnIdentity = (bool)(($mixFieldData->flags & MYSQLI_AUTO_INCREMENT_FLAG));
        $this->blnNotNull = (bool)(($mixFieldData->flags & MYSQLI_NOT_NULL_FLAG));
        $this->blnPrimaryKey = (bool)(($mixFieldData->flags & MYSQLI_PRI_KEY_FLAG));
        $this->blnUnique = (bool)(($mixFieldData->flags & MYSQLI_UNIQUE_KEY_FLAG));

        $this->setFieldType($mixFieldData->type, $mixFieldData->flags);
    }

    /**
     * Sets the field type based on MySQL field type constants and flags.
     *
     * @param int $intMySqlFieldType The numeric constant representing the MySQL field type.
     * @param int $intFlags Additional flags that provide context for the field type (e.g., binary or update flags).
     * @return void
     * @throws Exception When the field type is unsupported or cannot be determined.
     */
    protected function setFieldType(int $intMySqlFieldType, int $intFlags): void
    {

        if (version_compare(PHP_VERSION, '5.6.15') >= 0) {
            if (defined("MYSQLI_TYPE_JSON") && $intMySqlFieldType == MYSQLI_TYPE_JSON) {
                $this->strType = FieldType::JSON;
                return;
            }
        }
        switch ($intMySqlFieldType) {
            case MYSQLI_TYPE_TINY:
                if ($this->intMaxLength == 1) {
                    $this->strType = FieldType::BIT;
                } else {
                    $this->strType = FieldType::INTEGER;
                }
                break;
            case MYSQLI_TYPE_SHORT:
            case MYSQLI_TYPE_LONG:
            case MYSQLI_TYPE_LONGLONG:
            case MYSQLI_TYPE_YEAR:
            case MYSQLI_TYPE_INT24:
                $this->strType = FieldType::INTEGER;
                break;
            case MYSQLI_TYPE_NEWDECIMAL:
            case MYSQLI_TYPE_DECIMAL:
                // NOTE: PHP's best response to fixed point exact precision numbers is to use the bcmath library.
                // Bcmath requires string inputs. If you try to do math directly on these, PHP will convert to float,
                // so for those who care, they will need to be careful. For those who do not care, then PHP will do
                // the conversion anyway.
                $this->strType = FieldType::VAR_CHAR;
                break;

            case MYSQLI_TYPE_FLOAT:
                $this->strType = FieldType::FLOAT;
                break;
            case MYSQLI_TYPE_DOUBLE:
                // NOTE: PHP does not offer full support of double-precision floats.
                // The Value will be set as a VarChar which will guarantee that the precision will be maintained.
                //    However, you will not be able to support full typing control (e.g., you would
                //    not be able to use a QFloatTextBox -- only a regular QTextBox)
                $this->strType = FieldType::VAR_CHAR;
                break;
            case MYSQLI_TYPE_DATE:
                $this->strType = FieldType::DATE;
                break;
            case MYSQLI_TYPE_TIME:
                $this->strType = FieldType::TIME;
                break;
            case MYSQLI_TYPE_TIMESTAMP:
                // A special situation that we take advantage of to automatically implement optimistic locking
                if ($intFlags & MYSQLI_ON_UPDATE_NOW_FLAG) {
                    $this->strType = FieldType::VAR_CHAR;
                    $this->blnTimestamp = true;
                } else {
                    $this->strType = FieldType::DATE_TIME;
                }
                break;
            case MYSQLI_TYPE_DATETIME:
                $this->strType = FieldType::DATE_TIME;
                break;
            case MYSQLI_TYPE_TINY_BLOB:
            case MYSQLI_TYPE_MEDIUM_BLOB:
            case MYSQLI_TYPE_LONG_BLOB:
            case MYSQLI_TYPE_BLOB:
            case MYSQLI_TYPE_STRING:
            case MYSQLI_TYPE_VAR_STRING:
                if ($intFlags & MYSQLI_BINARY_FLAG) {
                    $this->strType = FieldType::BLOB;
                } else {
                    $this->strType = FieldType::VAR_CHAR;
                }
                break;
            case MYSQLI_TYPE_CHAR:
                $this->strType = FieldType::CHAR;
                break;
            case defined('MYSQLI_TYPE_INTERVAL') ? MYSQLI_TYPE_INTERVAL : -1:
                throw new Exception("QCubed MySqliDatabase library: MYSQLI_TYPE_INTERVAL is not supported");
            case MYSQLI_TYPE_NULL:
                throw new Exception("QCubed MySqliDatabase library: MYSQLI_TYPE_NULL is not supported");
            case MYSQLI_TYPE_NEWDATE:
                throw new Exception("QCubed MySqliDatabase library: MYSQLI_TYPE_NEWDATE is not supported");
            case MYSQLI_TYPE_ENUM:
                throw new Exception("QCubed MySqliDatabase library: MYSQLI_TYPE_ENUM is not supported.  Use TypeTables instead.");
            case MYSQLI_TYPE_SET:
                throw new Exception("QCubed MySqliDatabase library: MYSQLI_TYPE_SET is not supported.  Use TypeTables instead.");
            case MYSQLI_TYPE_GEOMETRY:
                throw new Exception("QCubed MySqliDatabase library: MYSQLI_TYPE_GEOMETRY is not supported");
            default:
                throw new Exception("Unable to determine MySqli Database Field Type: " . $intMySqlFieldType);
        }
    }
}
