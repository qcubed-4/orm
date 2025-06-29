<?php
require_once(__DIR__ . '/qcubed.inc.php');

use QCubed\Project\Codegen\CodegenBase;
use QCubed\QString;

//$__CONFIG_ONLY__ = true;

    const QCUBED_CODE_GENERATING = true;

    if (!defined('QCUBED_URL_PREFIX')) {
        echo "Cannot find the configuration file. Make sure your qcubed.inc.php file is installed correctly."; exit;
    }

    if (QCUBED_URL_PREFIX == '{ url_prefix }') {
        // the config file has not been set up correctly,
        // what should it be?
        $uri = $_SERVER['REQUEST_URI'];
        $offset = strrpos ($uri, '/vendor');
        echo "Your config file is not set up correctly. In particular, look in the project/includes/configuration/active/config.cfg.php file and change the '{ url_prefix }' to '";
        echo substr($uri, 0, $offset);
        echo "'";
        exit;
    }

    $strOrmPath = dirname(__DIR__);
    $strQCubedPath = dirname($strOrmPath);
    $loader = require(dirname($strQCubedPath) . '/autoload.php'); // Add the Composer autoloader if using Composer
    $loader->addPsr4('QCubed\\', $strOrmPath . '/../common/src');
    $loader->addPsr4('QCubed\\', $strOrmPath . '/src');

    // Load in the Project classes
    $loader->addPsr4('QCubed\\Project\\', QCUBED_PROJECT_DIR . '/qcubed'); // make sure user side codegen is included


    /////////////////////////////////////////////////////
    // Run CodeGen, using the ./codegen_settings.xml file
    /////////////////////////////////////////////////////

    CodegenBase::run(QCUBED_CONFIG_DIR . '/codegen_settings.xml');

/**
 * Converts a given text into a monospaced format by escaping special characters
 * and replacing spaces, tabs, and newline characters with their HTML equivalents.
 *
 * @param string $strText The input text to be converted into a monospaced format.
 * @return void
 */
function displayMonospacedText(string $strText): void
    {
        $strText = QString::htmlEntities($strText);
        $strText = str_replace('	', '    ', $strText);
        $strText = str_replace(' ', '&nbsp;', $strText);
        $strText = str_replace("\r", '', $strText);
        $strText = str_replace("\n", '<br/>', $strText);

        echo($strText);
    }

    $strPageTitle = "QCubed Development Framework - Code Generator";
    ?>
    <h1>Code Generator</h1>
    <div class="headerLine"><span><strong>PHP Version:</strong> <?php echo(PHP_VERSION); ?>;&nbsp;&nbsp;<strong>Zend Engine Version:</strong> <?php echo(zend_version()); ?>
            ;&nbsp;<strong>QCubed Version:</strong> <?php echo(QCUBED_VERSION); ?></span></div>

    <div class="headerLine"><span><?php if (array_key_exists('OS', $_SERVER)) {
                printf('<strong>Operating System:</strong> %s;&nbsp;&nbsp;', $_SERVER['OS']);
            } ?><strong>Application:</strong> <?php echo($_SERVER['SERVER_SOFTWARE']); ?>
            ;&nbsp;&nbsp;<strong>Server Name:</strong> <?php echo($_SERVER['SERVER_NAME']); ?></span></div>

    <div class="headerLine"><span><strong>Code Generated:</strong> <?php echo(date('l, F j Y, g:i:s A')); ?></span></div>

    <?php if (CodegenBase::$TemplatePaths) { ?>
        <div>
            <p><strong>Template Paths</strong></p>
            <pre><code><?php DisplayMonospacedText(implode("\r\n", CodegenBase::$TemplatePaths)); ?></code></pre>
        </div>
    <?php } ?>

    <div>
        <?php if ($strErrors = CodegenBase::$RootErrors) { ?>
            <p><strong>The following root errors were reported:</strong></p>
            <pre><code><?php DisplayMonospacedText($strErrors); ?></code></pre>
        <?php } else { ?>
            <p><strong>CodeGen Settings (as evaluated from <?php echo(CodegenBase::$SettingsFilePath); ?>):</strong></p>
            <pre><code><?php DisplayMonospacedText(CodegenBase::getSettingsXml()); ?></code></pre>
        <?php } ?>

        <?php foreach (CodegenBase::$CodeGenArray as $objCodeGen) { ?>
            <p><strong><?= QString::htmlEntities($objCodeGen->getTitle()); ?></strong></p>
            <pre><code><p class="code_title"><?php QString::htmlEntities($objCodeGen->getReportLabel()); ?></p><?php
                        DisplayMonospacedText($objCodeGen->generateAll());
                    ?>
                    <?php if ($strErrors = $objCodeGen->Errors) { ?>
                        <p class="code_title">The following errors were reported:</p>
                        <?php DisplayMonospacedText($objCodeGen->Errors); ?>
                    <?php } ?>
                    <?php if ($strWarnings = $objCodeGen->Warnings) { ?>
                        <p class="code_title">The following warnings were reported:</p>

                        <?php DisplayMonospacedText($objCodeGen->Warnings); ?>
                    <?php } ?>
                </code></pre>
        <?php } ?>

        <?php
        if (!$strErrors) foreach (CodegenBase::generateAggregate() as $strMessage) { ?>
            <p><strong><?php QString::htmlEntities($strMessage); ?></strong></p>
        <?php } ?>
    </div>