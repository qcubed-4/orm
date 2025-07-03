<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Query\Node;

use QCubed\Exception\Caller;
use Exception;
use QCubed\ObjectBase;
use QCubed\Query\Builder;
use QCubed\Query\Clause\Select;
use QCubed\Query\Condition\ConditionInterface as iCondition;
use QCubed\Type;

/**
 * The abstract Node base class. This represents an "object" in an SQL join tree. There are a number of different subclasses of
 * node, depending on the type of object being represented. The top of a join tree is generally a table node, and
 * the bottom is generally a column node, but this depends on the context in which the node is used.
 *
 * The properties begin with underscores to prevent name conflicts with codegenerated subclasses.
 *
 * @property-read NodeBase|null $_ParentNode        // Parent object in a tree.
 * @property-read string|null $_Name                // Default SQL name in query, or default alias
 * @property-read string|null $_Alias               // Actual alias. Usually the name, unless changed by QQ::alias() calls
 * @property-read string|null $_PropertyName        // The name as used in PHP
 * @property-read string|null $_Type                // The type of object. A SQL type if referring to a column.
 * @property-read string|null $_RootTableName       // The name of the table at the top of the tree. Redundant, since it could be found to be following the chain.
 * @property-read string|null $_TableName           // The name of the table associated with this node, if it's not a column node.
 * @property-read string|null $_PrimaryKey
 * @property-read string|null $_ClassName
 * @property-read NodeBase|null $_PrimaryKeyNode
 * @property bool $ExpandAsArray                    // True if this node should be arrayed expanded.
 * @property-read bool|null $IsType                 // Is a type table node. For association type arrays.
 * @property-read array<string, NodeBase>|null $ChildNodeArray
 */
abstract class NodeBase extends ObjectBase
{
    /** @var NodeBase|null|bool $objParentNode */
    protected NodeBase|null|bool $objParentNode = null;

    /** @var string|null $strType */
    protected ?string $strType = null;

    /** @var string|null $strName */
    protected ?string $strName = null;

    /** @var string|null $strAlias */
    protected ?string $strAlias = null;

    /** @var string|null $strFullAlias */
    protected ?string $strFullAlias = null;

    /** @var string|null $strPropertyName */
    protected ?string $strPropertyName = null;

    /** @var string|null $strRootTableName */
    protected ?string $strRootTableName = null;

    /** @var string|null $strTableName */
    protected ?string $strTableName = null;

    /** @var string|null $strPrimaryKey */
    protected ?string $strPrimaryKey = null;

    /** @var string|null $strClassName */
    protected ?string $strClassName = null;

    /** @var bool $blnExpandAsArray */
    protected bool $blnExpandAsArray = false;

    /** @var array<string, NodeBase>|null $objChildNodeArray */
    protected ?array $objChildNodeArray = null;

    /** @var bool|null $blnIsType */
    protected ?bool $blnIsType = null;

    /**
     * @param Builder $objBuilder
     * @param bool $blnExpandSelection
     * @param iCondition|null $objJoinCondition
     * @param Select|null $objSelect
     * @return mixed
     */
    abstract public function join(
        Builder $objBuilder,
        ?bool $blnExpandSelection = false,
        ?iCondition $objJoinCondition = null,
        ?Select $objSelect = null
    ): mixed;

    /**
     * Return the variable type. Should be a FieldType enum.
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->strType;
    }

    /**
     * Change the alias of the node, primarily for joining the same table more than once.
     * @param string $strAlias
     * @throws Caller
     * @throws Exception
     */
    public function setAlias(string $strAlias): void
    {
        if ($this->strFullAlias) {
            throw new Exception("You cannot set an alias on a node after you have used it in a query. See the example doc. You must set the alias while creating the node.");
        }
        try {
            $strNewAlias = Type::cast($strAlias, Type::STRING);
            if ($this->objParentNode instanceof NodeBase) {
                unset($this->objParentNode->objChildNodeArray[$this->strAlias]);
                $this->objParentNode->objChildNodeArray[$strNewAlias] = $this;
            }
            $this->strAlias = $strNewAlias;
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }
    }

    /**
     * Aid to generating full aliases. Recursively gets and sets the parent alias.
     * @return string|null
     */
    public function fullAlias(): ?string
    {
        if ($this->strFullAlias) {
            return $this->strFullAlias;
        } else {
            if (empty($this->strAlias)) {
                return null;
            }
            if ($this->objParentNode instanceof NodeBase) {
                return $this->objParentNode->fullAlias() . '__' . $this->strAlias;
            } else {
                return $this->strAlias;
            }
        }
    }

    /**
     * Returns the fields in this node. Assumes a table node.
     * @return string[]
     */
    public function fields(): array
    {
        return [];
    }

    /**
     * Returns the primary key fields in this node.
     * @return string[]
     */
    public function primaryKeyFields(): array
    {
        return [];
    }

    /**
     * Merges a node tree into this node, building the child nodes.
     * @param NodeBase $objNewNode
     * @throws Caller
     */
    public function _MergeExpansionNode(NodeBase $objNewNode): void
    {
        if (empty($objNewNode->objChildNodeArray)) {
            return;
        }

        if ($objNewNode->strName != $this->strName) {
            throw new Caller('Expansion node tables must match.');
        }

        if (!$this->objChildNodeArray) {
            $this->objChildNodeArray = $objNewNode->objChildNodeArray;
        } else {
            $objChildNode = reset($objNewNode->objChildNodeArray);
            if ($objChildNode instanceof NodeBase) {
                if (isset($this->objChildNodeArray[$objChildNode->strAlias])) {
                    if ($objChildNode->blnExpandAsArray) {
                        $this->objChildNodeArray[$objChildNode->strAlias]->blnExpandAsArray = true;
                    } else {
                        $this->objChildNodeArray[$objChildNode->strAlias]->_MergeExpansionNode($objChildNode);
                    }
                } else {
                    $this->objChildNodeArray[$objChildNode->strAlias] = $objChildNode;
                }
            }
        }
    }

    /**
     * Puts the "Select" clause fields for this node into the builder.
     * @param Builder $objBuilder
     * @param string|null $strPrefix
     * @param Select|null $objSelect
     * @return void
     */
    public function putSelectFields(Builder $objBuilder, ?string $strPrefix = null, ?Select $objSelect = null): void
    {
        $strTableName   = $strPrefix ?: $this->strTableName;
        $strAliasPrefix = $strPrefix ? ($strPrefix . '__') : '';

        if ($objSelect) {
            if (!$objSelect->skipPrimaryKey() && !$objBuilder->Distinct) {
                $strFields = $this->primaryKeyFields();
                foreach ($strFields as $strField) {
                    $objBuilder->addSelectItem($strTableName, $strField, $strAliasPrefix . $strField);
                }
            }
            $objSelect->addSelectItems($objBuilder, $strTableName, $strAliasPrefix);
        } else {
            $strFields = $this->fields();
            foreach ($strFields as $strField) {
                $objBuilder->addSelectItem($strTableName, $strField, $strAliasPrefix . $strField);
            }
        }
    }

    /**
     * @return NodeBase|null
     */
    public function firstChild(): ?NodeBase
    {
        $a = $this->objChildNodeArray;
        if ($a) {
            $first = reset($a);
            return $first instanceof NodeBase ? $first : null;
        }
        return null;
    }

    /**
     * Retrieves the table name or alias by delegating to the fullAlias method.
     * @return string|null
     */
    public function getTable(): ?string
    {
        return $this->fullAlias();
    }

    /**
     * @param mixed $mixValue
     * @param Builder $objBuilder
     * @param bool|null $blnEqualityType
     * @return string
     * @throws Caller
     */
    public static function getValue(mixed $mixValue, Builder $objBuilder, ?bool $blnEqualityType = null): string
    {
        if ($mixValue instanceof NamedValue) {
            return $mixValue->parameter($blnEqualityType);
        }

        if ($mixValue instanceof NodeBase) {
            if ($n = $mixValue->_PrimaryKeyNode) {
                $mixValue = $n; // Convert table node to column node
            }
            $strToReturn = '';
            if (!is_null($blnEqualityType)) {
                $strToReturn = $blnEqualityType ? '= ' : '!= ';
            }
            /** @var Column $mixValue */
            return $strToReturn . $mixValue->getColumnAlias($objBuilder);
        } else {
            // Scalar value
            $blnIncludeEquality = !is_null($blnEqualityType);
            $blnReverseEquality = $blnEqualityType === false;
            return $objBuilder->Database->sqlVariable($mixValue, $blnIncludeEquality, $blnReverseEquality);
        }
    }

    public function __get(string $strName): mixed
    {
        switch ($strName) {
            case '_ParentNode':
                return $this->objParentNode;
            case '_Name':
                return $this->strName;
            case '_Alias':
                return $this->strAlias;
            case '_PropertyName':
                return $this->strPropertyName;
            case '_Type':
                return $this->strType;
            case '_RootTableName':
                return $this->strRootTableName;
            case '_TableName':
                return $this->strTableName;
            case '_PrimaryKey':
                return $this->strPrimaryKey;
            case '_ClassName':
                return $this->strClassName;
            case '_PrimaryKeyNode':
                return null;
            case 'ExpandAsArray':
                return $this->blnExpandAsArray;
            case 'IsType':
                return $this->blnIsType;
            case 'ChildNodeArray':
                return $this->objChildNodeArray;
            default:
                try {
                    return parent::__get($strName);
                } catch (Caller $objExc) {
                    $objExc->incrementOffset();
                    throw $objExc;
                }
        }
    }

    public function __set(string $strName, mixed $mixValue): void
    {
        switch ($strName) {
            case 'ExpandAsArray':
                try {
                    $this->blnExpandAsArray = Type::cast($mixValue, Type::BOOLEAN);
                } catch (Caller $objExc) {
                    $objExc->incrementOffset();
                    throw $objExc;
                }
                break;
            default:
                try {
                    parent::__set($strName, $mixValue);
                } catch (Caller $objExc) {
                    $objExc->incrementOffset();
                    throw $objExc;
                }
        }
    }
}

