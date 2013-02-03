<?php
/**
 * ShipmentDate
 *
 * @category   IllApps
 * @package    IllApps_ShipmentDate
 * @author     Jonathan Cantrell (j@kernelhack.com)
 * @copyright  Copyright (c) 2011 EcoMATICS, Inc. DBA IllApps (http://www.illapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
 
class IllApps_ShipmentDate_Model_Observer extends Mage_Core_Model_Abstract
{				
	
    public function checkout_controller_onepage_save_shipping_method($observer)
    {        
        $request = $observer->getEvent()->getRequest();
        $session = Mage::getSingleton('core/session');
        $quote   = $observer->getEvent()->getQuote();

        $desiredShipmentDate = Mage::helper('shipmentdate')->getFormatedDeliveryDateToSave($session->getDateUpdate());

        $quote->setShippingShipmentDate($desiredShipmentDate)->save();

        return $this;
    }

    public function attachToOrderSaveEvent($observer)
    {
        $event = $observer->getEvent();
        $event->getOrder()->setShippingShipmentDate($event->getQuote()->getShippingShipmentDate());
        return $this;
    }
		
}