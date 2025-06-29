
    /**
    * Retrieve the value of a virtual attribute by its name.
    *
    * @param string $strName The name of the virtual attribute to retrieve.
    * @return string|null The value of the virtual attribute if it exists, or null if it does not.
    */
    public function getVirtualAttribute(string $strName): ?string
    {
        $strName = QQ::GetVirtualAlias($strName);
        if (isset($this->__strVirtualAttributeArray[$strName])) {
            return $this->__strVirtualAttributeArray[$strName];
        }
        return null;
    }

    /**
    * Checks if a virtual attribute exists for the given name.
    *
    * @param string $strName The name of the virtual attribute to check.
    * @return bool True if the virtual attribute exists, otherwise false.
    */
    public function hasVirtualAttribute(string $strName): bool
    {
        $strName = QQ::GetVirtualAlias($strName);
        if (array_key_exists($strName, $this->__strVirtualAttributeArray)) {
            return true;
        }
        return false;
    }