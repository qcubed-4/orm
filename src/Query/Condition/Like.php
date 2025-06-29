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
 * Class Like
 * Represent a test for a SQL Like function.
 * @package QCubed\Query\Condition
 */
class Like extends ComparisonBase
{
    /**
     * Constructs the object and initializes the operand based on the provided value.
     *
     * @param Column $objQueryNode The query node associated with this object.
     * @param mixed $strValue The value to initialize the operand. Can be an instance of Node\NamedValue or a value to be cast to a string.
     * @throws Caller
     * @throws InvalidCast
     */
    public function __construct(Node\Column $objQueryNode, mixed $strValue)
    {
        parent::__construct($objQueryNode);

        if ($strValue instanceof Node\NamedValue) {
            $this->mixOperand = $strValue;
        } else {
            try {
                $this->mixOperand = Type::cast($strValue, Type::STRING);
            } catch (Caller $objExc) {
                $objExc->incrementOffset();
                $objExc->incrementOffset();
                throw $objExc;
            }
        }
    }

    /**
     * Updates the query builder with a WHERE clause based on the operand.
     *
     * @param Builder $objBuilder The query builder instance to be updated.
     * @return void
     * @throws Caller
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        $mixOperand = $this->mixOperand;
        if ($mixOperand instanceof Node\NamedValue) {
            $objBuilder->addWhereItem($this->objQueryNode->getColumnAlias($objBuilder) . ' LIKE ' . $mixOperand->parameter());
        } else {
            $objBuilder->addWhereItem($this->objQueryNode->getColumnAlias($objBuilder) . ' LIKE ' . $objBuilder->Database->sqlVariable($mixOperand));
        }
    }
}
