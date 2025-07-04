<?php
/**
 * QCodeGen
 *
 * Overrides the Codegen\AbstractBase class.
 *
 * Feel free to override any of those methods here to customize your code generation.
 *
 */

namespace QCubed\Project\Codegen;

/**
 * Class Codegen
 *
 * Overrides the default codegen class. Override and implement any functions here to customize the code generation process.
 * @package Project
 * @was QCodeGen
 */
class CodegenBase extends \QCubed\Codegen\CodegenBase
{

    /**
     * Constructor method.
     *
     * Initializes the template paths using the settings XML provided.
     *
     * @param mixed $objSettingsXml The settings XML object used for initialization.
     * @return void
     */
    public function __construct(mixed $objSettingsXml)
    {
        static::$TemplatePaths = $this->getInstalledTemplatePaths();
    }

    /**
     * Calls the super class, then inserts our own paths to our templates.
     */
    public function getInstalledTemplatePaths(): array
    {
        $paths = parent::getInstalledTemplatePaths();

        // Add the paths to your custom template files here. These paths will be searched in the order declared, to
        // find a particular template file. Template files found lower down in the order will override the previous ones.
        $paths[] = QCUBED_PROJECT_DIR . '/codegen/templates/';
        return $paths;
    }

    /**
     * QCodeGen::pluralize()
     *
     * Example: Overriding the Pluralize method
     *
     * @param string $strName
     * @return string
     */
    protected function pluralize(string $strName): string
    {
        // Special Rules go Here
        switch (true) {
            case ($strName == 'person'):
                return 'people';
            case ($strName == 'Person'):
                return 'People';
            case ($strName == 'PERSON'):
                return 'PEOPLE';

            // Trying to be cute here...
            case (strtolower($strName) == 'fish'):
                return $strName . 'ies';

            // Otherwise, call parent
            default:
                return parent::pluralize($strName);
        }
    }
}
