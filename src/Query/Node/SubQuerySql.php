<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Query\Node;

use QCubed\Query\Builder;

/**
 * Class SubQuerySql
 * Node to output custom SQL as a sub-query
 * @package QCubed\Query\Node
 */
class SubQuerySql extends NoParentBase
{
    protected string $strSql;
    /** @var NodeBase[] */
    protected array $objParentQueryNodes;

    /**
     * Constructor method that initializes the object with the provided SQL string and parent query nodes.
     *
     * @param string $strSql The SQL string to be set.
     * @param array $objParentQueryNodes An optional array of parent query nodes.
     * @return void
     */
    public function __construct(string $strSql, array $objParentQueryNodes = [])
    {
        parent::__construct('', '', '');
        $this->objParentNode = true;
        $this->objParentQueryNodes = $objParentQueryNodes;
        $this->strSql = $strSql;
    }

    /**
     * Retrieves the column alias corresponding to the SQL expression, processing any dynamic placeholders.
     *
     * @param Builder $objBuilder The builder instance used to resolve column aliases for query nodes.
     * @return string The processed SQL expression with resolved column aliases or formatted as a subquery if necessary.
     */
    public function getColumnAlias(Builder $objBuilder): string
    {
        $strSql = $this->strSql;
        for ($intIndex = 1; $intIndex < count($this->objParentQueryNodes); $intIndex++) {
            $parentNode = $this->objParentQueryNodes[$intIndex] ?? null;
            if ($parentNode !== null && method_exists($parentNode, 'getColumnAlias')) {
                $strSql = str_replace(
                    '{' . $intIndex . '}',
                    $parentNode->getColumnAlias($objBuilder),
                    $strSql
                );
            }
        }
        if (stripos($strSql, 'SELECT') === 0) {
            return '(' . $strSql . ')';
        }
        return $strSql;
    }



//    public function getColumnAlias(Builder $objBuilder): string
//    {
//        $strSql = $this->strSql;
//        for ($intIndex = 1; $intIndex < count($this->objParentQueryNodes); $intIndex++) {
//            if (!is_null($this->objParentQueryNodes[$intIndex])) {
//                $strSql = str_replace('{' . $intIndex . '}',
//                    $this->objParentQueryNodes[$intIndex]->getColumnAlias($objBuilder), $strSql);
//            }
//        }
//        if (stripos($strSql, 'SELECT') === 0) {
//            return '(' . $strSql . ')';
//        } else {
//            return $strSql;
//        }
//    }
}
