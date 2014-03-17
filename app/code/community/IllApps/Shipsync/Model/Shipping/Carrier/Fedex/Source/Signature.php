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
 * IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Source_Signature
 */
class IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Source_Signature
{
    public function toOptionArray()
    {
        $fedex = Mage::getSingleton('usa/shipping_carrier_fedex');
        
		$arr = array();
        
		foreach ($fedex->getCode('signature') as $k => $v) {
            $arr[] = array('value' => $k, 'label' => $v);
        }
		
        return $arr;
    }
}
