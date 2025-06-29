<?php
use QCubed\Codegen\CodegenBase;
use QCubed\Codegen\SqlTable;

/** @var SqlTable $objTable */

/** @var CodegenBase $objCodeGen */
?>
<?php $objColumnArray = $objCodeGen->GetColumnArray($objTable, $objIndex->ColumnNameArray); ?>
    /**
    * Load an array of <?= $objTable->ClassName ?> objects by <?= $objCodeGen->ImplodeObjectArray(', ', '', '', 'PropertyName', $objCodeGen->GetColumnArray($objTable, $objIndex->ColumnNameArray)) ?> Index(es)
<?php foreach ($objColumnArray as $objColumn) {
    // Override type specification:
    $displayType = $objColumn->VariableType;
    if ($displayType === 'integer') {
        $displayType = 'int';
    }
?>
    *
<?php } ?>
<?php foreach ($objColumnArray as $objColumn) { ?>
<?php if ($objColumn->Reference) { ?>
    * @param <?= $displayType ?? $objColumn->VariableType ?> $<?= $objColumn->VariableName ?> the ID of the <?= $objColumn->Reference->PropertyName ?> to filter by
<?php } else { ?>
    * @param <?= $objColumn->VariableType ?> <?= $objCodeGen->ParameterListFromColumnArray($objColumnArray); ?> The <?= $objCodeGen->ImplodeObjectArray(', ', '', '', 'PropertyName', $objCodeGen->GetColumnArray($objTable, $objIndex->ColumnNameArray)) ?> to filter <?= $objTable->ClassName ?> objects by.
<?php } ?>
<?php } ?>
    * @param iClause[]|null $objOptionalClauses additional optional iClause objects for this query
<?php if ($objColumn->VariableType == 'integer') { ?>
    * @return <?= $objTable->ClassName ?>[] an array of <?= $objTable->ClassName ?> objects that match the given type ID
<?php } else { ?>
    * @return <?= $objTable->ClassName ?>[] an array of <?= $objTable->ClassName ?> objects that match the specified <?= $objCodeGen->ImplodeObjectArray(', ', '', '', 'PropertyName', $objCodeGen->GetColumnArray($objTable, $objIndex->ColumnNameArray)) ?>.
<?php } ?>
    * @throws Caller
    * @throws InvalidCast
    */
    public static function loadArrayBy<?= $objCodeGen->ImplodeObjectArray('', '', '', 'PropertyName', $objColumnArray); ?>(<?= $displayType ?? $objColumn->VariableType ?> <?= $objCodeGen->ParameterListFromColumnArray($objColumnArray); ?>, ?array $objOptionalClauses = null): array
    {
        // Call <?= $objTable->ClassName ?>::queryArray to perform the loadArrayBy<?= $objCodeGen->ImplodeObjectArray('', '', '', 'PropertyName', $objColumnArray); ?> query
        try {
            return <?= $objTable->ClassName; ?>::queryArray(
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
    * Counts the number of <?= $objTable->ClassName ?> objects associated with a specific <?= $objCodeGen->ImplodeObjectArray(', ', '', '', 'PropertyName', $objCodeGen->GetColumnArray($objTable, $objIndex->ColumnNameArray)) ?>
<?php foreach ($objColumnArray as $objColumn) {
    // Override type specification:
    $displayType = $objColumn->VariableType;
    if ($displayType === 'integer') {
        $displayType = 'int';
    }
?>

<?php } ?>
    *
<?php foreach ($objColumnArray as $objColumn) { ?>
<?php if ($objColumn->Reference) { ?>
    * @param <?= $displayType ?? $objColumn->VariableType ?> <?= $objCodeGen->ParameterListFromColumnArray($objColumnArray) ?> The ID of the <?= $objColumn->Reference->PropertyName ?> for which to count associated <?= strtolower($objTable->ClassNamePlural) ?>
<?php } else { ?>
    * @param <?= $objColumn->VariableType ?> <?= $objCodeGen->ParameterListFromColumnArray($objColumnArray); ?> The <?= $objCodeGen->ImplodeObjectArray(', ', '', '', 'PropertyName', $objCodeGen->GetColumnArray($objTable, $objIndex->ColumnNameArray)) ?> to filter <?= $objTable->ClassName ?> objects by
<?php } ?>
<?php } ?>

    * @return int The count of <?= strtolower($objTable->ClassNamePlural) ?> associated with the given <?= $objCodeGen->ImplodeObjectArray(', ', '', '', 'PropertyName', $objCodeGen->GetColumnArray($objTable, $objIndex->ColumnNameArray)) ?>

    * @throws Caller
    * @throws InvalidCast
    */
    public static function countBy<?= $objCodeGen->ImplodeObjectArray('', '', '', 'PropertyName', $objColumnArray); ?>(<?= $displayType ?? $objColumn->VariableType ?> <?= $objCodeGen->ParameterListFromColumnArray($objColumnArray); ?>): int
    {
        // Call <?= $objTable->ClassName ?>::queryCount to perform the countBy<?= $objCodeGen->ImplodeObjectArray('', '', '', 'PropertyName', $objColumnArray); ?> query
        return <?= $objTable->ClassName ?>::queryCount(
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