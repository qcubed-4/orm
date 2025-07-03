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
use QCubed\Query\Builder;

/**
 * Class Math
 * Node to represent a math operation between a set of values
 * @package QCubed\Query\Node
 */
class Math extends SubQueryBase
{
    /** @var  string */
    protected string $strOperation;
    /** @var  array Could be constants or column nodes */
    protected array $params;

    /**
     * Constructor method for initializing the class with operation and parameters.
     *
     * @param string $strOperation The operation to be performed.
     * @param array $params An array of parameters required for the operation.
     * @return void
     */
    public function __construct(string $strOperation, array $params)
    {
        parent::__construct('', '', '');
        $this->strOperation = $strOperation;
        $this->params = $params;
    }

    /**
     * Generates and returns the column alias based on the operation and parameters.
     *
     * @param Builder $objBuilder The query builder instance used to construct the SQL expression.
     * @return string The generated SQL column alias as a string.
     * @throws Caller
     */
    public function getColumnAlias(Builder $objBuilder): string
    {
        if (count($this->params) == 0) {
            return '';
        }

        $strSql = '(';

        if (count($this->params) == 1) {
            // unary
            $strSql .= $this->strOperation;
        }
        foreach ($this->params as $param) {
            if ($param instanceof Column) {
                $strSql .= $param->getColumnAlias($objBuilder);
            } else {
                // just a basic value
                $strSql .= $param;
            }
            $strSql .= ' ' . $this->strOperation . ' ';
        }
        $strSql = substr($strSql, 0, -(strlen($this->strOperation) + 2));    // get rid of the last operation
        $strSql .= ')';
        return $strSql;
    }

    /**
     * Converts the object to its string representation.
     *
     * @return string A string that represents the object.
     */
    public function __toString(): string
    {
        return 'Math Node ' . $this->strOperation;
    }

}

