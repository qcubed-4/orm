<?php
	// TODO: Eventually test the $clauses to see if there is a select, and if not, select only valid data back in to the object.
?>

    /**
    * Reloads the current <?= $objTable->ClassName ?> object from the database, discarding any unsaved or cached changes.
    *
    * @param iClause[]|null $clauses An optional array of iClause objects for customizing the reload query
    * @return void
    * @throws Caller If the object is new and not yet saved to the database
    */
    public function reload(?array $clauses = null): void
    {
        // Make sure we are actually Restored from the database
        if (!$this->__blnRestored)
            throw new Caller('Cannot call Reload() on a new, unsaved <?= $objTable->ClassName ?> object.');

        // throw away all previous states of the object
        $this->deleteFromCache();
        $this->__blnValid = null;
        $this->__blnDirty = null;

        // Reload the Object
        $objReloaded = <?= $objTable->ClassName ?>::load(<?php foreach ($objTable->PrimaryKeyColumnArray as $objColumn) { ?>$this-><?= $objColumn->Identity ? '' : '__' ?><?= $objColumn->VariableName ?>, <?php } ?><?php GO_BACK(2); ?>, $clauses);

        // Update $this's local variables to match
<?php foreach ($objTable->ColumnArray as $objColumn) { ?>
<?php   if ($objColumn->Identity) { // implies primary key too ?>
        $this->__blnValid[self::<?= strtoupper($objColumn->Name) ?>_FIELD] = true;
<?php   } elseif ($objColumn->PrimaryKey) { ?>
            $this-><?= $objColumn->VariableName ?> = $objReloaded-><?= $objColumn->VariableName ?>;
            $this->__blnValid[self::<?= strtoupper($objColumn->Name) ?>_FIELD] = true;
<?php   } elseif ($objColumn->Reference) { ?>

        if (isset($objReloaded->__blnValid[self::<?= strtoupper($objColumn->Name) ?>_FIELD])) {
<?php   if ($objColumn->Reference->IsType) { ?>
            $this-><?= $objColumn->VariableName ?> = $objReloaded-><?= $objColumn->VariableName ?>;
<?php   } else { ?>
            $this-><?= $objColumn->VariableName ?> = $objReloaded-><?= $objColumn->VariableName ?>;
            $this-><?= $objColumn->Reference->VariableName ?> = $objReloaded-><?= $objColumn->Reference->VariableName ?>;
<?php   } ?>
            $this->__blnValid[self::<?= strtoupper($objColumn->Name) ?>_FIELD] = true;
        }
<?php   }  else { ?>
        if (isset($objReloaded->__blnValid[self::<?= strtoupper($objColumn->Name) ?>_FIELD])) {
            $this-><?= $objColumn->VariableName ?> = $objReloaded-><?= $objColumn->VariableName ?>;
            $this->__blnValid[self::<?= strtoupper($objColumn->Name) ?>_FIELD] = true;
        }
<?php   } ?>
<?php } ?>
    }
