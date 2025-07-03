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
use QCubed\Query\QQ;

/**
 * Class Virtual
 * Class to represent a computed value or sub SQL expression with an alias that can be used to query and sort
 *
 * @package QCubed\Query\Node
 */
class Virtual extends NoParentBase
{
    protected ?SubQueryBase $objSubQueryDefinition = null;

    /**
     * Constructor method for initializing the object.
     *
     * @param string $strName The name used to define the object or entity.
     * @param SubQueryBase|null $objSubQueryDefinition An optional sub-query definition to be associated with the object.
     * @return void
     */
    public function __construct(string $strName, ?SubQueryBase $objSubQueryDefinition = null)
    {
        parent::__construct('', '', '');
        $this->objParentNode = true;
        $this->strName = QQ::getVirtualAlias($strName);
        $this->strAlias = $this->strName;
        $this->objSubQueryDefinition = $objSubQueryDefinition;
    }

    /**
     * Retrieves the column alias based on the provided builder and the defined sub-query.
     *
     * @param Builder $objBuilder The query builder instance used to construct the SQL and manage aliases.
     * @return string The resolved column alias for the current node or sub-query.
     * @throws Caller If the virtual node cannot be resolved.
     */
    public function getColumnAlias(Builder $objBuilder): string
    {
        if ($this->objSubQueryDefinition) {
            $objBuilder->setVirtualNode($this->strName, $this->objSubQueryDefinition);
            return $this->objSubQueryDefinition->getColumnAlias($objBuilder);
        } else {
            try {
                $objNode = $objBuilder->getVirtualNode($this->strName);
                return $objNode->getColumnAlias($objBuilder);
            } catch (Caller $objExc) {
                $objExc->incrementOffset();
                $objExc->incrementOffset();
                throw $objExc;
            }
        }
    }

    /**
     * Retrieves the name of the attribute.
     *
     * @return string The name of the attribute.
     */
    public function getAttributeName(): string
    {
        return $this->strName;
    }

    /**
     * Determines if a subquery is associated with the object.
     *
     * @return bool Returns true if a subquery definition exists; otherwise, false.
     */
    public function hasSubquery(): bool
    {
        return $this->objSubQueryDefinition != null;
    }
}
