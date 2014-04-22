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
 * IllApps_Shipsync_Block_Adminhtml_Shipsync
 */
class IllApps_Shipsync_Block_Adminhtml_Shipsync extends Mage_Adminhtml_Block_Widget
{

    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct();
        
        $order_id = $this->getRequest()->getParam('order_id'); // Get order id
        
        $shippingPackage = Mage::getModel('shipsync/shipping_package'); // Get shipping package model
        
        $this->setOrder(Mage::getModel('sales/order')->load($order_id)); // Set order model        
        $this->setOrderUrl($this->getUrl('adminhtml/sales_order/view', array('order_id' => $order_id))); // Set order url
        $this->setOrderAdminDate($this->formatDate($this->getOrder()->getCreatedAtDate(), 'medium', true)); // Set order admin date
        $this->setOrderStoreDate($this->formatDate($this->getOrder()->getCreatedAtStoreDate(), 'medium', true)); // Set order store date
        $this->setOrderTimezone($this->getOrder()->getCreatedAtStoreDate()->getTimezone()); // Set order timezone
        $this->setShippingMethod(explode('_', $this->getOrder()->getShippingMethod())); // Set shipping method
        $this->setDefaultPackages(Mage::getModel('shipsync/shipping_package')->getDefaultPackages(array('fedex')));
        $this->setItems($shippingPackage->getParsedItems($this->getOrder()->getAllItems(), true)); // Set items
        $this->setPackages($shippingPackage->estimatePackages($this->getItems(), $this->getDefaultPackages())); // Set packages
        $this->setCarrier(Mage::getModel('usa/shipping_carrier_fedex')); // Set carrier model
        $this->setCarrierTitle(Mage::getStoreConfig('carriers/fedex/title')); // Set carrier title
        $this->setCarrierCode(strtoupper($this->getShippingMethod(0))); // Set carrier code
        $this->setMethodCode($this->getShippingMethod(1)); // Set method code
        $this->setMethod($this->getCarrier()->getCode('method', $this->getMethodCode())); // Set method	
        $this->setAllowedMethods(explode(",", Mage::getStoreConfig('carriers/fedex/allowed_methods'))); // Set allowed methods	
        $this->setDimensionUnits($this->getCarrier()->getDimensionUnits()); // Set dimension units
        $this->setWeightUnits($this->getCarrier()->getWeightUnits()); // Set weight units	
        $this->setSaturdayDelivery((bool) strpos($this->getOrder()->getShippingDescription(), 'Saturday Delivery')); // Determine if shipment delivers on Saturday
        $this->setAddressValidation(Mage::getStoreConfig('carriers/fedex/shipping_address_validation'));
		$this->setResidenceDelivery(Mage::getStoreConfig('carriers/fedex/shipping_residence_delivery'));
        $this->setFormKey(Mage::getSingleton('core/session')->getFormKey()); // Set form key		
        
		$this->setShipperCompany(Mage::app()->getStore()->getFrontendName());

        if ($this->getOrder()->getEmailSent()) // Check if order email is sent
            {
            $this->setEmailSentMsg(Mage::helper('sales')->__('the order confirmation email was sent'));
        } // Set 'sent' message
        else {
            $this->setEmailSentMsg(Mage::helper('sales')->__('the order confirmation email is not sent'));
        } // Set 'not sent' message

		$this->setLabelImageType(Mage::getStoreConfig('carriers/fedex/label_image'));
		$this->setLabelStockType(Mage::getStoreConfig('carriers/fedex/label_stock'));
		$this->setLabelPrintingOrientation(Mage::getStoreConfig('carriers/fedex/label_orientation'));
		$this->setEnableJavaPrinting(Mage::getStoreConfig('carriers/fedex/enable_java_printing'));
        $this->setPrinterName(Mage::getStoreConfig('carriers/fedex/printer_name'));
		$this->setPackingList(Mage::getStoreConfig('carriers/fedex/packing_list'));
		$this->setSignature(Mage::getStoreConfig('carriers/fedex/signature'));
		$this->setReturnLabel(Mage::getStoreConfig('carriers/fedex/return_label'));
    }
    
}
