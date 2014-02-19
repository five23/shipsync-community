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
 * IllApps_Shipsync_Block_Adminhtml_Sales_Order_Shipment_Packages_View_Items
 */
class IllApps_Shipsync_Block_Adminhtml_Sales_Order_Shipment_Packages_View_Items extends Mage_Adminhtml_Block_Sales_Order_Abstract
{
    public function getShipmentId()
    {
        return $this->getModel()->getShipmentId();
    }

    public function getActivePackage()
    {
        return $this->getModel()->getActivePackage();
    }

    public function getActiveItems()
    {
        return $this->collectItems();
    }

    public function collectItems()
    {
        return $this->getModel()->collectItems()->getAllItems();
    }

    public function getModel()
    {
        return Mage::getSingleton('shipsync/sales_order_shipment_package');
    }
}