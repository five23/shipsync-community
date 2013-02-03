<?php

/**
 * ShipSync
 *
 * @category   IllApps
 * @package    IllApps_Shipsync
 * @author     David Kirby (d@kernelhack.com)
 * @copyright  Copyright (c) 2011 EcoMATICS, Inc. DBA IllApps (http://www.illapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Unit of dimension
 */
class IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Source_Unitofdimension
{
    public function toOptionArray()
    {
        $fedex = Mage::getSingleton('usa/shipping_carrier_fedex');
        $arr = array();
        foreach ($fedex->getCode('unit_of_dimension') as $k=>$v) {
            $arr[] = array('value'=>$k, 'label'=>$v);
        }
        return $arr;
    }
}