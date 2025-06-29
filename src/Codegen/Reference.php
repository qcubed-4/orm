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
use QCubed\ObjectBase;
use QCubed\Type;
use Exception;

/**
 * Used by the QCubed Code Generator to describe a column reference
 * (aka a Foreign Key)
 * @package Codegen
 *
 * @property string $KeyName
 * @property string $Table
 * @property string $Column
 * @property string $PropertyName
 * @property string $VariableName
 * @property string $VariableType
 * @property boolean $IsType
 * @property ReverseReference ReverseReference
 * @property string $Name
 */
class Reference extends ObjectBase
{

    /////////////////////////////
    // Protected Member Variables
    /////////////////////////////

    /**
     * Name of the foreign key object, as defined in the database or create a script
     * @var string KeyName
     */
    protected string $strKeyName;

    /**
     * Name of the table that is being referenced
     * @var string Table
     */
    protected string $strTable;

    /**
     * Name of the column that is being referenced
     * @var string Column
     */
    protected string $strColumn;

    /**
     * Name of the referenced object as a class Property,
     * So if the column that this reference points from is named
     * "primary_annual_report_id", it would be PrimaryAnnualReport
     * @var string PropertyName
     */
    protected string $strPropertyName;

    /**
     * Name of the referenced object as a class-protected Member object,
     * So if the column that this reference points from is named
     * "primary_annual_report_id", it would be objPrimaryAnnualReport
     * @var string VariableName
     */
    protected string $strVariableName;

    /**
     * The type of the protected member object (should be based off of $this->strTable)
     * So if referencing the table "annual_report", it would be AnnualReport
     * @var string VariableType
     */
    protected string $strVariableType;

    /**
     * If the table that this reference points to is a type table, then this is true
     * @var bool IsType
     */
    protected bool $blnIsType;

    /**
     * The reverse reference pointing back to this reference.
     *
     * @var ReverseReference
     */
    protected ReverseReference $objReverseReference;

    /**
     * The name of the object, used by JSON and other encodings.
     *
     * @var string
     */
    protected string $strName;


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
            case 'Table':
                return $this->strTable;
            case 'Column':
                return $this->strColumn;
            case 'PropertyName':
                return $this->strPropertyName;
            case 'VariableName':
                return $this->strVariableName;
            case 'VariableType':
                return $this->strVariableType;
            case 'IsType':
                return $this->blnIsType;
            case 'ReverseReference':
                return $this->objReverseReference;
            case 'Name':
                return $this->strName;
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
     * @param mixed $mixValue New value of the property
     * @return void
     * @throws Caller
     * @throws Exception
     */
    public function __set(string $strName, mixed $mixValue): void
    {
        try {
            match ($strName) {
                'KeyName' => $this->strKeyName = Type::cast($mixValue, Type::STRING),
                'Table' => $this->strTable = Type::cast($mixValue, Type::STRING),
                'Column' => $this->strColumn = Type::cast($mixValue, Type::STRING),
                'PropertyName' => $this->strPropertyName = Type::cast($mixValue, Type::STRING),
                'VariableName' => $this->strVariableName = Type::cast($mixValue, Type::STRING),
                'VariableType' => $this->strVariableType = Type::cast($mixValue, Type::STRING),
                'IsType' => $this->blnIsType = Type::cast($mixValue, Type::BOOLEAN),
                'ReverseReference' => $this->objReverseReference = $mixValue,
                'Name' => $this->strName = Type::cast($mixValue, Type::STRING),
                default => parent::__set($strName, $mixValue),
            };
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }
    }
}