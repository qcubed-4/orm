
    ////////////////////////
    //  GETTERS and SETTERS
    ////////////////////////

<?php foreach ($objTable->ColumnArray as $objColumn) {
    // Override type specification:
    $displayType = $objColumn->VariableType;
    if ($displayType === 'integer') {
        $displayType = 'int';
    }
    if ($displayType === 'double') {
        $displayType = 'float';
    }
    if ($displayType === 'boolean') {
        $displayType = 'bool';
    }
    if ($displayType === '\QCubed\QDateTime') {
        $displayType = 'QDateTime';
    }
    if ($displayType === 'QCubed\Type') {
        $displayType = 'Type';
    }
    // add more else-ifs if necessary!
?>

   /**
    * Retrieves the value of the current property
    *
    * @return <?= $displayType ?? $objColumn->VariableType ?>|null The property value, or null if not set
    * @throws Caller
    */
    public function get<?= $objColumn->PropertyName ?>(): ?<?= $displayType ?? $objColumn->VariableType ?>

    {
<?php if (!$objColumn->Identity) { ?>
        if ($this->__blnRestored && empty($this->__blnValid[self::<?= strtoupper($objColumn->Name) ?>_FIELD])) {
            throw new Caller("<?= $objColumn->PropertyName ?> was not selected in the most recent query and is not valid.");
        }
<?php } ?>
        return $this-><?= $objColumn->VariableName ?>;
    }
<?php if ($objColumn->Reference && $objColumn->Reference->IsType) { ?>

    /**
    * Retrieves the <?= strtolower($objTable->ClassName) ?> status type as a string based on the current <?= strtolower($objTable->ClassName) ?>'s status type ID.
    *
    * @return string|null Returns the <?= strtolower($objTable->ClassName) ?> status type as a string. If the status type ID is null, an empty string is returned.
    * @throws Caller
    */
    public function get<?= $objColumn->Reference->PropertyName ?>():?string
    {
        $intId = $this->get<?= $objColumn->PropertyName ?>();
        if ($intId === null) {
            return "";
        }
        return <?= $objColumn->Reference->VariableType ?>::toString($intId);
    }
<?php } ?>
<?php if ($objColumn->Reference && !$objColumn->Reference->IsType) { ?>

    /**
    * Receives the value of the <?= $objColumn->Reference->VariableType ?> object referenced by <?= $objColumn->VariableName ?>

    *
    * If the object is not loaded, will load the object (caching it) before returning it
    *
    * @return <?= $objColumn->Reference->VariableType ?>|false|null The associated Person object, false if retrieval fails, or null if no PersonId is set.
    * @throws Caller
    * @throws InvalidCast
    */
     public function get<?= $objColumn->Reference->PropertyName ?>(): <?= $objColumn->Reference->VariableType ?>|false|null
     {
        if ($this->__blnRestored && empty($this->__blnValid[self::<?= strtoupper($objColumn->Name) ?>_FIELD])) {
            throw new Caller("<?= $objColumn->PropertyName ?> was not selected in the most recent query and is not valid.");
        }
        if ((!$this-><?= $objColumn->Reference->VariableName ?>) && (!is_null($this-><?= $objColumn->VariableName ?>))) {
            $this-><?= $objColumn->Reference->VariableName ?> = <?= $objColumn->Reference->VariableType ?>::load($this-><?= $objColumn->VariableName ?>);
        }
        return $this-><?= $objColumn->Reference->VariableName ?>;
     }
<?php } ?>
<?php if ((!$objColumn->Identity) && (!$objColumn->Timestamp)) { ?>

    /**
    * Receives the value of $<?= $objColumn->VariableName ?>

    *
    * @param <?= $displayType ?? $objColumn->VariableType ?>|null $<?= $objColumn->VariableName ?>

    * @return static Returns the current instance for method chaining.
    * @throws Caller
    */
    public function set<?= $objColumn->PropertyName ?>(?<?= $displayType ?? $objColumn->VariableType ?> $<?= $objColumn->VariableName ?>): static
    {
<?php if ($objColumn->NotNull) { ?>
        if ($<?= $objColumn->VariableName ?> === null) {
<?php if (is_null($objColumn->Default)) { ?>
             // invalidate
             $<?= $objColumn->VariableName ?> = null;
             $this->__blnValid[self::<?= strtoupper($objColumn->Name) ?>_FIELD] = false;
<?php } else { ?>
             $<?= $objColumn->VariableName ?> = static::<?= $objColumn->PropertyName ?>Default;
<?php } ?>
            return $this; // allows chaining
        }
<?php } ?>
        $<?= $objColumn->VariableName ?> = Type::cast($<?= $objColumn->VariableName ?>, <?= $objColumn->VariableTypeAsConstant ?>);

        if ($this-><?= $objColumn->VariableName ?> !== $<?= $objColumn->VariableName ?>) {
<?php if (($objColumn->Reference) && (!$objColumn->Reference->IsType)) { ?>
            $this-><?= $objColumn->Reference->VariableName ?> = null; // remove the associated object
<?php } ?>
            $this-><?= $objColumn->VariableName ?> = $<?= $objColumn->VariableName ?>;
            $this->__blnDirty[self::<?= strtoupper($objColumn->Name) ?>_FIELD] = true;
        }
        $this->__blnValid[self::<?= strtoupper($objColumn->Name) ?>_FIELD] = true;
        return $this; // allows chaining
    }
<?php if (($objColumn->Reference) && (!$objColumn->Reference->IsType)) { ?>

    /**
    * Sets the associate of <?= $objTable->ClassName ?> for this instance
    * Validates that the input is a saved object of the correct type and associates it with the current instance
    * If a null value is passed, the association will be cleared
    *
    * @param <?= $objTable->ClassName ?>|null $<?= $objColumn->Reference->VariableName ?> The associated object to set. Pass null to clear the association
    * @return static The current instance for method chaining
    * @throws Caller If an unsaved object is passed.
    */
    public function set<?= $objColumn->Reference->PropertyName ?>(<?= $objTable->ClassName ?>|null $<?= $objColumn->Reference->VariableName ?>): static
    {
        if (is_null($<?= $objColumn->Reference->VariableName ?>)) {
            $this->set<?= $objColumn->PropertyName ?>(null);
        } else {
            $<?= $objColumn->Reference->VariableName ?> = Type::cast($<?= $objColumn->Reference->VariableName ?>, '<?= $objColumn->Reference->VariableType ?>');

            // Make sure its SAVED <?= $objColumn->Reference->VariableType ?> object
            if (is_null($<?= $objColumn->Reference->VariableName ?>-><?= $objCodeGen->TableArray[strtolower($objColumn->Reference->Table)]->ColumnArray[strtolower($objColumn->Reference->Column)]->PropertyName ?>)) {
                throw new Caller('Unable to set an unsaved <?= $objColumn->Reference->PropertyName ?> for this <?= $objTable->ClassName ?>');
            }

            // Update Local Member Variables
            $this->set<?= $objColumn->PropertyName ?>($<?= $objColumn->Reference->VariableName ?>->get<?= $objCodeGen->TableArray[strtolower($objColumn->Reference->Table)]->ColumnArray[strtolower($objColumn->Reference->Column)]->PropertyName ?>());
            $this-><?= $objColumn->Reference->VariableName ?> = $<?= $objColumn->Reference->VariableName ?>;
        }
        return $this;
    }
<?php } ?>
<?php } ?>
<?php } ?>

<?php
    // Unique reverse reference properties
foreach ($objTable->ReverseReferenceArray as $objReverseReference) {
    if ($objReverseReference->Unique) {
        $objReverseReferenceTable = $objCodeGen->TableArray[strtolower($objReverseReference->Table)];
        $objReverseReferenceColumn = $objReverseReferenceTable->ColumnArray[strtolower($objReverseReference->Column)]; ?>
   /**
    * Receives the value of the <?= $objReverseReference->VariableType ?> object that uniquely references this <?= $objTable->ClassName ?> <?= $objReverseReference->ObjectMemberVariable ?> (Unique)
    *
    * Returns null if the object does not exist
    * @return <?= $objReverseReference->VariableType ?>|false|null
    * @throws Caller
    */
    public function get<?= $objReverseReference->ObjectPropertyName ?>(): <?= $objReverseReference->VariableType ?>|false|null
    {
        if (!$this->__blnRestored ||
            $this-><?= $objReverseReference->ObjectMemberVariable ?> === false) {
            // Either this is a new object, or we've attempted early binding—and the reverse reference object does not exist
            return null;
        }
        if (!$this-><?= $objReverseReference->ObjectMemberVariable ?>) {
            $this-><?= $objReverseReference->ObjectMemberVariable ?> = <?= $objReverseReference->VariableType ?>::loadBy<?= $objReverseReferenceColumn->PropertyName ?>(<?= $objCodeGen->ImplodeObjectArray(', ', '$this->', '', 'VariableName', $objTable->PrimaryKeyColumnArray) ?>);
        }
        return $this-><?= $objReverseReference->ObjectMemberVariable ?>;
    }

   /**
    * Sets the <?= $objTable->ClassName ?> object associated with this entity
    *
    * @param <?= $objReverseReference->VariableType ?>|null $<?= $objReverseReference->ObjectMemberVariable ?> The <?= $objTable->ClassName ?> object to associate or null to unset it
    * @return static Returns the current instance for method chaining.
    * @throws Exception If the provided object cannot be cast to the required <?= $objTable->ClassName ?> type.
    * @throws Caller
    */
    public function set<?= $objReverseReference->ObjectPropertyName ?>(?<?= $objReverseReference->VariableType ?> $<?= $objReverseReference->ObjectMemberVariable ?>): static
    {
        if (is_null($<?= $objReverseReference->ObjectMemberVariable ?>)) {
            $this-><?= $objReverseReference->ObjectMemberVariable ?> = null;

            // Make sure we update the adjoined <?= $objReverseReference->VariableType ?> object the next time we call Save()
            $this->blnDirty<?= $objReverseReference->ObjectPropertyName ?> = true;
        } else {
            $<?= $objReverseReference->ObjectMemberVariable ?> = Type::cast($<?= $objReverseReference->ObjectMemberVariable ?>, '<?= $objReverseReference->VariableType ?>');

            // Are we setting <?= $objReverseReference->ObjectMemberVariable ?> to a DIFFERENT $<?= $objReverseReference->ObjectMemberVariable ?>?
            if ((!$this-><?= $objReverseReference->ObjectPropertyName ?>) || ($this-><?= $objReverseReference->ObjectPropertyName ?>-><?= $objCodeGen->GetTable($objReverseReference->Table)->PrimaryKeyColumnArray[0]->PropertyName ?> != $<?= $objReverseReference->ObjectMemberVariable ?>-><?= $objCodeGen->GetTable($objReverseReference->Table)->PrimaryKeyColumnArray[0]->PropertyName ?>)) {
                // Yes -- therefore, set the "Dirty" flag to true
                // to make sure we update the adjoined <?= $objReverseReference->VariableType ?> object the next time we call Save()
                $this->blnDirty<?= $objReverseReference->ObjectPropertyName ?> = true;

                // Update Local Member Variable
                $this-><?= $objReverseReference->ObjectMemberVariable ?> = $<?= $objReverseReference->ObjectMemberVariable ?>;
            }
        }
        return $this;
    }

<?php }
}