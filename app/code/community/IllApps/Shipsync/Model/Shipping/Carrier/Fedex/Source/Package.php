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
 * IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Source_Package
 */
class IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Source_Package
{

    
    /**
     * toOptionArray
     * 
     * @return array
     */
    public function toOptionArray()
    {
	$arr = array();

	$fedexPackages = Mage::getModel('usa/shipping_carrier_fedex_package')->getFedexPackages();
	
	foreach ($fedexPackages as $key => $value)
	{
            $arr[] = array('value' => $key, 'label' => $value['label']);
        }

        return $arr;
    }    
    
}