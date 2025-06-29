    /**
    * Load an array of <?= $objManyToManyReference->VariableType ?> objects for a given <?= $objManyToManyReference->ObjectDescription ?> via the <?= $objManyToManyReference->Table ?> table
    *
    * @param int $<?= $objManyToManyReference->OppositeVariableName ?> The identifier used for filtering, retrieving, or relating data.
    * @param iClause[]|null $objClauses Optional additional iClause objects for customizing the query
    * @return <?= $objTable->ClassName ?>[] An array of objects matching the specified criteria
    * @throws Caller
    * @throws InvalidCast
    */
    public static function loadArrayBy<?= $objManyToManyReference->ObjectDescription ?>(int $<?= $objManyToManyReference->OppositeVariableName ?>, ?array $objClauses = null): array
    {
        // Call <?= $objTable->ClassName ?>::queryArray to perform the loadArrayBy<?= $objManyToManyReference->ObjectDescription ?> query
        try {
            return <?= $objTable->ClassName; ?>::queryArray(
                QQ::Equal(QQN::<?= $objTable->ClassName ?>()-><?= $objManyToManyReference->ObjectDescription ?>-><?= $objManyToManyReference->OppositePropertyName ?>, $<?= $objManyToManyReference->OppositeVariableName ?>),
                $objClauses
            );
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }
    }

    /**
    * Count <?= $objTable->ClassNamePlural ?> for a given <?= $objManyToManyReference->ObjectDescription ?> via the <?= $objManyToManyReference->Table ?> table
    *
    * @param int $<?= $objManyToManyReference->OppositeVariableName ?> The identifier used to filter or relate data.
    * @return int The number of matching records
    * @throws Caller
    * @throws InvalidCast
    */
    public static function countBy<?= $objManyToManyReference->ObjectDescription ?>(int $<?= $objManyToManyReference->OppositeVariableName ?>): int
    {
        return <?= $objTable->ClassName ?>::QueryCount(
            QQ::Equal(QQN::<?= $objTable->ClassName ?>()-><?= $objManyToManyReference->ObjectDescription ?>-><?= $objManyToManyReference->OppositePropertyName ?>, $<?= $objManyToManyReference->OppositeVariableName ?>)
        );
    }
