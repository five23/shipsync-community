<?php

/**
 * ShipSync
 *
 * @category   IllApps
 * @package    IllApps_Shipsync
 * @author     David Kirby (d@kernelhack.com) / Jonathan Cantrell (j@kernelhack.com)
 * @copyright  Copyright (c) 2011 EcoMATICS, Inc. DBA IllApps (http://www.illapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * IllApps_Shipsync_Block_Firebug
 *
 * @license    Code adapted from Netresearch_Debug (http://www.opensource.org/licenses/osl-3.0.php (OSL 3.0))
 */
class IllApps_Shipsync_Block_Firebug extends Mage_Core_Block_Abstract
{


    /**
     * toHtml
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->_beforeToHtml() || !Mage::getStoreConfig('carriers/fedex/debug_firebug')
		|| !Mage::helper('core')->isDevAllowed() || 0) { return ''; }

	$log = Mage::helper('shipsync')->getLog();

	$output = "";
	foreach ($log as $entry)
	{
	    $entry['message']['stack'] = $entry['stack'];
            $output .= $this->__group($entry['caption'], $entry['message'], false);
        }

        return "<script>\n" . $output. "</script>\n";
    }


    
    /**
     * Get last logged html
     *
     * @return string
     */
    public function getLastLoggedAsHtml()
    {
        $log = Mage::helper('shipsync')->getLog();
	$entry = $log[sizeof($log)-1];
	$output = "";
        $entry['message']['stack'] = $entry['stack'];
        $output .= $this->__group($entry['caption'], $entry['message'], false);

	return "<script>\n" . $output . "</script>\n";
    }


    
    /**
     * __group
     *
     * @param string $caption
     * @param array $content
     * @param bool $collapsed
     * @return string
     */
    private function __group($caption, $content, $collapsed)
    {
	$normalLines = array();

	$return = "console.group" . ($collapsed ? "Collapsed" : "") . "('" . str_replace("'", "\'", $caption) . "');\n";

        foreach ($content as $key => $element)
	{
            if (is_array($element) and count($element)>0)
	    {
                if (count($normalLines)>0)
		{
		    $return .= "console.log('  " . implode("\\n  ", str_replace("'", "\'", $normalLines)) . "');\n";
                }
                $return .= $this->__group($key, $element, false);
                $normalLines = array();
            }
            else { $normalLines[] = $key; }
        }
        if (count($normalLines)>0)
	{
            $return .= "console.log('  " . implode("\\n  ", str_replace("'", "\'", $normalLines)) . "');\n";
        }
        $return .= "console.groupEnd();\n";

        return $return;
    }
}