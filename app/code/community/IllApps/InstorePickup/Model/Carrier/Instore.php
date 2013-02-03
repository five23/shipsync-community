<?php

/**
* Our test shipping method module adapter
*/
class IllApps_InstorePickup_Model_Carrier_Instore extends Mage_Shipping_Model_Carrier_Abstract
{
    
    protected $_code = 'instorepickup';

    /**
    * Collect rates for this shipping method based on information in $request
    *
    * @param Mage_Shipping_Model_Rate_Request $data
    * @return Mage_Shipping_Model_Rate_Result
    */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!Mage::getStoreConfig('carriers/'.$this->_code.'/active')) { return false; }
        
        $result = Mage::getModel('shipping/rate_result');
        $method = Mage::getModel('shipping/rate_result_method');

        $method->setCarrier($this->_code)
            ->setCarrierTitle(Mage::getStoreConfig('carriers/'.$this->_code.'/title'))
            ->setMethod('local')
            ->setMethodTitle($this->getMethodTitleText())      
            ->setCost(0)
            ->setPrice(0);

        $result->append($method);

        return $result;
    }
    
    public function getMethodTitleText()
    {
        Mage::log($this->getDate());
        return sprintf(Mage::getStoreConfig('carriers/'.$this->_code.'/method_title'), $this->getDate());
        /*return  'Local Pickup --Pickup '
                . $this->getDate()
                . ' After 1pm until 8pm';*/
    }
    
    public function getDate()
    {
        return Mage::getSingleton('core/session')->getDateUpdate();
    }

    /**
    * This method is used when viewing / listing Shipping Methods with Codes programmatically
    */
    public function getAllowedMethods() 
    {
        return array($this->_code => $this->getConfigData('name'));
    }
}