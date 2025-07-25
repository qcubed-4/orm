    // Override or Create New load/count methods
    // (For obvious reasons, these methods are commented out...
    // But feel free to use these as a starting point)
/*

    public static function loadArrayBySample($strParam1, $intParam2, $objOptionalClauses = null) {
        // This will return an array of <?= $objTable->ClassName ?> objects
        return <?= $objTable->ClassName ?>::queryArray(
            QQ::AndCondition(
                QQ::Equal(QQN::<?= $objTable->ClassName ?>()->Param1, $strParam1),
                QQ::GreaterThan(QQN::<?= $objTable->ClassName ?>()->Param2, $intParam2)
            ),
            $objOptionalClauses
        );
    }


    public static function loadBySample($strParam1, $intParam2, $objOptionalClauses = null) {
        // This will return a single <?= $objTable->ClassName ?> object
        return <?= $objTable->ClassName ?>::querySingle(
            QQ::AndCondition(
                QQ::Equal(QQN::<?= $objTable->ClassName ?>()->Param1, $strParam1),
                QQ::GreaterThan(QQN::<?= $objTable->ClassName ?>()->Param2, $intParam2)
            ),
            $objOptionalClauses
        );
    }


    public static function countBySample($strParam1, $intParam2, $objOptionalClauses = null) {
        // This will return a count of <?= $objTable->ClassName ?> objects
        return <?= $objTable->ClassName ?>::queryCount(
            QQ::AndCondition(
                QQ::Equal(QQN::<?= $objTable->ClassName ?>()->Param1, $strParam1),
                QQ::Equal(QQN::<?= $objTable->ClassName ?>()->Param2, $intParam2)
            ),
            $objOptionalClauses
        );
    }


    public static function loadArrayBySample($strParam1, $intParam2, $objOptionalClauses) {
        // Performing the load manually (instead of using QCubed Query)

        // Get the Database Object for this Class
        $objDatabase = <?= $objTable->ClassName ?>::getDatabase();

        // Properly Escape All Input Parameters using Database->SqlVariable()
        $strParam1 = $objDatabase->SqlVariable($strParam1);
        $intParam2 = $objDatabase->SqlVariable($intParam2);

        // Setup the SQL Query
        $strQuery = sprintf('
            SELECT
                <?= $strEscapeIdentifierBegin ?><?= $objTable->Name ?><?= $strEscapeIdentifierEnd ?>.*
            FROM
                <?= $strEscapeIdentifierBegin ?><?= $objTable->Name ?><?= $strEscapeIdentifierEnd ?> AS <?= $strEscapeIdentifierBegin ?><?= $objTable->Name ?><?= $strEscapeIdentifierEnd ?>

            WHERE
                param_1 = %s AND
                param_2 < %s',
            $strParam1, $intParam2);

        // Perform the Query and Instantiate the Result
        $objDbResult = $objDatabase->Query($strQuery);
        return <?= $objTable->ClassName ?>::instantiateDbResult($objDbResult);
    }
*/
