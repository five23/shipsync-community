<?php

/**
 * ShipSync
 *
 * @category   IllApps
 * @package    IllApps_Shipsync
 * @author     David Kirby (d@kernelhack.com) / Jonathan Cantrell (j@kernelhack.com)
 * @copyright  Copyright (c) 2014 EcoMATICS, Inc. DBA IllApps (http://www.illapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * IllApps_Shipsync_Helper_Data
 */
class IllApps_Shipsync_Helper_Data extends Mage_Core_Helper_Abstract
{
	
    public static function mageLog($var, $name)
    {
        if (Mage::getStoreConfig('carriers/fedex/mage_log')) {
            Mage::log($var, null, 'shipsync_' . $name . '.log');
        }
    }
}