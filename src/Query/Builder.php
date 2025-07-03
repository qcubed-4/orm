<?php
    /**
     *
     * Part of the QCubed PHP framework.
     *
     * @license MIT
     *
     */

    namespace QCubed\Query;

    use QCubed\Exception\Caller;
    use QCubed\Exception\InvalidCast;
    use QCubed\ObjectBase;
    use QCubed\Query\Node\NodeBase;
    use QCubed\Query\Node\Column;
    use QCubed\Query\Clause\OrderBy;
    use QCubed\Query\Condition\ConditionInterface as iCondition;
    use QCubed\Database\DatabaseBase;

    /**
     * Builder class
     * @property DatabaseBase $Database
     * @property string $RootTableName
     * @property string[] $ColumnAliasArray
     * @property NodeBase $ExpandAsArrayNode
     * @property-read boolean $Distinct true if this is having a distinct clause
     */
    class Builder extends ObjectBase
    {
        /** @var string[] */
        protected array $strSelectArray;
        /** @var array */
        protected array $strColumnAliasArray;
        /** @var int */
        protected int $intColumnAliasCount = 0;
        /** @var string[] */
        protected array $strTableAliasArray;
        /** @var int */
        protected int $intTableAliasCount = 0;
        /** @var string[] */
        protected array $strFromArray;
        /** @var string[] */
        protected array $strJoinArray;
        /** @var string[] */
        protected array $strJoinConditionArray;
        /** @var string[] */
        protected array $strWhereArray;
        /** @var string[] */
        protected array $strOrderByArray;
        /** @var string[] */
        protected array $strGroupByArray;
        /** @var string[] */
        protected array $strHavingArray;
        /** @var Node\Virtual[] */
        protected array $objVirtualNodeArray;
        /** @var  ?string */
        protected ?string $strLimitInfo = null;
        /** @var  bool */
        protected bool $blnDistinctFlag = false;
        /** @var  ?NodeBase */
        protected ?NodeBase $objExpandAsArrayNode = null;
        /** @var  bool */
        protected bool $blnCountOnlyFlag = false;

        /** @var DatabaseBase */
        protected DatabaseBase $objDatabase;
        /** @var string */
        protected string $strRootTableName;
        /** @var string */
        protected string $strEscapeIdentifierBegin;
        /** @var string */
        protected string $strEscapeIdentifierEnd;

        /** @var ?OrderBy */
        protected ?OrderBy $objOrderByClause = null;

        /**
         * @param DatabaseBase $objDatabase
         * @param string $strRootTableName
         */
        public function __construct(DatabaseBase $objDatabase, string $strRootTableName)
        {
            $this->objDatabase = $objDatabase;
            $this->strEscapeIdentifierBegin = $objDatabase->EscapeIdentifierBegin;
            $this->strEscapeIdentifierEnd = $objDatabase->EscapeIdentifierEnd;
            $this->strRootTableName = $strRootTableName;

            $this->strSelectArray = array();
            $this->strColumnAliasArray = array();
            $this->strTableAliasArray = array();
            $this->strFromArray = array();
            $this->strJoinArray = array();
            $this->strJoinConditionArray = array();
            $this->strWhereArray = array();
            $this->strOrderByArray = array();
            $this->strGroupByArray = array();
            $this->strHavingArray = array();
            $this->objVirtualNodeArray = array();
        }

        /**
         * Adds a select item to the query with a specified table, column, and alias.
         *
         * @param string $strTableName The name of the table containing the column.
         * @param string $strColumnName The name of the column to be selected.
         * @param string $strFullAlias The full alias to be used for the column in the query.
         */
        public function addSelectItem(string $strTableName, string $strColumnName, string $strFullAlias): void
        {
            $strTableAlias = $this->getTableAlias($strTableName);

            if (!array_key_exists($strFullAlias, $this->strColumnAliasArray)) {
                $strColumnAlias = 'a' . $this->intColumnAliasCount++;
                $this->strColumnAliasArray[$strFullAlias] = $strColumnAlias;
            } else {
                $strColumnAlias = $this->strColumnAliasArray[$strFullAlias];
            }

            $this->strSelectArray[$strFullAlias] = sprintf('%s%s%s.%s%s%s AS %s%s%s',
                $this->strEscapeIdentifierBegin, $strTableAlias, $this->strEscapeIdentifierEnd,
                $this->strEscapeIdentifierBegin, $strColumnName, $this->strEscapeIdentifierEnd,
                $this->strEscapeIdentifierBegin, $strColumnAlias, $this->strEscapeIdentifierEnd);
        }

        /**
         * Adds a select function to the query.
         *
         * @param string|null $strFunctionName The name of the SQL function to apply.
         * @param string $strColumnName The name of the column on which the function is applied.
         * @param string $strFullAlias The full alias to be used for the resulting column.
         */
        public function addSelectFunction(?string $strFunctionName, string $strColumnName, string $strFullAlias): void
        {
            $this->strSelectArray[$strFullAlias] = sprintf('%s(%s) AS %s__%s%s',
                $strFunctionName, $strColumnName,
                $this->strEscapeIdentifierBegin, $strFullAlias, $this->strEscapeIdentifierEnd);
        }

        /**
         * Adds a table to the FROM a clause of the query.
         *
         * @param string $strTableName The name of the table to be added to FROM the clause.
         *
         * @return void
         */
        public function addFromItem(string $strTableName): void
        {
            $strTableAlias = $this->getTableAlias($strTableName);

            $this->strFromArray[$strTableName] = sprintf('%s%s%s AS %s%s%s',
                $this->strEscapeIdentifierBegin, $strTableName, $this->strEscapeIdentifierEnd,
                $this->strEscapeIdentifierBegin, $strTableAlias, $this->strEscapeIdentifierEnd);
        }

        /**
         * Retrieves the table alias for a given table name. If the alias does not already exist,
         * a new alias is generated, stored, and returned.
         *
         * @param string $strTableName The name of the table for which the alias is to be retrieved or generated.
         *
         * @return string The alias associated with the specified table name.
         */
        public function getTableAlias(string $strTableName): string
        {
            if (!array_key_exists($strTableName, $this->strTableAliasArray)) {
                $strTableAlias = 't' . $this->intTableAliasCount++;
                $this->strTableAliasArray[$strTableName] = $strTableAlias;
                return $strTableAlias;
            } else {
                return $this->strTableAliasArray[$strTableName];
            }
        }

        /**
         * Adds a JOIN clause to the query with the specified table, alias, columns, and optional condition.
         *
         * @param string $strJoinTableName Name of the table to join.
         * @param string $strJoinTableAlias Alias for the join table.
         * @param string $strTableName Name of the base table in the JOIN clause.
         * @param string $strColumnName Column name from the base table to use in the join condition.
         * @param string $strLinkedColumnName Column name from the join table to use in the join condition.
         * @param iCondition|null $objJoinCondition A condition objects to specify additional conditions for the join (optional).
         *
         * @return void
         *
         * @throws Caller If conflicting join conditions exist for the same table.
         */
        public function addJoinItem(
            string      $strJoinTableName,
            string      $strJoinTableAlias,
            string      $strTableName,
            string      $strColumnName,
            string      $strLinkedColumnName,
            ?iCondition $objJoinCondition = null
        ): void
        {
            $strJoinItem = sprintf('LEFT JOIN %s%s%s AS %s%s%s ON %s%s%s.%s%s%s = %s%s%s.%s%s%s',
                $this->strEscapeIdentifierBegin, $strJoinTableName, $this->strEscapeIdentifierEnd,
                $this->strEscapeIdentifierBegin, $this->getTableAlias($strJoinTableAlias), $this->strEscapeIdentifierEnd,

                $this->strEscapeIdentifierBegin, $this->getTableAlias($strTableName), $this->strEscapeIdentifierEnd,
                $this->strEscapeIdentifierBegin, $strColumnName, $this->strEscapeIdentifierEnd,

                $this->strEscapeIdentifierBegin, $this->getTableAlias($strJoinTableAlias), $this->strEscapeIdentifierEnd,
                $this->strEscapeIdentifierBegin, $strLinkedColumnName, $this->strEscapeIdentifierEnd);

            $strJoinIndex = $strJoinItem;
            $strConditionClause = null;
            if ($objJoinCondition &&
                ($strConditionClause = $objJoinCondition->getWhereClause($this))
            ) {
                $strJoinItem .= ' AND ' . $strConditionClause;
            }

            /* If this table has already been joined, then we need to check for the following:
                1. Condition wasn't specified before, and we aren't specifying one now
                    Do Nothing --b/c nothing was changed or updated
                2. The Condition wasn't specified before, but we ARE specifying one now
                    Update the indexed item in the joinArray with the new JoinItem WITH Condition
                3. Condition WAS specified before, but we aren't specifying one now
                    Do Nothing -- we need to keep the old condition intact
                4. Condition WAS specified before, and we are specifying the SAME one now
                    Do Nothing --b/c nothing was changed or updated
                5. The Condition WAS specified before, and we are specifying a DIFFERENT one now
                    Throw exception
            */
            if (array_key_exists($strJoinIndex, $this->strJoinArray)) {
                // Case 1 and 2
                if (!array_key_exists($strJoinIndex, $this->strJoinConditionArray)) {

                    // Case 1
                    if ($strConditionClause) {
                        $this->strJoinArray[$strJoinIndex] = $strJoinItem;
                        $this->strJoinConditionArray[$strJoinIndex] = $strConditionClause;
                    }
                    return;
                }

                // Case 3
                if (!$strConditionClause) {
                    return;
                }

                // Case 4
                if ($strConditionClause == $this->strJoinConditionArray[$strJoinIndex]) {
                    return;
                }

                // Case 5
                throw new Caller('You have two different Join Conditions on the same Expanded Table: ' . $strJoinIndex . "\r\n[" . $this->strJoinConditionArray[$strJoinIndex] . ']   vs.   [' . $strConditionClause . ']');
            }

            // Create the new JoinItem in the JoinArray
            $this->strJoinArray[$strJoinIndex] = $strJoinItem;

            // If there is a condition, record that condition against this JoinIndex
            if ($strConditionClause) {
                $this->strJoinConditionArray[$strJoinIndex] = $strConditionClause;
            }
        }

        /**
         * Adds a custom JOIN clause to the query with the specified table, alias, and condition.
         *
         * @param string $strJoinTableName Name of the table to join.
         * @param string $strJoinTableAlias Alias for the join table.
         * @param iCondition $objJoinCondition A condition object to specify the join condition.
         *
         * @return void
         *
         */
        public function addJoinCustomItem(string $strJoinTableName, string $strJoinTableAlias, iCondition $objJoinCondition): void
        {
            $strJoinItem = sprintf('LEFT JOIN %s%s%s AS %s%s%s ON ',
                $this->strEscapeIdentifierBegin, $strJoinTableName, $this->strEscapeIdentifierEnd,
                $this->strEscapeIdentifierBegin, $this->getTableAlias($strJoinTableAlias), $this->strEscapeIdentifierEnd
            );

            $strJoinIndex = $strJoinItem;

            if (($strConditionClause = $objJoinCondition->getWhereClause($this, true))) {
                $strJoinItem .= ' AND ' . $strConditionClause;
            }

            $this->strJoinArray[$strJoinIndex] = $strJoinItem;
        }

        /**
         * Adds a custom SQL JOIN clause to the query.
         *
         * @param string $strSql The custom SQL string representing the JOIN clause to be added.
         *
         * @return void
         */
        public function addJoinCustomSqlItem(string $strSql): void
        {
            $this->strJoinArray[$strSql] = $strSql;
        }

        /**
         * Adds a WHERE clause item to the query.
         *
         * @param string $strItem The WHERE clause conditions to be added.
         *
         * @return void
         */
        public function addWhereItem(string $strItem): void
        {
            $this->strWhereArray[] = $strItem;
        }

        /**
         * Adds an ORDER BY clause item to the query.
         *
         * @param string $strItem The column or expression to include in the ORDER BY clause.
         *
         * @return void
         */
        public function addOrderByItem(string $strItem): void
        {
            $this->strOrderByArray[] = $strItem;
        }

        /**
         * Adds an item to the GROUP BY clause of the query.
         *
         * @param string $strItem The column or expression to be added to the GROUP BY clause.
         *
         * @return void
         */
        public function addGroupByItem(string $strItem): void
        {
            $this->strGroupByArray[] = $strItem;
        }


        /**
         * Adds a HAVING clause item to the query.
         *
         * @param string $strItem The HAVING clause conditions to be added.
         *
         * @return void
         */
        public function addHavingItem(string $strItem): void
        {
            $this->strHavingArray[] = $strItem;
        }

        /**
         * Sets the limit information for the query.
         *
         * @param string $strLimitInfo The limit information, such as SQL limit clauses or constraints.
         *
         * @return void
         */
        public function setLimitInfo(string $strLimitInfo): void
        {
            $this->strLimitInfo = $strLimitInfo;
        }

        /**
         * Sets the distinct flag to ensure the query will return distinct results.
         *
         * @return void
         */
        public function setDistinctFlag(): void
        {
            $this->blnDistinctFlag = true;
        }

        /**
         * Sets the internal flag to indicate that only a count operation should be performed.
         *
         * @return void
         */
        public function setCountOnlyFlag(): void
        {
            $this->blnCountOnlyFlag = true;
        }

        /**
         * Sets a virtual node for the given name and column node.
         *
         * @param string $strName The name to associate with the virtual node.
         * @param Column $objNode The column node to be stored as a virtual node.
         *
         * @return void
         */
        public function setVirtualNode(string $strName, Column $objNode): void
        {
            $this->objVirtualNodeArray[QQ::getVirtualAlias($strName)] = $objNode;
        }

        /**
         * Retrieves a virtual node from the virtual node array by its name.
         *
         * @param string $strName The name of the virtual node to retrieve.
         *
         * @return mixed The virtual node associated with the given name.
         *
         * @throws Caller If the virtual node with the specified name is not defined.
         */
        public function getVirtualNode(string $strName): Node\Column
        {
            $strName = QQ::getVirtualAlias($strName);
            if (isset($this->objVirtualNodeArray[$strName])) {
                return $this->objVirtualNodeArray[$strName];
            } else {
                throw new Caller('Undefined Virtual Node: ' . $strName);
            }
        }

        /**
         * Marks a node for expansion as an array and integrates it into the current expansion structure.
         *
         * @param NodeBase $objNode The node to be marked for array expansion.
         *
         * @return void
         * @throws Caller
         */
        public function addExpandAsArrayNode(NodeBase $objNode): void
        {
            /** @var Node\ReverseReference|Node\Association $objNode */
            // build child nodes and find the top node of the given node
            $objNode->ExpandAsArray = true;
            while ($objNode->_ParentNode) {
                $objNode = $objNode->_ParentNode;
            }

            if (!$this->objExpandAsArrayNode) {
                $this->objExpandAsArrayNode = $objNode;
            } else {
                // integrate the information into current nodes
                $this->objExpandAsArrayNode->_MergeExpansionNode($objNode);
            }
        }

        /**
         * Generates and returns the SQL query string based on the current state of clauses, flags, and conditions.
         *
         * The method processes SELECT, FROM, JOIN, WHERE, GROUP BY, HAVING, ORDER BY clauses,
         * and applies DISTINCT or LIMIT modifiers if necessary. It also accounts for
         * special cases like counting distinct entries.
         *
         * @return string The generated SQL query string.
         * @throws Caller
         */
        public function getStatement(): string
        {
            $this->processClauses();

            // SELECT Clause
            if ($this->blnCountOnlyFlag) {
                if ($this->blnDistinctFlag) {
                    $strSql = "SELECT\r\n    COUNT(*) AS q_row_count\r\n" .
                        "FROM    (SELECT DISTINCT ";
                    $strSql .= "    " . implode(",\r\n    ", $this->strSelectArray);
                } else {
                    $strSql = "SELECT\r\n    COUNT(*) AS q_row_count\r\n";
                }
            } else {
                if ($this->blnDistinctFlag) {
                    $strSql = "SELECT DISTINCT\r\n";
                } else {
                    $strSql = "SELECT\r\n";
                }
                if ($this->strLimitInfo) {
                    $strSql .= $this->objDatabase->sqlLimitVariablePrefix($this->strLimitInfo) . "\r\n";
                }
                $strSql .= "    " . implode(",\r\n    ", $this->strSelectArray);
            }

            // FROM and JOIN Clauses
            $strSql .= sprintf("\r\nFROM\r\n    %s\r\n    %s",
                implode(",\r\n    ", $this->strFromArray),
                implode("\r\n    ", $this->strJoinArray));

            // WHERE Clause
            if (count($this->strWhereArray)) {
                $strWhere = implode("\r\n    ", $this->strWhereArray);
                if (trim($strWhere) != '1=1') {
                    $strSql .= "\r\nWHERE\r\n    " . $strWhere;
                }
            }

            // Additional Ordering/Grouping/Having clauses
            if (count($this->strGroupByArray)) {
                $strSql .= "\r\nGROUP BY\r\n    " . implode(",\r\n    ", $this->strGroupByArray);
            }
            if (count($this->strHavingArray)) {
                $strHaving = implode("\r\n    ", $this->strHavingArray);
                $strSql .= "\r\nHaving\r\n    " . $strHaving;
            }
            if (count($this->strOrderByArray)) {
                $strSql .= "\r\nORDER BY\r\n    " . implode(",\r\n    ", $this->strOrderByArray);
            }

            // Limit Suffix (if applicable)
            if ($this->strLimitInfo) {
                $strSql .= "\r\n" . $this->objDatabase->sqlLimitVariableSuffix($this->strLimitInfo);
            }

            // For Distinct Count Queries
            if ($this->blnCountOnlyFlag && $this->blnDistinctFlag) {
                $strSql .= "\r\n) as q_count_table";
            }

            return $strSql;
        }

        /**
         * Sets the ORDER BY clause for the query.
         *
         * @param OrderBy $objOrderByClause The OrderBy object defining the ORDER BY clause for the query.
         *
         * @return void
         *
         */
        public function setOrderByClause(OrderBy $objOrderByClause): void
        {
            $this->objOrderByClause = $objOrderByClause;
        }

        /**
         * Processes the clauses associated with the query, such as applying the OrderBy clause.
         *
         * @return void
         * @throws Caller
         */
        protected function processClauses(): void
        {
            $this->objOrderByClause?->_UpdateQueryBuilder($this);
        }

        /**
         * @throws Caller
         * @throws InvalidCast
         */
        public function __get($strName): mixed
        {
            switch ($strName) {
                case 'Database':
                    return $this->objDatabase;
                case 'RootTableName':
                    return $this->strRootTableName;
                case 'ColumnAliasArray':
                    return $this->strColumnAliasArray;
                case 'ExpandAsArrayNode':
                    return $this->objExpandAsArrayNode;
                case 'Distinct':
                    return $this->blnDistinctFlag;

                default:
                    try {
                        return parent::__get($strName);
                    } catch (Caller $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
            }
        }
    }