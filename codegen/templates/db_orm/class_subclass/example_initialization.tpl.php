<?php
$blnAutoInitialize = $objCodeGen->AutoInitialize;
if (!$blnAutoInitialize) {
?>
/*
    // Initialize each property with default values from the database definition
    public function __construct()
    {
        $this->Initialize();
    }
*/

/*
    public function initialize()
    {
        parent::Initialize();
        // You additional initializations here
    }
*/

<?php
} ?>