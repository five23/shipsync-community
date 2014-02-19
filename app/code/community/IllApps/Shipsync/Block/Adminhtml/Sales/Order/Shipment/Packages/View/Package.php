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
 * IllApps_Shipsync_Block_Adminhtml_Sales_Order_Shipment_Packages_View_Package
 */
class IllApps_Shipsync_Block_Adminhtml_Sales_Order_Shipment_Packages_View_Package extends Mage_Adminhtml_Block_Sales_Order_Abstract
{
    public function __construct()
    {}

    public function getShipmentId()
    {
        return $this->getModel()->getShipmentId();
    }

    public function getShippingPackages()
    {
        return Mage::getModel('shipping/shipment_package')->getCollection()->getItemsByColumnValue('order_shipment_id', $this->getShipmentId());
    }

    public function getActivePackage()
    {
        return $this->getModel()->getActivePackage();
    }
    
    public function getModel()
    {
        return Mage::getSingleton('shipsync/sales_order_shipment_package');
    }

    public function getShippingLabelUrl($package_id)
    {
        return $this->getUrl('shipsync/index/label/', array('id' => $package_id));
    }

    public function getCodLabelUrl($package_id)
    {
        return $this->getUrl('shipsync/index/codlabel/', array('id' => $package_id));
    }
}