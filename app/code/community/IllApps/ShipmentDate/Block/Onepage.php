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

class IllApps_ShipmentDate_Block_Onepage extends Mage_Checkout_Block_Onepage
{
    public function getSteps()
    {
        $steps = array();

        if (!$this->isCustomerLoggedIn()) {
            $steps['login'] = $this->getCheckout()->getStepData('login');
        }

        $stepCodes = array('billing', 'shipping', 'shipmentoptions', 'shipping_method', 'payment', 'review');

        foreach ($stepCodes as $step) {
            $steps[$step] = $this->getCheckout()->getStepData($step);
            if($step == 'shipmentoptions' && !$steps[$step] && $this->isShowShipmentOptions()) { 
                $steps[$step] = array(
                    'label'     => Mage::helper('checkout')->__('Local Pickup & Shipping Options'),
                    'is_show'   => $this->isShow());
            } elseif ($step == 'shipmentoptions' && !$this->isShowShipmentOptions()) {
                unset($steps[$step]);
            }
        }
        
        return $steps;
    }
    
    public function isShowShipmentOptions()
    {
        return !Mage::getSingleton('checkout/session')->getQuote()->isVirtual();
    }
}