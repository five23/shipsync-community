<?php

/**
 * ShipSync
 *
 * @category   IllApps
 * @package    IllApps_Shipsync
 * @author     David Kirby (d@kernelhack.com)
 * @copyright  Copyright (c) 2011 EcoMATICS, Inc. DBA IllApps (http://www.illapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Fedex shipping methods
 */
class IllApps_Shipsync_Model_Shipping_Carrier_Fedex extends Mage_Usa_Model_Shipping_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface
{
    
    protected $_code = 'fedex';
    protected $_request = null;
    protected $_rateRequest = null;
    protected $_shipRequest = null;
    protected $_trackRequest = null;
    protected $_result = null;
    protected $_packageCount = 0;


    /**
     * Collect rates
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return mixed
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        /** Check if method is active */
	if (!$this->getConfigFlag('active')) { return false; }

	/** Disable shipsync for packages over maximum package weight */
	if ($this->getConfigData('disable_for_max')
		&& ($request->getPackageWeight() > $this->getConfigData('max_package_weight'))) { return false; }

        /** Set request */
	$this->setRateRequest($request);

	/** Get quotes */
	$this->_result = $this->_getQuotes();

	/** Return result */
        return $this->getResult();
    }

 
    /**
     * Get credentials
     */
    protected function _initWebServices()
    {
	/** Set SOAP cache */
	ini_set('soap.wsdl_cache_enabled', $this->getConfigData('enable_soap_cache'));

	$r = new Varien_Object();

	/** Test mode */
	if ($this->getConfigData('test_mode'))
	{
	    $r->setFedexApiKey($this->getConfigData('test_key'));	    /** Set FedEx API key */
	    $r->setFedexApiPassword($this->getConfigData('test_password')); /** Set FedEx API password */
	    $r->setFedexAccount($this->getConfigData('test_account'));	    /** Set FedEx account */
	    $r->setFedexMeter($this->getConfigData('test_meter'));	    /** Set FedEx meter */
	    $r->setWsdlPath(dirname(__FILE__) . '/wsdl/test/');		    /** Set WSDL path */
        }
	/** Production mode */
        else
        {
            $r->setFedexApiKey($this->getConfigData('key'));		    /** Set FedEx API key */
	    $r->setFedexApiPassword($this->getConfigData('password'));	    /** Set FedEx API password */
	    $r->setFedexAccount($this->getConfigData('account'));	    /** Set FedEx account */
	    $r->setFedexMeter($this->getConfigData('meter'));		    /** Set FedEx meter */
	    $r->setWsdlPath(dirname(__FILE__) . '/wsdl/');		    /** Set WSDL path */
        }	
	return $r;
    }


    /**
     * Get Quotes
     *
     * @return mixed
     */
    protected function _getQuotes()
    {
	return $this->_getWsdlQuotes();
    }


    /**
     * Get dimension units
     *
     * @return string
     */
    public function getDimensionUnits()
    {
	return Mage::getStoreConfig('carriers/fedex/dimension_units', $this->getStore());
    }
    

    /**
     * Get Weight units
     *
     * @return string
     */
    public function getWeightUnits()
    {
	return Mage::getStoreConfig('carriers/fedex/weight_units', $this->getStore());
    }

     /**
     * Get default ShipSync packages
     *
     * @return array
     */
    public function getDefaultPackages()
    {
	/** Special packaging, a wildcard package for products needing their own package */
	$packages[0] = array(
	    'id'       => 0,
	    'title'    => 'Special Packaging',
	    'weight'   => null,
	    'length'   => null,
	    'width'    => null,
	    'height'   => null,
	    'volume'   => null,
            'baseline' => null);

        $fedexPkgs = Mage::getStoreConfig('shipping/packages/enablefedexpkg');

        if($fedexPkgs)
        {
            $packages = (Mage::Helper('shipsync/packages')->asArray($fedexPkgs) ? 
                        array_merge($packages, Mage::Helper('shipsync/packages')->asArray($fedexPkgs)) : $packages);
            
            foreach($packages as $key => $package)
            {
                $packages[$key]['id'] = $key;
            }
        }

	/** Iterate through 6 package slots */
	for ($i=1; $i <= 6; $i++)
	{
	    /** If package is enabled */
	    if (Mage::getStoreConfig('shipping/packages/pkg' . $i . 'enabled'))
	    {
		$title    = Mage::getStoreConfig('shipping/packages/pkg' . $i . 'title');  /** Get package title */
		$weight   = Mage::getStoreConfig('shipping/packages/pkg' . $i . 'weight'); /** Get package weight */
		$length   = Mage::getStoreConfig('shipping/packages/pkg' . $i . 'length'); /** Get package length */
		$width    = Mage::getStoreConfig('shipping/packages/pkg' . $i . 'width');  /** Get package width */
		$height   = Mage::getStoreConfig('shipping/packages/pkg' . $i . 'height'); /** Get package height */
                $baseline = Mage::getStoreConfig('shipping/packages/pkg' . $i . 'base');   /** Get baseline weight */

		/** Calculate package volume */
		$volume = $length * $width * $height;

		/** Add package to array */
		$packages[] = array(
				    'title'    => $title,
				    'weight'   => $weight,
				    'length'   => $length,
				    'width'    => $width,
				    'height'   => $height,
				    'volume'   => $volume,
                                    'baseline' => $baseline,
                                    'id'       => count($packages));
	    }
	}

	/** If no packages are found, return false */
	if (!is_array($packages)) { return false; }

	/** Sort packages by weight */
	usort($packages, array($this, '_sortByWeight'));

	/** Return packages array */
	return $packages;
    }

    
    /**
     * Get items, parses Magento's items into a usable format for ShipSync
     *
     * @param object $items
     * @return array $_items
     */
    protected function _getItems($items)
    {
	$i=0; /** Master item counter */

        /** Iterate through items */
        foreach ($items as $item)
        {
            /** If the item is a child, and is not set to ship separately */
	    if ($item->getParentItem() && !$item->isShipSeparately()) { continue; }
	    
	    /** If the item is a parent, and is set to ship separately */
	    if ($item->getHasChildren() && $item->isShipSeparately()) { continue; }

	    /** If item is virtual */
	    if ($item->getIsVirtual()) { continue; }
	    
	    /** Load associated product */
	    $product = Mage::getModel('catalog/product')->load($item->getProductId());
            
	    /** Get item quantity */
	    $qty = ($item->getQty() > 0) ? $item->getQty() : 1;

	    /** While quantity is greater than 0 */
	    while ($qty > 0)
	    {
		$_items[$i]['name']       = $item->getName();		         /** Set item name */
		$_items[$i]['weight']     = $item->getWeight();			 /** Set item weight */
		$_items[$i]['value']	  = $product->getPrice();		 /** Set item value */
		$_items[$i]['special']    = $product->getSpecialPackaging();	 /** Set special packaging flag */
                $_items[$i]['free_shipping'] = $product->getFreeShipping();      /** Set free shipping flag */
		$_items[$i]['dimensions'] = false;				 /** Dimensions false */

		/** If dimensions are enabled and present for this item */
		if ($this->getConfigData('enable_dimensions')
			&& $product->getWidth() && $product->getHeight() && $product->getLength())
		{
		    $_items[$i]['dimensions'] = true;			/** Dimensions true */
		    $_items[$i]['length'] = $product->getLength();	/** Set length */
		    $_items[$i]['width']  = $product->getWidth();	/** Set width */
		    $_items[$i]['height'] = $product->getHeight();	/** Set height */

		    /** Set volume */
		    $_items[$i]['volume'] = $product->getLength() * $product->getWidth() * $product->getHeight();
		}

		$qty--; /** Decrement item quantity */
		$i++;   /** Increment master item counter */
	    }
        }

	/** If no items are found, return false */
	if (!isset($_items) || !is_array($_items)) { return false; }

	/** Sort items by weight */
	usort($_items, array($this, '_sortByWeight'));
	/** Return items array */
	return $_items;
    }


    /**
     * Get result
     *
     * @return object
     */
    public function getResult() { return $this->_result; }    

    
    /**
     * Set request
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     */
    public function setRateRequest(Mage_Shipping_Model_Rate_Request $request)
    {
	/** Init FedEx web services */
	$r = $this->_initWebServices();	

	/** Set default packages */
	$r->setDefaultPackages($this->getDefaultPackages());

        /** Set limit method */
        if ($request->getLimitMethod()) {
            $r->setService($request->getLimitMethod());
        }

	/** Parse and set items */
	$r->setItems($this->_getItems($request->getAllItems()));	

	/** Set origin country */
        if ($request->getOrigCountry()) {
            $origCountry = $request->getOrigCountry();
        } 
	else {
            $origCountry = Mage::getStoreConfig('shipping/origin/country_id', $this->getStore());
        }
        $r->setOrigCountry(Mage::getModel('directory/country')->load($origCountry)->getIso2Code());

	/** Set origin region id */
	$r->setOrigRegionId(Mage::getStoreConfig('shipping/origin/region_id'));

	/** Set origin region code */
        $r->setOrigRegionCode(Mage::getModel('directory/region')->load($r->getOrigRegionId())->getCode());

	/** Set origin postal code */
        if ($request->getOrigPostcode()) {
            $r->setOrigPostcode($request->getOrigPostcode());
        } 
	else {
            $r->setOrigPostcode(Mage::getStoreConfig('shipping/origin/postcode', $this->getStore()));
        }

	/** Set origin city */
        if ($request->getOrigCity()) {
            $r->setOrigCity($request->getOrigCity());
        }
	else {
            $r->setOrigCity(Mage::getStoreConfig('shipping/origin/city', $this->getStore()));
        }

	/** Get shipper streetlines */
	$shipperstreetlines = array(Mage::getStoreConfig('shipping/origin/address1'));
        if (Mage::getStoreConfig('shipping/origin/address2') != '') { $shipperstreetlines[] = Mage::getStoreConfig('shipping/origin/address2'); }
        if (Mage::getStoreConfig('shipping/origin/address3') != '') { $shipperstreetlines[] = Mage::getStoreConfig('shipping/origin/address3'); }

	/** Set origin street */
	$r->setOrigStreet($shipperstreetlines);

	/** Set destination street */
	if ($request->getDestStreet()) {
	    $r->setDestStreet($request->getDestStreet());
	}

	/** Set destination city */
	if ($request->getDestCity()) {
	    $r->setDestCity($request->getDestCity());
	}

	/** Set destination region code */
	if ($request->getDestRegionCode() && strlen($request->getDestRegionCode()) === 2) {
	    $r->setDestRegionCode($request->getDestRegionCode());
	}

	/** Set destination postal code */
        if ($request->getDestPostcode()) {
            $r->setDestPostcode($request->getDestPostcode());
        }

	/** Set destination country */
	if ($request->getDestCountryId()) {
            $r->setDestCountry(Mage::getModel('directory/country')->load($request->getDestCountryId())->getIso2Code());
        } 
	else {
            $r->setDestCountry(Mage::getModel('directory/country')->load(self::USA_COUNTRY_ID)->getIso2Code());
        }
	
	/** Check residential status */
	if ($this->getConfigData('address_validation') 
		&& ($r->getDestCountry() == 'US') && $r->getDestStreet() && $r->getDestPostcode()) {
	    $r->setResidential($this->getResidential($r->getDestStreet(), $r->getDestPostcode()));
	}
	else {
	    $r->setResidential($this->getConfigData('residence_delivery'));
	}

        /** Set package weight */
        $r->setPackageWeight($request->getPackageWeight());

        /** Set free method weight */
        if ($request->getFreeMethodWeight() != $request->getPackageWeight()) {
            $r->setFreeMethodWeight($request->getFreeMethodWeight());
        }
        else
        {
            $r->setFreeMethodWeight($request->getPackageWeight());
        }

        foreach ($r->getItems() as $item)
        {
            if ($item['free_shipping'])
            {
                $r->setFreeMethodWeight((int) ($r->getFreeMethodWeight() - $item['weight']));
            }
        }


	if ($request->getPackages())
	{
	    $r->setPackages($request->getPackages());
	}

	$r->setValue($request->getPackageValue());						  /** Set package value */
	$r->setValueWithDiscount($request->getPackageValueWithDiscount());			  /** Set package value with discount */
	$r->setDropoffType($this->getUnderscoreCodeFromCode($this->getConfigData('dropoff')));	  /** Set FedEx dropoff type */
	
        $this->_rateRequest = $r;
        
        return $this;
    }

      
    /**
     * Get quotes
     *
     * @return mixed
     */
    protected function _getWsdlQuotes()
    {

	$r = $this->_rateRequest;

	$result = Mage::getModel('shipping/rate_result'); /** Get rate result model */

        try { $client = new SoapClient($r->getWsdlPath() . "RateService_v9.wsdl"); }		/** Instantiate SOAP client */
	catch (Exception $ex) { Mage::throwException($ex->getMessage()); }			/** If SOAP fails, throw exception */

	$request['WebAuthenticationDetail']['UserCredential'] ['Key'] = $r->getFedexApiKey();			    /** Set request key */
	$request['WebAuthenticationDetail']['UserCredential']['Password'] = $r->getFedexApiPassword();		    /** Set request password */
	$request['ClientDetail']['AccountNumber'] = $r->getFedexAccount();					    /** Set request account */
	$request['ClientDetail']['MeterNumber']   = $r->getFedexMeter();					    /** Set request meter */
	$request['TransactionDetail']['CustomerTransactionId'] = ' *** Rate Request v9 using PHP ***';		    /** Set transaction detail */
	$request['Version'] = array('ServiceId'	=> 'crs', 'Major' => '9', 'Intermediate' => '0', 'Minor' => '0');   /** Set version */
	$request['ReturnTransitAndCommit'] = true;								    /** Return transit and commit */	
	$request['RequestedShipment']['DropoffType'] = $r->getDropoffType();					    /** Set dropoff type */
        $request['RequestedShipment']['ShipTimestamp'] = date('c');						    /** Set timestamp */
	$request['RequestedShipment']['RateRequestTypes'] = $this->getConfigData('rate_type');			    /** Set rate request type */

	// Enable saturday delivery
	if ($this->getConfigData('saturday_delivery'))
	{
	    $request['RequestedShipment']['SpecialServicesRequested']['SpecialServiceTypes'] = array('SATURDAY_DELIVERY');
	}

        /** Smartpost */
        if ($this->getConfigData('enable_smartpost'))
        {            
	    $request['RequestedShipment']['SmartPostDetail'] = array(
		'Indicia'	       => $this->getConfigData('smartpost_indicia_type'),	    /** Indicia */
		'AncillaryEndorsement' => $this->getConfigData('smartpost_ancillary_endorsement'),  /** Ancillary endorsement */
		'HubId'		       => $this->getConfigData('smartpost_hub_id'),		    /** HubId */
		'SpecialServices'      => 'USPS_DELIVERY_CONFIRMATION');			    /** Special services */

	    /** If customer manifest id is set (rarely */
	    if ($this->getConfigData('smartpost_customer_manifest_id'))
            {
		/** Set customer manifest id */
                $request['RequestedShipment']['SmartPostDetail']['CustomerManifestId'] = $this->getConfigData('smartpost_customer_manifest_id');
            }
        }
        
	/** If third party payer */
        if ($this->getConfigData('third_party'))
        {
	     /** Set third party payment type */
	    $request['RequestedShipment']['ShippingChargesPayment']['PaymentType'] = 'THIRD_PARTY';

	    /** Set third party account and country */
	    $request['RequestedShipment']['ShippingChargesPayment']['Payor'] = array(
		'AccountNumber' => $this->getConfigData('third_party_fedex_account'),
		'CountryCode'   => $this->getConfigData('third_party_fedex_account_country'));
        }
        else
        {
	    /** Set sender payment type */
	    $request['RequestedShipment']['ShippingChargesPayment']['PaymentType'] = 'SENDER';

	    /** Set payor account and country */
	    $request['RequestedShipment']['ShippingChargesPayment']['Payor'] = array(
		'AccountNumber' => $r->getFedexAccount(),
		'CountryCode'   => $this->getConfigData('account_country'));
        }

	/** Set shipper address array */
        $request['RequestedShipment']['Shipper']['Address'] = array(
                'StreetLines'	      => $r->getOrigStreet(),		/** Set shipper street */
                'City'	              => $r->getOrigCity(),		/** Set shipper city */
                'StateOrProvinceCode' => $r->getOrigRegionCode(),	/** Set shipper state/province */
                'PostalCode'	      => $r->getOrigPostcode(),		/** Set shipper postal code */
                'CountryCode'	      => $r->getOrigCountry());		/** Set shipper country code */

	/** Set streetlines */
        if ($r->getDestStreet())
	{
	    $request['RequestedShipment']['Recipient']['Address']['StreetLines'] = $r->getDestStreet();
	}

	/** Set destination city */
	if ($r->getDestCity())
	{
	    $request['RequestedShipment']['Recipient']['Address']['City'] = $r->getDestCity();
	}

	/** Set postal code */
        if ($r->getDestPostcode())
	{
	    $request['RequestedShipment']['Recipient']['Address']['PostalCode'] = $r->getDestPostcode();
	}

	/** Set region code */
	if ($r->getDestRegionCode())
	{ 
	    $request['RequestedShipment']['Recipient']['Address']['StateOrProvinceCode'] = $r->getDestRegionCode();
	}

	if ($r->getDestCountry() == 'US')
	{
	    $request['RequestedShipment']['Recipient']['Address']['Residential'] = $r->getResidential();
	}

	/** Set country code */
        $request['RequestedShipment']['Recipient']['Address']['CountryCode'] = $r->getDestCountry();	

	/** If items are available */
	if ($r->getItems())
	{	    
	    if ($r->getPackages())
	    {			    
		$packages = $r->getPackages();
	    }
	    else
	    {
		$packages = $this->estimatePackages($r->getItems(), $r->getDefaultPackages());
	    }
	    if ($this->getPackingException())
	    {
		$error = Mage::getModel('shipping/rate_result_error');  /** Set error object */
		$error->setCarrier('fedex');				/** Set carrier */
		$error->setCarrierTitle($this->getConfigData('title')); /** Set carrier title */
		$error->setErrorMessage($this->getPackingException());	/** Set error message */
		$result->append($error);				/** Append error */

		return $result;
	    }
	    if (isset($packages))
	    {
		/** Debug to firebug */
		if (Mage::getStoreConfig('carriers/fedex/debug_firebug')) {
		    Mage::Helper('shipsync')->log($packages);
		}

		$i=0;                

		/** Iterate through packages */
		foreach($packages as $package)
		{
		    /** If products are measured in grams */
		    if ($this->getWeightUnits() == 'G')
		    {
			/** Set package weight in kilograms */
			$package['weight'] = $package['weight'] * 0.001;

			/** Set weight unit to KG */
			$weightUnit = 'KG';
		    }
		    else
		    {
			/** Otherwise, set weight unit to configuration value */
			$weightUnit = $this->getWeightUnits();
		    }                    

		    $weight = (round($package['weight'], 1) > 0) ? round($package['weight'], 1) : 0.1;	/** Round weight */
		    $length = (round($package['length']) > 0)    ? round($package['length'])    : 1;	/** Round length */
		    $width  = (round($package['width']) > 0)     ? round($package['width'])     : 1;	/** Round width */
		    $height = (round($package['height']) > 0)    ? round($package['height'])    : 1;	/** Round height */

		    /** If SmartPost is enabled, set minimum weight of 1lb/1kg */
		    if (Mage::getStoreConfig('carriers/fedex/enable_smartpost') && ($weight < 1)) { $weight = 1; }		    

		    /** Set package */
		    $request['RequestedShipment']['RequestedPackageLineItems'][$i]['Weight'] = array('Value' => $weight, 'Units' => $weightUnit);


		    // Send insured value
		    if ($this->getConfigData('rating_insured_value'))
		    {
			$package_value = 0.0;

			// Iterate through package items
			foreach ($package['items'] as $item)
			{
			    $package_value += $item['value']; // Get value
			}

			$package_value = round($package_value, 1); // Round to two decimal places
			
			$request['RequestedShipment']['RequestedPackageLineItems'][$i]['InsuredValue']['Amount'] = $package_value;
			$request['RequestedShipment']['RequestedPackageLineItems'][$i]['InsuredValue']['Currency'] = $this->getCurrencyCode();
		    }		    

		    /** If dimensions are enabled */
		    if ($this->getConfigData('enable_dimensions'))
		    {			
			$request['RequestedShipment']['RequestedPackageLineItems'][$i]['Dimensions'] = array(
			    'Length' => $length, 'Width'  => $width, 'Height' => $height, 'Units' => $this->getDimensionUnits());
		    }

		    $i++;
		}

		$request['RequestedShipment']['PackageCount']  = $i;			    /** Set package count */
		$request['RequestedShipment']['PackageDetail'] = 'INDIVIDUAL_PACKAGES';	    /** Set package detail */
	    }
	    else
	    {

		$error = Mage::getModel('shipping/rate_result_error');  /** Set error object */
		$error->setCarrier('fedex');				/** Set carrier */
		$error->setCarrierTitle($this->getConfigData('title')); /** Set carrier title */
		$error->setErrorMessage('Unable to estimate packages');	/** Set error message */
		$result->append($error);				/** Append error */

		return $result;
	    }
	}
	else
	{
	    $error = Mage::getModel('shipping/rate_result_error');  /** Set error object */
            $error->setCarrier('fedex');			    /** Set carrier */
            $error->setCarrierTitle($this->getConfigData('title')); /** Set carrier title */
            $error->setErrorMessage('No items found to ship');	    /** Set error message */
            $result->append($error);				    /** Append error */

            return $result;
	}	

	/** Set package count */
	$this->_packageCount = $request['RequestedShipment']['PackageCount'];

	/** Debug to firebug */
        if (Mage::getStoreConfig('carriers/fedex/debug_firebug')) {
	    Mage::Helper('shipsync')->log($request);
        }

	/** Get rates */
        try { $response = $client->getRates($request); }

	/** Catch fault */
        catch (SoapFault $ex)
	{
	    $error = Mage::getModel('shipping/rate_result_error');  /** Set error object */
            $error->setCarrier('fedex');			    /** Set carrier */
            $error->setCarrierTitle($this->getConfigData('title')); /** Set carrier title */
            $error->setErrorMessage($ex->getMessage());		    /** Set error message */
            $result->append($error);				    /** Append error */

            return $result;
	}

	/** Debug to firebug */
        if (Mage::getStoreConfig('carriers/fedex/debug_firebug')) {
	    Mage::Helper('shipsync')->log($response);
        }
        
        /** Return response */
        return $this->_parseWsdlResponse($response);
    }
    

    /**
     * Estimate packages
     *
     * @param array $items
     * @param array $defaultPackages
     * @return array
     */
    public function estimatePackages($_items, $defaultPackages)
    {

        $i = 0;     // Package counter
        $s = 0;     // Special package counter
        
        // Unset 'special packaging'
        array_pop($defaultPackages);

        foreach ($_items as $key => &$item)
        {
            $item['item_number'] = $key + 1;

            $items[] = $item;
            
            // If item requires special packaging
            if ($item['special'])
	    {
                $this->setSpecial($special_packages, $item, $s);
                unset($items[$key]);
	    }
        }

        foreach ($items as $key => $item)
        {
            if (!isset($item['dimensions']) || $item['dimensions'] == null) {$item['volume'] = 0;}            

            // If package has not been started
            if (!isset($packages[$i]['weight']))
            {
                $packages[$i]['volume'] = 0;
                $this->initPackage($defaultPackages, $packages, $item, $i, true);
            }
            // If a package has been started
            else
            {
                //echo '<pre>'; print_r($item); echo '</pre>';
                $key = $this->findBestFit($packages, $item);

                if (isset($key))
                {
                    //echo '<pre>'; print_r($item); echo '</pre>';die;
                    
                    $packages[$key]['items'][]  = $item;
                    $packages[$key]['weight']  += $item['weight'];
                    $packages[$key]['volume']  += $item['volume'];
                    $packages[$key]['free_weight'] = $packages[$key]['free_weight'] - $item['weight'];
                    $packages[$key]['free_volume'] = $packages[$key]['free_volume'] - $item['volume'];
                }
                else
                {
                    $i++;
                    $this->initPackage($defaultPackages, $packages, $item, $i);
                }
            }
        }//die;
        
        if(isset($packages)) { $this->optimizePackages($defaultPackages, $packages); }        
        
        /** Check if special packages were created */
	if (isset($special_packages) && is_array($special_packages))
	{
	    /** If so, add them to the packages array */
	    foreach ($special_packages as $special_package) { $packages[] = $special_package; }
	}

	/** If no packages were created, return false */
	if (!is_array($packages)) { return false; }

	/** Return packages */
	return $packages;
    }
    
    public function optimizePackages($defaultPackages, &$packages)
    {
        foreach ($defaultPackages as $key => $defaultPackage) {$free_weights[] = $defaultPackage['weight'];}

        foreach ($defaultPackages as $key => $defaultPackage) {$free_volumes[] = $defaultPackage['volume'];}

        foreach ($packages as $key => &$package)
        {
            $i = 0;
            while($i < count($free_weights))
            {
                if(isset($package['weight']) && isset($package['volume']) &&
                            $package['weight'] < $free_weights[$i] && $package['volume'] < $free_volumes[$i] ||
                            isset($package['weight']) && !isset($package['volume']) && $package['weight'] < $free_weights[$i])
                {
                    $j = $i;
                    
                    $packages[$key]['type']               = $defaultPackages[$j]['title'];			   // Set package type
                    $packages[$key]['max_weight']         = $defaultPackages[$j]['weight'];                      // Set max package weight
                    $packages[$key]['max_volume']         = $defaultPackages[$j]['volume'];                      // Set max package volume
                    $packages[$key]['length']             = $defaultPackages[$j]['length'];                      // Set package length
                    $packages[$key]['width']              = $defaultPackages[$j]['width'];			   // Set package width
                    $packages[$key]['height']             = $defaultPackages[$j]['height'];                      // Set package height
                    $packages[$key]['free_weight']        = $defaultPackages[$j]['weight'] - $package['weight'];
                    $packages[$key]['free_volume']        = $defaultPackages[$j]['volume'] - $package['volume'];
                    $packages[$key]['baseline']           = $defaultPackages[$j]['baseline'];
                }
                $i++;
            }
            if(isset($package['weight']) && isset($package['baseline'])) { $package['weight'] = $package['weight'] + $package['baseline']; }
        }
    }

    /**
     * init package
     * 
     * If package has not been started, creates a new package to fill
     *
     * @param array $item
     * @param array $defaultPackages
     * @param array $packages
     * @param int $i
     * @param bool $init
     */
    public function initPackage(&$defaultPackages, &$packages, $item, $i, $init = false)
    {
        foreach ($defaultPackages as &$defaultPackage)
        {
            if($init)
            {
                $defaultPackage['free_weight'] = $defaultPackage['weight'] - $defaultPackage['baseline'];
                $defaultPackage['free_volume'] = $defaultPackage['volume'];
            }

            if($this->findFit($defaultPackage, $item))
            {
                $packages[$i]['free_weight'] = $defaultPackage['free_weight'];
                $packages[$i]['free_volume'] = $defaultPackage['free_volume'];
                $this->setPackage($defaultPackage, $packages, $item, $i);
                break;
            }
	    else
	    {
		$this->setPackingException('There are no configured packages available large enough to ship this item.');
	    }
        }
    }

    /**
     * Find Best Fit
     *
     * Finds package in which item fits with least amount of remaining space
     *
     * @param array $packages
     * @param array $item
     *
     * @return index
     */
    public function findBestFit($packages, $item)
    {
        foreach ($packages as $key => $package)
        { 
            if ($this->findFit($package, $item))
            {
                $free_weights[$key] = $package['free_weight'];
                $free_volumes[$key] = $package['free_volume'];
            }
        }

        return (isset($free_weights) && isset($free_volumes) ? $this->minKey($free_weights, $free_volumes) : null);

    }

    /**
     * Min Key
     *
     * Compares two arrays, and returns the key where boths arrays have a minimum value, otherwise returns null
     * 
     * @param array $array
     * @param array $cmpArray
     * 
     * @return index
     */
    public function minKey($array, $cmpArray)
    {
        foreach ($array as $key => $val)
        {
            if ($val == min($array) && $cmpArray[$key] == min($cmpArray)) {return $key;}
            else {return null;}
        }
    }

    /**
     * Find Fit
     *
     * Finds if item fits in package, handles items with dimension and without
     * 
     * @param array $package
     * @param array $item
     * 
     * @return bool
     */
    public function findFit($package, $item)
    {
        if ($item['dimensions'])
        {
            if(($this->findFitWeight($package,$item)) && ($this->findFitVolume($package,$item))) {return true;}
            else {return false;}
        }
        else
        {
            return $this->findFitWeight($package,$item);
        }
    }

    /**
     * Find Fit Volume
     * 
     * Determines if item with dimension fits in package
     *
     * @param array $item
     * @param array $package
     * @return bool
     */
    public function findFitVolume($package, $item)
    {
        if ($item['volume'] <= $package['free_volume']) {return true;}
        else {return false;}
    }

    /**
     * Find Fit Weight
     *
     * Determines if item with weight fits in package
     *
     * @param array $item
     * @param array $package
     * @return bool
     */
    public function findFitWeight($package, $item)
    {
        if ($item['weight'] <= $package['free_weight']) {return true;}
        else {return false;}
    }

    /**
     * Set Special
     * 
     * Sets the array containing special packaging packages
     *
     * @param array $special_packages
     * @param array $item
     * @param int   $s
     */
    public function setSpecial(&$special_packages, &$item, &$s)
    {
        $special_packages[$s]['items'][0] = $item;		  // Add item to package
        $special_packages[$s]['weight']	  = $item['weight'];	  // Set item weight
        $special_packages[$s]['type']	  = 'Special Packaging';  // Set package type

        if ($item['dimensions'])
        {
            $special_packages[$s]['length'] = $item['length'];    // Set package length
            $special_packages[$s]['width']  = $item['width'];     // Set package width
            $special_packages[$s]['height'] = $item['height'];    // Set package height
            $special_packages[$s]['volume'] = $item['volume'];    // Set package volume
        }

        $s++; //Increment special package counter
    }

    /**
     * Set Package
     * 
     * Sets package with default package attributes
     *
     * @param array $defaultPackage
     * @param array $packages
     * @param array $item
     * @param int   $i  
     */
    public function setPackage($defaultPackage, &$packages, $item, $i)
    {
        $packages[$i]['type']               = $defaultPackage['title'];			     // Set package type
        $packages[$i]['max_weight']         = $defaultPackage['weight'];		     // Set max package weight
        $packages[$i]['max_volume']         = $defaultPackage['volume'];		     // Set max package volume
        $packages[$i]['items'][]            = $item;					     // Add item to package
        $packages[$i]['weight']             = $item['weight'] + $defaultPackage['baseline']; // Set package initial weight
        $packages[$i]['length']             = $defaultPackage['length'];		     // Set package length
        $packages[$i]['width']              = $defaultPackage['width'];			     // Set package width
        $packages[$i]['height']             = $defaultPackage['height'];		     // Set package height
        $packages[$i]['free_weight']        = $packages[$i]['free_weight'] - $item['weight'];
        $packages[$i]['free_volume']        = $packages[$i]['free_volume'] - $item['volume'];
       
        /** Set package volume */
        if (isset($item['dimensions'])) { $packages[$i]['volume'] =+ $item['volume']; }
        else { $packages[$i]['volume'] = 0; }
    }


    /**
     * Parse wsdl response
     *
     * @param mixed $response
     * @return mixed
     */
    protected function _parseWsdlResponse($response)
    {
        $r = $this->_rateRequest;

	/** Get allowed methods */
        $allowedMethods = explode(",", $this->getConfigData('allowed_methods'));
        
        /** Iterate through allowed methods */
        foreach ($allowedMethods as $method)
	{
	    /** Get underscore from code */
	    $parsedAllowedMethods[] = $this->getUnderscoreCodeFromCode($method);
	}
        
        /** Get rate result model */
        $result = Mage::getModel('shipping/rate_result');

        /** If error response */
        if ($response->HighestSeverity == 'ERROR' || $response->HighestSeverity == 'FAILURE' ||
	   ($response->HighestSeverity == 'WARNING' && !isset($response->RateReplyDetails)))
        {
            $msg = '';

            /** If multiple notifications */
            if (is_array($response->Notifications)) {

		/** Iterate through notifications and set msg */
                foreach ($response->Notifications as $notification) 
		{
		    $msg .= $notification->Severity . ': ';

		    if (($notification->Message == 'General Error') && $this->getConfigData('test_mode')) {
			$msg .= 'FedEx Testing servers are temporarily unavailable. Please try again in a few moments.<br />';
		    }
		    else { $msg .= $notification->Message . '<br />'; }
		}
            }
            else
	    {
		$msg .= $response->Notifications->Severity . ': ';

		if (($response->Notifications->Message == 'General Error') && $this->getConfigData('test_mode')) {
		    $msg .= 'FedEx Testing servers are temporarily unavailable. Please try again in a few moments.<br />';
		}
		else { $msg .= $response->Notifications->Message . '<br />'; }
	    }
            
            if ($this->getConfigData('showmethod'))
	    {
		$error = Mage::getModel('shipping/rate_result_error');	    /** Get rate result error object */
		$error->setCarrier('fedex');				    /** Set carrier */
		$error->setCarrierTitle($this->getConfigData('title'));	    /** Set carrier title */
		$error->setErrorMessage($msg);				    /** Set error message */
		$result->append($error);				    /** Append error to result */
	    }

            return $result;
        }
	elseif (!isset($response->RateReplyDetails))
	{
	    $error = Mage::getModel('shipping/rate_result_error');  /** Get rate result error object */
            $error->setCarrier('fedex');			    /** Set carrier */
            $error->setCarrierTitle($this->getConfigData('title')); /** Set carrier title */
            $error->setErrorMessage('Error: Empty rate result.  Please contact your system administrator.');
            $result->append($error);				    /** Append error to result */

            return $result;
	}

	if (!is_array($response->RateReplyDetails)) { $rateReplyDetails = array($response->RateReplyDetails); }
	else					    { $rateReplyDetails = $response->RateReplyDetails; }

	$i=0;
	
	$rateType = $this->getConfigData('rate_type');
        
	/** Iterate through rates */
	foreach ($rateReplyDetails as $rateReply)
	{
	    /** Check if service type is valid */
	    if (isset($rateReply->ServiceType) && in_array($rateReply->ServiceType, $parsedAllowedMethods))
	    {
		/** Remove underscores */
		$_serviceType = str_replace('_', '', $rateReply->ServiceType);

		$rateResult[$i]['serviceType'] = $_serviceType; /** Set service type */
		$rateResult[$i]['method']      = $_serviceType; /** Set method */

		/** Set method title */
		$rateResult[$i]['methodTitle'] = $this->getCode('method', $rateReply->ServiceType, true);

		/** If timestamp is enabled */
		if ($this->getConfigData('show_timestamp'))
		{
		    /** If timestamp data is available */
		    if (isset($rateReply->DeliveryTimestamp))
		    {
			/** Append timestamp to rate result */
			$rateResult[$i]['timestamp'] = ' (' . date("m/d g:ia", strtotime($rateReply->DeliveryTimestamp)) . ')';
		    }
		    /** If transit time is available */
		    elseif (isset($rateReply->CommitDetails->TransitTime))
		    {
			$transitTime = ucwords(strtolower(str_replace('_', ' ', $rateReply->CommitDetails->TransitTime)));

			$rateResult[$i]['timestamp'] = ' (' . $transitTime . ')';
		    }
		}

		if (is_array($rateReply->RatedShipmentDetails))
		{
		    foreach ($rateReply->RatedShipmentDetails as $ratedShipmentDetail)
		    {			
			$_rateType = $ratedShipmentDetail->ShipmentRateDetail->RateType;

			if (($_rateType == 'PAYOR_' . $rateType . '_SHIPMENT') || ($_rateType == 'RATED_' . $rateType . '_SHIPMENT') ||
			    ($_rateType == 'PAYOR_' . $rateType . '_PACKAGE')  || ($_rateType == 'RATED_' . $rateType . '_PACKAGE'))
			{
			    $shipmentRateDetail = $ratedShipmentDetail->ShipmentRateDetail; break;
			}
		    }
		}
		elseif (isset($rateReply->RatedShipmentDetails->ShipmentRateDetail))
		{
		    $shipmentRateDetail = $rateReply->RatedShipmentDetails->ShipmentRateDetail;
		}

		/** Set rate */
		$rate = $shipmentRateDetail->TotalNetCharge->Amount;

		/** If subtract VAT */
		if ($this->getConfigData('subtract_vat') > 0)
		{		    
		    /** Deduct from rate */
		    $rate = $rate / (1 + ($this->getConfigData('subtract_vat') / 100));
		}

		/** If handling fee is set */
		if ($this->getConfigData('handling_fee') > 0)
		{
		    /** Set handling fee */
		    $handling_fee = $this->getConfigData('handling_fee');

		    /** If handling fee is per order */
		    if ($this->getConfigData('handling_action') == 'P')
		    {
			/** Multiply handling fee * package count */
			$handling_fee = $handling_fee * $this->_packageCount;
		    }

		    /** If handling type is fixed */
		    if ($this->getConfigData('handling_type') == 'F')
		    {
			/** Add handling fee to rate */
			 $rate = $rate + $handling_fee;
		    }
		    /** Otherwise, handling type is percentage */
		    else
		    {
			/** Logarithmic handling */
			if (Mage::getStoreConfig('carriers/fedex/handling_shelf') && $rate >= 10)
			{
			    $handling_fee = $handling_fee / log($rate);	    /** Scale fee logarithmically */
			    $rate = $rate + ($rate * ($handling_fee/100));  /** Add fee to rate */
			}
			/** Else, simply calculate percentage */
			else
			{
			    $rate = $rate + ($rate * ($handling_fee/100));  /** Add fee to rate */
			}
		    }
		}

		$rateResult[$i]['cost'] = $rate;    /** Set cost */
		$rateResult[$i]['price'] = $rate;   /** Set price */
		
		# Increment counter
		$i++;
	    }
	}
            
	/** Calculate free shipping before discounts */
	if ($this->getConfig('free_shipping_discounts'))
	{
	    /** Set value */
	    $value = $this->_rateRequest->getValue();
	}
	/** Else, calculate after discounts */
	else
	{
	    /** Set value */
	    $value = $this->_rateRequest->getValueWithDiscount();
	}

	/** If rate result is not set */
	if (!isset($rateResult))
	{
	    $error = Mage::getModel('shipping/rate_result_error');	 /** Get rate result error object */
            $error->setCarrier('fedex');				 /** Set carrier */
            $error->setCarrierTitle($this->getConfigData('title'));	 /** Set carrier title */
            $error->setErrorMessage('No applicable rates available');    /** Set error message */
            $result->append($error);					 /** Append error to result */

            return $result;
	}

	/** Iterate through rate results to find free shipping method */
	foreach ($rateResult as $_rate)
	{
	    $rate = Mage::getModel('shipping/rate_result_method');	/** Get rate result object */
	    $rate->setCarrier('fedex');					/** Set carrier */
	    $rate->setCarrierTitle($this->getConfigData('title'));	/** Set carrier title */
	    $rate->setMethod($_rate['method']);				/** Set carrier method */

            /** Get free method */
            $free_method = $this->getConfigData('free_method');

            /** Set alternate free methods */
            $alt_free_method = "";
            if ($free_method == "GROUNDHOMEDELIVERY") { $alt_free_method = "FEDEXGROUND"; }
            if ($free_method == "FEDEXGROUND")        { $alt_free_method = "GROUNDHOMEDELIVERY"; }

	    /** If free shipping is enabled */
	    if ($this->getConfigData('free_shipping_enable')
                    && (($_rate['method'] == $free_method) || ($_rate['method'] == $alt_free_method)))
	    {
                if ($value > $this->getConfigData('free_shipping_subtotal'))
                {
		    $rate->setCost($_rate['cost']);	/** Set cost */
		    $rate->setPrice('0');		/** Set price to 0 */

		    /** If show_timestamp is enabled and timestamp data is available */
		    if ($this->getConfigData('show_timestamp') && isset($_rate['timestamp']))
		    {
			/** Add timestamp string to method title */
			$rate->setMethodTitle('Free Shipping (' . $_rate['methodTitle'] . $_rate['timestamp'] . ')');
		    }
		    /** Else, simply set free method title */
		    else { $rate->setMethodTitle('Free Shipping (' . $_rate['methodTitle'] . ')'); }

		    /** Set method description */
		    $rate->setMethodDescription($rate->getMethodTitle());
		}
                elseif ($r->getFreeMethodWeight() == 0)
                {
                    $rate->setCost($_rate['cost']);	/** Set cost */
                    $rate->setPrice('0');		/** Set price to 0 */

                    /** If show_timestamp is enabled and timestamp data is available */
                    if ($this->getConfigData('show_timestamp') && isset($_rate['timestamp']))
                    {
                        /** Add timestamp string to method title */
                        $rate->setMethodTitle('Free Shipping (' . $_rate['methodTitle'] . $_rate['timestamp'] . ')');
                    }
                    /** Else, simply set free method title */
                    else { $rate->setMethodTitle('Free Shipping (' . $_rate['methodTitle'] . ')'); }

                    /** Set method description */
                    $rate->setMethodDescription($rate->getMethodTitle());
                }
                elseif ($r->getFreeMethodWeight() != $r->getPackageWeight())
                {
                    $discountPercent = $r->getFreeMethodWeight() / $r->getPackageWeight();

                    $rate->setCost($_rate['cost']);                             /** Set cost */
                    $rate->setPrice($_rate['price'] * $discountPercent);	/** Set price to 0 */

                    /** If show_timestamp is enabled and timestamp data is available */
                    if ($this->getConfigData('show_timestamp') && isset($_rate['timestamp']))
                    {
                        /** Add timestamp string to method title */
                        $rate->setMethodTitle('Discounted (' . $_rate['methodTitle'] . $_rate['timestamp'] . ')');
                    }
                    /** Else, simply set free method title */
                    else { $rate->setMethodTitle('Discounted (' . $_rate['methodTitle'] . ')'); }

                    /** Set method description */
                    $rate->setMethodDescription($rate->getMethodTitle());
                }
		/** If not eligible */
		else
		{
		    $rate->setCost($_rate['cost']);	/** Set cost */
		    $rate->setPrice($_rate['price']);   /** Set price */

		    /** If show_timestamp is enabled and timestamp data is available */
		    if ($this->getConfigData('show_timestamp') && isset($_rate['timestamp']))
		    {
			$rate->setMethodTitle($_rate['methodTitle'] . $_rate['timestamp']);
		    }
		    /** Else, simply set method title */
		    else { $rate->setMethodTitle($_rate['methodTitle']); }

		    /** Set method description */
		    $rate->setMethodDescription($rate->getMethodTitle());
		}
	    }
	    /** If free shipping is not enabled */
	    else
	    {
		$rate->setCost($_rate['cost']);	    /** Set cost */
		$rate->setPrice($_rate['price']);   /** Set price */

		/** If show_timestamp is enabled and timestamp data is available */
		if ($this->getConfigData('show_timestamp') && isset($_rate['timestamp']))
		{
		    $rate->setMethodTitle($_rate['methodTitle'] . $_rate['timestamp']);
		}
		/** Else, simply set method title */
		else { $rate->setMethodTitle($_rate['methodTitle']); }

		/** Set method description */
		$rate->setMethodDescription($rate->getMethodTitle());
	    }

	    /** Append results to the rate object */
	    $result->append($rate);
	}

	return $result;
    }
    

     /**
     *  Return FedEx currency ISO code by Magento Base Currency Code
     *
     *  @return   string 3-digit currency code
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
            'FEDEXBOXSMALL' => 'FEDEX_BOX_SMALL',
            'FEDEXBOXMED' => 'FEDEX_BOX_MED',
            'FEDEXBOXLARGE' => 'FEDEX_BOX_LARGE',
            'FEDEXTUBE' => 'FEDEX_TUBE',
            'FEDEX10KGBOX' => 'FEDEX_10KG_BOX',
            'FEDEX25KGBOX' => 'FEDEX_25KG_BOX',
            'YOURPACKAGING' => 'YOUR_PACKAGING',
            'SMARTPOST' => 'SMART_POST'
        );
        return $codes[$code];
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
        /** Inches TODO: delete me? */
        if ($this->getConfigData('dimension_units') == 'IN')
        {
            $cdef_height_in = round($this->getConfigData('default_height'), 2);
            $cdef_width_in = round($this->getConfigData('default_width'), 2);
            $cdef_length_in = round($this->getConfigData('default_length'), 2);
            $cdef_height_cm = round($this->getConfigData('default_height') * 2.54, 2);
            $cdef_width_cm = round($this->getConfigData('default_width') * 2.54, 2);
            $cdef_length_cm = round($this->getConfigData('default_length') * 2.54, 2);
        }
	/** Centimeters */
        else
        {
          $cdef_height_in = round($this->getConfigData('default_height') * 0.393700787, 2);
          $cdef_width_in = round($this->getConfigData('default_width') * 0.393700787, 2);
          $cdef_length_in = round($this->getConfigData('default_length') * 0.393700787, 2);
          $cdef_height_cm = round($this->getConfigData('default_height'), 2);
          $cdef_width_cm = round($this->getConfigData('default_width'), 2);
          $cdef_length_cm = round($this->getConfigData('default_length'), 2);
        }

	/** Codes underscored */
        $codes_underscore = array(
	    /** Shipping methods */
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
	    /** Dropoff types */
            'dropoff' => array(
                'REGULAR_PICKUP' => Mage::helper('usa')->__('Regular Pickup'),
                'REQUEST_COURIER' => Mage::helper('usa')->__('Request Courier'),
                'DROP_BOX' => Mage::helper('usa')->__('Drop Box'),
                'BUSINESS_SERVICE_CENTER' => Mage::helper('usa')->__('Business Service Center'),
                'STATION' => Mage::helper('usa')->__('Station'),
            ),
	    /** Packaging types TODO: deprecated? Add these back? */
            'packaging' => array(
                'FEDEX_ENVELOPE' => Mage::helper('usa')->__('FedEx Envelope'),
                'FEDEX_PAK' => Mage::helper('usa')->__('FedEx Pak'),
                'FEDEX_BOX_SMALL' => Mage::helper('usa')->__('FedEx Box Small'),
                'FEDEX_BOX_MED' => Mage::helper('usa')->__('FedEx Box Medium'),
                'FEDEX_BOX_LARGE' => Mage::helper('usa')->__('FedEx Box Large'),
                'FEDEX_TUBE' => Mage::helper('usa')->__('FedEx Tube'),
                'FEDEX_10KG_BOX' => Mage::helper('usa')->__('FedEx 10kg Box'),
                'FEDEX_25KG_BOX' => Mage::helper('usa')->__('FedEx 25kg Box'),
                'YOUR_PACKAGING' => Mage::helper('usa')->__('Your Packaging'),
            )
        );

	/** Codes, without underscores */
        $codes = array(
	    /** Shipping methods */
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
	    /** Dropoff types */
            'dropoff' => array(
                'REGULARPICKUP' => Mage::helper('usa')->__('Regular Pickup'),
                'REQUESTCOURIER' => Mage::helper('usa')->__('Request Courier'),
                'DROPBOX' => Mage::helper('usa')->__('Drop Box'),
                'BUSINESSSERVICECENTER' => Mage::helper('usa')->__('Business Service Center'),
                'STATION' => Mage::helper('usa')->__('Station'),
            ),
	    /** Packaging types */
            'packaging' => array(
                'FEDEXENVELOPE' => Mage::helper('usa')->__('FedEx Envelope'),
                'FEDEXPAK' => Mage::helper('usa')->__('FedEx Pak'),
                'FEDEXBOXSMALL' => Mage::helper('usa')->__('FedEx Box Small'),
                'FEDEXBOXMED' => Mage::helper('usa')->__('FedEx Box Medium'),
                'FEDEXBOXLARGE' => Mage::helper('usa')->__('FedEx Box Large'),
                'FEDEXTUBE' => Mage::helper('usa')->__('FedEx Tube'),
                'FEDEX10KGBOX' => Mage::helper('usa')->__('FedEx 10kg Box'),
                'FEDEX25KGBOX' => Mage::helper('usa')->__('FedEx 25kg Box'),
                'YOURPACKAGING' => Mage::helper('usa')->__('Your Packaging'),
            ),
	    /** Rate types */
            'rate_type' => array(
                'LIST' => Mage::helper('usa')->__('List Rates'),
                'ACCOUNT' => Mage::helper('usa')->__('Account Rates'),
            ),
	    /** Weight units */
            'weight_unit' => array(
                'LB' => Mage::helper('usa')->__('Pounds'),
                'KG' => Mage::helper('usa')->__('Kilograms'),
		'G'  => Mage::helper('usa')->__('Grams')
            ),
	    /** Packing styles */
            'packing_style' => array(
		'FIRSTFITASC' => Mage::helper('usa')->__('First-Fit Ascending'),
		'FIRSTFITDESC' => Mage::helper('usa')->__('First-Fit Descending'),                
                'ONEPERPACKAGE' => Mage::helper('usa')->__('One Item Per Package'),
                'MAGENTODEFAULT' => Mage::helper('usa')->__('Magento Default')
            ),
	    /** Label image types */
            'label_image_type' => array(
                'PDF' => Mage::helper('usa')->__('Adobe PDF'),
                'PNG' => Mage::helper('usa')->__('PNG Image'),
                'EPL2' => Mage::helper('usa')->__('Zebra EPL2'),
                'ZPLII' => Mage::helper('usa')->__('Zebra ZPLII'),
                'DPL' => Mage::helper('usa')->__('Datamax DPL'),
            ),
	    /** Label stock types */
            'label_stock_type' => array(
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
                'STOCK_4X9_TRAILING_DOC_TAB' => Mage::helper('usa')->__('STOCK_4X9_TRAILING_DOC_TAB'),
            ),
	    /** Label orientation */
            'label_orientation' => array(
                'BOTTOM_EDGE_OF_TEXT_FIRST' => Mage::helper('usa')->__('Bottom edge of text first'),
                'TOP_EDGE_OF_TEXT_FIRST' => Mage::helper('usa')->__('Top edge of text first'),
            ),
	    /** Unit of dimension */
            'unit_of_dimension' => array(
                'IN' => Mage::helper('usa')->__('Inches'),
                'CM' => Mage::helper('usa')->__('Centimeters'),
            ),
	    /** Smartpost indicia type */
            'smartpost_indicia_type' => array(
                'MEDIA_MAIL' => Mage::helper('usa')->__('Media Mail'),
                'PARCEL_SELECT' => Mage::helper('usa')->__('Parcel Select'),
                'PRESORTED_BOUND_PRINTED_MATTER' => Mage::helper('usa')->__('Presorted Bound Printed Matter'),
                'PRESORTED_STANDARD' => Mage::helper('usa')->__('Presorted Standard')
            ),
	    /** Smartpost ancillary endorsement */
            'smartpost_ancillary_endorsement' => array(
                'ADDRESS_CORRECTION' => Mage::helper('usa')->__('Address Correction'),
                'CARRIER_LEAVE_IF_NO_RESPONSE' => Mage::helper('usa')->__('Carrier Leave If No Response'),
                'CHANGE_SERVICE' => Mage::helper('usa')->__('Change Service'),
                'FORWARDING_SERVICE' => Mage::helper('usa')->__('Forwarding Service'),
                'RETURN_SERVICE' => Mage::helper('usa')->__('Return Service')
            ),
	    /** Package dimensions (cm) */
            'package_dimensions_cm' => array(
                'FEDEX_ENVELOPE' => array(
                    'height' => 0,
                    'width' => 24.13,
                    'length' => 31.75,
                ),
                'FEDEX_PAK' => array(
                    'width' => 26.04,
                    'length' => 32.39,
                    'height' => 0,
                ),
                'FEDEX_BOX' => array(
                    'height' => 6.03,
                    'width' => 29.21,
                    'length' => 33.66,
                ),
                'FEDEX_TUBE' => array(
                    'height' => 15.24,
                    'width' => 15.24,
                    'length' => 96.52,
                ),
                'FEDEX_10KG_BOX' => array(
                    'height' => 25.88,
                    'width' => 32.86,
                    'length' => 40.16,
                ),
                'FEDEX_25KG_BOX' => array(
                    'height' => 33.5,
                    'width' => 42.07,
                    'length' => 54.77,
                ),
                'YOUR_PACKAGING' => array(
                    'height' => $cdef_height_cm,
                    'width' => $cdef_width_cm,
                    'length' => $cdef_length_cm,
                ),
            ),
	    /** Package dimensions (in) */
            'package_dimensions_in' => array(
                'FEDEX_ENVELOPE' => array(
                    'height' => 0,
                    'width' => 9.5,
                    'length' => 12.5,
                ),
                'FEDEX_PAK' => array(
                    'height' => 0,
                    'width' => 10.25,
                    'length' => 12.75,
                ),
                'FEDEX_BOX' => array(
                    'height' => 2.38,
                    'width' => 11.5,
                    'length' => 13.25,
                ),
                'FEDEX_TUBE' => array(
                    'height' => 6,
                    'width' => 6,
                    'length' => 38,
                ),
                'FEDEX_10KG_BOX' => array(
                    'height' => 15.81,
                    'width' => 12.94,
                    'length' => 40.16,
                ),
                'FEDEX_25KG_BOX' => array(
                    'height' => 13.19,
                    'width' => 16.56,
                    'length' => 21.56,
                ),
                'YOUR_PACKAGING' => array(
                    'height' => $cdef_height_in,
                    'width' => $cdef_width_in,
                    'length' => $cdef_length_in,
                ),
            ),
        );
        if ($underscore)
        {
            if (!isset($codes_underscore[$type])) { return false; }
            elseif ('' === $code) { return $codes_underscore[$type]; }
            
	    if (!isset($codes_underscore[$type][$code])) { return false; }
            else { return $codes_underscore[$type][$code]; }
        }
        else
        {
            if (!isset($codes[$type])) { return false; }
            elseif ('' === $code) { return $codes[$type]; }
            
	    if (!isset($codes[$type][$code])) { return false; }
            else { return $codes[$type][$code]; }
        }
    }


    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
	/** Get allowed methods */
        $allowed = explode(',', $this->getConfigData('allowed_methods'));

        $arr = array();

        /** Iterate through methods */
	foreach ($allowed as $k)
        {
	    /** Translate method and set */
            $arr[$k] = $this->getCode('method', $k);
        }
	
	if (is_array($arr)) { return $arr; }
	else		    { return false; }
    }


    /**
     * Create shipment
     *
     * @param object
     * @return object
     */
    public function createShipment($request)
    {        
	/** Check if method is active */
	if (!$this->getConfigFlag('active')) { return false; }
        
	/** Set ship request */
	$this->setShipRequest($request);

	/** Set result */
        $this->_result = $this->_createShipment();

	/** Return result */
	return $this->_result;
    }

    
    /**
     * Set shipment request
     * 
     * @param object
     * @return IllApps_Shipsync_Model_Shipping_Carrier_Fedex
     */
    public function setShipRequest($request)
    {
	/** Init web services */
	$r = $this->_initWebServices();

	$r->setOrderId($request->getOrderId());							    /** Set order id */
	$r->setOrder(Mage::getModel('sales/order')->loadByIncrementId($r->getOrderId()));	    /** Set order */
	$r->setStore($r->getOrder()->getStore());						    /** Set store */
	$r->setPackages($request->getPackages());						    /** Set packages */
	$r->setMethodCode($request->getMethodCode());						    /** Set method code */
	$r->setServiceType($this->getUnderscoreCodeFromCode($r->getMethodCode()));		    /** Set service type */
	$r->setDropoffType($this->getUnderscoreCodeFromCode($this->getConfigData('dropoff')));	    /** Set dropoff type */

	/** Set shipper company name */
	if ($this->getConfigData('shipper_company'))
		{ $r->setShipperCompany($r->getOrder()->getStoreName(1)); }
	else	{ $r->setShipperCompany($r->getOrder()->getStoreName(0)); }

	/** Set shipper streets */
	$shipper_streetlines[] = Mage::getStoreConfig('shipping/origin/address1');

        if (Mage::getStoreConfig('shipping/origin/address2') != '') { $shipper_streetlines[] = Mage::getStoreConfig('shipping/origin/address2'); }
        if (Mage::getStoreConfig('shipping/origin/address3') != '') { $shipper_streetlines[] = Mage::getStoreConfig('shipping/origin/address3'); }
	
	$r->setShipperStreetLines($shipper_streetlines);				/** Set shipper streetlines */
	$r->setShipperCity(Mage::getStoreConfig('shipping/origin/city'));		/** Set shipper city */
	$r->setShipperPostalCode(Mage::getStoreConfig('shipping/origin/postcode'));	/** Set shipper postal code */
	$r->setShipperCountryCode(Mage::getStoreConfig('shipping/origin/country_id'));	/** Set shipper country code */
	$r->setShipperPhone(Mage::getStoreConfig('shipping/origin/phone'));		/** Set shipper phone */	
	$r->setShipperStateOrProvinceCode(Mage::getModel('directory/region')		/** Set shipper state/province code */
		->load(Mage::getStoreConfig('shipping/origin/region_id'))->getCode());	
	$r->setRecipientAddress($request->getRecipientAddress());		        /** Set recipient address */
	$r->setInsureShipment($request->getInsureShipment());				/** Insure shipment */

	/** Set insurance amount */
	if ($request->getInsureAmount() != '')
		{ $r->setInsureAmount($request->getInsureAmount()); }
	else { $r->setInsureAmount(100); }

	/** Set required signature */
	if ($request->getRequireSignature())
		{ $r->setRequireSignature('DIRECT'); }
	else { $r->setRequireSignature('SERVICE_DEFAULT'); }

	/** Check residential status */
	if ($this->getConfigData('address_validation') && ($r->getRecipientAddress()->getCountryId() == 'US')) {
	    $r->setResidential($this->getResidential($r->getRecipientAddress()->getStreet(), $r->getRecipientAddress()->getPostcode()));
	}
	else {
	    $r->setResidential($this->getConfigData('residence_delivery'));
	}

	$this->_shipRequest = $r;

	return $this;
    }


    /**
     * Create shipment
     *
     * @return mixed
     */
    protected function _createShipment()
    {
	$r = $this->_shipRequest;
            
	/** Iterate through each package to ship */
        foreach ($r->getPackages() as $packageToShip)
        {
                            
            $shipResponse = $this->_sendShipmentRequest($packageToShip);    /** Send shipment request */
	    $shipResult   = $this->_parseShipmentResponse($shipResponse);   /** Parse response */

            /** Iterate through shipped packages */
	    foreach ($shipResult->getPackages() as $packageShipped)
            {
                /** Load order convertor model */
		$convertor = Mage::getModel('sales/convert_order');

		/** Convert order to shipment */
                $shipment = $convertor->toShipment($r->getOrder());

                foreach ($packageToShip->getItems() as $itemToShip)
                {
                    $orderItem = $r->getOrder()->getItemById($itemToShip['item_id']);       // Get order item.
		    $item = $convertor->itemToShipmentItem($orderItem);                     // Convert order item to shipment item
                    $item->setQty(1);                                                       // Set qty to 1 (..one item at a time)
                    $shipment->addItem($item);                                              // Add item to shipment
                }

                /*
                 * TODO: Add quantity count to shipment email.
                 *
                foreach($packageToShip->getItems() as $itemToShip)
                {
                    $quantity_count[$itemToShip['product_id']][] = $itemToShip;
                }

		foreach ($quantity_count as $itemGroup)
                {
                    echo $itemGroup[0]['item_id'];
                    $orderItem = $r->getOrder()->getItemById($itemGroup[0]['item_id']);	
		    $item = $convertor->itemToShipmentItem($orderItem);			
                    $item->setQty(count($itemGroup));					
                    $shipment->addItem($item);						
                }*/

		/** Get tracking object */
                $track = Mage::getModel('sales/order_shipment_track')
                    ->setTitle($this->getCode('method', $r->getServiceType(), true))	/** Set method title */
                    ->setCarrierCode('fedex')						/** Set carrier code */
                    ->setNumber($packageShipped['tracking_number'])			/** Set tracking number */
                    ->setShipment($shipment);
                    /** Set shipment */

                

                $shipment->addTrack($track);						/** Add tracking code */
                $shipment->register();							/** Register shipment */
		$shipment->getOrder()->setIsInProcess(true);				/** Set order to processing */

		/** Save transaction */
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($shipment)
                    ->addObject($shipment->getOrder())
                    ->save();

                /** Send shipment email TODO if isset: */
                if ($packageToShip->getData('confirmation'))
                {
                    $shipment->sendEmail();
                }
                
		/** Prepare package object */
                $pkg = Mage::getModel('shipping/shipment_package')
                    ->setOrderIncrementId($r->getOrder()->getIncrementId())			    /** Set order increment id */
                    ->setOrderShipmentId($shipment->getId())					    /** Set order shipment id */
                    ->setCarrier('fedex')							    /** Set carrier */
                    ->setCarrierShipmentId($shipResult->getShipmentIdentificationNumber())	    /** Set carrier shipment */
                    ->setWeightUnits($shipResult->getBillingWeightUnits())			    /** Set weight units */
                    ->setWeight($shipResult->getBillingWeight())				    /** Set billing weight */
                    ->setTrackingNumber($packageShipped['tracking_number'])			    /** Set tracking number */
                    ->setCurrencyUnits($shipResult->getCurrencyUnits())				    /** Set currency units */
                    ->setTransportationCharge($shipResult->getTransportationShippingCharges())	    /** Set transporatation charge */
                    ->setServiceOptionCharge($shipResult->getServiceOptionsShippingCharges())	    /** Set service option charge */
                    ->setShippingTotal($shipResult->getTotalShippingCharges())			    /** Set shipping total */
                    ->setNegotiatedTotal($shipResult->getNegotiatedTotalShippingCharges())	    /** Set negotiated total */
                    ->setLabelFormat($packageShipped['label_image_format'])			    /** Set label format */
                    ->setLabelImage($packageShipped['label_image'])				    /** Set label image */
                    ->setCodLabelImage($packageShipped['cod_label_image'])                          /** Set cod label image */
                    ->setDateShipped(date('Y-m-d H:i:s'))->save();				    /** Set date shipped */
                    
                $retval[] = $pkg;           
            }
        }
        return $retval;
    }


    /**
     * Send shipment request
     * 
     * @param object $package
     * @return object
     */
    protected function _sendShipmentRequest($package)
    {
	$r = $this->_shipRequest;

        try { $client = new SoapClient($r->getWsdlPath() . "ShipService_v9.wsdl"); }
	catch (Exception $ex) { Mage::throwException($ex->getMessage()); }
	
	$request['WebAuthenticationDetail']['UserCredential']['Key']      = $r->getFedexApiKey();	/** Set request key */
	$request['WebAuthenticationDetail']['UserCredential']['Password'] = $r->getFedexApiPassword();	/** Set request password */
	$request['ClientDetail']['AccountNumber'] = $r->getFedexAccount();				/** Set request account */
	$request['ClientDetail']['MeterNumber']   = $r->getFedexMeter();        			/** Set request meter */	
	
        /** Iterate through package items */
	foreach ($package->getItems() as $_item)
        {
            /** Load item by order item id */
	    $item = Mage::getModel('sales/order_item')->load($_item['item_id']);

	    /** Set contents to ship request */
            $contents[] = array(
                'ItemNumber' => $item['item_id'],
                'Description' => $item->getName(),
                'ReceivedQuantity' => 1);
        }

	/** Check if shipment is international */
	if ($r->getStore()->getConfig('shipping/origin/country_id') != $r->getRecipientAddress()->getCountryId())
        {
	    /** Set transaction type */
	    $transactionType = 'International';
	}
	/** Otherwise, shipment is domestic */
	else
	{
	    /** Set transaction type */
	    $transactionType = 'Domestic';
	}

	/** If service type is Home Delivery or Ground */
	if ($r->getServiceType() == 'GROUND_HOME_DELIVERY' || $r->getServiceType() == 'FEDEX_GROUND')
	{
	    /** Set transaction method */
	    $transactionMethod = 'Ground';
	}
	/** Otherwise, assume Express */
	else
	{
	    /** Set transaction method */
	    $transactionMethod = 'Express';
	}


	$request['TransactionDetail']['CustomerTransactionId'] = "*** $transactionMethod $transactionType Shipping Request v9 using PHP ***";
	$request['Version'] = array('ServiceId' => 'ship', 'Major' => '9', 'Intermediate' => '0', 'Minor' => '0');

	/** Shipper address */
	$shipper = array(
	    'Contact' => array(
		'CompanyName' => $r->getShipperCompany(),
		'PhoneNumber' => $r->getShipperPhone()),
	    'Address'     => array(
		'StreetLines'	      => $r->getShipperStreetLines(),
		'City'		      => $r->getShipperCity(),
		'StateOrProvinceCode' => $r->getShipperStateOrProvinceCode(),
		'PostalCode'	      => $r->getShipperPostalCode(),
		'CountryCode'	      => $r->getShipperCountryCode()));

	/** Recipient address */
	$recipient = array(
	    'Contact' => array(
		'PersonName'  => $r->getRecipientAddress()->getName(),		
		'PhoneNumber' => $r->getRecipientAddress()->getTelephone()),
		'Address' => array(
		    'StreetLines'	  => $r->getRecipientAddress()->getStreet(),
		    'City'		  => $r->getRecipientAddress()->getCity(),
		    'StateOrProvinceCode' => $r->getRecipientAddress()->getRegionCode(),
		    'PostalCode'	  => $r->getRecipientAddress()->getPostcode(),
		    'CountryCode'	  => $r->getRecipientAddress()->getCountryId(),
		    'Residential'	  => $r->getResidential()));

	if ($r->getRecipientAddress()->getCompany() != '')
	{
	    $recipient['Contact']['CompanyName'] = $r->getRecipientAddress()->getCompany();
	}
	
	/** If weight unit is 'G' */
	if ($this->getWeightUnits() == 'G')
	{
	    /** Set package weight in KG */
	    $package['weight'] = $package['weight'] * 0.001;

	    /** Set weight unit to KG */
	    $weightUnit = 'KG';
	}
	else
	{
	    /** Otherwise, set weight unit to configuration default */
	    $weightUnit = $this->getWeightUnits();
	}

	$package['weight'] = (round($package['weight'], 1) > 0) ? round($package['weight'], 1) : 0.1;	/** Round weight */
        $package['length'] = (round($package['length']) > 0)    ? round($package['length'])    : 1;	/** Round length */
	$package['width']  = (round($package['width']) > 0)     ? round($package['width'])     : 1;	/** Round width */
	$package['height'] = (round($package['height']) > 0)    ? round($package['height'])    : 1;	/** Round height */

	/** Shipment request */
	$request['RequestedShipment'] = array(
	    'ShipTimestamp'	 => date('c'),
	    'DropoffType'	 => $r->getDropoffType(),
	    'ServiceType'	 => $r->getServiceType(),
	    'PackagingType'	 => $package['container_code'],
	    'TotalWeight'	 => array(
		'Value' => $package['weight'],
		'Units' => $weightUnit),
	    'Shipper'		 => $shipper,
	    'Recipient'		 => $recipient,
	    'LabelSpecification' => array(
		'LabelFormatType'	   => 'COMMON2D',
                'ImageType'		   => $this->getConfigData('label_image_type'),
                'LabelStockType'	   => $this->getConfigData('label_stock_type'),
                'LabelPrintingOrientation' => $this->getConfigData('label_orientation')),
	    'RateRequestTypes' => array('ACCOUNT'),
	    'PackageCount' => 1,
	    'PackageDetail' => 'INDIVIDUAL_PACKAGES',
            'RequestedPackageLineItems' => array(
                'Weight' => array(
                    'Value' => $package['weight'],
                    'Units' => $weightUnit),
		'Dimensions' => array(
		    'Length' => $package['length'],
		    'Width'  => $package['width'],
		    'Height' => $package['height'],
		    'Units'  => $this->getDimensionUnits()),
                'CustomerReferences' => array(
                    '0' => array(
                        'CustomerReferenceType' => 'CUSTOMER_REFERENCE',
                        'Value' => $r->getOrder()->getIncrementId() . '_pkg' . $package['package_number']),
		    '1' => array(
			'CustomerReferenceType' => 'INVOICE_NUMBER',
			'Value' => 'INV' . $r->getOrder()->getIncrementId())),
                'ContentRecords' => $contents));

	/** Check if shipment needs to be insured and if insurance amount is available */
	if ($r->getInsureShipment())
	{
	    /** Request shipment insurance */
	    $request['RequestedShipment']['RequestedPackageLineItems']['InsuredValue'] = array(
		'Currency' => $this->getCurrencyCode(),
    		'Amount'   => $r->getInsureAmount());

	    /** Set require signature */
	    $request['RequestedShipment']['RequestedPackageLineItems']['SignatureOptionDetail'] = array(
		'OptionType' => $r->getRequireSignature());
	}

	/** If package is dangerous */
        if ($package['dangerous'])
        {
            $request['RequestedShipment']['RequestedPackageLineItems']['SpecialServicesRequested'] = array(
		'SpecialServiceTypes'  => array('DANGEROUS_GOODS'),
                'DangerousGoodsDetail' => array('Accessibility' => 'ACCESSIBLE', 'Options' => 'ORM_D'));
	}
        
        if ($package['cod'])
        {
	    $request['RequestedShipment']['SpecialServicesRequested'] = array(
		'SpecialServiceTypes'  => array('COD'),
		'CodDetail'	       => array(
		    'CodCollectionAmount' => array(
			'Amount' => $package['cod_amount'],
			'Currency' => $this->getCurrencyCode()),
		    'CollectionType' => 'ANY'));
        }

	/** If third party payer */
        if ($this->getConfigData('third_party'))
        {
	     /** Set third party payment type */
	    $request['RequestedShipment']['ShippingChargesPayment']['PaymentType'] = 'THIRD_PARTY';

	    /** Set third party account and country */
	    $request['RequestedShipment']['ShippingChargesPayment']['Payor'] = array(
		'AccountNumber' => $this->getConfigData('third_party_fedex_account'),
		'CountryCode'   => $this->getConfigData('third_party_fedex_account_country'));
        }
        else
        {
	    /** Set sender payment type */
	    $request['RequestedShipment']['ShippingChargesPayment']['PaymentType'] = 'SENDER';

	    /** Set payor account and country */
	    $request['RequestedShipment']['ShippingChargesPayment']['Payor'] = array(
		'AccountNumber' => $r->getFedexAccount(),
		'CountryCode'   => $this->getConfigData('account_country'));
        }

        if (Mage::getStoreConfig('carriers/fedex/enable_smartpost'))
        {
            $request['RequestedShipment']['SmartPostDetail']['Indicia'] = $this->getConfigData('smartpost_indicia_type');
            $request['RequestedShipment']['SmartPostDetail']['AncillaryEndorsement'] = $this->getConfigData('smartpost_ancillary_endorsement');
            $request['RequestedShipment']['SmartPostDetail']['SpecialServices'] = 'USPS_DELIVERY_CONFIRMATION';
            $request['RequestedShipment']['SmartPostDetail']['HubId'] = $this->getConfigData('smartpost_hub_id');

            if ($this->getConfigData('smartpost_customer_manifest_id'))
            {
                $request['RequestedShipment']['SmartPostDetail']['CustomerManifestId'] = $this->getConfigData('smartpost_customer_manifest_id');
            }
        }	

	// International shipments
        if ($transactionType == 'International')
        {	    
	    // If tax ID number is present
	    if ($this->getConfig('tax_id_number') != '')
	    {
		$request['TIN'] = $this->getConfig('tax_id_number');
	    }

	    $itemtotal = 0;
            $itemdetails = array();

	    /** Iterate through package items */
	    foreach ($package->getItems() as $_item)
	    {
		/** Load item by order item id */
		$item = Mage::getModel('sales/order_item')->load($_item['item_id']);

		$itemtotal += $item->getPrice();

		$qty = ($item->getQty() > 0) ? $item->getQty(): 1;

		/** If weight units are grams */
		if ($this->getWeightUnits() == 'G')
		{
		    /** Get item weight in KG */
		    $itemWeight = $item->getWeight() * 0.001;

		    /** Set weight unit to KG */
		    $weightUnit = 'KG';
		}
		else
		{
		    /** Otherwise, round and set item weight */
		    $itemWeight = $item->getWeight();

		    /** Set weight unit */
		    $weightUnit = $this->getWeightUnits();
		}
		
		//$itemWeight = (round($itemWeight, 1) > 0) ? round($itemWeight, 1) : 0.1;

                $itemdetails[] = array(
                    'NumberOfPieces' => 1,
                    'Description' => $item->getName(),
                    'CountryOfManufacture' => $r->getStore()->getConfig('shipping/origin/country_id'),
                    'Weight' => array(
                        'Value' => $itemWeight,
                        'Units' => $weightUnit),
                    'Quantity' => $qty,
                    'QuantityUnits' => 'EA',
                    'UnitPrice' => array(
                        'Amount' => sprintf('%01.2f', $item->getPrice()), 'Currency' => $this->getCurrencyCode()),
                    'CustomsValue' => array(
                        'Amount' => sprintf('%01.2f', ($item->getPrice() * $qty)), 'Currency' => $this->getCurrencyCode()));
	    }

            $request['RequestedShipment']['CustomsClearanceDetail'] = array(
		'DutiesPayment' => array(
		    'PaymentType' => 'SENDER',
		    'Payor'	      => array(
			'AccountNumber' => $r->getFedexAccount(),
			'CountryCode'   => $this->getConfigData('account_country'))),
                'DocumentContent' => 'NON_DOCUMENTS', 
                'CustomsValue' => array(
                    'Amount'   => sprintf('%01.2f', $itemtotal),
		    'Currency' => $this->getCurrencyCode()),
                'Commodities' => $itemdetails,
		'ExportDetail' => array(
		    'B13AFilingOption' => 'NOT_REQUIRED'));	    
        }

	if (!$response = $client->processShipment($request))
	{
	    throw Mage::exception('Mage_Shipping', Mage::helper('usa')->__('Error: Empty API response. Please contact system administrator.'));
	}

        return $response;
    }


    /**
     * Parse shipment response
     *
     * @param object $response
     * @return object
     */
    protected function _parseShipmentResponse($response)
    {
	$r = $this->_shipRequest;

        if ($response->HighestSeverity == 'FAILURE' || $response->HighestSeverity == 'ERROR')
        {
            $msg = '';

            if (is_array($response->Notifications))
            {
                foreach ($response->Notifications as $notification)
                {
                    $msg .= $notification->Severity . ': ' . $notification->Message . '<br />';
                }
            }
            else
            {
                $msg .= $response->Notifications->Severity . ': ' . $response->Notifications->Message . '<br /';
            }

            throw Mage::exception('Mage_Shipping', $msg);
        }
	elseif (!isset($response->CompletedShipmentDetail->ShipmentRating))
	{
	    throw Mage::exception('Mage_Shipping', Mage::helper('usa')->__('Error: Incomplete API response. Please check your request and try again.'));
	}
        else
        {
	    $result = new Varien_Object();

	    /** Todo: Add support for third party shipment creation */
            if (!$this->getConfigData('third_party'))
            {
                if (!is_array($response->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails))
                {
                    $currency    = $response->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails->TotalNetCharge->Currency;
                    $amount      = $response->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails->TotalNetCharge->Amount;
                    $weightUnits = $response->CompletedShipmentDetail->CompletedPackageDetails->PackageRating->PackageRateDetails->BillingWeight->Units;
                    $weightValue = $response->CompletedShipmentDetail->CompletedPackageDetails->PackageRating->PackageRateDetails->BillingWeight->Value;
                }
                else
                {
                    $currency    = $response->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails[0]->TotalNetCharge->Currency;
                    $amount      = $response->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails[0]->TotalNetCharge->Amount;
                    $weightUnits = $response->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails[0]->TotalBillingWeight->Units;
                    $weightValue = $response->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails[0]->TotalBillingWeight->Value;
                }

                $result->setCurrencyUnits($currency);
                $result->setTotalShippingCharges($amount);
                $result->setBillingWeightUnits($weightUnits);
                $result->setBillingWeight($weightValue);
            }

            $packages = array();

            if (!is_array($response->CompletedShipmentDetail->CompletedPackageDetails->TrackingIds))
            {
                $trackingNumber = $response->CompletedShipmentDetail->CompletedPackageDetails->TrackingIds->TrackingNumber;
            }
            else
            {
                $trackingNumber = $response->CompletedShipmentDetail->CompletedPackageDetails->TrackingIds[1]->TrackingNumber;
            }

            $packages[] = array(
		'package_number'	  => 1,
                'tracking_number'	  => $trackingNumber,
                'service_option_currency' => '',
                'service_option_charge'   => '',
                'label_image_format'	  => $this->getConfigData('label_image_type'),
                'label_image'		  => base64_encode($response->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image),
                'cod_label_image'         => 
                    (isset($response->CompletedShipmentDetail->CodReturnDetail->Label->Parts->Image)) ?
                    base64_encode($response->CompletedShipmentDetail->CodReturnDetail->Label->Parts->Image) : null,
                'html_image'		  => '');

            $result->setPackages($packages);
            
	    return $result;
        }
    }


    /**
     * Check residential status
     */
    public function getResidential($street, $postcode)
    {
	$r = $this->_initWebServices();

	try { $client = new SoapClient($r->getWsdlPath() . "AddressValidationService_v2.wsdl"); }
	catch (Exception $ex) { Mage::throwException($ex->getMessage()); }

	$request['WebAuthenticationDetail']['UserCredential']['Key']      = $r->getFedexApiKey();	/** Set request key */
	$request['WebAuthenticationDetail']['UserCredential']['Password'] = $r->getFedexApiPassword();  /** Set request password */
	$request['ClientDetail']['AccountNumber'] = $r->getFedexAccount();				/** Set request account */
	$request['ClientDetail']['MeterNumber']   = $r->getFedexMeter();				/** Set request meter */

	$request['TransactionDetail'] = array('CustomerTransactionId' => ' *** Address Validation Request v2 using PHP ***');
	$request['Version'] = array('ServiceId' => 'aval', 'Major' => '2', 'Intermediate' => '0', 'Minor' => '0');

	$request['RequestTimestamp'] = date('c');

	$request['Options'] = array(
	    'CheckResidentialStatus' => 1,
	    'MaximumNumberOfMatches' => 1,
	    'StreetAccuracy' => 'LOOSE',
	    'DirectionalAccuracy' => 'LOOSE',
	    'CompanyNameAccuracy' => 'LOOSE',
	    'ConvertToUpperCase' => 1,
	    'RecognizeAlternateCityNames' => 1,
	    'ReturnParsedElements' => 1);

	$request['AddressesToValidate'] = array(
	    0 => array(
		'AddressId' => 'Destination',
		'Address' => array(
		    'StreetLines' => $street,
		    'PostalCode'  => $postcode,
		    'CountryCode' => 'US',
		)));

	try
	{
	    $_response = $client->addressValidation($request);
	    
	    if (isset($_response->AddressResults->ProposedAddressDetails->DeliveryPointValidation) &&
		$_response->AddressResults->ProposedAddressDetails->DeliveryPointValidation == 'CONFIRMED')
	    {
		if ($_response->AddressResults->ProposedAddressDetails->ResidentialStatus == 'RESIDENTIAL')
		    { return true; }

		return false;
	    }
	}
	catch (SoapFault $exception) {}

	return $this->getConfigData('residence_delivery');
    }    

    
    /**
     * Get trackings
     *
     * @param array trackings
     * @return mixed
     */
    public function getTracking($trackings)
    {
        /** Set tracking request */
        $this->setTrackingRequest();

        /** Set to array */
        if (!is_array($trackings)) { $trackings = array($trackings); }

        /** Iterate through each tracking response */
        foreach ($trackings as $tracking)
        {
	    /** Set tracking */
            $this->_getWsdlTracking($tracking);
        }

        /** Debug to firebug */
	if (Mage::getStoreConfig('carriers/fedex/debug_firebug')) {
            Mage::Helper('shipsync')->log($this->_result);
	}
        /** Return tracking result */
        return $this->_result;
    }


    /**
     * Set tracking request
     */
    protected function setTrackingRequest()
    {
        /** New Varien object */
	$r = new Varien_Object();

	/** Set SOAP cache */
	ini_set('soap.wsdl_cache_enabled', $this->getConfigData('enable_soap_cache'));

	/** Test mode */
	if (Mage::getStoreConfig('carriers/fedex/test_mode'))
	{
	    $r->setFedexApiKey($this->getConfigData('test_key'));	    /** Set FedEx API key */
	    $r->setFedexApiPassword($this->getConfigData('test_password')); /** Set FedEx API password */
	    $r->setFedexAccount($this->getConfigData('test_account'));	    /** Set FedEx account */
	    $r->setFedexMeter($this->getConfigData('test_meter'));	    /** Set FedEx meter */
	    $r->setWsdlPath(dirname(__FILE__) . '/wsdl/test/');		    /** Set WSDL path */
        }
	/** Production mode */
        else
        {
            $r->setFedexApiKey($this->getConfigData('key'));		    /** Set FedEx API key */
	    $r->setFedexApiPassword($this->getConfigData('password'));	    /** Set FedEx API password */
	    $r->setFedexAccount($this->getConfigData('account'));	    /** Set FedEx account */
	    $r->setFedexMeter($this->getConfigData('meter'));		    /** Set FedEx meter */
	    $r->setWsdlPath(dirname(__FILE__) . '/wsdl/');		    /** Set WSDL path */
        }

        /** Set tracking request */
	$this->_trackRequest = $r;
    }


    /**
     * Get XML tracking
     *
     * @param mixed $tracking
     */
    protected function _getWsdlTracking($tracking)
    {
        /** Get tracking request */
        $r = $this->_trackRequest;

	/** Instantiate SOAP client */
        try { $client = new SoapClient($r->getWsdlPath() . "TrackService_v4.wsdl"); }

	/** If SOAP fails, throw exception */
	catch (Exception $ex) { Mage::throwException($ex->getMessage()); }

	$request['WebAuthenticationDetail']['UserCredential']['Key']      = $r->getFedexApiKey();	/** Set request key */
	$request['WebAuthenticationDetail']['UserCredential']['Password'] = $r->getFedexApiPassword();	/** Set request password */
	$request['ClientDetail']['AccountNumber'] = $r->getFedexAccount();				/** Set request account */
	$request['ClientDetail']['MeterNumber']   = $r->getFedexMeter();        			/** Set request meter */

	/** Set transaction detail */
	$request['TransactionDetail']['CustomerTransactionId'] = '*** Track Request v4 using PHP ***';

	/** Set version */
        $request['Version'] = array('ServiceId'	=> 'trck', 'Major' => '4', 'Intermediate' => '0', 'Minor' => '0');

        $request['PackageIdentifier']['Value'] = $tracking;
	$request['PackageIdentifier']['Type']  = 'TRACKING_NUMBER_OR_DOORTAG';

        /** Debug to firebug */
	if (Mage::getStoreConfig('carriers/fedex/debug_firebug')) {
            Mage::Helper('shipsync')->log($request);
	}
        
        try
        {
            $response = $client->track($request);

            /** Debug to firebug */
            if (Mage::getStoreConfig('carriers/fedex/debug_firebug')) {
                Mage::Helper('shipsync')->log($response);
            }

            $this->_parseWsdlTrackingResponse($tracking, $response);
        }
        catch (SoapFault $exception)
        {
            $this->_parseWsdlTrackingResponse($tracking, null);
        }
    }


    /**
     * Parse tracking response
     *
     * @param string $trackingvalue
     * @param mixed $response
     */
    protected function _parseWsdlTrackingResponse($trackingvalue, $response)
    {
        $newline = "<br />";

        $resultArr = array();

	if ($response->HighestSeverity != 'FAILURE' && $response->HighestSeverity != 'ERROR')
        {
            $resultArr['status'] = $response->TrackDetails->StatusDescription;
            $resultArr['service'] = $response->TrackDetails->ServiceInfo;

            if (isset($response->TrackDetails->ShipTimestamp))
                { $resultArr['shippeddate'] = $response->TrackDetails->ShipTimestamp; }

            if (isset($response->TrackDetails->EstimatedDeliveryTimestamp))
            {
		$timestamp = explode('T', $response->TrackDetails->EstimatedDeliveryTimestamp);

		$resultArr['deliverydate'] = $timestamp[0];
		$resultArr['deliverytime'] = $timestamp[1];
	    }

            $weight = $response->TrackDetails->PackageWeight->Value;
            $unit = $response->TrackDetails->PackageWeight->Units;

            $resultArr['weight'] = "{$weight} {$unit}";
        }

        else
        {
            $errorTitle = '';
            foreach ($response->Notifications as $notification)
            {
                if (is_array($response->Notifications))
                {
                    $errorTitle = $notification->Severity . ': ' . $notification->Message . $newline;
                }
                else
                {
                    $errorTitle = $notification . $newline;
                }
            }
        }

        if (!$this->_result)
        {
            $this->_result = Mage::getModel('shipping/tracking_result');
        }

        if ($resultArr)
        {
            $tracking = Mage::getModel('shipping/tracking_result_status');
            $tracking->setCarrier('fedex');
            $tracking->setCarrierTitle($this->getConfigData('title'));
            $tracking->setTracking($trackingvalue);
            $tracking->addData($resultArr);
            $this->_result->append($tracking);
        }
        else
        {
            $error = Mage::getModel('shipping/tracking_result_error');
            $error->setCarrier('fedex');
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setTracking($trackingvalue);
            $error->setErrorMessage($errorTitle);
            $this->_result->append($error);
        }
    }
    

    /**
     * Sort by weight compare function
     *
     * @param array $a
     * @param array $b
     * @return array
     */
    protected function _sortByWeight($a, $b)
    {
	$a_weight = (is_array($a) && isset($a['weight'])) ? $a['weight'] : 0;
	$b_weight = (is_array($b) && isset($b['weight'])) ? $b['weight'] : 0;

	if ($a_weight == $b_weight) { return 0; }

	return ($a_weight > $b_weight) ? -1 : 1;
    }


    /**
     * Get tracking response
     * 
     * @return string
     */
    public function getResponse()
    {
        $statuses = '';

        if ($this->_result instanceof Mage_Shipping_Model_Tracking_Result)
        {
            if ($trackings = $this->_result->getAllTrackings())
            {
                foreach ($trackings as $tracking)
                {
                    if ($data = $tracking->getAllData())
                    {
                        if (!empty($data['status']))
                        {
                            $statuses .= Mage::helper('usa')->__($data['status']) . "\n<br/>";
                        }
                        else
                        {
                            $statuses .= Mage::helper('usa')->__('Empty response') . "\n<br/>";
                        }
                    }
                }
            }
        }
        if (empty($statuses))
        {
            $statuses = Mage::helper('usa')->__('Empty response');
        }
        return $statuses;
    }
}