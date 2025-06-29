<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Query\Condition;

use QCubed\Exception\Caller;
use QCubed\Exception\InvalidCast;
use QCubed\Query\Builder;
use QCubed\Query\Condition\ConditionInterface as iCondition;
use QCubed\Query\Node;
use QCubed\Query\Clause;

/**
 * Class ComparisonBase
 * @package QCubed\Query\Condition
 */
abstract class ComparisonBase extends ConditionBase implements ConditionInterface
{
    /** @var Node\Column */
    public Node\Column $objQueryNode;
    public mixed $mixOperand;

    /**
     * @param Node\Column $objQueryNode
     * @param mixed|null $mixOperand
     * @throws InvalidCast
     */
    public function __construct(Node\Column $objQueryNode, mixed $mixOperand = null)
    {
        $this->objQueryNode = $objQueryNode;
        if ($mixOperand instanceof Node\NamedValue || $mixOperand === null) {
            $this->mixOperand = $mixOperand;
        } elseif ($mixOperand instanceof Node\Association) {
            throw new InvalidCast('Comparison operand cannot be an Association-based Node', 3);
        } elseif ($mixOperand instanceof iCondition) {
            throw new InvalidCast('Comparison operand cannot be a Condition', 3);
        } elseif ($mixOperand instanceof Clause\ClauseInterface) {
            throw new InvalidCast('Comparison operand cannot be a Clause', 3);
        } elseif (!($mixOperand instanceof Node\NodeBase)) {
            $this->mixOperand = $mixOperand;
        } elseif (!($mixOperand instanceof Node\Column)) {
            throw new InvalidCast('Unable to cast "' . $mixOperand->_Name . '" table to Column-based QQNode',
                3);
        } else {
            $this->mixOperand = $mixOperand;
        }
    }

    /**
     * Updates the query builder by adding a where clause based on the current query node, operator, and operand.
     *
     * @param Builder $objBuilder The query builder instance to which the where clause will be added.
     * @throws Caller
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        $objBuilder->addWhereItem($this->objQueryNode->getColumnAlias($objBuilder) . $this->strOperator . Node\NodeBase::getValue($this->mixOperand,
                $objBuilder));
    }

    /**
     * Compares the table name of the current query node with the given table name.
     *
     * @param string $strTableName The name of the table to compare with.
     * @return bool True if the table names are equal, false otherwise.
     */
    public function equalTables(string $strTableName): bool
    {
        return $this->objQueryNode->getTable() == $strTableName;
    }
}
