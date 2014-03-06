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
 * IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Source_Freemethod
 */
class IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Source_Freemethod
    extends IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Source_Method
{
    public function toOptionArray()
    {
        $arr = parent::toOptionArray();
        array_unshift($arr, array('value' => '', 'label' => Mage::helper('shipping')->__('None')));
        return $arr;
    }
}
