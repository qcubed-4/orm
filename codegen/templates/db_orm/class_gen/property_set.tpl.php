
    /**
    * Override method to perform a property "Set"
    * This will set the property $strName to be $mixValue
    *
    * @param string $strName Name of the property to set
    * @param string $mixValue New value of the property
    * @return void
    * @throws Caller
    */
    public function __set(string $strName, mixed $mixValue): void
    {
        try {
            // Use a setter if it exists
            $strMethod = 'set' . $strName;
            if (method_exists($this, $strMethod)) {
                $this->$strMethod($mixValue);
            } else {
                parent::__set($strName, $mixValue);
            }
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }
    }