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
 * IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Rate
 */
class IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Rate extends IllApps_Shipsync_Model_Shipping_Carrier_Fedex
{
    
    
    protected $_rateRequest;
    protected $_rateResult;
    protected $_rateResultCollection;
    protected $_rateResultError;
    protected $_rateServiceClient;
    protected $_rateServiceVersion = '14';
    protected $_rateServiceWsdlPath = 'RateService_v14.wsdl';
    protected $_saturdayDelivery;
    
    
    
    /**
     * collectRates
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return object
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        $rateRequest = new Varien_Object();
        
        $this->_rateServiceClient = $this->_initWebServices($this->_rateServiceWsdlPath);
        
        $origins = Mage::getModel('shipsync/shipping_package_origins');
        
        $this->prepareRequest($request, $itemsByOrigin, $packagesByOrigin);
        
        foreach ($itemsByOrigin as $key => $items) {
            unset($this->_rateRequest);
            if (isset($packagesByOrigin)) {
                $rateRequest->setPackages($packagesByOrigin[$key]);
            }
            
            $rateRequest->setItems($items);
            
            $this->setOrigins($rateRequest, (int) $items[0]['alt_origin']);
            
            $this->_saturdayDelivery = 0;
            
            if (Mage::getStoreConfig('carriers/fedex/saturday_delivery')) {
                for ($i = 2; $i > 0; $i--) {
                    $this->_rateRequest = $rateRequest;
                    $this->setRateRequest($request);
                    $saturdayResultsCollection[] = $this->_getQuotes();
                    $this->_saturdayDelivery     = 1;
                    
                }
                $this->_rateResult = $origins->collectSaturdayResponses($saturdayResultsCollection);
            } else {
                $this->_rateRequest = $rateRequest;
                $this->setRateRequest($request);
                $this->_rateResult = $this->_getQuotes();
            }
            
            if ($this->_rateResult->getError()) {
                $error = Mage::getModel('shipping/rate_result_error');
                $error->setCarrier('fedex');
                $error->setCarrierTitle(Mage::getStoreConfig('carriers/fedex/title'));
                $error->setErrorMessage($this->_rateResultError);
                $this->_rateResult->append($error);
            }
            
            $this->_rateResultCollection[] = $this->_rateResult;
        }
        
        //return $origins->collectMultipleResponses($this->_rateResultCollection);
        
        $multipleResponses = $origins->collectMultipleResponses($this->_rateResultCollection);
        
        if ($multipleResponses->getError())
        {
            return false;
        }
        
        return $multipleResponses;
    }
    
    /*
     * Prepare Request
     *
     * Takes request object, and <empty> arrays for items and packages.
     * Parses request packages and divides the items by package, or divides the items by origin
     * if request packages not set.
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @param array $itemsByOrigin
     * @param array $packagesByOrigin
     *
     * @return void
     *
     */
    public function prepareRequest($request, &$itemsByOrigin, &$packagesByOrigin)
    {
        if ($request->getPackages()) {
            foreach ($request->getPackages() as $package) {
                if (isset($itemsByOrigin[(int) $package['alt_origin']])) {
                    $itemsToMerge                                = $itemsByOrigin[(int) $package['alt_origin']];
                    $itemsByOrigin[(int) $package['alt_origin']] = array_merge($itemsToMerge, $package['items']);
                } else {
                    $itemsByOrigin[(int) $package['alt_origin']] = $package['items'];
                }
                $packagesByOrigin[(int) $package['alt_origin']][] = $package;
            }
        } else {
            $_items        = Mage::getModel('shipsync/shipping_package')->getParsedItems($request->getAllItems());
            $itemsByOrigin = Mage::getModel('shipsync/shipping_package_item')->byOrigin($_items);
        }
    }
    
    /*
     * Set Origins
     * Sets request object's origins based on key.
     *
     * @param object $request
     * @param int $int
     *
     * @return void
     */
    public function setOrigins(&$request, $int)
    {
        $origin = Mage::getModel('shipsync/shipping_package_item')->getOrigin($int); //(int) $item['alt_origin']);
        
        $request->setOrigCountryId($origin['country']);
        $request->setOrigRegionId($origin['regionId']);
        $request->setOrigRegionCode($origin['regionCode']);
        $request->setOrigPostcode($origin['postcode']);
        $request->setOrigCity($origin['city']);
        $request->setOrigStreet($origin['street']);
    }
    
    /**
     * setRateRequest
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Rate
     */
    public function setRateRequest(Mage_Shipping_Model_Rate_Request $request)
    {
        $rateRequest = $this->_rateRequest;
        
        $rateRequest->setShippingPackage(Mage::getModel('shipsync/shipping_package'));
        
        $rateRequest->setOrder($request->getOrder());
        
        if ($request->getLimitMethod()) {
            $rateRequest->setService($request->getLimitMethod());
        }
        
        if ($rateRequest->getItems()) {
        } else if (!$request->getAllItems()) {
            $rateRequest->setItems(Mage::getModel('shipsync/shipping_package')->getParsedItems($request->getAllItems()));
        } else {
            $rateRequest->setItems($request->getAllItems());
        }
        
        $rateRequest->setDefaultPackages(Mage::getModel('shipsync/shipping_package')->getDefaultPackages(array(
            'fedex'
        )));
        
        if ($request->getOrigCountry()) {
            $origCountry = $request->getOrigCountry();
        } else {
            $origCountry = Mage::getStoreConfig('shipping/origin/country_id', $this->getStore());
            $rateRequest->setOrigCountry(Mage::getModel('directory/country')->load($origCountry)->getIso2Code());
        }
        
        if ($request->getOrigRegionId()) {
            $rateRequest->setOrigRegionId($request->getOrigRegionId());
            $rateRequest->setOrigRegionCode($request->getOrigRegionCode());
        } else {
            $rateRequest->setOrigRegionId(Mage::getStoreConfig('shipping/origin/region_id'));
            
            $origRegionCode = Mage::getModel('directory/region')->load($rateRequest->getOrigRegionId())->getCode();
            
            if (strlen($origRegionCode) > 2) {
                $rateRequest->setOrigRegionCode('');
            } else {
                $rateRequest->setOrigRegionCode($origRegionCode);
            }
        }
        
        if ($request->getInsureShipment()) {
            $rateRequest->setInsureShipment(true)->setInsureAmount($request->getInsureAmount());
        }
        
        if ($request->getOrigPostcode()) {
            $rateRequest->setOrigPostcode($request->getOrigPostcode());
        } else {
            $rateRequest->setOrigPostcode(Mage::getStoreConfig('shipping/origin/postcode', $this->getStore()));
        }
        
        if ($request->getOrigCity()) {
            $rateRequest->setOrigCity($request->getOrigCity());
        } else {
            $rateRequest->setOrigCity(Mage::getStoreConfig('shipping/origin/city', $this->getStore()));
        }
        
        if (!$request->getOrigStreet()) {
            $shipperstreetlines = array(
                Mage::getStoreConfig('shipping/origin/address1')
            );
            if (Mage::getStoreConfig('shipping/origin/address2') != '') {
                $shipperstreetlines[] = Mage::getStoreConfig('shipping/origin/address2');
            }
            if (Mage::getStoreConfig('shipping/origin/address3') != '') {
                $shipperstreetlines[] = Mage::getStoreConfig('shipping/origin/address3');
            }
            
            $rateRequest->setOrigStreet($shipperstreetlines);
        } else {
            $rateRequest->setOrigStreet($request->getOrigStreet());
        }
        
        if ($request->getDestStreet()) {
            $rateRequest->setDestStreet($request->getDestStreet());
        }
        
        if ($request->getDestCity()) {
            $rateRequest->setDestCity($request->getDestCity());
        }
        
        if ($request->getDestRegionCode() && strlen($request->getDestRegionCode()) === 2) {
            $rateRequest->setDestRegionCode($request->getDestRegionCode());
        }
        
        if ($request->getDestPostcode()) {
            $rateRequest->setDestPostcode($request->getDestPostcode());
        }
        
        if ($request->getDestCountryId()) {
            $rateRequest->setDestCountry(Mage::getModel('directory/country')->load($request->getDestCountryId())->getIso2Code());
        } else {
            $rateRequest->setDestCountry(Mage::getModel('directory/country')->load(self::USA_COUNTRY_ID)->getIso2Code());
        }
        
        $rateRequest->setPackageWeight(Mage::getModel('shipsync/shipping_package')->getPackageWeight($rateRequest->getItems()));

		/*<<<<<<< HEAD
        
        $rateRequest->setFreeMethodWeight($request->getFreeMethodWeight());
        
        if (Mage::getStoreConfig('carriers/fedex/address_validation') && $rateRequest->getDestCountry() == 'US' && $rateRequest->getDestStreet() && $rateRequest->getDestPostcode()) {
            $rateRequest->setResidential($this->getResidential($rateRequest->getDestStreet(), $rateRequest->getDestPostcode()));
        } else {
            $rateRequest->setResidential(Mage::getStoreConfig('carriers/fedex/residence_delivery'));
		
		======*/

        $rateRequest->setFreeMethodWeight($rateRequest->getPackageWeight() -
            Mage::getModel('shipsync/shipping_package')->getFreeMethodWeight($rateRequest->getItems()));

		if (Mage::getStoreConfig('carriers/fedex/address_validation')
			&& $rateRequest->getDestCountry() == 'US'
			&& $rateRequest->getDestStreet()
			&& $rateRequest->getDestPostcode())
		{
	    	$rateRequest->setResidential($this->getResidential($rateRequest->getDestStreet(), $rateRequest->getDestPostcode()));
		}
		else { $rateRequest->setResidential(Mage::getStoreConfig('carriers/fedex/residence_delivery')); }

        if ($request->getPackages()) {
            $rateRequest->setPackages($request->getPackages());
        }
        
        $rateRequest->setValue(Mage::getModel('shipsync/shipping_package')->getPackageValue($rateRequest->getItems()));
        
        if ($request->getPackageValueWithDiscount()) {
            $rateRequest->setValueWithDiscount($request->getPackageValueWithDiscount());
        } elseif ($rateRequest->getOrder()) {
            $rateRequest->setValueWithDiscount($rateRequest->getValue() - Mage::getModel('shipsync/shipping_package')->getPackageDiscount($rateRequest->getItems(), $rateRequest->getOrder()));
        }
        
        $rateRequest->setDropoff($this->getUnderscoreCodeFromCode(Mage::getStoreConfig('carriers/fedex/dropoff')));
        $rateRequest->setRateType(Mage::getStoreConfig('carriers/fedex/rate_type'));
        $rateRequest->setShipTimestamp(date('c'));
        $rateRequest->setSaturdayDelivery($this->_saturdayDelivery);
        
        if (Mage::getStoreConfig('carriers/fedex/enable_smartpost')) {
            $rateRequest->setEnableSmartPost(true);
            $rateRequest->setSmartPostIndiciaType(Mage::getStoreConfig('carriers/fedex/smartpost_indicia'));
            $rateRequest->setSmartPostAncillaryEndorsement(Mage::getStoreConfig('carriers/fedex/smartpost_endorsement'));
            $rateRequest->setSmartPostHubId(Mage::getStoreConfig('carriers/fedex/smartpost_hub_id'));
            $rateRequest->setSmartPostSpecialServices('USPS_DELIVERY_CONFIRMATION');
            
            if (Mage::getStoreConfig('carriers/fedex/smartpost_customer_manifest_id')) {
                $rateRequest->setSmartPostCustomerManifestId(Mage::getStoreConfig('carriers/fedex/smartpost_customer_manifest_id'));
            }
        }
        
        /*if (Mage::getStoreConfig('carriers/fedex/third_party'))
        {
        $rateRequest->setThirdParty(true);
        $rateRequest->setThirdPartyFedexAccount(Mage::getStoreConfig('carriers/fedex/third_party_fedex_account'));
        $rateRequest->setThirdPartyFedexAccountCountry(Mage::getStoreConfig('carriers/fedex/third_party_fedex_account_country'));
        }*/
        
        $this->_rateRequest = $rateRequest;
        
        return $this;
    }
    
    
    
    /**
     * _getQuotes
     * 
     * @return object
     */
    protected function _getQuotes()
    {
        return $this->getWsdlQuotes();
    }
    
    
    
    /**
     * getWsdlQuotes
     * 
     * @return object
     */
    public function getWsdlQuotes()
    {
        $rateRequest        = $this->_rateRequest;
        $rateServiceClient  = $this->_rateServiceClient;
        $rateServiceVersion = $this->_rateServiceVersion;
        
        $rateResult = Mage::getModel('shipping/rate_result');
        
        $request['WebAuthenticationDetail'] = array(
            'UserCredential' => array(
                'Key' => $this->getFedexKey(),
                'Password' => $this->getFedexPassword()
            )
        );
        
        $request['ClientDetail'] = array(
            'AccountNumber' => $this->getFedexAccount(),
            'MeterNumber' => $this->getFedexMeter()
        );
        
        $request['TransactionDetail']['CustomerTransactionId'] = '*** Rate Request v' . $rateServiceVersion . ' Using PHP ***';
        
        $request['Version'] = array(
            'ServiceId' => 'crs',
            'Major' => $rateServiceVersion,
            'Intermediate' => '0',
            'Minor' => '0'
        );
        
        $request['ReturnTransitAndCommit'] = true;
        
        $request['RequestedShipment']['DropoffType']      = $rateRequest->getDropoff();
        $request['RequestedShipment']['ShipTimestamp']    = $rateRequest->getShipTimestamp();
        $request['RequestedShipment']['RateRequestTypes'] = $rateRequest->getRateType();
        $request['RequestedShipment']['PreferredCurrency'] = 'USD';
        
        if ($rateRequest->getSaturdayDelivery()) {
            $request['RequestedShipment']['SpecialServicesRequested']['SpecialServiceTypes'] = array(
                'SATURDAY_DELIVERY'
            );
        }
        
        if ($rateRequest->getEnableSmartPost()) {
            $request['RequestedShipment']['SmartPostDetail'] = array(
                'Indicia' => $rateRequest->getSmartPostIndiciaType(),
                'AncillaryEndorsement' => $rateRequest->getSmartPostAncillaryEndorsement(),
                'HubId' => $rateRequest->getSmartPostHubId(),
                'SpecialServices' => $rateRequest->getSmartPostSpecialServices()
            );
            
            if ($rateRequest->getSmartPostCustomerManifestId()) {
                $request['RequestedShipment']['SmartPostDetail']['CustomerManifestId'] = $rateRequest->getSmartPostCustomerManifestId();
            }
        }
        
        if ($rateRequest->getThirdParty()) {
            $request['RequestedShipment']['ShippingChargesPayment'] = array(
                'PaymentType' => 'THIRD_PARTY',
                'Payor' => array(
					'ResponsibleParty' => array(
                    	'AccountNumber' => $rateRequest->getThirdPartyFedexAccount(),
                    	'CountryCode' => $rateRequest->getThirdPartyFedexAccountCountry()
					)
                )
            );
        } else {
            $request['RequestedShipment']['ShippingChargesPayment'] = array(
                'PaymentType' => 'SENDER',
                'Payor' => array(
					'ResponsibleParty' => array(
                    	'AccountNumber' => $this->getFedexAccount(),
                    	'CountryCode' => $this->getFedexAccountCountry()
					)
                )
            );
        }
        
        $request['RequestedShipment']['Shipper']['Address'] = array(
            'StreetLines' => $rateRequest->getOrigStreet(),
            'City' => $rateRequest->getOrigCity(),
            'StateOrProvinceCode' => $rateRequest->getOrigRegionCode(),
            'PostalCode' => $rateRequest->getOrigPostcode(),
            'CountryCode' => $rateRequest->getOrigCountry()
        );
        
        if ($rateRequest->getDestStreet()) {
            $request['RequestedShipment']['Recipient']['Address']['StreetLines'] = $rateRequest->getDestStreet();
        }
        
        if ($rateRequest->getDestCity()) {
            $request['RequestedShipment']['Recipient']['Address']['City'] = $rateRequest->getDestCity();
        }
        
        if ($rateRequest->getDestPostcode()) {
            $request['RequestedShipment']['Recipient']['Address']['PostalCode'] = $rateRequest->getDestPostcode();
        }
        
        if ($rateRequest->getDestRegionCode()) {
            $request['RequestedShipment']['Recipient']['Address']['StateOrProvinceCode'] = $rateRequest->getDestRegionCode();
        }
        
        if ($rateRequest->getDestCountry() == 'US') {
            $request['RequestedShipment']['Recipient']['Address']['Residential'] = $rateRequest->getResidential();
        }
        
        $request['RequestedShipment']['Recipient']['Address']['CountryCode'] = $rateRequest->getDestCountry();
        
		/*
		<CustomsClearanceDetail>
               <DutiesPayment>
                  <PaymentType>SENDER</PaymentType>
                  <Payor>
                     <ResponsibleParty>
                        <AccountNumber>"Input Your Information"</AccountNumber>
                        <Tins>
                           <TinType>BUSINESS_STATE</TinType>
                           <Number>123456</Number>
                        </Tins>
                     </ResponsibleParty>
                  </Payor>
               </DutiesPayment>
               <DocumentContent>DOCUMENTS_ONLY</DocumentContent>
               <CustomsValue>
                  <Currency>USD</Currency>
                  <Amount>100.00</Amount>
               </CustomsValue>
               <CommercialInvoice>
                  <TermsOfSale>FOB_OR_FCA</TermsOfSale>
               </CommercialInvoice>
               <Commodities>
                  <NumberOfPieces>1</NumberOfPieces>
                  <Description>ABCD</Description>
                  <CountryOfManufacture>US</CountryOfManufacture>
                  <Weight>
                     <Units>LB</Units>
                     <Value>1.0</Value>
                  </Weight>
                  <Quantity>1</Quantity>
                  <QuantityUnits>cm</QuantityUnits>
                  <UnitPrice>
                     <Currency>USD</Currency>
                     <Amount>1.000000</Amount>
                  </UnitPrice>
                  <CustomsValue>
                     <Currency>USD</Currency>
                     <Amount>100.000000</Amount>
                  </CustomsValue>
               </Commodities>
               <ExportDetail>
                  <ExportComplianceStatement>30.37(f)</ExportComplianceStatement>
               </ExportDetail>
            </CustomsClearanceDetail>
			*/
		
        if ($rateRequest->getOrigCountry() != $rateRequest->getDestCountry()) {
            $request['RequestedShipment']['CustomsClearanceDetail'] = array(
                'DutiesPayment' => array(
                    'PaymentType' => 'SENDER',
                    'Payor' => array(
						'ResponsibleParty' => array(
                        	'AccountNumber' => $this->getFedexAccount(),
                        	'CountryCode' => $rateRequest->getOrigCountry()
						)
                    )
                ),
				'DocumentContent' => 'NON_DOCUMENTS',
                'CustomsValue' => array(
                    'Currency' => $this->getCurrencyCode(),
                    'Amount' => $rateRequest->getValue()
                ),
				/*'CommercialInvoice' => array(
					'TermsOfSale' => 'FOB_OR_FCA' // 'CFR_OR_CPT', etc...
				)*/
            );
        }
        if ($rateRequest->getItems()) {
            if ($rateRequest->getPackages()) {
                $packages = $rateRequest->getPackages();
            } else {
                $packages = $rateRequest->getShippingPackage()->estimatePackages($rateRequest->getItems(), $rateRequest->getDefaultPackages());
            }
            
            if ($rateRequest->getShippingPackage()->getPackageError()) {
                $this->_rateResultError = $rateRequest->getShippingPackage()->getPackageError();
                return $rateResult;
            }
            
            if (isset($packages) && is_array($packages)) {
                if (Mage::getStoreConfig('carriers/fedex/debug_firebug')) {
                    Mage::Helper('shipsync')->log($packages);
                }
                
                $i = 0;
                
                foreach ($packages as $package) {
					
                    $weightUnit = (Mage::getModel('shipsync/shipping_carrier_fedex')->getWeightUnits()  == Zend_Measure_Weight::POUND ? 'LB' : 'KG');                    
                    
                    $weight = (isset($package['weight']) && round($package['weight'], 1) > 0) ? round($package['weight'], 1) : 0.1;
                    $length = (isset($package['length']) && round($package['length']) > 0) ? round($package['length']) : 1;
                    $width  = (isset($package['width']) && round($package['width']) > 0) ? round($package['width']) : 1;
                    $height = (isset($package['height']) && round($package['height']) > 0) ? round($package['height']) : 1;
                    
                    if ($this->getEnableSmartPost() && ($weight < 1)) {
                        $weight = 1;
                    }
					
                    $request['RequestedShipment']['RequestedPackageLineItems'][$i]['SequenceNumber'] = $i + 1;
                    $request['RequestedShipment']['RequestedPackageLineItems'][$i]['GroupPackageCount'] = $i + 1;                    
                    $request['RequestedShipment']['RequestedPackageLineItems'][$i]['Weight'] = array(
                        'Value' => $weight,
                        'Units' => $weightUnit
                    );
                    
                    if ($rateRequest->getOrigCountry() != $rateRequest->getDestCountry()) {
						
                        $itemsById = $this->getItemsById($package['items']);
                        
                        foreach ($itemsById as $qty => $item) {
							
                            $itemValue     = isset($package['package_value']) && $package['package_value'] < $item['value'] ? $package['package_value'] : $item['value'];
							
                            $itemName      = preg_replace('/[^\w\d_ -]/si', '', $item['name']);
							
                            $commodities[] = array(
                                'NumberOfPieces' => 1,
								'Description' => $itemName,
                                'CountryOfManufacture' => $rateRequest->getOrigCountry(),
                                'Weight' => array(
                                    'Units' => $weightUnit,
                                    'Value' => $item['weight']
                                ),
                                'Quantity' => $item['qty_to_ship'],
                                'QuantityUnits' => 'EA',
                                'UnitPrice' => array(
                                    'Currency' => $this->getCurrencyCode(),
                                    'Amount' => $itemValue
                                ),
                                'CustomsValue' => array(
                                    'Currency' => $this->getCurrencyCode(),
                                    'Amount' => ($itemValue * $item['qty_to_ship'])
                                )
                            );
                        }
                    }
					
                    if (isset($commodities)) {
                        $request['RequestedShipment']['CustomsClearanceDetail']['Commodities'] = $commodities;
                    }
                    
                    if ($rateRequest->getInsureShipment()) {
						
                        $request['RequestedShipment']['RequestedPackageLineItems'][$i]['InsuredValue']['Amount']   = $rateRequest->getInsureAmount();
                        $request['RequestedShipment']['RequestedPackageLineItems'][$i]['InsuredValue']['Currency'] = $this->getCurrencyCode();
						
                    } else if (Mage::getStoreConfig('carriers/fedex/rating_insured_value')) {
						
                        if (isset($package['package_value'])) {
                            $package_value = $package['package_value'];
                        } else {
                            $package_value = 0.0;
                            foreach ($package['items'] as $item) {
                                $package_value += $item['value'];
                            }
                        }
                        $request['RequestedShipment']['RequestedPackageLineItems'][$i]['InsuredValue']['Amount']   = round($package_value, 1);
                        $request['RequestedShipment']['RequestedPackageLineItems'][$i]['InsuredValue']['Currency'] = $this->getCurrencyCode();
                    }
                    
                    if (Mage::getStoreConfig('carriers/fedex/enable_dimensions')) {
                        $request['RequestedShipment']['RequestedPackageLineItems'][$i]['Dimensions'] = array(
                            'Length' => $length,
                            'Width' => $width,
                            'Height' => $height,
                            'Units' => ($this->getDimensionUnits() == Zend_Measure_Length::INCH ? 'IN' : 'CM')
                        );
                    }
                    
                    $i++;
                }
                
                $request['RequestedShipment']['PackageCount']  = $i;
                $request['RequestedShipment']['PackageDetail'] = 'INDIVIDUAL_PACKAGES';
                
            } else {
                $this->_rateResultError = 'Unable to estimate packages';
                return $rateResult;
            }
        } else {
            $this->_rateResultError = 'No items found to ship';
            return $rateResult;
        }
        
        $this->setPackageCount($request['RequestedShipment']['PackageCount']);
        
        if (Mage::getStoreConfig('carriers/fedex/debug_firebug')) {
            Mage::Helper('shipsync')->log($request);
        }
        try {
            Mage::Helper('shipsync')->mageLog($request, 'rate');			
            $response = $rateServiceClient->getRates($request);            
			Mage::Helper('shipsync')->mageLog($response, 'rate');
        }
        catch (SoapFault $ex) {
            $this->_rateResultError = $ex->getMessage();
            return $rateResult;
        }
        
        if (Mage::getStoreConfig('carriers/fedex/debug_firebug')) {
            Mage::Helper('shipsync')->log($response);
        }
        
        $this->_rateResult = $rateResult;
        
        return $this->_parseWsdlResponse($response);
    }
    
    public function getItemsById($items)
    {
        $itemsById = array();
        
        foreach ($items as $item) {
            $id                  = $item['product_id'];
            $count               = isset($itemsById[$id]['qty_to_ship']) ? $itemsById[$id]['qty_to_ship'] : 0;
            $item['qty_to_ship'] = 1 + $count;
            $itemsById[$id]      = $item;
        }
        
        return $itemsById;
    }
    
    
    /**
     * _parseWsdlResponse
     * 
     * @param object $response
     * @return object
     */
    protected function _parseWsdlResponse($response)
    {
        $rateRequest = $this->_rateRequest;
        $rateResult  = $this->_rateResult;
        
        if ($response->HighestSeverity == 'ERROR' || $response->HighestSeverity == 'FAILURE' || ($response->HighestSeverity == 'WARNING' && !isset($response->RateReplyDetails))) {
            $errorMsg = '';
            
            if (is_array($response->Notifications)) {
                foreach ($response->Notifications as $notification) {
                    $errorMsg .= $notification->Severity . ': ' . $notification->Message . '<br />';
                }
            } elseif (($response->Notifications->Message == 'General Error') && Mage::getStoreConfig('carriers/fedex/test_mode')) {
                $errorMsg .= 'ERROR: FedEx Testing servers are temporarily unavailable. Please try again in a few moments.<br />';
            } else {
                $errorMsg .= $response->Notifications->Severity . ': ' . $response->Notifications->Message . '<br />';
            }
            
            $this->_rateResultError = $errorMsg;
            return $rateResult;
        } elseif (!isset($response->RateReplyDetails)) {
            $this->_rateResultError = 'Error: Empty rate result.  Please contact your system administrator.';
            return $rateResult;
        }
        
        if (!is_array($response->RateReplyDetails)) {
            $rateReplyDetails = array(
                $response->RateReplyDetails
            );
        } else {
            $rateReplyDetails = $response->RateReplyDetails;
        }
        
        $rateType = Mage::getStoreConfig('carriers/fedex/rate_type');
        
        $i = 0;
        
        foreach ($rateReplyDetails as $rateReply) {
            if (isset($rateReply->ServiceType) && in_array($rateReply->ServiceType, $this->getParsedAllowedRatingMethods())) {
                $rateResultMethod = Mage::getModel('shipping/rate_result_method');
                
                $rateResultMethod->setCarrier('fedex');
                $rateResultMethod->setCarrierTitle(Mage::getStoreConfig('carriers/fedex/title'));
                $rateResultMethod->setMethod(str_replace('_', '', $rateReply->ServiceType));
                $rateResultMethod->setMethodTitle($this->getCode('method', $rateReply->ServiceType, true));
                
                if (isset($rateReply->DeliveryDayOfWeek) && $rateReply->DeliveryDayOfWeek == 'SAT') {
                    $rateResultMethod->setMethodTitle($rateResultMethod->getMethodTitle() . ' - Saturday Delivery');
                }
                
                if (Mage::getStoreConfig('carriers/fedex/show_timestamp')) {
                    if (isset($rateReply->DeliveryTimestamp)) {
                        $rateResultMethod->setMethodTitle($rateResultMethod->getMethodTitle() . ' (' . date("m/d g:ia", strtotime($rateReply->DeliveryTimestamp)) . ')');
                    } elseif (isset($rateReply->CommitDetails->TransitTime)) {
                        if ($rateReply->ServiceType == 'SMART_POST') {
                            $transitTimeInt = (isset($transitTimeInt)) ? $transitTimeInt + 1 : 2;
                            $transitTime    = $transitTimeInt . '-8 Days';
                            $rateResultMethod->setMethodTitle($rateResultMethod->getMethodTitle() . ' (' . $transitTime . ')');
                            $rateResultMethod->setTransitTime($transitTimeInt);
                        } else {
                            $transitTime    = strtolower($rateReply->CommitDetails->TransitTime);
                            $tmp            = explode('_', $transitTime);
                            $transitTimeInt = Mage::helper('shipsync')->getNumberAsInt($tmp[0]);
                            $transitTime    = $transitTimeInt . ' ' . ucwords($tmp[1]);
                            $rateResultMethod->setMethodTitle($rateResultMethod->getMethodTitle() . ' (' . $transitTime . ')');
                            $rateResultMethod->setTransitTime($transitTimeInt);
                        }
                    }
                }
                
                if (!Mage::getStoreConfig('carriers/fedex/test_mode') && ($rateType == 'PREFERRED')) {
                    if (is_array($rateReply->RatedShipmentDetails)) {
                        
                        foreach ($rateReply->RatedShipmentDetails as $ratedShipmentDetail) {
                            
                            if ($ratedShipmentDetail->ShipmentRateDetail->RateType == 'PREFERRED_ACCOUNT_SHIPMENT') {                            
                                    $shipmentRateDetail = $ratedShipmentDetail->ShipmentRateDetail;                       
                            }
                        }
                    }
                }
                else {
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
                }
                    
                $rate = $shipmentRateDetail->TotalNetCharge->Amount;
                
                if (Mage::getStoreConfig('carriers/fedex/subtract_vat') > 0) {
                    $rate = $rate / (1 + (Mage::getStoreConfig('carriers/fedex/subtract_vat') / 100));
                }
                
                if (Mage::getStoreConfig('carriers/fedex/handling_fee') > 0) {
                    $handling_fee = Mage::getStoreConfig('carriers/fedex/handling_fee');
                    
                    if (Mage::getStoreConfig('carriers/fedex/handling_action') == 'P') {
                        $handling_fee = $handling_fee * $this->getPackageCount();
                    }
                    
                    if (Mage::getStoreConfig('carriers/fedex/handling_type') == 'F') {
                        $rate = $rate + $handling_fee;
                    } else {
                        if (Mage::getStoreConfig('carriers/fedex/handling_shelf') && $rate >= 10) {
                            $handling_fee = $handling_fee / log($rate);
                            $rate         = $rate + ($rate * ($handling_fee / 100));
                        } else {
                            $rate = $rate + ($rate * ($handling_fee / 100));
                        }
                    }
                }
                
                $rateResultMethod->setCost($rate);
                $rateResultMethod->setPrice($rate);
                
                //Test for Continental US destination
                $freeMethodLimit = Mage::getStoreConfig('carriers/fedex/free_method_limit');
                $destCode        = $this->_rateRequest->getDestRegionCode();
                $destContinental = ($destCode == 'AK' || $destCode == 'HI') ? false : true;
                $continentalTest = (($freeMethodLimit && $destContinental) || !$freeMethodLimit) ? true : false;
                
                if (Mage::getStoreConfig('carriers/fedex/free_shipping_enable') && $continentalTest) {
                    if (Mage::getStoreConfig('carriers/fedex/free_shipping_discounts')) {
                        $value = $this->_rateRequest->getValue();
                    } else {
                        $value = $this->_rateRequest->getValueWithDiscount();
                    }
                    
                    $freeMethods = array(
                        Mage::getStoreConfig('carriers/fedex/free_method')
                    );
                    
                    $freeMethods = explode(",", $freeMethods[0]);
                    
                    if (in_array($rateResultMethod->getMethod(), $freeMethods) && $value > Mage::getStoreConfig('carriers/fedex/free_shipping_subtotal')) {
                        if (Mage::getStoreConfig('carriers/fedex/free_shipping_enable_all')) {
                            $rateResultMethod->setCost($rate);
                            $rateResultMethod->setPrice('0');
                            $rateResultMethod->setMethodTitle('Free Shipping (' . $rateResultMethod->getMethodTitle() . ')');
                            $rateResultMethod->setMethodDescription($rateResultMethod->getMethodTitle());
                        } elseif ($rateRequest->getFreeMethodWeight() == 0) {
                            $rateResultMethod->setCost($rate);
                            $rateResultMethod->setPrice('0');
                            $rateResultMethod->setMethodTitle('Free Shipping (' . $rateResultMethod->getMethodTitle() . ')');
                            $rateResultMethod->setMethodDescription($rateResultMethod->getMethodTitle());
                        } elseif ($rateRequest->getFreeMethodWeight() != $rateRequest->getPackageWeight()) {
                            $discountPercent = $rateRequest->getFreeMethodWeight() / $rateRequest->getPackageWeight();
                            $rateResultMethod->setCost($rate);
                            $rateResultMethod->setPrice($rate * $discountPercent);
                            $rateResultMethod->setMethodTitle('Discounted (' . $rateResultMethod->getMethodTitle() . ')');
                            $rateResultMethod->setMethodDescription($rateResultMethod->getMethodTitle());
                        }
                    }
                } else {
                    $rateResultMethod->setCost($rate);
                    $rateResultMethod->setPrice($rate);
                    $rateResultMethod->setMethodTitle($rateResultMethod->getMethodTitle());
                    $rateResultMethod->setMethodDescription($rateResultMethod->getMethodTitle());
                }
                $rateResult->append($rateResultMethod);
            }
        }
        if (!isset($rateResult)) {
            $this->_rateResultError('No applicable rates available');
            return $rateResult;
        }
        
        return $rateResult;
    }
}