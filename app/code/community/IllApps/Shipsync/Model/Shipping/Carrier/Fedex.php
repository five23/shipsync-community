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
 * IllApps_Shipsync_Model_Shipping_Carrier_Fedex
 */
class IllApps_Shipsync_Model_Shipping_Carrier_Fedex
   extends Mage_Usa_Model_Shipping_Carrier_Abstract
   implements Mage_Shipping_Model_Carrier_Interface
{

    /**
     * Code of the carrier
     *
     * @var string
     */
    const CODE = 'fedex';

    /**
     * Code of the carrier
     *
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * Rate request data
     *
     * @var Mage_Shipping_Model_Rate_Request|null
     */
    protected $_request = null;

    /**
     * Raw rate request data
     *
     * @var Varien_Object|null
     */
    protected $_rawRequest = null;

    /**
     * Rate result data
     *
     * @var Mage_Shipping_Model_Rate_Result|null
     */
    protected $_result = null;

    /**
     * Container types that could be customized for FedEx carrier
     *
     * @var array
     */
    protected $_customizableContainerTypes = array('YOUR_PACKAGING');

    /**
     * Path to wsdl file of rate service
     *
     * @var string
     */
    protected $_rateServiceWsdl = null;
	protected $_rateServiceVersion = '14';
    protected $_rateServiceWsdlPath = 'RateService_v14.wsdl';

    /**
     * Path to wsdl file of ship service
     *
     * @var string
     */
    protected $_shipServiceWsdl = null;
    protected $_shipServiceVersion = '13';
    protected $_shipServiceWsdlPath = 'ShipService_v13.wsdl';

    /**
     * Path to wsdl file of track service
     *
     * @var string
     */
    protected $_trackServiceWsdl = null;
    protected $_trackServiceVersion = '5';
    protected $_trackServiceWsdlPath = 'TrackService_v5.wsdl';

    /**
     * Path to wsdl file of address service
     *
     * @var string
     */
    protected $_addressServiceWsdl = null;
    protected $_addressServiceVersion = '2';
    protected $_addressServiceWsdlPath = 'AddressValidationService_v2.wsdl';

	/**
     * Base wsdl path
     *
     * @var string
     */
	protected $_wsdlBasePath = null;

	/**
	 * Construct
	 */
	public function __construct()
    {
        parent::__construct();

        $this->_wsdlBasePath = Mage::getModuleDir('etc', 'IllApps_Shipsync')  . DS . 'wsdl' . DS . 'FedEx' . DS;
	}

    /**
     * collectRates
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return object
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if ((!$this->getConfigFlag('active'))
			|| Mage::getStoreConfig('carriers/fedex/disable_rating')) {
            return false;
        }

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
        if (!$this->getConfigFlag('active')) {
            return false;
        }

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
            $this->setWsdlPath($this->_wsdlBasePath . 'test' . DS . $wsdlPath);
        } else {
            $this->setFedexKey(Mage::getStoreConfig('carriers/fedex/key'));
            $this->setFedexPassword(Mage::getStoreConfig('carriers/fedex/password'));
            $this->setFedexAccount(Mage::getStoreConfig('carriers/fedex/account'));
            $this->setFedexMeter(Mage::getStoreConfig('carriers/fedex/meter_number'));
            $this->setWsdlPath($this->_wsdlBasePath . $wsdlPath);
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
		switch (Mage::getStoreConfig('carriers/fedex/unit_of_measure'))
		{
			case 'LB' : return 'IN';
			case 'KG' : return 'CM';
			case  'G' : return 'CM';
		}
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
     *  Return FeDex currency ISO code by Magento Base Currency Code
     *
     *  @return string 3-digit currency code
     */
    public function getCurrencyCode ()
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
            'TWD' => 'NTD', // New Taiwan Dollars
        );
        $currencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
        return isset($codes[$currencyCode]) ? $codes[$currencyCode] : $currencyCode;
    }

    /**
     * Get configuration data of carrier
     *
     * @param string $type
     * @param string $code
     * @return array|bool
     */
    public function getCode($type, $code='')
    {
        $codes = array(
            'method' => array(
                'EUROPE_FIRST_INTERNATIONAL_PRIORITY' => Mage::helper('usa')->__('Europe First Priority'),
                'FEDEX_1_DAY_FREIGHT'                 => Mage::helper('usa')->__('1 Day Freight'),
                'FEDEX_2_DAY_FREIGHT'                 => Mage::helper('usa')->__('2 Day Freight'),
                'FEDEX_2_DAY'                         => Mage::helper('usa')->__('2 Day'),
                'FEDEX_2_DAY_AM'                      => Mage::helper('usa')->__('2 Day AM'),
                'FEDEX_3_DAY_FREIGHT'                 => Mage::helper('usa')->__('3 Day Freight'),
                'FEDEX_EXPRESS_SAVER'                 => Mage::helper('usa')->__('Express Saver'),
                'FEDEX_GROUND'                        => Mage::helper('usa')->__('Ground'),
                'FIRST_OVERNIGHT'                     => Mage::helper('usa')->__('First Overnight'),
                'GROUND_HOME_DELIVERY'                => Mage::helper('usa')->__('Home Delivery'),
                'INTERNATIONAL_ECONOMY'               => Mage::helper('usa')->__('International Economy'),
                'INTERNATIONAL_ECONOMY_FREIGHT'       => Mage::helper('usa')->__('Intl Economy Freight'),
                'INTERNATIONAL_FIRST'                 => Mage::helper('usa')->__('International First'),
                'INTERNATIONAL_GROUND'                => Mage::helper('usa')->__('International Ground'),
                'INTERNATIONAL_PRIORITY'              => Mage::helper('usa')->__('International Priority'),
                'INTERNATIONAL_PRIORITY_FREIGHT'      => Mage::helper('usa')->__('Intl Priority Freight'),
                'PRIORITY_OVERNIGHT'                  => Mage::helper('usa')->__('Priority Overnight'),
                'SMART_POST'                          => Mage::helper('usa')->__('Smart Post'),
                'STANDARD_OVERNIGHT'                  => Mage::helper('usa')->__('Standard Overnight'),
                'FEDEX_FREIGHT'                       => Mage::helper('usa')->__('Freight'),
                'FEDEX_NATIONAL_FREIGHT'              => Mage::helper('usa')->__('National Freight'),
            ),
            'dropoff' => array(
                'REGULAR_PICKUP'          => Mage::helper('usa')->__('Regular Pickup'),
                'REQUEST_COURIER'         => Mage::helper('usa')->__('Request Courier'),
                'DROP_BOX'                => Mage::helper('usa')->__('Drop Box'),
                'BUSINESS_SERVICE_CENTER' => Mage::helper('usa')->__('Business Service Center'),
                'STATION'                 => Mage::helper('usa')->__('Station')
            ),
            'packaging' => array(
                'FEDEX_ENVELOPE' => Mage::helper('usa')->__('FedEx Envelope'),
                'FEDEX_PAK'      => Mage::helper('usa')->__('FedEx Pak'),
                'FEDEX_BOX'      => Mage::helper('usa')->__('FedEx Box'),
                'FEDEX_TUBE'     => Mage::helper('usa')->__('FedEx Tube'),
                'FEDEX_10KG_BOX' => Mage::helper('usa')->__('FedEx 10kg Box'),
                'FEDEX_25KG_BOX' => Mage::helper('usa')->__('FedEx 25kg Box'),
                'YOUR_PACKAGING' => Mage::helper('usa')->__('Your Packaging')
            ),
            'containers_filter' => array(
                array(
                    'containers' => array('FEDEX_ENVELOPE', 'FEDEX_PAK'),
                    'filters'    => array(
                        'within_us' => array(
                            'method' => array(
                                'FEDEX_EXPRESS_SAVER',
                                'FEDEX_2_DAY',
                                'FEDEX_2_DAY_AM',
                                'STANDARD_OVERNIGHT',
                                'PRIORITY_OVERNIGHT',
                                'FIRST_OVERNIGHT',
                            )
                        ),
                        'from_us' => array(
                            'method' => array(
                                'INTERNATIONAL_FIRST',
                                'INTERNATIONAL_ECONOMY',
                                'INTERNATIONAL_PRIORITY',
                            )
                        )
                    )
                ),
                array(
                    'containers' => array('FEDEX_BOX', 'FEDEX_TUBE'),
                    'filters'    => array(
                        'within_us' => array(
                            'method' => array(
                                'FEDEX_2_DAY',
                                'FEDEX_2_DAY_AM',
                                'STANDARD_OVERNIGHT',
                                'PRIORITY_OVERNIGHT',
                                'FIRST_OVERNIGHT',
                                'FEDEX_FREIGHT',
                                'FEDEX_1_DAY_FREIGHT',
                                'FEDEX_2_DAY_FREIGHT',
                                'FEDEX_3_DAY_FREIGHT',
                                'FEDEX_NATIONAL_FREIGHT',
                            )
                        ),
                        'from_us' => array(
                            'method' => array(
                                'INTERNATIONAL_FIRST',
                                'INTERNATIONAL_ECONOMY',
                                'INTERNATIONAL_PRIORITY',
                            )
                        )
                    )
                ),
                array(
                    'containers' => array('FEDEX_10KG_BOX', 'FEDEX_25KG_BOX'),
                    'filters'    => array(
                        'within_us' => array(),
                        'from_us' => array('method' => array('INTERNATIONAL_PRIORITY'))
                    )
                ),
                array(
                    'containers' => array('YOUR_PACKAGING'),
                    'filters'    => array(
                        'within_us' => array(
                            'method' =>array(
                                'FEDEX_GROUND',
                                'GROUND_HOME_DELIVERY',
                                'SMART_POST',
                                'FEDEX_EXPRESS_SAVER',
                                'FEDEX_2_DAY',
                                'FEDEX_2_DAY_AM',
                                'STANDARD_OVERNIGHT',
                                'PRIORITY_OVERNIGHT',
                                'FIRST_OVERNIGHT',
                                'FEDEX_FREIGHT',
                                'FEDEX_1_DAY_FREIGHT',
                                'FEDEX_2_DAY_FREIGHT',
                                'FEDEX_3_DAY_FREIGHT',
                                'FEDEX_NATIONAL_FREIGHT',
                            )
                        ),
                        'from_us' => array(
                            'method' =>array(
                                'INTERNATIONAL_FIRST',
                                'INTERNATIONAL_ECONOMY',
                                'INTERNATIONAL_PRIORITY',
                                'INTERNATIONAL_GROUND',
                                'FEDEX_FREIGHT',
                                'FEDEX_1_DAY_FREIGHT',
                                'FEDEX_2_DAY_FREIGHT',
                                'FEDEX_3_DAY_FREIGHT',
                                'FEDEX_NATIONAL_FREIGHT',
                                'INTERNATIONAL_ECONOMY_FREIGHT',
                                'INTERNATIONAL_PRIORITY_FREIGHT',
                            )
                        )
                    )
                )
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
            'signature' => array(
                'NO_SIGNATURE_REQUIRED' => Mage::helper('usa')->__('No Signature Required'),
                'ADULT'                 => Mage::helper('usa')->__('Adult'),
                'DIRECT'                => Mage::helper('usa')->__('Direct'),
                'INDIRECT'              => Mage::helper('usa')->__('Indirect'),
				'SERVICE_DEFAULT'       => Mage::helper('usa')->__('Service Default')
            ),
			'unit_of_measure'=>array(
                'LB'   =>  Mage::helper('usa')->__('Pounds'),
                'KG'   =>  Mage::helper('usa')->__('Kilograms'),
				'G'    =>  Mage::helper('usa')->__('Grams'),
            ),
			'b13a_filing_option' => array(
				'FILED_ELECTRONICALLY' 	=> Mage::helper('usa')->__('Filed Electronically'),
				'MANUALLY_ATTACHED' 	=> Mage::helper('usa')->__('Manually Attached'),
				'NOT_REQUIRED' 			=> Mage::helper('usa')->__('Not Required'),
				'SUMMARY_REPORTING' 	=> Mage::helper('usa')->__('Summary Reporting'),
				'FEDEX_TO_STAMP'		=> Mage::helper('usa')->__('Fedex to Stamp')
			),
			'rate_discount' => array(
				'BONUS'		=> Mage::helper('usa')->__('Bonus'),
				'COUPON'	=> Mage::helper('usa')->__('Coupon'),
				'EARNED'	=> Mage::helper('usa')->__('Earned'),
				'OTHER'		=> Mage::helper('usa')->__('Other'),
				'VOLUME'	=> Mage::helper('usa')->__('Volume'),
			)
        );

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

    /**
     * Get allowed methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        $allowed = explode(",", $this->getConfigData('allowed_methods'));
        
        $arr = array();
        
        foreach ($allowed as $k) {
            $arr[] = $k;
        }
        
        if (is_array($arr)) {
            return $arr;
        } else {
            return false;
        }
    }

	
    protected function _doShipmentRequest(Varien_Object $request)
    {
    }
}
