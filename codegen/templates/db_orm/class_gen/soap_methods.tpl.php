
    ///////////////////////////////////////
    // METHODS for SOAP-BASED WEB SERVICES
    ///////////////////////////////////////

    /**
    * Generate the SOAP complex type XML definition for a <?= $objTable->ClassName ?> object
    *
    * @return string A string containing the XML representation of the SOAP complex type for a <?= $objTable->ClassName ?> object
    */
    public static function getSoapComplexTypeXml(): string
    {
        $strToReturn = '<complexType name="<?= $objTable->ClassName ?>"><sequence>';
<?php foreach ($objTable->ColumnArray as $objColumn) { ?>
<?php if (!$objColumn->Reference || $objColumn->Reference->IsType) { ?>
        $strToReturn .= '<element name="<?= $objColumn->PropertyName ?>" type="xsd:<?= \QCubed\Type::SoapType($objColumn->VariableType) ?>"/>';
<?php } ?><?php if ($objColumn->Reference && (!$objColumn->Reference->IsType)) { ?>
        $strToReturn .= '<element name="<?= $objColumn->Reference->PropertyName ?>" type="xsd1:<?= $objColumn->Reference->VariableType ?>"/>';
<?php } ?>
<?php } ?>
        $strToReturn .= '<element name="__blnRestored" type="xsd:boolean"/>';
        $strToReturn .= '</sequence></complexType>';
        return $strToReturn;
    }

    /**
    * Modifies the SOAP complex type array to include the definition for the <?= $objTable->ClassName ?> type
    * If the <?= $objTable->ClassName ?> type is not already defined, it will be added along with any relevant nested types
    *
    * @param array $strComplexTypeArray The SOAP complex type array to be altered, passed by reference
    * @return void
    */
    public static function alterSoapComplexTypeArray(array &$strComplexTypeArray): void
    {
        if (!array_key_exists('<?= $objTable->ClassName ?>', $strComplexTypeArray)) {
            $strComplexTypeArray['<?= $objTable->ClassName ?>'] = <?= $objTable->ClassName ?>::getSoapComplexTypeXml();
<?php foreach ($objTable->ColumnArray as $objColumn) { ?>
<?php if ($objColumn->Reference && (!$objColumn->Reference->IsType)) { ?>
            <?= $objColumn->Reference->VariableType ?>::AlterSoapComplexTypeArray($strComplexTypeArray);
<?php } ?>
<?php } ?>
        }
    }

    /**
    * Converts a SOAP array of objects to an array of <?= $objTable->ClassName ?> objects
    *
    * @param array $objSoapArray The SOAP array to be converted
    * @return <?= $objTable->ClassName ?>[] An array of <?= $objTable->ClassName ?> objects created from the SOAP array
    */
    public static function getArrayFromSoapArray(array $objSoapArray): array
    {
        $objArrayToReturn = array();

        foreach ($objSoapArray as $objSoapObject)
            $objArrayToReturn[] = <?= $objTable->ClassName ?>::getObjectFromSoapObject($objSoapObject);

        return $objArrayToReturn;
    }

    /**
    * Converts a SOAP object into a <?= $objTable->ClassName ?> object by mapping properties
    * @param object $objSoapObject The SOAP object to be converted
    * @return <?= $objTable->ClassName ?> The resulting <?= $objTable->ClassName ?> object populated with properties from the SOAP object
    */
    public static function getObjectFromSoapObject(object $objSoapObject): <?= $objTable->ClassName ?>

    {
        $objToReturn = new <?= $objTable->ClassName ?>();
<?php foreach ($objTable->ColumnArray as $objColumn) { ?>
<?php if (!$objColumn->Reference || $objColumn->Reference->IsType) { ?>
        if (property_exists($objSoapObject, '<?= $objColumn->PropertyName ?>'))
<?php if ($objColumn->VariableType != \QCubed\Type::DATE_TIME) { ?>
            $objToReturn-><?= $objColumn->VariableName ?> = $objSoapObject-><?= $objColumn->PropertyName ?>;
<?php } ?><?php if ($objColumn->VariableType == \QCubed\Type::DATE_TIME) { ?>
            $objToReturn-><?= $objColumn->VariableName ?> = new QDateTime($objSoapObject-><?= $objColumn->PropertyName ?>);
<?php } ?>
<?php } ?><?php if ($objColumn->Reference && (!$objColumn->Reference->IsType)) { ?>
        if ((property_exists($objSoapObject, '<?= $objColumn->Reference->PropertyName ?>')) &&
            ($objSoapObject-><?= $objColumn->Reference->PropertyName ?>))
            $objToReturn-><?= $objColumn->Reference->PropertyName ?> = <?= $objColumn->Reference->VariableType ?>::GetObjectFromSoapObject($objSoapObject-><?= $objColumn->Reference->PropertyName ?>);
<?php } ?>
<?php } ?>
        if (property_exists($objSoapObject, '__blnRestored'))
            $objToReturn->__blnRestored = $objSoapObject->__blnRestored;
        return $objToReturn;
    }
    /**
    * Converts an array of objects into a serialized SOAP-compatible array
    * @param array|null $objArray an array of objects to be converted, or null
    * @return array|null a SOAP-compatible array or null if the input array is empty
    */
    public static function getSoapArrayFromArray(?array $objArray): ?array
    {
        if (!$objArray)
            return null;

        $objArrayToReturn = array();

        foreach ($objArray as $objObject)
            $objArrayToReturn[] = <?= $objTable->ClassName ?>::GetSoapObjectFromObject($objObject, true);

        return unserialize(serialize($objArrayToReturn));
    }

    /**
    * Converts a given object into a SOAP-compatible object
    * Updates related objects and dates to ensure compliance with SOAP data structures
    *
    * @param mixed $objObject The object to be converted to a SOAP-compatible object
    * @param bool $blnBindRelatedObjects Determines whether related objects should be bound or their IDs set to null
    * @return mixed The SOAP-compatible version of the provided object
    */
    public static function getSoapObjectFromObject(mixed $objObject, bool $blnBindRelatedObjects): mixed
    {
<?php foreach ($objTable->ColumnArray as $objColumn) { ?>
<?php if ($objColumn->VariableType == \QCubed\Type::DATE_TIME) { ?>
        if ($objObject-><?= $objColumn->VariableName ?>)
            $objObject-><?= $objColumn->VariableName ?> = $objObject-><?= $objColumn->VariableName ?>->qFormat(QDateTime::FORMAT_SOAP);
<?php } ?><?php if ($objColumn->Reference && (!$objColumn->Reference->IsType)) { ?>
        if ($objObject-><?= $objColumn->Reference->VariableName ?>)
            $objObject-><?= $objColumn->Reference->VariableName ?> = <?= $objColumn->Reference->VariableType ?>::getSoapObjectFromObject($objObject-><?= $objColumn->Reference->VariableName ?>, false);
        else if (!$blnBindRelatedObjects)
            $objObject-><?= $objColumn->VariableName ?> = null;
<?php } ?>
<?php } ?>
        return $objObject;
    }