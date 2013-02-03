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
 * IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Source_Unit
 */
class IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Source_Origins
{

    /**
     * toOptionArray
     *
     * @return array
     */
    public function toOptionArray()
    {
	return array
	(
	    array('value' => 'default', 'label' => Mage::helper('usa')->__('Default')),
	    array('value' => 'origin_1', 'label' => Mage::helper('usa')->__('Origin 1')),
	    array('value' => 'origin_2', 'label' => Mage::helper('usa')->__('Origin 1'))
	);
    }

}