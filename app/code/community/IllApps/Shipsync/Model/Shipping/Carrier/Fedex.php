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
 * IllApps_Shipsync_Model_Shipping_Carrier_Fedex
 */
class IllApps_Shipsync_Model_Shipping_Carrier_Fedex extends Mage_Usa_Model_Shipping_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface
{
    
    protected $_code = 'fedex';
    protected $_request;
    protected $_rateResult;
    
    
    /**
     * collectRates
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return object
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        // Check if method is active
        if (!$this->getConfigFlag('active')) {
            return false;
        }
        
        if (Mage::getStoreConfig('carriers/fedex/disable_rating')) {
            return false;
        }
        
        // Collect rates
        return Mage::getModel('usa/shipping_carrier_fedex_rate')->collectRates($request);
    }
    
    
    
    /**
     * createShipment
     * 
     * @param mixed $request
     * @return mixed
     */
    public function createShipment($request)
    {
        // Check if method is active
        if (!$this->getConfigFlag('active')) {
            return false;
        }
        
        // Create shipment
        return Mage::getModel('usa/shipping_carrier_fedex_ship')->createShipment($request);
    }
    
    
    
    /**
     * getTracking
     *
     * @param mixed $trackings
     * @return mixed
     */
    public function getTracking($trackings)
    {
        return Mage::getModel('usa/shipping_carrier_fedex_track')->getTracking($trackings);
    }
    
    
    
    /**
     * Check residential status
     */
    public function getResidential($street, $postcode)
    {
        return Mage::getModel('usa/shipping_carrier_fedex_address')->getResidential($street, $postcode);
    }
    
    
    /**
     * getPackages
     *
     * @return mixed
     */
    public function getPackages()
    {
        return Mage::getModel('usa/shipping_carrier_fedex_package')->getPackages();
    }
    
    
    /**
     * _initWebServices
     *
     * @param string $wsdlPath
     * @return SoapClient
     */
    protected function _initWebServices($wsdlPath)
    {
        ini_set('soap.wsdl_cache_enabled', Mage::getStoreConfig('carriers/fedex/enable_soap_cache'));
        
        $this->setFedexAccountCountry(Mage::getStoreConfig('carriers/fedex/account_country'));
        
        if (Mage::getStoreConfig('carriers/fedex/test_mode')) {
            $this->setFedexKey(Mage::getStoreConfig('carriers/fedex/test_key'));
            $this->setFedexPassword(Mage::getStoreConfig('carriers/fedex/test_password'));
            $this->setFedexAccount(Mage::getStoreConfig('carriers/fedex/test_account'));
            $this->setFedexMeter(Mage::getStoreConfig('carriers/fedex/test_meter'));
            $this->setWsdlPath(dirname(__FILE__) . '/Fedex/wsdl/test/' . $wsdlPath);
        } else {
            $this->setFedexKey(Mage::getStoreConfig('carriers/fedex/key'));
            $this->setFedexPassword(Mage::getStoreConfig('carriers/fedex/password'));
            $this->setFedexAccount(Mage::getStoreConfig('carriers/fedex/account'));
            $this->setFedexMeter(Mage::getStoreConfig('carriers/fedex/meter_number'));
            $this->setWsdlPath(dirname(__FILE__) . '/Fedex/wsdl/' . $wsdlPath);
        }
        
        try {
            $soapClient = new SoapClient($this->getWsdlPath(), array(
                'trace' => 1
            ));
        }
        catch (Exception $ex) {
            Mage::throwException($ex->getMessage());
        }
        
        return $soapClient;
    }
    
    
    
    /**
     * getDimensionUnits
     *
     * @return string
     */
    public function getDimensionUnits()
    {
        return Mage::getStoreConfig('carriers/fedex/unit_of_measure');
    }
    
    
    
    /**
     * getWeightUnits
     *
     * @return string
     */
    public function getWeightUnits()
    {
        return Mage::getStoreConfig('carriers/fedex/unit_of_measure');
    }
    
    
    
    /**
     *  Return FedEx currency ISO code by Magento Base Currency Code
     *
     *  @return   string 3-digit currency code
     */
    public function getCurrencyCode()
    {
        $codes = array(
            'DOP' => 'RDD', // Dominican Peso
            'XCD' => 'ECD', // Caribbean Dollars
            'ARS' => 'ARN', // Argentina Peso
            'SGD' => 'SID', // Singapore Dollars
            'KRW' => 'WON', // South Korea Won
            'JMD' => 'JAD', // Jamaican Dollars
            'CHF' => 'SFR', // Swiss Francs
            'JPY' => 'JYE', // Japanese Yen
            'KWD' => 'KUD', // Kuwaiti Dinars
            'GBP' => 'UKL', // British Pounds
            'AED' => 'DHS', // UAE Dirhams
            'MXN' => 'NMP', // Mexican Pesos
            'UYU' => 'UYP', // Uruguay New Pesos
            'CLP' => 'CHP', // Chilean Pesos
            'TWD' => 'NTD' // New Taiwan Dollars
        );
        
        $currencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
        
        return isset($codes[$currencyCode]) ? $codes[$currencyCode] : $currencyCode;
    }
    
    
    
    /**
     * Get underscore from code
     *
     * @param string $code
     * @return string
     */
    public function getUnderscoreCodeFromCode($code)
    {
        /** Method codes */
        $codes = array(
            'PRIORITYOVERNIGHT' => 'PRIORITY_OVERNIGHT',
            'STANDARDOVERNIGHT' => 'STANDARD_OVERNIGHT',
            'FIRSTOVERNIGHT' => 'FIRST_OVERNIGHT',
            'FEDEX2DAY' => 'FEDEX_2_DAY',
            'FEDEXEXPRESSSAVER' => 'FEDEX_EXPRESS_SAVER',
            'INTERNATIONALPRIORITY' => 'INTERNATIONAL_PRIORITY',
            'INTERNATIONALECONOMY' => 'INTERNATIONAL_ECONOMY',
            'INTERNATIONALFIRST' => 'INTERNATIONAL_FIRST',
            'FEDEX1DAYFREIGHT' => 'FEDEX_1_DAY_FREIGHT',
            'FEDEX2DAYFREIGHT' => 'FEDEX_2_DAY_FREIGHT',
            'FEDEX3DAYFREIGHT' => 'FEDEX_3_DAY_FREIGHT',
            'FEDEXGROUND' => 'FEDEX_GROUND',
            'GROUNDHOMEDELIVERY' => 'GROUND_HOME_DELIVERY',
            'INTERNATIONALPRIORITY FREIGHT' => 'INTERNATIONAL_PRIORITY_FREIGHT',
            'INTERNATIONALECONOMY FREIGHT' => 'INTERNATIONAL_ECONOMY_FREIGHT',
            'EUROPEFIRSTINTERNATIONALPRIORITY' => 'EUROPE_FIRST_INTERNATIONAL_PRIORITY',
            'REGULARPICKUP' => 'REGULAR_PICKUP',
            'REQUESTCOURIER' => 'REQUEST_COURIER',
            'DROPBOX' => 'DROP_BOX',
            'BUSINESSSERVICECENTER' => 'BUSINESS_SERVICE_CENTER',
            'STATION' => 'STATION',
            'FEDEXENVELOPE' => 'FEDEX_ENVELOPE',
            'FEDEXPAK' => 'FEDEX_PAK',
            'FEDEXBOX' => 'FEDEX_BOX',
            'FEDEXBOXSMALL' => 'FEDEX_BOX_SMALL',
            'FEDEXBOXMED' => 'FEDEX_BOX_MED',
            'FEDEXBOXLARGE' => 'FEDEX_BOX_LARGE',
            'FEDEXTUBE' => 'FEDEX_TUBE',
            'FEDEX10KGBOX' => 'FEDEX_10KG_BOX',
            'FEDEX25KGBOX' => 'FEDEX_25KG_BOX',
            'YOURPACKAGING' => 'YOUR_PACKAGING',
            'SMARTPOST' => 'SMART_POST'
        );
        
        return isset($codes[$code]) ? $codes[$code] : $code;
    }
    
    
    
    /**
     * Get code
     *
     * @param string $type
     * @param string $code
     * @param bool $underscore
     * @return array
     */
    public function getCode($type, $code = '', $underscore = false)
    {
        $codes_underscore = array(
            'method' => array(
                'PRIORITY_OVERNIGHT' => Mage::helper('usa')->__('Priority Overnight'),
                'STANDARD_OVERNIGHT' => Mage::helper('usa')->__('Standard Overnight'),
                'FIRST_OVERNIGHT' => Mage::helper('usa')->__('First Overnight'),
                'FEDEX_2DAY' => Mage::helper('usa')->__('2Day'),
                'FEDEX_2_DAY' => Mage::helper('usa')->__('2Day'),
                'FEDEX_EXPRESS_SAVER' => Mage::helper('usa')->__('Express Saver'),
                'INTERNATIONAL_PRIORITY' => Mage::helper('usa')->__('International Priority'),
                'INTERNATIONAL_ECONOMY' => Mage::helper('usa')->__('International Economy'),
                'INTERNATIONAL_FIRST' => Mage::helper('usa')->__('International First'),
                'FEDEX_1_DAY_FREIGHT' => Mage::helper('usa')->__('1 Day Freight'),
                'FEDEX_2_DAY_FREIGHT' => Mage::helper('usa')->__('2 Day Freight'),
                'FEDEX_3_DAY_FREIGHT' => Mage::helper('usa')->__('3 Day Freight'),
                'FEDEX_GROUND' => Mage::helper('usa')->__('Ground'),
                'GROUND_HOME_DELIVERY' => Mage::helper('usa')->__('Home Delivery'),
                'INTERNATIONAL_PRIORITY_FREIGHT' => Mage::helper('usa')->__('Intl Priority Freight'),
                'INTERNATIONAL_ECONOMY_FREIGHT' => Mage::helper('usa')->__('Intl Economy Freight'),
                'EUROPE_FIRST_INTERNATIONAL_PRIORITY' => Mage::helper('usa')->__('Europe First Priority'),
                'SMART_POST' => Mage::helper('usa')->__('SmartPost')
            ),
            'dropoff' => array(
                'REGULAR_PICKUP' => Mage::helper('usa')->__('Regular Pickup'),
                'REQUEST_COURIER' => Mage::helper('usa')->__('Request Courier'),
                'DROP_BOX' => Mage::helper('usa')->__('Drop Box'),
                'BUSINESS_SERVICE_CENTER' => Mage::helper('usa')->__('Business Service Center'),
                'STATION' => Mage::helper('usa')->__('Station')
            ),
            'packaging' => array(
                'FEDEX_ENVELOPE' => Mage::helper('usa')->__('FedEx Envelope'),
                'FEDEX_PAK' => Mage::helper('usa')->__('FedEx Pak'),
                'FEDEX_BOX' => Mage::helper('usa')->__('FedEx Box'),
                'FEDEX_BOX_SMALL' => Mage::helper('usa')->__('FedEx Box Small'),
                'FEDEX_BOX_MED' => Mage::helper('usa')->__('FedEx Box Medium'),
                'FEDEX_BOX_LARGE' => Mage::helper('usa')->__('FedEx Box Large'),
                'FEDEX_TUBE' => Mage::helper('usa')->__('FedEx Tube'),
                'FEDEX_10KG_BOX' => Mage::helper('usa')->__('FedEx 10kg Box'),
                'FEDEX_25KG_BOX' => Mage::helper('usa')->__('FedEx 25kg Box'),
                'YOUR_PACKAGING' => Mage::helper('usa')->__('Your Packaging')
            )
        );
        
        $codes = array(
            'method' => array(
                'PRIORITYOVERNIGHT' => Mage::helper('usa')->__('Priority Overnight'),
                'STANDARDOVERNIGHT' => Mage::helper('usa')->__('Standard Overnight'),
                'FIRSTOVERNIGHT' => Mage::helper('usa')->__('First Overnight'),
                'FEDEX2DAY' => Mage::helper('usa')->__('2Day'),
                'FEDEXEXPRESSSAVER' => Mage::helper('usa')->__('Express Saver'),
                'INTERNATIONALPRIORITY' => Mage::helper('usa')->__('International Priority'),
                'INTERNATIONALECONOMY' => Mage::helper('usa')->__('International Economy'),
                'INTERNATIONALFIRST' => Mage::helper('usa')->__('International First'),
                'FEDEX1DAYFREIGHT' => Mage::helper('usa')->__('1 Day Freight'),
                'FEDEX2DAYFREIGHT' => Mage::helper('usa')->__('2 Day Freight'),
                'FEDEX3DAYFREIGHT' => Mage::helper('usa')->__('3 Day Freight'),
                'FEDEXGROUND' => Mage::helper('usa')->__('Ground'),
                'GROUNDHOMEDELIVERY' => Mage::helper('usa')->__('Home Delivery'),
                'INTERNATIONALPRIORITY FREIGHT' => Mage::helper('usa')->__('Intl Priority Freight'),
                'INTERNATIONALECONOMY FREIGHT' => Mage::helper('usa')->__('Intl Economy Freight'),
                'EUROPEFIRSTINTERNATIONALPRIORITY' => Mage::helper('usa')->__('Europe First Priority'),
                'SMARTPOST' => Mage::helper('usa')->__('SmartPost')
            ),
            'dropoff' => array(
                'REGULARPICKUP' => Mage::helper('usa')->__('Regular Pickup'),
                'REQUESTCOURIER' => Mage::helper('usa')->__('Request Courier'),
                'DROPBOX' => Mage::helper('usa')->__('Drop Box'),
                'BUSINESSSERVICECENTER' => Mage::helper('usa')->__('Business Service Center'),
                'STATION' => Mage::helper('usa')->__('Station')
            ),
            
            'packaging' => array(
                'FEDEXENVELOPE' => Mage::helper('usa')->__('FedEx Envelope'),
                'FEDEXPAK' => Mage::helper('usa')->__('FedEx Pak'),
                'FEDEXBOX' => Mage::helper('usa')->__('FedEx Box'),
                'FEDEXBOXSMALL' => Mage::helper('usa')->__('FedEx Box Small'),
                'FEDEXBOXMED' => Mage::helper('usa')->__('FedEx Box Medium'),
                'FEDEXBOXLARGE' => Mage::helper('usa')->__('FedEx Box Large'),
                'FEDEXTUBE' => Mage::helper('usa')->__('FedEx Tube'),
                'FEDEX10KGBOX' => Mage::helper('usa')->__('FedEx 10kg Box'),
                'FEDEX25KGBOX' => Mage::helper('usa')->__('FedEx 25kg Box'),
                'YOURPACKAGING' => Mage::helper('usa')->__('Your Packaging')
            ),
            'rate_type' => array(
                'LIST' => Mage::helper('usa')->__('List Rates'),
                'ACCOUNT' => Mage::helper('usa')->__('Account Rates'),
                'PREFERRED' => Mage::helper('usa')->__('Preferred Rates')
            ),
            'label_image' => array(
                'PDF' => Mage::helper('usa')->__('Adobe PDF'),
                'PNG' => Mage::helper('usa')->__('PNG Image'),
                'EPL2' => Mage::helper('usa')->__('Zebra EPL2'),
                'ZPLII' => Mage::helper('usa')->__('Zebra ZPLII'),
                'DPL' => Mage::helper('usa')->__('Datamax DPL')
            ),
            'label_stock' => array(
                'PAPER_7X4.75' => Mage::helper('usa')->__('PAPER_7X4.75'),
                'PAPER_4X6' => Mage::helper('usa')->__('PAPER_4X6'),
                'PAPER_4X8' => Mage::helper('usa')->__('PAPER_4X8'),
                'PAPER_4X9' => Mage::helper('usa')->__('PAPER_4X9'),
                'PAPER_8.5X11_BOTTOM_HALF_LABEL' => Mage::helper('usa')->__('PAPER_8.5X11_BOTTOM_HALF_LABEL'),
                'PAPER_8.5X11_TOP_HALF_LABEL' => Mage::helper('usa')->__('PAPER_8.5X11_TOP_HALF_LABEL'),
                'STOCK_4X6' => Mage::helper('usa')->__('STOCK_4X6'),
                'STOCK_4X6.75_LEADING_DOC_TAB' => Mage::helper('usa')->__('STOCK_4X6.75_LEADING_DOC_TAB'),
                'STOCK_4X6.75_TRAILING_DOC_TAB' => Mage::helper('usa')->__('STOCK_4X6.75_TRAILING_DOC_TAB'),
                'STOCK_4X8' => Mage::helper('usa')->__('STOCK_4X8'),
                'STOCK_4X9_LEADING_DOC_TAB' => Mage::helper('usa')->__('STOCK_4X9_LEADING_DOC_TAB'),
                'STOCK_4X9_TRAILING_DOC_TAB' => Mage::helper('usa')->__('STOCK_4X9_TRAILING_DOC_TAB')
            ),
            'label_orientation' => array(
                'BOTTOM_EDGE_OF_TEXT_FIRST' => Mage::helper('usa')->__('Bottom edge of text first'),
                'TOP_EDGE_OF_TEXT_FIRST' => Mage::helper('usa')->__('Top edge of text first')
            ),
            'smartpost_indicia' => array(
                'MEDIA_MAIL' => Mage::helper('usa')->__('Media Mail'),
                'PARCEL_SELECT' => Mage::helper('usa')->__('Parcel Select'),
                'PRESORTED_BOUND_PRINTED_MATTER' => Mage::helper('usa')->__('Presorted Bound Printed Matter'),
                'PRESORTED_STANDARD' => Mage::helper('usa')->__('Presorted Standard')
            ),
            'smartpost_endorsement' => array(
                'ADDRESS_CORRECTION' => Mage::helper('usa')->__('Address Correction'),
                'CARRIER_LEAVE_IF_NO_RESPONSE' => Mage::helper('usa')->__('Carrier Leave If No Response'),
                'CHANGE_SERVICE' => Mage::helper('usa')->__('Change Service'),
                'FORWARDING_SERVICE' => Mage::helper('usa')->__('Forwarding Service'),
                'RETURN_SERVICE' => Mage::helper('usa')->__('Return Service')
            ),			
            'delivery_confirmation_types' => array(
                'NO_SIGNATURE_REQUIRED' => Mage::helper('usa')->__('Not Required'),
                'ADULT'                 => Mage::helper('usa')->__('Adult'),
                'DIRECT'                => Mage::helper('usa')->__('Direct'),
                'INDIRECT'              => Mage::helper('usa')->__('Indirect'),
            )
        );
        
        if ($underscore) {
            if (!isset($codes_underscore[$type])) {
                return false;
            } elseif ('' === $code) {
                return $codes_underscore[$type];
            }
            
            if (!isset($codes_underscore[$type][$code])) {
                return false;
            } else {
                return $codes_underscore[$type][$code];
            }
        } else {
            if (!isset($codes[$type])) {
                return false;
            } elseif ('' === $code) {
                return $codes[$type];
            }
            
            if (!isset($codes[$type][$code])) {
                return false;
            } else {
                return $codes[$type][$code];
            }
        }
    }
    
    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        $allowed = explode(',', Mage::getStoreConfig('carriers/fedex/allowed_methods'));
        
        $arr = array();
        
        foreach ($allowed as $k) {
            $arr[$k] = $this->getCode('method', $k);
        }
        
        if (is_array($arr)) {
            return $arr;
        } else {
            return false;
        }
    }
    
    
    
    /**
     * getParsedAllowedMethods
     *
     * @return array
     */
    public function getParsedAllowedMethods()
    {
        if ($allowedMethods = explode(",", Mage::getStoreConfig('carriers/fedex/allowed_methods'))) {
            foreach ($allowedMethods as $method) {
                $parsedAllowedMethods[] = $this->getUnderscoreCodeFromCode($method);
            }
            
            return $parsedAllowedMethods;
        }
    }
    
    /**
     * getParsedAllowedRatingMethods
     *
     * @return array
     */
    public function getParsedAllowedRatingMethods()
    {
        if (!Mage::getStoreConfig('carriers/fedex/alternate_rating_methods')) {
            return $this->getParsedAllowedMethods();
        }
        
        if ($allowedMethods = explode(",", Mage::getStoreConfig('carriers/fedex/allowed_rating_methods'))) {
            foreach ($allowedMethods as $method) {
                $parsedAllowedMethods[] = $this->getUnderscoreCodeFromCode($method);
            }
            
            return $parsedAllowedMethods;
        }
    }
    	
	
    protected function _doShipmentRequest(Varien_Object $request)
    {
    }
}
?>