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
use Exception;
use QCubed\Exception\UndefinedProperty;
use QCubed\ObjectBase;
use QCubed\Type;

/**
 * Used by the QCubed Code Generator to describe a column reference from
 * the table's perspective (aka a Foreign Key from the referenced Table's point of view)
 * @package Codegen
 *
 * @property Reference $Reference
 * @property string $KeyName
 * @property string $Table
 * @property string $Column
 * @property boolean $NotNull
 * @property boolean $Unique
 * @property string $VariableName
 * @property string $VariableType
 * @property string $PropertyName
 * @property string $ObjectDescription
 * @property string $ObjectDescriptionPlural
 * @property string $ObjectMemberVariable
 * @property string $ObjectPropertyName
 * @property array $Options
 */
class ReverseReference extends ObjectBase implements ColumnInterface
{

    /////////////////////////////
    // Protected Member Variables
    /////////////////////////////

    /**
     * The peer QReference object for which this object is the reverse reference of
     * @var Reference KeyName
     */
    protected Reference $objReference;

    /**
     * Name of the foreign key object itself, as defined in the database or create a script
     * @var string KeyName
     */
    protected string $strKeyName;

    /**
     * Name of the referencing table (the table that owns the column that is the foreign key)
     * @var string Table
     */
    protected string $strTable;

    /**
     * Name of the referencing column (the column that owns the foreign key)
     * @var string Column
     */
    protected string $strColumn;

    /**
     * Specifies whether the referencing column is specified as "NOT NULL"
     * @var bool NotNull
     */
    protected bool $blnNotNull;

    /**
     * Specifies whether the referencing column is unique
     * @var bool Unique
     */
    protected bool $blnUnique;

    /**
     * Name of the reverse-referenced object as a function parameter.
     * So if this is a reverse reference to "person" via "report.person_id",
     * the VariableName would be "objReport"
     * @var string VariableName
     */
    protected string $strVariableName;

    /**
     * Type of the reverse-referenced object as a class.
     * So if this is a reverse reference to "person" via "report.person_id",
     * the VariableName would be "Report"
     * @var string VariableType
     */
    protected string $strVariableType;

    /**
     * Property Name of the referencing column (the column that owns the foreign key)
     * in the associated Class.  So if this is a reverse reference to the "person" table
     * via the table/column "report.owner_person_id", the PropertyName would be "OwnerPersonId"
     * @var string PropertyName
     */
    protected string $strPropertyName;

    /**
     * Singular object description used in the function names for the
     * reverse reference.  See documentation for more details.
     * @var string ObjectDescription
     */
    protected string $strObjectDescription;

    /**
     * Plural object description used in the function names for the
     * reverse reference.  See documentation for more details.
     * @var string ObjectDescriptionPlural
     */
    protected string $strObjectDescriptionPlural;

    /**
     * A member variable name to be used by classes that contain the local member variable
     * for this unique reverse reference.  Only aggregated when blnUnique is true.
     * @var string ObjectMemberVariable
     */
    protected string $strObjectMemberVariable;

    /**
     * A property name to be used by classes that contain the property
     * for this unique reverse reference.  Only aggregated when blnUnique is true.
     * @var string ObjectPropertyName
     */
    protected string $strObjectPropertyName;

    /**
     * A keyed array of overrides read from the override file
     * @var array Overrides
     */
    protected array $options = [];


    ////////////////////
    // Public Overriders
    ////////////////////

    /**
     * Magic getter method to retrieve the value of a property based on its name.
     *
     * @param string $strName The name of the property to retrieve.
     * @return mixed
     * @throws Caller If the property does not exist or cannot be accessed.
     * @throws UndefinedProperty
     */
    public function __get(string $strName): mixed
    {
        switch ($strName) {
            case 'Reference':
                return $this->objReference;
            case 'KeyName':
                return $this->strKeyName;
            case 'Table':
                return $this->strTable;
            case 'Column':
                return $this->strColumn;
            case 'NotNull':
                return $this->blnNotNull;
            case 'Unique':
                return $this->blnUnique;
            case 'VariableName':
                return $this->strVariableName;
            case 'VariableType':
                return $this->strVariableType;
            case 'PropertyName':
                return $this->strPropertyName;
            case 'ObjectDescription':
                return $this->strObjectDescription;
            case 'ObjectDescriptionPlural':
                return $this->strObjectDescriptionPlural;
            case 'ObjectMemberVariable':
                return $this->strObjectMemberVariable;
            case 'ObjectPropertyName':
                return $this->strObjectPropertyName;
            case 'Options':
                return $this->options;

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
     * This will set the value of $strName to $mixValue
     *
     * @param string $strName Name of the property to set
     * @param mixed $mixValue The value to assign to the property
     * @return void
     * @throws Caller
     * @throws Exception
     */
    public function __set(string $strName, mixed $mixValue): void
    {
        try {
            switch ($strName) {
                case 'Reference':
                    $this->objReference = Type::cast($mixValue, Reference::class);
                    break;
                case 'KeyName':
                    $this->strKeyName = Type::cast($mixValue, Type::STRING);
                    break;
                case 'Table':
                    $this->strTable = Type::cast($mixValue, Type::STRING);
                    break;
                case 'Column':
                    $this->strColumn = Type::cast($mixValue, Type::STRING);
                    break;
                case 'NotNull':
                    $this->blnNotNull = Type::cast($mixValue, Type::BOOLEAN);
                    break;
                case 'Unique':
                    $this->blnUnique = Type::cast($mixValue, Type::BOOLEAN);
                    break;
                case 'VariableName':
                    $this->strVariableName = Type::cast($mixValue, Type::STRING);
                    break;
                case 'VariableType':
                    $this->strVariableType = Type::cast($mixValue, Type::STRING);
                    break;
                case 'PropertyName':
                    $this->strPropertyName = Type::cast($mixValue, Type::STRING);
                    break;
                case 'ObjectDescription':
                    $this->strObjectDescription = Type::cast($mixValue, Type::STRING);
                    break;
                case 'ObjectDescriptionPlural':
                    $this->strObjectDescriptionPlural = Type::cast($mixValue, Type::STRING);
                    break;
                case 'ObjectMemberVariable':
                    $this->strObjectMemberVariable = Type::cast($mixValue, Type::STRING);
                    break;
                case 'ObjectPropertyName':
                    $this->strObjectPropertyName = Type::cast($mixValue, Type::STRING);
                    break;
                case 'Options':
                    $this->options = Type::cast($mixValue, Type::ARRAY_TYPE);
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