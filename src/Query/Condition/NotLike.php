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
 * Class NotLike
 * Represent a test for a SQL Like function.
 * @package QCubed\Query\Condition
 */
class NotLike extends ComparisonBase
{
    /**
     * Constructs a new instance of the class.
     *
     * @param Column $objQueryNode The query node object to associate with this instance.
     * @param mixed $strValue The value to be used for the operand, which will be cast to a string if not an instance of Node\NamedValue.
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
     * Updates the query builder with a condition that ensures the column associated with the query node
     * does not match the specified operand using the SQL 'NOT LIKE' clause.
     *
     * @param Builder $objBuilder The query builder instance to be updated.
     * @return void
     * @throws Caller
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        $mixOperand = $this->mixOperand;
        if ($mixOperand instanceof Node\NamedValue) {
            $objBuilder->addWhereItem($this->objQueryNode->getColumnAlias($objBuilder) . ' NOT LIKE ' . $mixOperand->parameter());
        } else {
            $objBuilder->addWhereItem($this->objQueryNode->getColumnAlias($objBuilder) . ' NOT LIKE ' . $objBuilder->Database->sqlVariable($mixOperand));
        }
    }
}
