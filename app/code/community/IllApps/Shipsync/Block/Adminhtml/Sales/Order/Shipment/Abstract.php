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
 * IllApps_Shipsync_Block_Adminhtml_Sales_Order_Shipment_Abstract
 */
class IllApps_Shipsync_Block_Adminhtml_Sales_Order_Shipment_Abstract extends Mage_Adminhtml_Block_Sales_Order_Abstract
{
	/**
	 * Get Shipment Id
	 *
  	 * @return integer
  	 */
    public function getShipmentId()
    {
        return $this->getModel()->getShipmentId();
    }

	/**
	 * Get Shipping Packages
	 */
    public function getShippingPackages()
    {
        return Mage::getModel('shipping/shipment_package')
			->getCollection()->getItemsByColumnValue('order_shipment_id', $this->getShipmentId());
    }

	/**
	 * Set Active Package
	 */
    public function setActivePackage($package)
    {
        $this->getModel()->setActivePackage($package);

		return $this;
    }

	/**
	 * Get Active Package
	 */
    public function getActivePackage()
    {
        return $this->getModel()->getActivePackage();
    }

	/**
	 * Get Active Items
	 */
    public function getActiveItems()
    {
        return $this->collectItems();
    }

	/**
	 * Collect Items
	 */
    public function collectItems()
    {
        return $this->getModel()->collectItems()->getAllItems();
    }

	/**
	 * Get Shipping Label Url
	 *
	 * @param integer $package_id
	 */
    public function getShippingLabelUrl($package_id)
    {
        return $this->getUrl('shipsync/index/label/', array('id' => $package_id));
    }

	/**
	 * Get Cod Label Url
	 *
	 * @param integer @package_id
	 */
    public function getCodLabelUrl($package_id)
    {
        return $this->getUrl('shipsync/index/codlabel/', array('id' => $package_id));
    }

	/**
	 * Get Model
	 *
	 * @return IllApps_Shipsync_Model_Sales_Order_Shipment_Package
	 */
    public function getModel()
    {
        return Mage::getSingleton('shipsync/sales_order_shipment_package');
    }

}
