/**
    * Creates a copy of the current object, resetting certain properties to ensure
    * it is treated as a new instance when saved.
    *
    * The copy will have its restored state set to false, all valid data marked as dirty,
    * and primary keys nullified to avoid conflicts. Additionally, specific properties
    * may be reset to their default values.
    *
    * @return <?= $objTable->ClassName ?>|static A new instance of the object with specific properties reset.
    */
   	public function copy(): <?= $objTable->ClassName ?>|static
    {
        $objCopy = clone $this;
        $objCopy->__blnRestored = false;

        // Make sure all valid data is dirty so it will be saved
        foreach ($this->__blnValid as $key=>$val) {
            $objCopy->__blnDirty[$key] = $val;
        }

        // Nullify primary keys so they will be saved as a different object
<?php foreach ($objTable->PrimaryKeyColumnArray as $objPkColumn) { ?>
        $objCopy-><?= $objPkColumn->VariableName ?> = null;
<?php } ?>

<?php foreach ($objTable->ColumnArray as $objColumn) { ?>
<?php if (($objColumn->Reference) && (!$objColumn->Reference->IsType)) { ?>
<?php if ($objColumn->Unique) { ?>
<?php $strConstName = strtoupper($objColumn->Name);?>
        $objCopy-><?= $objColumn->VariableName ?> = self::<?= $strConstName ?>_DEFAULT;
        $objCopy-><?= $objColumn->Reference->VariableName ?> = null;
<?php } 	// NOTE HERE: Non-unique forward references can persist here. ?>
<?php } ?>
<?php } ?>
<?php if ($objTable->ReverseReferenceArray) { ?>
        // Reverse references
<?php foreach ($objTable->ReverseReferenceArray as $objReverseReference) { ?>
<?php if ($objReverseReference->Unique) { ?>
        $objCopy-><?= $objReverseReference->ObjectMemberVariable ?> = null;
        $objCopy->blnDirty<?= $objReverseReference->ObjectPropertyName ?> = false;
<?php } else { ?>
        $objCopy->_obj<?= $objReverseReference->ObjectDescription ?> = null;
        $objCopy->_obj<?= $objReverseReference->ObjectDescription ?>Array = null;
<?php } ?>
<?php } ?>
<?php } ?>

<?php if ($objTable->ManyToManyReferenceArray) { ?>
        // Many-to-many references
<?php foreach ($objTable->ManyToManyReferenceArray as $objReference) { ?>
<?php
        $objAssociatedTable = $objCodeGen->GetTable($objReference->AssociatedTable);
        $varPrefix = (is_a($objAssociatedTable, \QCubed\Codegen\TypeTable::class) ? '_int' : '_obj');
?>
        $objCopy-><?= $varPrefix . $objReference->ObjectDescription ?> = null;
        $objCopy-><?= $varPrefix . $objReference->ObjectDescription ?>Array = null;
<?php } ?>
<?php } ?>
        return $objCopy;
    }