<?php
/**
 * ZoneRates
 *
 * @category   IllApps
 * @package    IllApps_ZoneRates
 * @author     Jonathan Cantrell (j@kernelhack.com)
 * @copyright  Copyright (c) 2011 EcoMATICS, Inc. DBA IllApps (http://www.illapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

 class IllApps_ZoneRates_Model_Carrier_Zonerates extends Mage_Shipping_Model_Carrier_Abstract
 {
     protected $_code = 'zonerates';
     protected $_zone;

    /**
     * Collect rates for this shipping method based on information in $request
     *
     * @param Mage_Shipping_Model_Rate_Request $data
     * @return IllApps_ZoneRates_Model_Carrier_Zonerates
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!Mage::getStoreConfig('carriers/'.$this->_code.'/active')) {
            return false;
        }

        $this->setShipmentZone($request);

        $result = Mage::getModel('shipping/rate_result');

        foreach($this->getFedexMethodsAvailable($request)->getAllRates() as $method)
        {            
            $method->setZone($this->_zone)
                ->setOrderPrice($request->getPackageValue())
                ->setCarrier($this->getConfigData('name'))
                ->setCarrierTitle($this->getConfigData('title'));

            $method->modifyForZone();

            //Just for right now
            $method->setMethodTitle($method->getMethodTitle() . '<br />' . $method->getArrival());
            
            if($method->getShippable()) { $result->append($method); }
        }
        return $result;
    }

    /*
     * Get FedEx Methods Available - Rate Available request using ShipSync
     * 
     * @return IllApps_ZoneRates_Model_Rate_Result_Method
     */
    public function getFedexMethodsAvailable($request)
    {
        return Mage::getModel('usa/shipping_carrier_fedex_rate')->collectRates($request);
    }

    /*
     * Set Shipment Zone
     * 
     * @param Mage_Shipping_Model_Rate_Request
     * @return IllApps_ZoneRates_Model_Carrier_Zonerates
     */
    public function setShipmentZone($request)
    {
        $destZip = (string) $request->getDestPostcode();

        $this->_zone = Mage::getModel('zonerates/zones')->findZone($destZip, 'zipcode');

        return $this;
    }
 }