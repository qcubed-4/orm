<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Codegen;

use QCubed\Exception\Caller;
use QCubed\Exception\InvalidCast;
use QCubed\Exception\UndefinedProperty;
use QCubed\ObjectBase;
use QCubed\Type;
use Exception;

/**
 * Used by the QCubed Code Generator to describe a table Index
 * @package Codegen
 *
 * @property string $KeyName
 * @property boolean $Unique
 * @property boolean $PrimaryKey
 * @property string[] $ColumnNameArray
 */
class Index extends ObjectBase
{

    /////////////////////////////
    // Protected Member Variables
    /////////////////////////////

    /**
     * Name of the index object, as defined in the database or create a script
     * @var string KeyName
     */
    protected string $strKeyName;

    /**
     * Specifies whether or not the index is unique
     * @var bool Unique
     */
    protected bool $blnUnique;

    /**
     * Specifies whether or not the column is the Primary Key index
     * @var bool PrimaryKey
     */
    protected bool $blnPrimaryKey;

    /**
     * Array of strings containing the names of the columns that
     * this index indexes (indexed numerically)
     * @var string[] ColumnNameArray
     */
    protected array $strColumnNameArray;


    ////////////////////
    // Public Overriders
    ////////////////////

    /**
     * Override method to perform a property "Get"
     * This will get the value of $strName
     *
     * @param string $strName Name of the property to get
     * @return mixed
     *@throws Caller
     */
    public function __get(string $strName): mixed
    {
        switch ($strName) {
            case 'KeyName':
                return $this->strKeyName;
            case 'Unique':
                return $this->blnUnique;
            case 'PrimaryKey':
                return $this->blnPrimaryKey;
            case 'ColumnNameArray':
                return $this->strColumnNameArray;
            default:
                try {
                    return parent::__get($strName);
                } catch (Caller $objExc) {
                    $objExc->incrementOffset();
                    throw $objExc;
                }
        }
    }

    /**
     * Override method to perform a property "Set"
     * This will set the property $strName to be $mixValue
     *
     * @param string $strName Name of the property to set
     * @param string $mixValue New value of the property
     * @return void
     * @throws Caller
     * @throws InvalidCast
     * @throws UndefinedProperty
     * @throws Exception
     */
    public function __set(string $strName, mixed $mixValue): void
    {
        try {
            switch ($strName) {
                case 'KeyName':
                    $this->strKeyName = Type::cast($mixValue, Type::STRING);
                    break;
                case 'Unique':
                    $this->blnUnique = Type::cast($mixValue, Type::BOOLEAN);
                    break;
                case 'PrimaryKey':
                    $this->blnPrimaryKey = Type::cast($mixValue, Type::BOOLEAN);
                    break;
                case 'ColumnNameArray':
                    $this->strColumnNameArray = Type::cast($mixValue, Type::ARRAY_TYPE);
                    break;
                default:
                    parent::__set($strName, $mixValue);
            }
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }
    }
}