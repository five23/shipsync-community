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

class IllApps_ShipmentDate_Block_Onepage_Shipmentoptions extends Mage_Checkout_Block_Onepage_Abstract
{
    /*public function getCacheLifetime()
    {
        return null;
    }*/
    
    protected function _construct()
    {
        $this->getCheckout()->setStepData('shipmentoptions', array(
            'label'     => Mage::helper('checkout')->__('Local Pickup & Shipping Options'),
            'is_show'   => $this->isShow(),
        ));

        parent::_construct();
    }

    public function getOptionTitle($method = 'delivery')
    {
        return Mage::getStoreConfig('shipmentdate/' . $method . '/title');
    }
    
    public function isShow()
    {
        return !Mage::getSingleton('checkout/session')->getQuote()->isVirtual();
    }
}