<?php

/**
 * ShipSync
 *
 * @category   IllApps
 * @package    IllApps_Shipsync
 * @copyright  Copyright (c) 2014 EcoMATICS, Inc. DBA IllApps (http://www.illapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Source_Label_Orientation
 */
class IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Source_Label_Orientation
{
	
    /**
     * toOptionArray
     *
     * @return array
     */
    public function toOptionArray()
    {
        $fedex = Mage::getSingleton('usa/shipping_carrier_fedex');
        
		$arr = array();
        
		foreach ($fedex->getCode('label_orientation') as $k => $v) {
            $arr[] = array('value' => $k, 'label' => $v);
        }
		
        return $arr;
    }
    
}
