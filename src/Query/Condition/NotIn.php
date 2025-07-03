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
 * Class NotIn
 * Represent a test for an item being in a set of values.
 * @package QCubed\Query\Condition
 */
class NotIn extends ComparisonBase
{
    /**
     * Constructor method.
     *
     * @param Column $objQueryNode The query node to be set.
     * @param mixed $mixValuesArray An array of values or an instance of Node\NamedValue or Node\SubQueryBase.
     * @throws Caller
     * @throws InvalidCast
     */
    public function __construct(Node\Column $objQueryNode, mixed $mixValuesArray)
    {
        parent::__construct($objQueryNode);

        if ($mixValuesArray instanceof Node\NamedValue) {
            $this->mixOperand = $mixValuesArray;
        } else {
            if ($mixValuesArray instanceof Node\SubQueryBase) {
                $this->mixOperand = $mixValuesArray;
            } else {
                try {
                    $this->mixOperand = Type::cast($mixValuesArray, Type::ARRAY_TYPE);
                } catch (Caller $objExc) {
                    $objExc->incrementOffset();
                    $objExc->incrementOffset();
                    throw $objExc;
                }
            }
        }
    }

    /**
     * Updates the query builder with a NOT IN condition based on the operand.
     *
     * @param Builder $objBuilder The query builder to update with the NOT IN condition.
     * @return void
     * @throws Caller
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        $mixOperand = $this->mixOperand;
        if ($mixOperand instanceof Node\NamedValue) {
            $objBuilder->addWhereItem($this->objQueryNode->getColumnAlias($objBuilder) . ' NOT IN (' . $mixOperand->parameter() . ')');
        } else {
            if ($mixOperand instanceof Node\SubQueryBase) {
                $objBuilder->addWhereItem($this->objQueryNode->getColumnAlias($objBuilder) . ' NOT IN ' . $mixOperand->getColumnAlias($objBuilder));
            } else {
                $strParameters = array();
                foreach ($mixOperand as $mixParameter) {
                    $strParameters[] = $objBuilder->Database->sqlVariable($mixParameter);
                }
                if (count($strParameters)) {
                    $objBuilder->addWhereItem($this->objQueryNode->getColumnAlias($objBuilder) . ' NOT IN (' . implode(',',
                            $strParameters) . ')');
                } else {
                    $objBuilder->addWhereItem('1=1');
                }
            }
        }
    }
}
