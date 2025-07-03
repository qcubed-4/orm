<?php $objManyToManyReferenceTable = $objCodeGen->TableArray[strtolower($objManyToManyReference->AssociatedTable)]; ?>


    // Related Many-to-Many Objects' Methods for <?= $objManyToManyReference->ObjectDescription ?>

    //-------------------------------------------------------------------

    /**
    * Gets all many-to-many associated <?= $objManyToManyReference->ObjectDescription ?> as an array of <?= $objTable->ClassName; ?> objects
    * @param iClause[]|null $objClauses additional optional iClause objects for this query
    * @return <?= $objManyToManyReference->VariableType ?>[]
    * @throws Caller
    */
    public function get<?= $objManyToManyReference->ObjectDescription ?>Array(?array $objClauses = null): array
    {
        if (<?= $objCodeGen->implodeObjectArray(' || ', 'is_null($this->', ')', 'VariableName', $objTable->PrimaryKeyColumnArray) ?>)
            return array();

        try {
            return <?= $objManyToManyReference->VariableType ?>::loadArrayBy<?= $objManyToManyReference->OppositeObjectDescription ?>($this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName ?>, $objClauses);
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }
    }

    /**
    * Retrieves all related project keys associated with the current <?= $objTable->ClassName ?> object
    * through the many-to-many relationship table <?= $strEscapeIdentifierBegin ?><?= $objManyToManyReference->Table ?><?= $strEscapeIdentifierEnd ?>

    *
    * @return int[] Array of related <?= strtolower($objTable->ClassName) ?> IDs
    * @throws Caller
    * @throws UndefinedPrimaryKey If the current project does not have a defined primary key
    */
    public function get<?= $objManyToManyReference->ObjectDescription ?>Keys(): array
    {
        if (<?= $objCodeGen->implodeObjectArray(' || ', 'is_null($this->', ')', 'VariableName', $objTable->PrimaryKeyColumnArray) ?>)
            throw new UndefinedPrimaryKey('Unable to call get<?= $objManyToManyReference->ObjectDescription ?> Ids on this unsaved <?= $objTable->ClassName ?>.');

        // Get the Database Object for this Class
        $objDatabase = <?= $objTable->ClassName ?>::getDatabase();

        // Perform the SQL Query
        $objResult = $objDatabase->query('
            SELECT <?= $strEscapeIdentifierBegin ?><?= $objManyToManyReference->OppositeColumn ?><?= $strEscapeIdentifierEnd ?>

            FROM <?= $strEscapeIdentifierBegin ?><?= $objManyToManyReference->Table ?><?= $strEscapeIdentifierEnd ?>

            WHERE <?= $strEscapeIdentifierBegin ?><?= $objManyToManyReference->Column ?><?= $strEscapeIdentifierEnd ?> = ' . $objDatabase->sqlVariable($this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName ?>) . '
        ');

        $keys = array();
        while ($row = $objResult->fetchRow()) {
            $keys[] = $row[0];
        }
        return $keys;
    }

    /**
    * Counts the number of many-to-many associated <?= $objManyToManyReference->ObjectDescriptionPlural ?> as related to this object
    * @return int The count of associated <?= $objManyToManyReference->ObjectDescription ?> objects
    * @throws Caller
    * @throws InvalidCast
    */
    public function count<?= $objManyToManyReference->ObjectDescriptionPlural ?>(): int
    {
        if (<?= $objCodeGen->implodeObjectArray(' || ', 'is_null($this->', ')', 'VariableName', $objTable->PrimaryKeyColumnArray) ?>)
            return 0;

        return <?= $objManyToManyReference->VariableType ?>::countBy<?= $objManyToManyReference->OppositeObjectDescription ?>($this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName ?>);
    }

    /**
    * Checks if a given <?= $objManyToManyReference->VariableType ?> is associated with a related <?= $objManyToManyReference->VariableType ?>

    * @param <?= $objManyToManyReference->VariableType ?> $<?= $objManyToManyReference->VariableName ?> The <?= $objManyToManyReference->VariableType ?> objects to check association with.
    * @return bool True if the given <?= $objManyToManyReference->VariableType ?> is associated as related, false otherwise.
    * @throws Caller
    * @throws InvalidCast
    * @throws UndefinedPrimaryKey Thrown if the current or provided <?= $objManyToManyReference->VariableType ?> has an undefined primary key.
    */
    public function is<?= $objManyToManyReference->ObjectDescription ?>Associated(<?= $objManyToManyReference->VariableType ?> $<?= $objManyToManyReference->VariableName ?>): bool
    {
        if (<?= $objCodeGen->implodeObjectArray(' || ', 'is_null($this->', ')', 'VariableName', $objTable->PrimaryKeyColumnArray) ?>)
            throw new UndefinedPrimaryKey('Unable to call Is<?= $objManyToManyReference->ObjectDescription ?>Associated on this unsaved <?= $objTable->ClassName ?>.');
        if (<?= $objCodeGen->implodeObjectArray(' || ', 'is_null($' . $objManyToManyReference->VariableName . '->', ')', 'PropertyName', $objManyToManyReferenceTable->PrimaryKeyColumnArray) ?>)
            throw new UndefinedPrimaryKey('Unable to call Is<?= $objManyToManyReference->ObjectDescription ?>Associated on this <?= $objTable->ClassName ?> with an unsaved <?= $objManyToManyReferenceTable->ClassName ?>.');

        $intRowCount = <?= $objTable->ClassName ?>::queryCount(
            QQ::andCondition(
                QQ::equal(QQN::<?= $objTable->ClassName ?>()-><?= $objTable->PrimaryKeyColumnArray[0]->PropertyName ?>, $this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName ?>),
                QQ::equal(QQN::<?= $objTable->ClassName ?>()-><?= $objManyToManyReference->ObjectDescription ?>-><?= $objManyToManyReference->OppositePropertyName ?>, $<?= $objManyToManyReference->VariableName ?>-><?= $objManyToManyReferenceTable->PrimaryKeyColumnArray[0]->PropertyName ?>)
            )
        );

        return ($intRowCount > 0);
    }

    /**
    * Determines whether a given <?= $objManyToManyReference->ObjectDescription ?> is associated with the calling <?= strtolower($objManyToManyReference->VariableType) ?> by the specified key
    * @param mixed $key key to check for association with the current <?= $objManyToManyReference->VariableType ?>

    * @return bool true if the <?= $objManyToManyReference->ObjectDescription ?> is associated with the key, false otherwise
    * @throws Caller
    * @throws InvalidCast
    * @throws UndefinedPrimaryKey if the current <?= $objManyToManyReference->VariableType ?> is unsaved
    */
    public function is<?= $objManyToManyReference->ObjectDescription ?>AssociatedByKey(mixed $key): bool
    {
        if (<?= $objCodeGen->implodeObjectArray(' || ', 'is_null($this->', ')', 'VariableName', $objTable->PrimaryKeyColumnArray) ?>)
            throw new UndefinedPrimaryKey('Unable to call is<?= $objManyToManyReference->ObjectDescription ?>AssociatedByKey on this unsaved <?= $objTable->ClassName ?>.');

        $intRowCount = <?= $objTable->ClassName ?>::queryCount(
            QQ::andCondition(
                QQ::equal(QQN::<?= $objTable->ClassName ?>()-><?= $objTable->PrimaryKeyColumnArray[0]->PropertyName ?>, $this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName ?>),
                QQ::equal(QQN::<?= $objTable->ClassName ?>()-><?= $objManyToManyReference->ObjectDescription ?>-><?= $objManyToManyReference->OppositePropertyName ?>, $key)
            )
        );

        return ($intRowCount > 0);
    }

    /**
    * Associates a given <?= $objManyToManyReference->VariableType ?> as related to the current <?= $objManyToManyReference->VariableType ?> in a many-to-many relationship
    *
    * @param <?= $objManyToManyReference->VariableType ?> $<?= $objManyToManyReference->VariableName ?> The <?= $objManyToManyReference->VariableType ?> objects to associate as related
    * @return void
    * @throws Caller
    * @throws UndefinedPrimaryKey Thrown if either the current <?= $objManyToManyReference->VariableType ?> or the provided <?= $objManyToManyReference->VariableType ?> does not have a primary key defined
    */
    public function associate<?= $objManyToManyReference->ObjectDescription ?>(<?= $objManyToManyReference->VariableType ?> $<?= $objManyToManyReference->VariableName ?>): void
    {
        if (<?= $objCodeGen->implodeObjectArray(' || ', 'is_null($this->', ')', 'VariableName', $objTable->PrimaryKeyColumnArray) ?>)
            throw new UndefinedPrimaryKey('Unable to call associate<?= $objManyToManyReference->ObjectDescription ?> on this unsaved <?= $objTable->ClassName ?>.');
        if (<?= $objCodeGen->implodeObjectArray(' || ', 'is_null($' . $objManyToManyReference->VariableName . '->', ')', 'PropertyName', $objManyToManyReferenceTable->PrimaryKeyColumnArray) ?>)
            throw new UndefinedPrimaryKey('Unable to call associate<?= $objManyToManyReference->ObjectDescription ?> on this <?= $objTable->ClassName ?> with an unsaved <?= $objManyToManyReferenceTable->ClassName ?>.');

        // Get the Database Object for this Class
        $objDatabase = <?= $objTable->ClassName ?>::getDatabase();

        // Perform the SQL Query
        $objDatabase->nonQuery('
            INSERT INTO <?= $strEscapeIdentifierBegin ?><?= $objManyToManyReference->Table ?><?= $strEscapeIdentifierEnd ?> (
                <?= $strEscapeIdentifierBegin ?><?= $objManyToManyReference->Column ?><?= $strEscapeIdentifierEnd ?>,
                <?= $strEscapeIdentifierBegin ?><?= $objManyToManyReference->OppositeColumn ?><?= $strEscapeIdentifierEnd ?>

            ) VALUES (
                ' . $objDatabase->sqlVariable($this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName ?>) . ',
                ' . $objDatabase->sqlVariable($<?= $objManyToManyReference->VariableName ?>-><?= $objManyToManyReferenceTable->PrimaryKeyColumnArray[0]->PropertyName ?>) . '
            )
        ');

        // Notify
        static::broadcastAssociationAdded("<?= $objManyToManyReference->Table ?>", $this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName ?>, $<?= $objManyToManyReference->VariableName ?>-><?= $objManyToManyReferenceTable->PrimaryKeyColumnArray[0]->PropertyName ?>);
    }

    /**
    * Associates a Child <?= $objTable->ClassName ?> with this <?= $objTable->ClassName ?> in the <?= $objManyToManyReference->Table ?> table
    * @param int $<?= $objManyToManyReference->OppositeVariableName ?> The ID of the Child <?= $objTable->ClassName ?> to associate
    * @return void
    * @throws Caller
    * @throws UndefinedPrimaryKey Thrown if this <?= $objTable->ClassName ?> instance does not have a defined primary key
    */
    public function associate<?= $objManyToManyReference->ObjectDescription ?>ByKey(int $<?= $objManyToManyReference->OppositeVariableName ?>): void
    {
        if (<?= $objCodeGen->implodeObjectArray(' || ', 'is_null($this->', ')', 'VariableName', $objTable->PrimaryKeyColumnArray) ?>)
            throw new UndefinedPrimaryKey('Unable to call associate<?= $objManyToManyReference->ObjectDescription ?> on this unsaved <?= $objTable->ClassName ?>.');

        // Get the Database Object for this Class
        $objDatabase = <?= $objTable->ClassName ?>::getDatabase();

        // Perform the SQL Query
        $objDatabase->nonQuery('
            INSERT INTO <?= $strEscapeIdentifierBegin ?><?= $objManyToManyReference->Table ?><?= $strEscapeIdentifierEnd ?> (
                <?= $strEscapeIdentifierBegin ?><?= $objManyToManyReference->Column ?><?= $strEscapeIdentifierEnd ?>,
                <?= $strEscapeIdentifierBegin ?><?= $objManyToManyReference->OppositeColumn ?><?= $strEscapeIdentifierEnd ?>

            ) VALUES (
                ' . $objDatabase->sqlVariable($this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName ?>) . ',
                ' . $objDatabase->sqlVariable($<?= $objManyToManyReference->OppositeVariableName ?>) . '
            )
        ');

         // Notify
        static::broadcastAssociationAdded("<?= $objManyToManyReference->Table ?>", $this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName ?>, $<?= $objManyToManyReference->OppositeVariableName ?>);
   }

    /**
    * Unassociates a specific related <?= $objManyToManyReference->VariableType ?> from this <?= $objManyToManyReference->VariableType ?> in a many-to-many relationship
    * @param <?= $objManyToManyReference->VariableType ?> $<?= $objManyToManyReference->VariableName ?> The Project object to unassociate
    * @return void
    * @throws Caller
    * @throws UndefinedPrimaryKey If this <?= $objManyToManyReference->VariableType ?> or the provided <?= $objManyToManyReference->VariableType ?> is unsaved
    */
    public function unassociate<?= $objManyToManyReference->ObjectDescription ?>(<?= $objManyToManyReference->VariableType ?> $<?= $objManyToManyReference->VariableName ?>): void
    {
        if (<?= $objCodeGen->implodeObjectArray(' || ', 'is_null($this->', ')', 'VariableName', $objTable->PrimaryKeyColumnArray) ?>)
            throw new UndefinedPrimaryKey('Unable to call unassociate<?= $objManyToManyReference->ObjectDescription ?> on this unsaved <?= $objTable->ClassName ?>.');
        if (<?= $objCodeGen->implodeObjectArray(' || ', 'is_null($' . $objManyToManyReference->VariableName . '->', ')', 'PropertyName', $objManyToManyReferenceTable->PrimaryKeyColumnArray) ?>)
            throw new UndefinedPrimaryKey('Unable to call unassociate<?= $objManyToManyReference->ObjectDescription ?> on this <?= $objTable->ClassName ?> with an unsaved <?= $objManyToManyReferenceTable->ClassName ?>.');

        // Get the Database Object for this Class
        $objDatabase = <?= $objTable->ClassName ?>::getDatabase();

        // Perform the SQL Query
        $objDatabase->nonQuery('
            DELETE FROM
                <?= $strEscapeIdentifierBegin ?><?= $objManyToManyReference->Table ?><?= $strEscapeIdentifierEnd ?>

            WHERE
                <?= $strEscapeIdentifierBegin ?><?= $objManyToManyReference->Column ?><?= $strEscapeIdentifierEnd ?> = ' . $objDatabase->sqlVariable($this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName ?>) . ' AND
                <?= $strEscapeIdentifierBegin ?><?= $objManyToManyReference->OppositeColumn ?><?= $strEscapeIdentifierEnd ?> = ' . $objDatabase->sqlVariable($<?= $objManyToManyReference->VariableName ?>-><?= $objManyToManyReferenceTable->PrimaryKeyColumnArray[0]->PropertyName ?>) . '
        ');

        // Notify
        static::broadcastAssociationRemoved("<?= $objManyToManyReference->Table ?>", $this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName ?>, $<?= $objManyToManyReference->VariableName ?>-><?= $objManyToManyReferenceTable->PrimaryKeyColumnArray[0]->PropertyName ?>);
    }

    /**
    * Unassociates all <?= $objManyToManyReference->VariableType ?>s that are related to this <?= $objManyToManyReference->VariableType ?> in a many-to-many relationship
    * by removing their associations in the <?= $strEscapeIdentifierBegin ?><?= $objManyToManyReference->Table ?><?= $strEscapeIdentifierEnd ?> table
    * @return void
    * @throws Caller
    * @throws UndefinedPrimaryKey if called on an unsaved Project without a primary key.
    */
    public function unassociateAll<?= $objManyToManyReference->ObjectDescriptionPlural ?>(): void
    {
        if (<?= $objCodeGen->implodeObjectArray(' || ', 'is_null($this->', ')', 'VariableName', $objTable->PrimaryKeyColumnArray) ?>)
            throw new UndefinedPrimaryKey('Unable to call unassociateAll<?= $objManyToManyReference->ObjectDescription ?>Array on this unsaved <?= $objTable->ClassName ?>.');

        // Get the Database Object for this Class
        $objDatabase = <?= $objTable->ClassName ?>::getDatabase();

        // Perform the SQL Query
        $objDatabase->nonQuery('
            DELETE FROM
                <?= $strEscapeIdentifierBegin ?><?= $objManyToManyReference->Table ?><?= $strEscapeIdentifierEnd ?>

            WHERE
                <?= $strEscapeIdentifierBegin ?><?= $objManyToManyReference->Column ?><?= $strEscapeIdentifierEnd ?> = ' . $objDatabase->sqlVariable($this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName ?>) . '
        ');

        static::broadcastAssociationRemoved("<?= $objManyToManyReference->Table ?>");

    }