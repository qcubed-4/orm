<?php $objReverseReferenceTable = $objCodeGen->TableArray[strtolower($objReverseReference->Table)]; ?>
<?php $objReverseReferenceColumn = $objReverseReferenceTable->ColumnArray[strtolower($objReverseReference->Column)]; ?>

    // Related Objects' Methods for <?= $objReverseReference->ObjectDescription ?>

    //-------------------------------------------------------------------

    /**
    * Retrieve an array of <?= $objReverseReference->ObjectDescriptionPlural ?> objects associated with the current <?= $objTable->ClassName ?>

    *
    * @param iClause[]|null $objOptionalClauses additional optional iClause objects for this query
    * @return <?= $objReverseReference->VariableType ?>[] an array of Address objects associated with the current <?= $objTable->ClassName ?>

    * @throws Caller
    */
    public function get<?= $objReverseReference->ObjectDescription ?>Array(?array $objOptionalClauses = null): array
    {
        if (<?= $objCodeGen->ImplodeObjectArray(' || ', 'is_null($this->', ')', 'VariableName', $objTable->PrimaryKeyColumnArray) ?>)
            return array();

        try {
            return <?= $objReverseReference->VariableType ?>::loadArrayBy<?= $objReverseReferenceColumn->PropertyName ?>(<?= $objCodeGen->ImplodeObjectArray(', ', '$this->', '', 'VariableName', $objTable->PrimaryKeyColumnArray) ?>, $objOptionalClauses);
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }
    }

    /**
    * Count the number of <?= $objReverseReference->ObjectDescriptionPlural ?> objects associated with the current <?= $objTable->ClassName ?> object
    *
    * @return int The count of <?= $objReverseReference->ObjectDescriptionPlural ?> objects linked to this <?= $objTable->ClassName ?>, or 0 if the <?= $objTable->ClassName ?> ID is null
    * @throws Caller
    * @throws InvalidCast
    */
    public function count<?= $objReverseReference->ObjectDescriptionPlural ?>(): int
    {
        if (<?= $objCodeGen->ImplodeObjectArray(' || ', '(is_null($this->', '))', 'VariableName', $objTable->PrimaryKeyColumnArray) ?>)
            return 0;

        return <?= $objReverseReference->VariableType ?>::countBy<?= $objReverseReferenceColumn->PropertyName ?>(<?= $objCodeGen->ImplodeObjectArray(', ', '$this->', '', 'VariableName', $objTable->PrimaryKeyColumnArray) ?>);
    }

    /**
    * Associates a <?= $objReverseReference->ObjectDescription ?> object with the current <?= $objTable->ClassName ?>

    *
    * @param <?= $objReverseReference->VariableType ?> $<?= $objReverseReference->VariableName ?> The <?= $objReverseReference->VariableType ?> objects to associate with the current Person
    * @return void
    * @throws Caller
    * @throws UndefinedPrimaryKey If the current <?= $objTable->ClassName ?> object or the <?= $objReverseReference->VariableType ?> object does not have a defined primary key
    */
    public function associate<?= $objReverseReference->ObjectDescription ?>(<?= $objReverseReference->VariableType ?> $<?= $objReverseReference->VariableName ?>): void
    {
        if (<?= $objCodeGen->ImplodeObjectArray(' || ', 'is_null($this->', ')', 'VariableName', $objTable->PrimaryKeyColumnArray) ?>)
            throw new UndefinedPrimaryKey('Unable to call Associate<?= $objReverseReference->ObjectDescription ?> on this unsaved <?= $objTable->ClassName ?>.');
        if (<?= $objCodeGen->ImplodeObjectArray(' || ', 'is_null($' . $objReverseReference->VariableName . '->', ')', 'PropertyName', $objReverseReferenceTable->PrimaryKeyColumnArray) ?>)
            throw new UndefinedPrimaryKey('Unable to call Associate<?= $objReverseReference->ObjectDescription ?> on this <?= $objTable->ClassName ?> with an unsaved <?= $objReverseReferenceTable->ClassName ?>.');

        // Get the Database Object for this Class
        $objDatabase = <?= $objTable->ClassName ?>::getDatabase();

        // Perform the SQL Query
        $objDatabase->NonQuery('
            UPDATE
                <?= $strEscapeIdentifierBegin ?><?= $objReverseReference->Table ?><?= $strEscapeIdentifierEnd ?>

            SET
                <?= $strEscapeIdentifierBegin ?><?= $objReverseReference->Column ?><?= $strEscapeIdentifierEnd ?> = ' . $objDatabase->SqlVariable($this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName ?>) . '
            WHERE
<?php foreach ($objReverseReferenceTable->ColumnArray as $objColumn) { ?>
<?php if ($objColumn->PrimaryKey) { ?>
                <?= $strEscapeIdentifierBegin ?><?= $objColumn->Name ?><?= $strEscapeIdentifierEnd ?> = ' . $objDatabase->SqlVariable($<?= $objReverseReference->VariableName ?>-><?= $objColumn->PropertyName ?>) . ' AND
<?php } ?><?php } ?><?php GO_BACK(5); ?>

        ');
    }

    /**
    * Unassociate the specified <?= $objReverseReference->ObjectDescription ?> object from this <?= $objTable->ClassName ?>

    *
    * @param <?= $objReverseReference->VariableType ?> $<?= $objReverseReference->VariableName ?> The Address objects to unassociate from this <?= $objTable->ClassName ?>

    * @return void
    * @throws Caller
    * @throws UndefinedPrimaryKey If this <?= $objTable->ClassName ?> or the given <?= $objReverseReference->VariableType ?> has an undefined primary key
    */
    public function unassociate<?= $objReverseReference->ObjectDescription ?>(<?= $objReverseReference->VariableType ?> $<?= $objReverseReference->VariableName ?>): void
    {
        if (<?= $objCodeGen->ImplodeObjectArray(' || ', 'is_null($this->', ')', 'VariableName', $objTable->PrimaryKeyColumnArray) ?>)
            throw new UndefinedPrimaryKey('Unable to call Unassociate<?= $objReverseReference->ObjectDescription ?> on this unsaved <?= $objTable->ClassName ?>.');
        if (<?= $objCodeGen->ImplodeObjectArray(' || ', 'is_null($' . $objReverseReference->VariableName . '->', ')', 'PropertyName', $objReverseReferenceTable->PrimaryKeyColumnArray) ?>)
            throw new UndefinedPrimaryKey('Unable to call Unassociate<?= $objReverseReference->ObjectDescription ?> on this <?= $objTable->ClassName ?> with an unsaved <?= $objReverseReferenceTable->ClassName ?>.');

        // Get the Database Object for this Class
        $objDatabase = <?= $objTable->ClassName ?>::getDatabase();

        // Perform the SQL Query
        $objDatabase->NonQuery('
            UPDATE
                <?= $strEscapeIdentifierBegin ?><?= $objReverseReference->Table ?><?= $strEscapeIdentifierEnd ?>

            SET
                <?= $strEscapeIdentifierBegin ?><?= $objReverseReference->Column ?><?= $strEscapeIdentifierEnd ?> = null
            WHERE
<?php foreach ($objReverseReferenceTable->ColumnArray as $objColumn) { ?>
<?php if ($objColumn->PrimaryKey) { ?>
                <?= $strEscapeIdentifierBegin ?><?= $objColumn->Name ?><?= $strEscapeIdentifierEnd ?> = ' . $objDatabase->SqlVariable($<?= $objReverseReference->VariableName ?>-><?= $objColumn->PropertyName ?>) . ' AND
<?php } ?><?php } ?><?php GO_BACK(1); ?>

                <?= $strEscapeIdentifierBegin ?><?= $objReverseReference->Column ?><?= $strEscapeIdentifierEnd ?> = ' . $objDatabase->SqlVariable($this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName ?>) . '
        ');
    }

    /**
    * Unassociates all <?= $objReverseReference->ObjectDescription ?> objects related to this <?= $objTable->ClassName ?> object by setting their <?= $objTable->ClassName ?> reference to null
    *
    * @return void
    * @throws Caller
    * @throws UndefinedPrimaryKey if the <?= $objTable->ClassName ?> object does not have a defined primary key
    */
    public function unassociateAll<?= $objReverseReference->ObjectDescriptionPlural ?>(): void
    {
        if (<?= $objCodeGen->ImplodeObjectArray(' || ', 'is_null($this->', ')', 'VariableName', $objTable->PrimaryKeyColumnArray) ?>)
            throw new UndefinedPrimaryKey('Unable to call Unassociate<?= $objReverseReference->ObjectDescription ?> on this unsaved <?= $objTable->ClassName ?>.');

        // Get the Database Object for this Class
        $objDatabase = <?= $objTable->ClassName ?>::getDatabase();

        // Perform the SQL Query
        $objDatabase->NonQuery('
            UPDATE
                <?= $strEscapeIdentifierBegin ?><?= $objReverseReference->Table ?><?= $strEscapeIdentifierEnd ?>

            SET
                <?= $strEscapeIdentifierBegin ?><?= $objReverseReference->Column ?><?= $strEscapeIdentifierEnd ?> = null
            WHERE
                <?= $strEscapeIdentifierBegin ?><?= $objReverseReference->Column ?><?= $strEscapeIdentifierEnd ?> = ' . $objDatabase->SqlVariable($this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName ?>) . '
        ');
    }

    /**
    * Deletes the association between this <?= $objTable->ClassName ?> and a given <?= $objReverseReference->ObjectDescription ?>

    *
    * @param <?= $objReverseReference->VariableType ?> $<?= $objReverseReference->VariableName ?> The <?= $objReverseReference->VariableType ?> objects to disassociate from this <?= $objTable->ClassName ?>

    * @return void
    * @throws Caller
    * @throws UndefinedPrimaryKey If this <?= $objTable->ClassName ?> or the provided <?= $objReverseReference->VariableType ?> does not have a valid primary key
    */
    public function deleteAssociated<?= $objReverseReference->ObjectDescription ?>(<?= $objReverseReference->VariableType ?> $<?= $objReverseReference->VariableName ?>): void
    {
        if (<?= $objCodeGen->ImplodeObjectArray(' || ', 'is_null($this->', ')', 'VariableName', $objTable->PrimaryKeyColumnArray) ?>)
            throw new UndefinedPrimaryKey('Unable to call Unassociate<?= $objReverseReference->ObjectDescription ?> on this unsaved <?= $objTable->ClassName ?>.');
        if (<?= $objCodeGen->ImplodeObjectArray(' || ', 'is_null($' . $objReverseReference->VariableName . '->', ')', 'PropertyName', $objReverseReferenceTable->PrimaryKeyColumnArray) ?>)
            throw new UndefinedPrimaryKey('Unable to call Unassociate<?= $objReverseReference->ObjectDescription ?> on this <?= $objTable->ClassName ?> with an unsaved <?= $objReverseReferenceTable->ClassName ?>.');

        // Get the Database Object for this Class
        $objDatabase = <?= $objTable->ClassName ?>::getDatabase();

        // Perform the SQL Query
        $objDatabase->NonQuery('
            DELETE FROM
                <?= $strEscapeIdentifierBegin ?><?= $objReverseReference->Table ?><?= $strEscapeIdentifierEnd ?>

            WHERE
<?php foreach ($objReverseReferenceTable->ColumnArray as $objColumn) { ?>
<?php if ($objColumn->PrimaryKey) { ?>
                <?= $strEscapeIdentifierBegin ?><?= $objColumn->Name ?><?= $strEscapeIdentifierEnd ?> = ' . $objDatabase->SqlVariable($<?= $objReverseReference->VariableName ?>-><?= $objColumn->PropertyName ?>) . ' AND
<?php } ?><?php } ?><?php GO_BACK(1); ?>

                <?= $strEscapeIdentifierBegin ?><?= $objReverseReference->Column ?><?= $strEscapeIdentifierEnd ?> = ' . $objDatabase->SqlVariable($this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName ?>) . '
        ');
    }

    /**
    * Deletes all <?= strtolower($objReverseReference->ObjectDescriptionPlural) ?> associated with the current <?= $objTable->ClassName ?> object in the database
    *
    * @return void
    * @throws Caller
    * @throws UndefinedPrimaryKey if the primary key for the current <?= $objTable->ClassName ?> object is not defined
    */
    public function deleteAll<?= $objReverseReference->ObjectDescriptionPlural ?>(): void
    {
        if (<?= $objCodeGen->ImplodeObjectArray(' || ', 'is_null($this->', ')', 'VariableName', $objTable->PrimaryKeyColumnArray) ?>)
            throw new UndefinedPrimaryKey('Unable to call Unassociate<?= $objReverseReference->ObjectDescription ?> on this unsaved <?= $objTable->ClassName ?>.');

        // Get the Database Object for this Class
        $objDatabase = <?= $objTable->ClassName ?>::getDatabase();

        // Perform the SQL Query
        $objDatabase->NonQuery('
            DELETE FROM
                <?= $strEscapeIdentifierBegin ?><?= $objReverseReference->Table ?><?= $strEscapeIdentifierEnd ?>

            WHERE
                <?= $strEscapeIdentifierBegin ?><?= $objReverseReference->Column ?><?= $strEscapeIdentifierEnd ?> = ' . $objDatabase->SqlVariable($this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName ?>) . '
        ');
    }
