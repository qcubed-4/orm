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
use Exception;
use QCubed\ObjectBase;
use QCubed\Type;

/**
 * A helper class used by the QCubed Code Generator to describe a database Table
 * @package Codegen
 *
 * @property int $OwnerDbIndex
 * @property string $Name
 * @property string $ClassNamePlural
 * @property string $ClassName
 * @property SqlColumn[] $ColumnArray
 * @property SqlColumn[] $PrimaryKeyColumnArray
 * @property ReverseReference[] $ReverseReferenceArray
 * @property ManyToManyReference[] $ManyToManyReferenceArray
 * @property Index[] $IndexArray
 * @property-read int $ReferenceCount
 * @property array $Options
 */
class SqlTable extends ObjectBase {

	/////////////////////////////
	// Protected Member Variables
	/////////////////////////////

	/**
	 * @var int DB Index to which it belongs in the configuration.inc.php and codegen_settings.xml files.
	 */
	protected int $intOwnerDbIndex;

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
	 * Pluralized Name as a collection of objects of this PHP Class
	 * @var string ClassNamePlural;
	 */
	protected string $strClassNamePlural;

	/**
	 * Array of Column objects (as indexed by Column name)
	 * @var SqlColumn[] ColumnArray
	 */
	protected array $objColumnArray;

	/**
	 * Array of ReverseReverence objects (indexed numerically)
	 * @var ReverseReference[] ReverseReferenceArray
	 */
	protected array $objReverseReferenceArray;

	/**
	 * Array of ManyToManyReference objects (indexed numerically)
	 * @var ManyToManyReference[] ManyToManyReferenceArray
	 */
	protected array $objManyToManyReferenceArray;

	/**
	 * Array of Index objects (indexed numerically)
	 * @var Index[] IndexArray
	 */
	protected array $objIndexArray;

	/**
	 * @var array developer specified options.
	 */
	protected array $options;


	/////////////////////
	// Public Constructor
	/////////////////////

	/**
	 * Default Constructor.  Simply sets up the TableName and ensures that ReverseReferenceArray is a blank array.
	 *
	 * @param string $strName Name of the Table
	 */
	public function __construct(string $strName) {
		$this->strName = $strName;
		$this->objReverseReferenceArray = array();
		$this->objManyToManyReferenceArray = array();
		$this->objColumnArray = array();
		$this->objIndexArray = array();
	}

    /**
     * Retrieves a column object by its name from the column array.
     *
     * @param string $strColumnName The name of the column to retrieve.
     * @return SqlColumn|null The column object if found, or null if no matching column exists.
     */
    public function getColumnByName(string $strColumnName): ?SqlColumn
    {
		if ($this->objColumnArray) {
			foreach ($this->objColumnArray as $objColumn){
				if ($objColumn->Name == $strColumnName)
					return $objColumn;
			}
		}
		return null;
	}

	/**
	 * Search within the table's columns for the given column
	 * @param string $strColumnName Name of the column
	 * @return boolean
	 */
	public function hasColumn(string $strColumnName): bool
    {
		return ($this->getColumnByName($strColumnName) !== null);
	}

    /**
     * Return the property name for a given column name (false if it doesn't exist)
     * @param string $strColumnName name of the column
     * @return SqlColumn
     */
	public function lookupColumnPropertyName(string $strColumnName): SqlColumn
    {
        return $this->getColumnByName($strColumnName);
    }


    /**
     * Determines whether the table has immediate array expansions.
     *
     * This method checks if the table has many-to-many references or reverse references
     * that are not unique and calculates the total count of such references to determine
     * the existence of array expansions.
     *
     * @return bool Returns true if the table has immediate array expansions, otherwise false.
     */
    public function hasImmediateArrayExpansions(): bool
    {
        $intCount = count($this->objManyToManyReferenceArray);
        foreach ($this->objReverseReferenceArray as $objReverseReference) {
            if (!$objReverseReference->Unique) {
                $intCount++;
            }
        }
        return $intCount > 0;
    }


    /**
     * Determines if the current table or its references have extended array expansions.
     *
     * This method checks the current table's columns and their references recursively
     * to verify if any table has immediate array expansions or further extended array expansions.
     * Circular references are avoided by maintaining a checked table array.
     *
     * @param DatabaseCodeGen $objCodeGen The code generator object used to query the database schema.
     * @param array $objCheckedTableArray An array of already checked tables to avoid circular references. This defaults to an empty array.
     * @return bool Returns true if extended array expansions exist, false otherwise.
     * @throws Caller
     */
    public function hasExtendedArrayExpansions(DatabaseCodeGen $objCodeGen, array $objCheckedTableArray = array()): bool
    {
        $objCheckedTableArray[] = $this;
        foreach ($this->ColumnArray as $objColumn) {
            if (($objReference = $objColumn->Reference) && !$objReference->IsType) {
                if ($objTable2 = $objCodeGen->getTable($objReference->Table)) {
                    if ($objTable2->hasImmediateArrayExpansions()) {
                        return true;
                    }
                    if (!in_array($objTable2, $objCheckedTableArray) &&	// watch out for circular references
                            $objTable2->hasExtendedArrayExpansions($objCodeGen, $objCheckedTableArray)) {
                        return true;
                    }
                }
            }
        }
        return false;
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
	public function __get(string $strName): mixed {
		switch ($strName) {
			case 'OwnerDbIndex':
				return $this->intOwnerDbIndex;
			case 'Name':
				return $this->strName;
			case 'ClassNamePlural':
				return $this->strClassNamePlural;
			case 'ClassName':
				return $this->strClassName;
			case 'ColumnArray':
				return $this->objColumnArray;
			case 'PrimaryKeyColumnArray':
				if ($this->objColumnArray) {
					$objToReturn = array();
					foreach ($this->objColumnArray as $objColumn)
						if ($objColumn->PrimaryKey)
							$objToReturn[] = $objColumn;
					return $objToReturn;
				} else
					return null;
			case 'ReverseReferenceArray':
				return $this->objReverseReferenceArray;
			case 'ManyToManyReferenceArray':
				return $this->objManyToManyReferenceArray;
			case 'IndexArray':
				return $this->objIndexArray;
			case 'ReferenceCount':
				$intCount = count($this->objManyToManyReferenceArray);
				foreach ($this->objColumnArray as $objColumn)
					if ($objColumn->Reference)
						$intCount++;
				return $intCount;

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
     * This will set the property $strName to be $mixValue
     *
     * @param string $strName Name of the property to set
     * @param mixed $mixValue New value of the property
     * @return void
     * @throws Caller
     * @throws InvalidCast
     * @throws UndefinedProperty
     * @throws Exception
     */
	public function __set(string $strName, mixed $mixValue): void {
		try {
			switch ($strName) {
				case 'OwnerDbIndex':
                    $this->intOwnerDbIndex = Type::cast($mixValue, Type::INTEGER);
                    break;
                case 'Name':
				    $this->strName = Type::cast($mixValue, Type::STRING);
                    break;
                case 'ClassName':
				    $this->strClassName = Type::cast($mixValue, Type::STRING);
                    break;
                case 'ClassNamePlural':
			        $this->strClassNamePlural = Type::cast($mixValue, Type::STRING);
                    break;
                case 'ColumnArray':
		            $this->objColumnArray = Type::cast($mixValue, Type::ARRAY_TYPE);
                    break;
                case 'ReverseReferenceArray':
					$this->objReverseReferenceArray = Type::cast($mixValue, Type::ARRAY_TYPE);
                    break;
                case 'ManyToManyReferenceArray':
					$this->objManyToManyReferenceArray = Type::cast($mixValue, Type::ARRAY_TYPE);
                    break;
                case 'IndexArray':
				    $this->objIndexArray = Type::cast($mixValue, Type::ARRAY_TYPE);
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
