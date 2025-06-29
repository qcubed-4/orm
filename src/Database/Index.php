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
 * To handle index in a table in a database
 * @package DatabaseAdapters
 */
class Index extends ObjectBase
{
    /** @var string|null Name of the index */
    protected ?string $strKeyName = null;
    /** @var bool Is the Index a primary key index? */
    protected bool $blnPrimaryKey;
    /** @var bool Is this a Unique index? */
    protected bool $blnUnique;
    /** @var array Array of column names on which this index is defined */
    protected array $strColumnNameArray;

    /**
     * Initializes a new instance of the class with the specified parameters.
     *
     * @param string|null $strKeyName The name of the key.
     * @param bool $blnPrimaryKey Indicates whether the key is a primary key.
     * @param bool $blnUnique Indicates whether the key is unique.
     * @param array $strColumnNameArray An array of column names associated with the key.
     *
     */
    public function __construct(?string $strKeyName, bool $blnPrimaryKey, bool $blnUnique, array $strColumnNameArray)
    {
        $this->strKeyName = $strKeyName;
        $this->blnPrimaryKey = $blnPrimaryKey;
        $this->blnUnique = $blnUnique;
        $this->strColumnNameArray = $strColumnNameArray;
    }

    /**
     * PHP magic function
     * @param string $strName
     *
     * @return mixed
     * @throws Exception|Caller
     */
    public function __get(string $strName): mixed
    {
        switch ($strName) {
            case "KeyName":
                return $this->strKeyName;
            case "PrimaryKey":
                return $this->blnPrimaryKey;
            case "Unique":
                return $this->blnUnique;
            case "ColumnNameArray":
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
}