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
 * IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Package
 */
class IllApps_Shipsync_Model_Sales_Order_Shipment_Package extends Varien_Object
{
    public function collectItems()
    {
        $jsonItems = $this->getActivePackage()->getPackageItems();
        
        foreach(json_decode($jsonItems, true) as $data)
        {
            $_items[] = Mage::getModel('shipsync/sales_order_shipment_package_item')->collectItemAttributes($data);
        }
        
        $this->setAllItems($_items);
        return $this;
    }
}