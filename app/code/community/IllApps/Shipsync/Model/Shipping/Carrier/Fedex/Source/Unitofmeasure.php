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
 * IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Source_Unitofmeasure
 */
class IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Source_Unitofmeasure
{
    
    
    /**
     * toOptionArray
     *
     * @return array
     */
    
    public function toOptionArray()
    {
        return array(
            'LB' => Mage::helper('usa')->__('Pounds'),
            'KG' => Mage::helper('usa')->__('Kilograms')
        );
    }
    
}