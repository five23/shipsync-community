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
 * IllApps_Shipsync_Block_Adminhtml_Sales_Order_Shipment_Packages_View_Form
 */
class IllApps_Shipsync_Block_Adminhtml_Sales_Order_Shipment_Packages_View_Form extends Mage_Adminhtml_Block_Sales_Order_Abstract
{

    public function __construct() {}

    public function getShipmentId()
    {
        return $this->getModel()->getShipmentId();
    }
    
    public function getShippingPackages()
    {
        return Mage::getModel('shipping/shipment_package')->getCollection()->getItemsByColumnValue('order_shipment_id', $this->getShipmentId());
    }

    public function setActivePackage($package)
    {
        $this->getModel()->setActivePackage($package);
        
		return $this;
    }

    public function getActivePackage()
    {
        return $this->getModel()->getActivePackage();
    }

    public function getModel()
    {
        return Mage::getSingleton('shipsync/sales_order_shipment_package');
    }
}
