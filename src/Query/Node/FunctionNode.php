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
 * Class FunctionNode
 * A node representing an SQL function call
 *
 * @package QCubed\Query\Node
 */
class FunctionNode extends SubQueryBase
{
    /** @var  string */
    protected string $strFunctionName;
    /** @var  array Could be constants or column nodes */
    protected array $params;

    /**
     * QQFunctionNode constructor.
     * @param string $strFunctionName
     * @param array $params
     */
    public function __construct(string $strFunctionName, array $params)
    {
        parent::__construct('', '', '');
        $this->strFunctionName = $strFunctionName;
        $this->params = $params;
    }

    /**
     * @param Builder $objBuilder
     * @return string
     * @throws Caller
     */
    public function getColumnAlias(Builder $objBuilder): string
    {
        $strSql = $this->strFunctionName . '(';
        foreach ($this->params as $param) {
            if ($param instanceof Column) {
                $strSql .= $param->getColumnAlias($objBuilder);
            } else {
                // just a basic value
                $strSql .= $param;
            }
            $strSql .= ',';
        }
        $strSql = substr($strSql, 0, -1);    // get rid of the last comma
        $strSql .= ')';
        return $strSql;
    }

    public function __toString()
    {
        return 'Function Node ' . $this->strFunctionName;
    }
}

