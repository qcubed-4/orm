<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Database;

use Exception;
use QCubed\Exception\Caller;
use QCubed\ObjectBase;


/**
 * Class ForeignKey
 *
 * @property-read string $KeyName
 * @property-read string[] $ColumnNameArray
 * @property-read string $ReferenceTableName
 * @property-read string[] $ReferenceColumnNameArray
 *
 * @package QCubed\Database
 */
class ForeignKey extends ObjectBase
{
    protected string $strKeyName;
    protected array $strColumnNameArray;
    protected string $strReferenceTableName;
    protected array $strReferenceColumnNameArray;

    /**
     * Constructor method to initialize the class with specified parameters.
     *
     * @param string $strKeyName The name of the key.
     * @param array $strColumnNameArray An array of column names.
     * @param string $strReferenceTableName The name of the reference table.
     * @param array $strReferenceColumnNameArray An array of reference column names.
     *
     * @return void
     */
    public function __construct(string $strKeyName, array $strColumnNameArray, string $strReferenceTableName, array $strReferenceColumnNameArray)
    {
        $this->strKeyName = $strKeyName;
        $this->strColumnNameArray = $strColumnNameArray;
        $this->strReferenceTableName = $strReferenceTableName;
        $this->strReferenceColumnNameArray = $strReferenceColumnNameArray;
    }

    /**
     * PHP magic method
     *
     * @param string $strName Property name
     *
     * @return mixed
     * @throws Exception|Caller
     */
    public function __get(string $strName): mixed
    {
        switch ($strName) {
            case "KeyName":
                return $this->strKeyName;
            case "ColumnNameArray":
                return $this->strColumnNameArray;
            case "ReferenceTableName":
                return $this->strReferenceTableName;
            case "ReferenceColumnNameArray":
                return $this->strReferenceColumnNameArray;
            default:
                try {
                    return parent::__get($strName);
                } catch (Caller $objExc) {
                    $objExc->incrementOffset();
                    throw $objExc;
                }
        }
    }
}

