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
 * IllApps_Shipsync_Model_Shipment_Abstract
 */
class IllApps_Shipsync_Model_Shipment_Abstract extends Varien_Object
{
    public function getFedexAccount()
    {
		if (Mage::getStoreConfig('carriers/fedex/test_mode')) {
        	return Mage::getStoreConfig('carriers/fedex/test_account');
		} 
		else {
			return Mage::getStoreConfig('carriers/fedex/account');
		}
    }

    public function getFedexAccountCountry()
    {
        return Mage::getStoreConfig('carriers/fedex/account_country');
    }
}