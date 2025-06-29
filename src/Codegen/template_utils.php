<?php
    /**
     * Removes a specified number of characters from the end of the current output buffer and restores the modified content.
     *
     * @param integer $intNumChars The number of characters to remove from the end of the output buffer.
     * @return void
     */

function GO_BACK(int $intNumChars): void
{
	$content_so_far = ob_get_contents();
	ob_end_clean();
	$content_so_far = substr($content_so_far, 0, strlen($content_so_far) - $intNumChars);
	ob_start();
	print $content_so_far;
}

/**
 * For indenting generated code.
 *
 * @param string $strText
 * @param integer $intCount	The number of indents to add
 * @return string
 */
function _indent_(string $strText, int $intCount = 1): string
{
	$strRepeat = '    ';
	$strTabs = str_repeat($strRepeat, $intCount);
    return preg_replace ( '/^/m', $strTabs , $strText);
}