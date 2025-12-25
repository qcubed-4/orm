<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Codegen;

use Exception;
use QCubed\Exception\Caller;
use QCubed\ObjectBase;
use QCubed\Type;

/**
 * Used by the QCubed Code Generator to describe a database Type Table
 * "Type" tables must be defined with at least two columns, the first one being an integer-based primary key,
 * and the second one being the name of the type.
 * @package Codegen
 *
 * @property string $Name
 * @property string $ClassName
 * @property string[] $NameArray
 * @property string[] $TokenArray
 * @property array $ExtraPropertyArray
 * @property array[] $ExtraFieldsArray
 * @property-read array[] $PrimaryKeyColumnArray
 * @property-write QSqlColumn $KeyColumn
 * @property array[] $ManyToManyReferenceArray
 * @noinspection PhpUndefinedClassInspection
 */
class TypeTable extends ObjectBase
{

    /////////////////////////////
    // Protected Member Variables
    /////////////////////////////

    /**
     * Name of the table (as defined in the database)
     * @var string Name
     */
    protected string $strName;

    /**
     * Name as a PHP Class
     * @var string ClassName
     */
    protected string $strClassName;

    /**
     * Array of Type Names (as entered into the rows of this database table)
     * This is indexed by an integer which represents the ID in the database, starting with 1
     * @var string[] NameArray
     */
    protected array $strNameArray = [];

    /**
     * Column names for extra properties (beyond the 2 basic columns), if any.
     */
    protected array $extraFields = [];

    /**
     * Array of extra properties. This is a double-array - array of arrays. Example:
     *      1 => ['col1' => 'valueA', 'col2 => 'valueB'],
     *      2 => ['col1' => 'valueC', 'col2 => 'valueD'],
     *      3 => ['col1' => 'valueC', 'col2 => 'valueD']
     */
    protected array $arrExtraPropertyArray = [];

    /**
     * Array of Type Names converted into Tokens (can be used as PHP Constants)
     * This is indexed by an integer which represents the ID in the database, starting with 1
     * @var string[] TokenArray
     */
    protected array $strTokenArray = [];

    /**
     * @var string|array|callable|null $objKeyColumn
     */
    protected mixed $objKeyColumn;
    /**
     * @var array
     */
    protected array $objManyToManyReferenceArray = [];

    /////////////////////
    // Public Constructor
    /////////////////////

    /**
     * TypeTable constructor.
     * @param string $strName
     */
    public function __construct(string $strName)
    {
        $this->strName = $strName;
    }

    /**
     * Returns the string that will be used to represent the literal value given when code genning a type table
     * @param mixed $mixColValue
     * @return int|string
     */
    public static function literal(mixed $mixColValue): int|string
    {
        if (is_null($mixColValue)) {
            return 'null';
        } elseif (is_integer($mixColValue)) {
            return $mixColValue;
        } elseif (is_bool($mixColValue)) {
            return ($mixColValue ? 'true' : 'false');
        } elseif (is_float($mixColValue)) {
            return "(float)$mixColValue";
        } elseif (is_object($mixColValue)) {
            return "t('" . $mixColValue->_toString() . "')";
        }    // whatever is suitable for the constructor of the object
        else {
            return "t('" . str_replace("'", "\\'", $mixColValue) . "')";
        }
    }

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
            case 'Name':
                return $this->strName;
            case 'ClassName':
                return $this->strClassName;
            case 'NameArray':
                return $this->strNameArray;
            case 'TokenArray':
                return $this->strTokenArray;
            case 'ExtraPropertyArray':
                return $this->arrExtraPropertyArray;
            case 'ExtraFieldsArray':
                return $this->extraFields;
            case 'PrimaryKeyColumnArray':
                $a[] = $this->objKeyColumn;
                return $a;
            case 'ManyToManyReferenceArray':
                return $this->objManyToManyReferenceArray;

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
     * Magic method to set a property value.
     * @param string $strName The name of the property to set.
     * @param mixed $mixValue The value to assign to the property.
     * @return void The newly set value of the property, or the result of the parent's __set method if no match is found.
     * @throws Caller If an invalid property is accessed.
     */
    public function __set(string $strName, mixed $mixValue): void
    {
        try {
            match ($strName) {
                'Name' => $this->strName = Type::cast($mixValue, Type::STRING),
                'ClassName' => $this->strClassName = Type::cast($mixValue, Type::STRING),
                'NameArray' => $this->strNameArray = Type::cast($mixValue, Type::ARRAY_TYPE),
                'TokenArray' => $this->strTokenArray = Type::cast($mixValue, Type::ARRAY_TYPE),
                'ExtraPropertyArray' => $this->arrExtraPropertyArray = Type::cast($mixValue, Type::ARRAY_TYPE),
                'ExtraFieldsArray' => $this->extraFields = Type::cast($mixValue, Type::ARRAY_TYPE),
                'KeyColumn' => $this->objKeyColumn = $mixValue,
                'ManyToManyReferenceArray' => $this->objManyToManyReferenceArray = Type::cast($mixValue, Type::ARRAY_TYPE),

                default => parent::__set($strName, $mixValue),
            };
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        } catch (Exception $e) {
        }
    }
}