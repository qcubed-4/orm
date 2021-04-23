<?php $objColumnArray = $objCodeGen->GetColumnArray($objTable, $objIndex->ColumnNameArray); ?>
    /**
     * Load an array of <?= $objTable->ClassName ?> objects,
     * by <?= $objCodeGen->ImplodeObjectArray(', ', '', '', 'PropertyName', $objCodeGen->GetColumnArray($objTable, $objIndex->ColumnNameArray)) ?> Index(es)
<?php foreach ($objColumnArray as $objColumn) { ?>
     * @param <?= $objColumn->VariableType ?> $<?= $objColumn->VariableName ?>

<?php } ?>
     * @param iClause[] $objOptionalClauses additional optional iClause objects for this query
     * @throws Caller
     * @return <?= $objTable->ClassName ?>[]
    */
    public static function loadArrayBy<?= $objCodeGen->ImplodeObjectArray('', '', '', 'PropertyName', $objColumnArray); ?>(<?= $objCodeGen->ParameterListFromColumnArray($objColumnArray); ?>, $objOptionalClauses = null)
    {
        // Call <?= $objTable->ClassName ?>::QueryArray to perform the LoadArrayBy<?= $objCodeGen->ImplodeObjectArray('', '', '', 'PropertyName', $objColumnArray); ?> query
        try {
            return <?= $objTable->ClassName; ?>::QueryArray(
<?php if (count($objColumnArray) > 1) { ?>
                QQ::AndCondition(
<?php } ?>
<?php foreach ($objColumnArray as $objColumn) { ?>
                QQ::Equal(QQN::<?= $objTable->ClassName ?>()-><?= $objColumn->PropertyName ?>, $<?= $objColumn->VariableName ?>),
<?php } ?><?php GO_BACK(2); ?>
<?php if (count($objColumnArray) > 1) { ?>
                )
<?php } ?>,
                $objOptionalClauses);
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }
    }

    /**
     * Count <?= $objTable->ClassNamePlural ?>

     * by <?= $objCodeGen->ImplodeObjectArray(', ', '', '', 'PropertyName', $objCodeGen->GetColumnArray($objTable, $objIndex->ColumnNameArray)) ?> Index(es)
<?php foreach ($objColumnArray as $objColumn) { ?>
     * @param <?= $objColumn->VariableType ?> $<?= $objColumn->VariableName ?>

<?php } ?>
     * @return int
    */
    public static function countBy<?= $objCodeGen->ImplodeObjectArray('', '', '', 'PropertyName', $objColumnArray); ?>(<?= $objCodeGen->ParameterListFromColumnArray($objColumnArray); ?>)
    {
        // Call <?= $objTable->ClassName ?>::QueryCount to perform the CountBy<?= $objCodeGen->ImplodeObjectArray('', '', '', 'PropertyName', $objColumnArray); ?> query
        return <?= $objTable->ClassName ?>::QueryCount(
<?php if (count($objColumnArray) > 1) { ?>
            QQ::AndCondition(
<?php } ?>
<?php foreach ($objColumnArray as $objColumn) { ?>
            QQ::Equal(QQN::<?= $objTable->ClassName ?>()-><?= $objColumn->PropertyName ?>, $<?= $objColumn->VariableName ?>),
<?php } ?><?php GO_BACK(2); ?>
<?php if (count($objColumnArray) > 1) { ?>
            )
<?php } ?>

        );
    }