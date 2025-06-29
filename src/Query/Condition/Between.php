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
use QCubed\Query\Node;
use QCubed\Query\Node\Column;

/**
 * Class Between
 * Represent a test for an item being between two values.
 * Note that different SQLs treat this clause differently and may produce different results. It's not transportable.
 * @package QCubed\Query\Condition
 */
class Between extends ComparisonBase
{
    /** @var  mixed */
    protected mixed $mixOperandTwo;

    /**
     * Constructor for the class.
     *
     * @param Column $objQueryNode The query node object.
     * @param mixed $mixMinValue The minimum value to be set for the operand.
     * @param mixed $mixMaxValue The maximum value to be set for the operand.
     * @throws Caller
     * @throws InvalidCast
     */
    public function __construct(Node\Column $objQueryNode, mixed $mixMinValue, mixed $mixMaxValue)
    {
        parent::__construct($objQueryNode);
        try {
            $this->mixOperand = $mixMinValue;
            $this->mixOperandTwo = $mixMaxValue;
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            $objExc->incrementOffset();
            throw $objExc;
        }
    }

    /**
     * Updates the query builder with a "BETWEEN" clause based on the provided operands.
     *
     * @param Builder $objBuilder The query builder being updated.
     * @return void
     * @throws Caller
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        $mixOperand = $this->mixOperand;
        $mixOperandTwo = $this->mixOperandTwo;
        if ($mixOperand instanceof Node\NamedValue) {
            /** @var Node\NamedValue $mixOperand */
            /** @var Node\NamedValue $mixOperandTwo */
            $objBuilder->addWhereItem($this->objQueryNode->getColumnAlias($objBuilder) . ' BETWEEN ' . $mixOperand->parameter() . ' AND ' . $mixOperandTwo->parameter());
        } else {
            $objBuilder->addWhereItem($this->objQueryNode->getColumnAlias($objBuilder) . ' BETWEEN ' . $objBuilder->Database->sqlVariable($mixOperand) . ' AND ' . $objBuilder->Database->sqlVariable($mixOperandTwo));
        }
    }
}
