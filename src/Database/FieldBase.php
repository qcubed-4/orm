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
 * Class FieldBase
 *
 * @property-read string $Name
 * @property-read string $OriginalName
 * @property-read string $Table
 * @property-read string $OriginalTable
 * @property-read string $Default
 * @property-read integer $MaxLength
 * @property-read boolean $Identity
 * @property-read boolean $NotNull
 * @property-read boolean $PrimaryKey
 * @property-read boolean $Unique
 * @property-read boolean $Timestamp
 * @property-read string $Type
 * @property-read string $Comment
 * @package QCubed\Database
 */
abstract class FieldBase extends ObjectBase
{
    protected string $strName;
    protected string $strOriginalName;
    protected string $strTable;
    protected string $strOriginalTable;
    protected ?string $strDefault = null;
    protected ?string $intMaxLength = null;
    protected ?string $strComment = null;

    // Bool
    protected bool $blnIdentity;
    protected bool $blnNotNull;
    protected bool $blnPrimaryKey;
    protected bool $blnUnique;
    protected bool $blnTimestamp = false;

    protected string $strType;

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
            case "Name":
                return $this->strName;
            case "OriginalName":
                return $this->strOriginalName;
            case "Table":
                return $this->strTable;
            case "OriginalTable":
                return $this->strOriginalTable;
            case "Default":
                return $this->strDefault;
            case "MaxLength":
                return $this->intMaxLength;
            case "Identity":
                return $this->blnIdentity;
            case "NotNull":
                return $this->blnNotNull;
            case "PrimaryKey":
                return $this->blnPrimaryKey;
            case "Unique":
                return $this->blnUnique;
            case "Timestamp":
                return $this->blnTimestamp;
            case "Type":
                return $this->strType;
            case "Comment":
                return $this->strComment;
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