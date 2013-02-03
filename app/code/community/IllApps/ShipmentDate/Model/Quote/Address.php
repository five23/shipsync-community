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

class IllApps_ShipmentDate_Model_Quote_Address extends Mage_Sales_Model_Quote_Address
{
    /*public function setLimitCarrier($var)
    {
        $this->_limitCarrier = $var;
        return $this;
    }

    public function getLimitCarrier()
    {
        return Mage::getSingleton('core/session')->getInstorePickup() ? 'freeshipping' : 'zonerates';
    }*/
    
    /**
     * Collecting shipping rates by address
     *
     * @return Mage_Sales_Model_Quote_Address
     */
    public function collectShippingRates()
    {
        //always get new rates
        /*if (!$this->getCollectShippingRates()) {
            return $this;
        }*/
 
        $this->setCollectShippingRates(false);

        $this->removeAllShippingRates();

        if (!$this->getCountryId()) {
            return $this;
        }

        $found = $this->requestShippingRates();
        if (!$found) {
            $this->setShippingAmount(0)
                ->setBaseShippingAmount(0)
                ->setShippingMethod('')
                ->setShippingDescription('');
        }

        return $this;
    }
}
