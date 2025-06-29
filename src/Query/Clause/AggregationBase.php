<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Query\Clause;

use QCubed\Exception\Caller;
use QCubed\ObjectBase;
use QCubed\Query\Builder;
use QCubed\Query\Node;
use QCubed\Query\QQ;

/**
 * Class AggregationBase
 * Base class for functions that work in cooperation with GroupBy clauses
 *
 * @package QCubed\Query\Clause
 */
abstract class AggregationBase extends ObjectBase implements ClauseInterface
{
    /** @var Node\NodeBase */
    protected Node\NodeBase $objNode;
    protected string $strAttributeName;
    protected string $strFunctionName;

    /**
     * Constructor for initializing the object with a column node and a virtual attribute name.
     *
     * @param Node\Column $objNode The column node to be processed.
     * @param string $strAttributeName The name of the virtual attribute to be used.
     * @return void
     */
    public function __construct(Node\Column $objNode, string $strAttributeName)
    {
        $this->objNode = QQ::func($this->strFunctionName, $objNode);
        $this->strAttributeName = QQ::getVirtualAlias($strAttributeName); // virtual attributes are queried lower case
    }

    /**
     * Updates the query builder with a virtual node and selects the appropriate column alias.
     *
     * @param Builder $objBuilder The query builder to be updated.
     * @return void
     * @throws Caller
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        $objBuilder->setVirtualNode($this->strAttributeName, $this->objNode);
        $objBuilder->addSelectFunction(null, $this->objNode->getColumnAlias($objBuilder), $this->strAttributeName);
    }
}
