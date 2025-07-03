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
use QCubed\Query\Node\Column;
use QCubed\Type;
use QCubed\Query\Node;


/**
 * Class NotBetween
 * Represent a test for an item being between two values.
 * Note that different SQLs treat this clause differently and may produce different results. It's not transportable.
 * @package QCubed\Query\Condition
 */
class NotBetween extends ComparisonBase
{
    /** @var mixed */
    protected mixed $mixOperandTwo;

    /**
     * Constructor for initializing the object with a query node, minimum value, and maximum value.
     *
     * @param Column $objQueryNode The query node used in the initialization.
     * @param mixed $strMinValue The minimum value to be used, either a string or a NamedValue instance.
     * @param mixed $strMaxValue The maximum value to be used, either a string or a NamedValue instance.
     * @throws Caller
     * @throws InvalidCast
     */
    public function __construct(Node\Column $objQueryNode, mixed $strMinValue, mixed $strMaxValue)
    {
        parent::__construct($objQueryNode);
        try {
            $this->mixOperand = Type::cast($strMinValue, Type::STRING);
            $this->mixOperandTwo = Type::cast($strMaxValue, Type::STRING);
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            $objExc->incrementOffset();
            throw $objExc;
        }

        if ($strMinValue instanceof Node\NamedValue) {
            $this->mixOperand = $strMinValue;
        }
        if ($strMaxValue instanceof Node\NamedValue) {
            $this->mixOperandTwo = $strMaxValue;
        }

    }

    /**
     * Updates the query builder with a 'NOT BETWEEN' condition based on the operands.
     *
     * @param Builder $objBuilder The query builder instance to which the condition is added.
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
            $objBuilder->addWhereItem($this->objQueryNode->getColumnAlias($objBuilder) . ' NOT BETWEEN ' . $mixOperand->parameter() . ' AND ' . $mixOperandTwo->parameter());
        } else {
            $objBuilder->addWhereItem($this->objQueryNode->getColumnAlias($objBuilder) . ' NOT BETWEEN ' . $objBuilder->Database->sqlVariable($mixOperand) . ' AND ' . $objBuilder->Database->sqlVariable($mixOperandTwo));
        }
    }
}
