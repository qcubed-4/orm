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
 * A helper class used by the QCubed Code Generator to describe a table's column
 *
 * @package Codegen
 * @property SqlTable|TypeTable $OwnerTable             Table in which this column exists
 * @property boolean $PrimaryKey             Is the column a (part of) primary key?
 * @property string $Name                   Column name
 * @property string $PropertyName           Corresponding property name for the table
 * @property string $VariableName           Corresponding variable name (in ORM class and elsewhere)
 * @property string $VariableType           Type of data this column is supposed to store (constant from Type class)
 * @property string $VariableTypeAsConstant Variable type expressed as Type cast string (integer column would have this value as: "\QCubed\Type::INTEGER")
 * @property string $DbType                 Type in the database
 * @property int $Length                    If applicable, the length of data to be stored (useful for varchar data types)
 * @property mixed $Default                 Default value of the column
 * @property boolean $NotNull                Is this column a "NOT NULL" column?
 * @property boolean $Identity               Is this column an Identity column?
 * @property boolean $Indexed                Is there a single column index on this column?
 * @property boolean $Unique                 Does this column have a 'Unique' key defined on it?
 * @property boolean $Timestamp              Can this column contain a timestamp value?
 * @property Reference $Reference            Reference to another column (if this one is a foreign key)
 * @property array $Options                  Options for codegen
 * @property string $Comment                 Comment on the column
 * @property boolean $AutoUpdate             Should a column that is a timestamp generate code to automatically update the timestamp?
 */
class SqlColumn extends ObjectBase implements ColumnInterface
{

    /////////////////////////////
    // Protected Member Variables
    /////////////////////////////

    /**
     * Reference to the owner table as an object-protected Member Variable
     * Represents the table associated with this entity or relationship
     * @var SqlTable|string OwnerTable
     */
    protected mixed $objOwnerTable;

    /**
     * Specifies whether or not the column is a Primary Key
     * @var bool PrimaryKey
     */
    protected bool $blnPrimaryKey;

    /**
     * Name of the column as defined in the database
     * So for example, "first_name"
     * @var string Name
     */
    protected string $strName;

    /**
     * Name of the column as an object Property,
     * So for "first_name", it would be FirstName
     * @var string PropertyName
     */
    protected string $strPropertyName;

    /**
     * Name of the column as an object-protected Member Variable,
     * So for "first_name VARCHAR(50)", it would be strFirstName
     * @var string VariableName
     */
    protected string $strVariableName;

    /**
     * The type of the protected member variable (uses one of the string constants from the Type class)
     * @var string VariableType
     */
    protected string $strVariableType;

    /**
     * The type of the protected member variable (uses the actual constant from the Type class)
     * @var string VariableType
     */
    protected string $strVariableTypeAsConstant;

    /**
     * The actual type of the column in the database (uses one of the string constants from the DatabaseType class)
     * @var string DbType
     */
    protected string $strDbType;

    /**
     * Length of the column as defined in the database
     * @var int|null Length
     */
    protected ?int $intLength = null;

    /**
     * The default value for the column as defined in the database
     * @var mixed Default
     */
    protected mixed $mixDefault;

    /**
     * Specifies whether or not the column is specified as "NOT NULL"
     * @var bool NotNull
     */
    protected bool $blnNotNull;

    /**
     * Specifies whether or not the column is an identification column (like auto increment)
     * @var bool Identity
     */
    protected bool $blnIdentity;

    /**
     * Specifies whether or not the column is a single-column Index
     * @var bool Indexed
     */
    protected bool $blnIndexed;

    /**
     * Specifies whether or not the column is unique
     * @var bool Unique
     */
    protected bool $blnUnique;

    /**
     * Specifies whether or not the column is a system-updated "timestamp" column
     * @var bool Timestamp
     */
    protected bool $blnTimestamp;


    /**
     * Reference to an associated object or entity as a protected member variable.
     * Typically used to establish relationships between objects or models.
     * @var object|null objReference
     */
    protected ?object $objReference = null;

    /**
     * The string value of the comment field in the database.
     * @var string|null Comment
     */
    protected ?string $strComment = '';

    /**
     * Various overrides and options embedded in the comment for the column as a JSON object.
     * @var array Overrides
     */
    protected array $options = array();

    /**
     * For Timestamp columns, will add to the SQL code to set this field NOW whenever there is a save
     * @var boolean AutoUpdate
     */
    protected bool $blnAutoUpdate = false;


    ////////////////////
    // Public Overriders
    ////////////////////

    /**
     * Override method to perform a property "Get"
     * This will get the value of $strName
     *
     * @param string $strName Name of the property to get
     * @return mixed
     * @throws Exception
     */
    public function __get(string $strName): mixed
    {
        switch ($strName) {
            case 'OwnerTable':
                return $this->objOwnerTable;
            case 'PrimaryKey':
                return $this->blnPrimaryKey;
            case 'Name':
                return $this->strName;
            case 'PropertyName':
                return $this->strPropertyName;
            case 'VariableName':
                return $this->strVariableName;
            case 'VariableType':
                return $this->strVariableType;
            case 'VariableTypeAsConstant':
                return $this->strVariableTypeAsConstant;
            case 'DbType':
                return $this->strDbType;
            case 'Length':
                return $this->intLength;
            case 'Default':
                return $this->mixDefault;
            case 'NotNull':
                return $this->blnNotNull;
            case 'Identity':
                return $this->blnIdentity;
            case 'Indexed':
                return $this->blnIndexed;
            case 'Unique':
                return $this->blnUnique;
            case 'Timestamp':
                return $this->blnTimestamp;
            case 'Reference':
                return $this->objReference;
            case 'Comment':
                return $this->strComment;
            case 'Options':
                return $this->options;
            case 'AutoUpdate':
                return $this->blnAutoUpdate;
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
     * @param string|null $mixValue New value of the property
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
                case 'OwnerTable':
                    $this->objOwnerTable = $mixValue;
                    break;
                case 'PrimaryKey':
                    $this->blnPrimaryKey = Type::cast($mixValue, Type::BOOLEAN);
                    break;
                case 'Name':
                    $this->strName = Type::cast($mixValue, Type::STRING);
                    break;
                case 'PropertyName':
                    $this->strPropertyName = Type::cast($mixValue, Type::STRING);
                    break;
                case 'VariableName':
                    $this->strVariableName = Type::cast($mixValue, Type::STRING);
                    break;
                case 'VariableType':
                    $this->strVariableType = Type::cast($mixValue, Type::STRING);
                    break;
                case 'VariableTypeAsConstant':
                    $this->strVariableTypeAsConstant = Type::cast($mixValue, Type::STRING);
                    break;
                case 'DbType':
                    $this->strDbType = Type::cast($mixValue, Type::STRING);
                    break;
                case 'Length':
                    $this->intLength = Type::cast($mixValue, Type::INTEGER);
                    break;
                case 'Default':
                    if (is_null($mixValue) || in_array($mixValue, ['0000-00-00', '0000-00-00 00:00:00'], true)) {
                        $this->mixDefault = null;
                    } elseif (ctype_digit($mixValue)) {
                        $this->mixDefault = Type::cast($mixValue, Type::INTEGER);
                    } elseif (is_numeric($mixValue)) {
                        $this->mixDefault = Type::cast($mixValue, Type::FLOAT);
                    } else {
                        $this->mixDefault = Type::cast($mixValue, Type::STRING);
                    }
                    break;

                    /*if ($mixValue === null || (($mixValue === '' || $mixValue === '0000-00-00 00:00:00' || $mixValue === '0000-00-00') && !$this->blnNotNull)) {
                        $this->mixDefault = null;
                    } else {
                        if (is_int($mixValue)) {
                            $this->mixDefault = Type::cast($mixValue, Type::INTEGER);
                        } else {
                            if (is_numeric($mixValue)) {
                                $this->mixDefault = Type::cast($mixValue, Type::FLOAT);
                            } else {
                                $this->mixDefault = Type::cast($mixValue, Type::STRING);
                            }
                        }
                    }
                    break;*/
                case 'NotNull':
                    $this->blnNotNull = Type::cast($mixValue, Type::BOOLEAN);
                    break;
                case 'Identity':
                    $this->blnIdentity = Type::cast($mixValue, Type::BOOLEAN);
                    break;
                case 'Indexed':
                    $this->blnIndexed = Type::cast($mixValue, Type::BOOLEAN);
                    break;
                case 'Unique':
                    $this->blnUnique = Type::cast($mixValue, Type::BOOLEAN);
                    break;
                case 'Timestamp':
                    $this->blnTimestamp = Type::cast($mixValue, Type::BOOLEAN);
                    break;
                case 'Reference':
                    $this->objReference = Type::cast($mixValue, Reference::class);
                    break;
                case 'Comment':
                    $this->strComment = Type::cast($mixValue, Type::STRING);
                    break;
                case 'Options':
                    $this->options = Type::cast($mixValue, Type::ARRAY_TYPE);
                    break;
                case 'AutoUpdate':
                    $this->blnAutoUpdate = Type::cast($mixValue, Type::BOOLEAN);
                    break;
                default:
                    parent::__set($strName, $mixValue);
                    break;
            }
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }
    }
}