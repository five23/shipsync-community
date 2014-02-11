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
 * IllApps_Shipsync_Block_Adminhtml_Shipsync
 */
class IllApps_Shipsync_Block_Adminhtml_Shipsync extends Mage_Adminhtml_Block_Widget
{
    
    /**
     * __construct
     */
    public function __construct()
    {
        parent::__construct();
        
        $order_id = $this->getRequest()->getParam('order_id'); // Get order id
        
        $shippingPackage = Mage::getModel('shipsync/shipping_package'); // Get shipping package model
        
        $this->setOrder(Mage::getModel('sales/order')->load($order_id)); // Set order model        
        $this->setOrderUrl($this->getUrl('adminhtml/sales_order/view', array(
            'order_id' => $order_id
        ))); // Set order url
        $this->setOrderAdminDate($this->formatDate($this->getOrder()->getCreatedAtDate(), 'medium', true)); // Set order admin date
        $this->setOrderStoreDate($this->formatDate($this->getOrder()->getCreatedAtStoreDate(), 'medium', true)); // Set order store date
        $this->setOrderTimezone($this->getOrder()->getCreatedAtStoreDate()->getTimezone()); // Set order timezone
        
        $this->setShippingMethod(explode('_', $this->getOrder()->getShippingMethod())); // Set shipping method        	        
        $this->setDefaultPackages($shippingPackage->getDefaultPackages(array(
            $this->getShippingMethod(0)
        ))); // Set default packages
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
        
        $this->setFormKey(Mage::getSingleton('core/session')->getFormKey()); // Set form key		
        
        if ($this->getOrder()->getEmailSent()) // Check if order email is sent
            {
            $this->setEmailSentMsg(Mage::helper('sales')->__('the order confirmation email was sent'));
        } // Set 'sent' message
        else {
            $this->setEmailSentMsg(Mage::helper('sales')->__('the order confirmation email is not sent'));
        } // Set 'not sent' message
    }
    
}