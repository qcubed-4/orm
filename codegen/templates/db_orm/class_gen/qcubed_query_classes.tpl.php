    //////////////////////////////////////
    // ADDITIONAL CLASSES for QCubed QUERY
    //////////////////////////////////////

<?php foreach ($objTable->ManyToManyReferenceArray as $objReference) { ?>
/**
 * @property-read Node\Column $<?= $objReference->OppositePropertyName ?>

 * @property-read Node<?= $objReference->VariableType ?> $<?= $objReference->VariableType ?>

 * @property-read Node<?= $objReference->VariableType ?> $_ChildTableNode
 **/
class Node<?= $objTable->ClassName ?><?= $objReference->ObjectDescription ?> extends Node\Association
{
    protected ?string $strType = Type::ASSOCIATION;
    protected ?string $strName = '<?= strtolower($objReference->ObjectDescription); ?>';

    protected ?string $strTableName = '<?= $objReference->Table ?>';
    protected ?string $strPrimaryKey = '<?= $objReference->Column ?>';
    protected ?string $strClassName = '<?= $objReference->VariableType ?>';
    protected ?string $strPropertyName = '<?= $objReference->ObjectDescription ?>';
    protected ?string $strAlias = '<?= strtolower($objReference->ObjectDescription); ?>';

    /**
    * Magic method to retrieve properties dynamically.
    *
    * @param string $strName The name of the property being accessed.
    * @return mixed Returns the value of the requested property if it exists, or delegates to the parent method.
    * @throws Caller If the requested property is not accessible or does not exist.
    */
    public function __get(string $strName): mixed
    {
        switch ($strName) {
            case '<?= $objReference->OppositePropertyName ?>':
                return new Node\Column('<?= $objReference->OppositeColumn ?>', '<?= $objReference->OppositePropertyName ?>', '<?= $objReference->OppositeDbType ?>', $this);
            case '<?= $objReference->VariableType ?>':
            case '_ChildTableNode':
                return new Node<?= $objReference->VariableType ?>('<?= $objReference->OppositeColumn ?>', '<?= $objReference->OppositePropertyName ?>', '<?= $objReference->OppositeDbType ?>', $this);

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

<?php } ?>
/**
<?php foreach ($objTable->ColumnArray as $objColumn) { ?>
 * @property-read Node\Column $<?= $objColumn->PropertyName ?>

<?php if ($objColumn->Reference) { ?>
 * @property-read Node<?= $objColumn->Reference->VariableType; ?> $<?= $objColumn->Reference->PropertyName ?>

<?php } ?>
<?php } ?>
<?php foreach ($objTable->ManyToManyReferenceArray as $objReference) { ?>
 * @property-read Node<?= $objTable->ClassName ?><?= $objReference->ObjectDescription ?> $<?= $objReference->ObjectDescription ?>

<?php } ?>
<?php foreach ($objTable->ReverseReferenceArray as $objReference) { ?>
 * @property-read ReverseReferenceNode<?= $objReference->VariableType ?> $<?= $objReference->ObjectDescription ?>

<?php } ?>
<?php $objPkColumn = $objTable->PrimaryKeyColumnArray[0]; ?>
 * @property-read Node\Column<?php if (($objPkColumn->Reference) && (!$objPkColumn->Reference->IsType)) print $objPkColumn->Reference->VariableType; ?> $_PrimaryKeyNode
 **/
class Node<?= $objTable->ClassName ?> extends Node\Table {
    protected ?string $strTableName = '<?= $objTable->Name ?>';
    protected ?string $strPrimaryKey = '<?= $objTable->PrimaryKeyColumnArray[0]->Name ?>';
    protected ?string $strClassName = '<?= $objTable->ClassName ?>';

    /**
    * Returns an array of fields.
    *
    * @return array The list of defined fields.
    */
    public function fields(): array
    {
        return [
<?php foreach ($objTable->ColumnArray as $objColumn) { ?>
            "<?= $objColumn->Name ?>",
<?php } ?>
        ];
    }

    /**
    * Retrieves the list of primary key fields for the entity.
    *
    * @return array The list of primary key fields.
    */
    public function primaryKeyFields(): array
    {
        return [
<?php foreach ($objTable->PrimaryKeyColumnArray as $objColumn) { ?>
            "<?= $objColumn->Name ?>",
<?php } ?>
        ];
    }

    /**
    * Retrieves and returns the database connection instance.
    *
    * @return DatabaseBase The database connection instance.
    */
    protected function database(): DatabaseBase
    {
        return Service::getDatabase(<?= $objCodeGen->DatabaseIndex; ?>);
    }

    /**
    * Retrieves the value of a property based on its name.
    *
    * @param string $strName The name of the property to retrieve.
    * @return mixed The value of the requested property. If the property does not exist, attempts to retrieve it using the parent class or throws an exception if not found.
    * @throws Caller
    * @throws UndefinedProperty
    */
    public function __get(string $strName): mixed
    {
        switch ($strName) {
<?php foreach ($objTable->ColumnArray as $objColumn) { ?>
            case '<?= $objColumn->PropertyName ?>':
                return new Node\Column('<?= $objColumn->Name ?>', '<?= $objColumn->PropertyName ?>', '<?= $objColumn->DbType ?>', $this);
<?php if ($objColumn->Reference) { ?>
            case '<?= $objColumn->Reference->PropertyName ?>':
                return new Node<?= $objColumn->Reference->VariableType; ?>('<?= $objColumn->Name ?>', '<?= $objColumn->Reference->PropertyName ?>', '<?= $objColumn->DbType ?>', $this);
<?php } ?>
<?php } ?>
<?php foreach ($objTable->ManyToManyReferenceArray as $objReference) { ?>
            case '<?= $objReference->ObjectDescription ?>':
                return new Node<?= $objTable->ClassName ?><?= $objReference->ObjectDescription ?>($this);
<?php } ?><?php foreach ($objTable->ReverseReferenceArray as $objReference) { ?>
            case '<?= $objReference->ObjectDescription ?>':
                return new ReverseReferenceNode<?= $objReference->VariableType ?>($this, '<?= strtolower($objReference->ObjectDescription); ?>', \QCubed\Type::REVERSE_REFERENCE, '<?= $objReference->Column ?>', '<?= $objReference->ObjectDescription ?>');
<?php } ?><?php $objPkColumn = $objTable->PrimaryKeyColumnArray[0]; ?>

            case '_PrimaryKeyNode':
<?php if (($objPkColumn->Reference) && (!$objPkColumn->Reference->IsType)) {?>
                return new Node<?= $objPkColumn->Reference->VariableType; ?>('<?= $objPkColumn->Name ?>', '<?= $objPkColumn->PropertyName ?>', '<?= $objPkColumn->DbType ?>', $this);
<?php } else { ?>
                return new Node\Column('<?= $objPkColumn->Name ?>', '<?= $objPkColumn->PropertyName ?>', '<?= $objPkColumn->DbType ?>', $this);
<?php } ?>
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

/**
<?php foreach ($objTable->ColumnArray as $objColumn) { ?>
 * @property-read Node\Column $<?= $objColumn->PropertyName ?>

<?php if (($objColumn->Reference) && (!$objColumn->Reference->IsType)) { ?>
 * @property-read Node<?= $objColumn->Reference->VariableType; ?> $<?= $objColumn->Reference->PropertyName ?>

<?php } ?>
<?php } ?>
<?php foreach ($objTable->ManyToManyReferenceArray as $objReference) { ?>
 * @property-read Node<?= $objTable->ClassName ?><?= $objReference->ObjectDescription ?> $<?= $objReference->ObjectDescription ?>

<?php } ?>
<?php foreach ($objTable->ReverseReferenceArray as $objReference) { ?>
 * @property-read ReverseReferenceNode<?= $objReference->VariableType ?> $<?= $objReference->ObjectDescription ?>

<?php } ?>
<?php $objPkColumn = $objTable->PrimaryKeyColumnArray[0]; ?>

 * @property-read Node\Column<?php if (($objPkColumn->Reference) && (!$objPkColumn->Reference->IsType)) print $objPkColumn->Reference->VariableType; ?> $_PrimaryKeyNode
 **/
class ReverseReferenceNode<?= $objTable->ClassName ?> extends Node\ReverseReference {
    protected ?string $strTableName = '<?= $objTable->Name ?>';
    protected ?string $strPrimaryKey = '<?= $objTable->PrimaryKeyColumnArray[0]->Name ?>';
    protected ?string $strClassName = '<?= $objTable->ClassName ?>';

    /**
    * Returns an array of fields.
    *
    * @return array The list of defined fields.
    */
    public function fields(): array
    {
        return [
<?php foreach ($objTable->ColumnArray as $objColumn) { ?>
            "<?= $objColumn->Name ?>",
<?php } ?>
        ];
    }

    /**
    * Retrieves an array of field names that represent the primary key for the corresponding entity.
    *
    * @return array An array of strings containing the names of the primary key fields.
    */
    public function primaryKeyFields(): array
    {
        return [
<?php foreach ($objTable->PrimaryKeyColumnArray as $objColumn) { ?>
            "<?= $objColumn->Name ?>",
<?php } ?>
        ];
    }

    /**
    * Retrieves the value of a property based on its name.
    *
    * @param string $strName The name of the property to retrieve.
    * @return mixed The value of the requested property. If the property does not exist, attempts to retrieve it using the parent class or throws an exception if not found.
    * @throws Caller
    * @throws UndefinedProperty
    */
    public function __get(string $strName): mixed
    {
        switch ($strName) {
<?php foreach ($objTable->ColumnArray as $objColumn) { ?>
            case '<?= $objColumn->PropertyName ?>':
                return new Node\Column('<?= $objColumn->Name ?>', '<?= $objColumn->PropertyName ?>', '<?= $objColumn->DbType ?>', $this);
<?php if (($objColumn->Reference) && (!$objColumn->Reference->IsType)) { ?>
            case '<?= $objColumn->Reference->PropertyName ?>':
                return new Node<?= $objColumn->Reference->VariableType; ?>('<?= $objColumn->Name ?>', '<?= $objColumn->Reference->PropertyName ?>', '<?= $objColumn->DbType ?>', $this);
<?php } ?>
<?php } ?>
<?php foreach ($objTable->ManyToManyReferenceArray as $objReference) { ?>
            case '<?= $objReference->ObjectDescription ?>':
                return new Node<?= $objTable->ClassName ?><?= $objReference->ObjectDescription ?>($this);
<?php } ?><?php foreach ($objTable->ReverseReferenceArray as $objReference) { ?>
            case '<?= $objReference->ObjectDescription ?>':
                return new ReverseReferenceNode<?= $objReference->VariableType ?>($this, '<?= strtolower($objReference->ObjectDescription); ?>', \QCubed\Type::REVERSE_REFERENCE, '<?= $objReference->Column ?>', '<?= $objReference->ObjectDescription ?>');
<?php } ?><?php $objPkColumn = $objTable->PrimaryKeyColumnArray[0]; ?>

            case '_PrimaryKeyNode':
<?php if (($objPkColumn->Reference) && (!$objPkColumn->Reference->IsType)) {?>
                return new Node<?= $objPkColumn->Reference->VariableType; ?>('<?= $objPkColumn->Name ?>', '<?= $objPkColumn->PropertyName ?>', '<?= $objPkColumn->DbType ?>', $this);
<?php } else { ?>
                return new Node\Column('<?= $objPkColumn->Name ?>', '<?= $objPkColumn->PropertyName ?>', '<?= $objPkColumn->DbType ?>', $this);
<?php } ?>
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