<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Query\Node;

use QCubed\Query\Clause;
use QCubed\Query\Builder;
use QCubed\Query\Condition\ConditionInterface as iCondition;

/**
 * Class NamedValue
 * Special node for referring to a node within a custom SQL clause.
 * @package QCubed\Query\Node
 */
class NamedValue extends NodeBase
{
    const DELIMITER_CODE = 3;

    /**
     * Constructor method for initializing the class with a name.
     *
     * @param string $strName The name to be assigned to the class property.
     * @return void
     */
    public function __construct(string $strName)
    {
        $this->strName = $strName;
    }

    /**
     * Determines and returns a formatted parameter string based on an equality type.
     *
     * @param bool|null $blnEqualityType The type of equality: null for default formatting,
     *                                   true for equality formatting, or false for inequality formatting.
     * @return string The formatted parameter string.
     */
    public function parameter(?bool $blnEqualityType = null): string
    {
        if (is_null($blnEqualityType)) {
            return chr(NamedValue::DELIMITER_CODE) . '{' . $this->strName . '}';
        } else {
            if ($blnEqualityType) {
                return chr(NamedValue::DELIMITER_CODE) . '{=' . $this->strName . '=}';
            } else {
                return chr(NamedValue::DELIMITER_CODE) . '{!' . $this->strName . '!}';
            }
        }
    }

    /**
     * Join a method for constructing a query with optional conditions and selection clauses.
     *
     * @param Builder $objBuilder The query builder to apply the join to.
     * @param bool $blnExpandSelection Whether to expand the selection in the query.
     * @param iCondition|null $objJoinCondition An optional condition to apply to the join.
     * @param Clause\Select|null $objSelect An optional selection clause to modify the join query.
     * @return bool
     */
    public function join(
        Builder        $objBuilder,
        ?bool           $blnExpandSelection = false,
        ?iCondition    $objJoinCondition = null,
        ?Clause\Select $objSelect = null
    ): bool
    {
        return assert(0);    // This kind of node is never a parent.
    }
}
