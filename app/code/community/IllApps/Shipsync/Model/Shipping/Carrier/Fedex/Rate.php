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
 * IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Rate
 */
class IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Rate extends IllApps_Shipsync_Model_Shipping_Carrier_Fedex
{

	protected $_rateServiceClient;
	protected $_rateRequest;
    protected $_rateResult;
    protected $_rateResultCollection;
    protected $_rateResultError;
    protected $_saturdayDelivery;

    /**
     * collectRates
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return object
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
		
        // Set client
		$this->_rateServiceClient = $this->_initWebServices($this->_rateServiceWsdlPath);
		
		// Init request object
 		$this->_rateRequest = new Varien_Object();
        
		// Set rate request
		$this->setRateRequest($request);
		
		// Rate result
        $this->_rateResult = $this->_getQuotes();
		
		if ($this->_rateResult->getError()) {
                $error = Mage::getModel('shipping/rate_result_error');
                $error->setCarrier('fedex');
                $error->setCarrierTitle($this->getConfigData('title'));
                $error->setErrorMessage($this->_rateResultError);
                $this->_rateResult->append($error);
        }
		
		// Return rate result
		return $this->getRateResult();
    }
	
	
    /**
     * Get result
     *
     * @return object
     */
    public function getRateResult() { return $this->_rateResult; }

	
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
        
		$rateRequest->setItems(Mage::getModel('shipsync/shipping_package')->getParsedItems($request->getAllItems()));       
        
        $rateRequest->setDefaultPackages(Mage::getModel('shipsync/shipping_package')->getDefaultPackages(array(
            'fedex'
        )));
        
        if ($request->getOrigCountry()) {
            $origCountry = $request->getOrigCountry();
        } else {
            $origCountry = Mage::getStoreConfig('shipping/origin/country_id', $this->getStore());
            $rateRequest->setOrigCountry(Mage::getModel('directory/country')->load($origCountry)->getIso2Code());
        }
		
		// Shipper region id
		$shipperRegionId = Mage::getStoreConfig('shipping/origin/region_id');				
        
		// Shipper region code
		if (is_numeric($shipperRegionId)) {
            $rateRequest->setOrigRegionCode(Mage::getModel('directory/region')->load($shipperRegionId)->getCode());
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
                Mage::getStoreConfig('shipping/origin/street_line1')
            );
            if (Mage::getStoreConfig('shipping/origin/street_line2') != '') {
                $shipperstreetlines[] = Mage::getStoreConfig('shipping/origin/street_line2');
            }
            if (Mage::getStoreConfig('shipping/origin/street_line3') != '') {
                $shipperstreetlines[] = Mage::getStoreConfig('shipping/origin/street_line3');
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
        
		
        $weight = $this->getTotalNumOfBoxes($request->getPackageWeight());
        $rateRequest->setWeight($weight);
        if ($request->getFreeMethodWeight()!= $request->getPackageWeight()) {
            $rateRequest->setFreeMethodWeight($request->getFreeMethodWeight());
        }
		
		
		if ($this->getConfigData('address_validation')
			&& $rateRequest->getDestCountry() == 'US'
			&& $rateRequest->getDestStreet()
			&& $rateRequest->getDestPostcode())
		{
	    	$rateRequest->setResidential($this->getResidential($rateRequest->getDestStreet(), $rateRequest->getDestPostcode()));
		}
		else { $rateRequest->setResidential($this->getConfigData('residence_delivery')); }

        
        $rateRequest->setValue($request->getPackagePhysicalValue());
        $rateRequest->setValueWithDiscount($request->getPackageValueWithDiscount());
		
        $rateRequest->setDropoff($this->getConfigData('dropoff'));
        $rateRequest->setRateType($this->getConfigData('rate_type'));
        $rateRequest->setShipTimestamp(date('c'));
        $rateRequest->setSaturdayDelivery($this->_saturdayDelivery);
        
        if ($this->getConfigData('enable_smartpost')) {
            $rateRequest->setEnableSmartPost(true);
            $rateRequest->setSmartPostIndiciaType($this->getConfigData('smartpost_indicia'));
            $rateRequest->setSmartPostAncillaryEndorsement($this->getConfigData('smartpost_endorsement'));
            $rateRequest->setSmartPostHubId($this->getConfigData('smartpost_hub_id'));
            $rateRequest->setSmartPostSpecialServices('USPS_DELIVERY_CONFIRMATION');
            
            if ($this->getConfigData('smartpost_customer_manifest_id')) {
                $rateRequest->setSmartPostCustomerManifestId($this->getConfigData('smartpost_customer_manifest_id'));
            }
        }
        
		if ($this->getConfigData('default_commodity')) {
			$rateRequest->setDefaultCommodity($this->getConfigData('default_commodity'));
		}
        
        $rateRequest->setIsReturn($request->getIsReturn());

        $rateRequest->setBaseSubtotalInclTax($request->getBaseSubtotalInclTax());
								   
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
        
        $request['RequestedShipment']['Shipper']['Address']['StreetLines'] = $rateRequest->getOrigStreet();
		$request['RequestedShipment']['Shipper']['Address']['City'] = $rateRequest->getOrigCity();
		$request['RequestedShipment']['Shipper']['Address']['PostalCode'] = $rateRequest->getOrigPostcode();
		$request['RequestedShipment']['Shipper']['Address']['CountryCode'] = $rateRequest->getOrigCountry();
            
		if ($rateRequest->getOrigRegionCode()) {
			$request['RequestedShipment']['Shipper']['Address']['StateOrProvinceCode'] = $rateRequest->getOrigRegionCode();
		}
        
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
                    'Amount' => sprintf('%01.2f', $rateRequest->getValue())
                ),
				/*'CommercialInvoice' => array(
					'TermsOfSale' => 'FOB_OR_FCA' // 'CFR_OR_CPT', etc...
				)*/
            );
        }
        if ($rateRequest->getItems()) {
            if ($rateRequest->getPackaging()) {
                $packages = $rateRequest->getPackaging();
            } else {
                $packages = $rateRequest->getShippingPackage()->estimatePackages($rateRequest->getItems(), $rateRequest->getDefaultPackages());
            }
            
            if ($rateRequest->getShippingPackage()->getPackageError()) {
                $this->_rateResultError = $rateRequest->getShippingPackage()->getPackageError();
                return $rateResult;
            }
            
            if (isset($packages) && is_array($packages)) {
                
                $i = 0;
                
                foreach ($packages as $package) {					
					
					$weightCoef = 1.0;
					
                    $weightUnit = Mage::getModel('shipsync/shipping_carrier_fedex')->getWeightUnits();

		    		if ($weightUnit == 'G') {
						$package['weight'] = $package['weight'] * 0.001; 
						$weightUnit = 'KG';
						$weightCoef = 0.001;
					}			   
					
		    		$weight = (isset($package['weight']) && round($package['weight'], 1) > 0) ? round($package['weight'], 1) : 0.1;
                    $length = (isset($package['length']) && round($package['length']) > 0)    ? round($package['length'])    : 1;
                    $width  = (isset($package['width'])  && round($package['width']) > 0)     ? round($package['width'])     : 1;
                    $height = (isset($package['height']) && round($package['height']) > 0)    ? round($package['height'])    : 1;

		    		if ($this->getEnableSmartPost() && ($weight < 1)) { $weight = 1; }

                    $request['RequestedShipment']['RequestedPackageLineItems'][$i]['SequenceNumber'] = $i + 1;
                    $request['RequestedShipment']['RequestedPackageLineItems'][$i]['GroupPackageCount'] = $i + 1;                    
                    $request['RequestedShipment']['RequestedPackageLineItems'][$i]['Weight'] = array(
                        'Value' => $weight,
                        'Units' => $weightUnit
                    );
                    
                    if ($rateRequest->getOrigCountry() != $rateRequest->getDestCountry()) {
						
                        $itemsById = $this->getItemsById($package['items']);
                        
                        foreach ($itemsById as $qty => $item) {
							
                            $itemValue = isset($package['package_value']) && $package['package_value'] < $item['value'] ? $package['package_value'] : $item['value'];

							if ($rateRequest->getDefaultCommodity() != "") {
								$itemName = $rateRequest->getDefaultCommodity();
							} else {								
                            	$itemName = preg_replace('/[^\w\d_ -]/si', '', $item['name']);
							}
							
							$itemWeight = (isset($item['weight']) 
										   && round($item['weight'] * $weightCoef, 1) > 0) 
										    ? round($item['weight'] * $weightCoef, 1) : 0.1;
							
                            $commodities[] = array(
                                'NumberOfPieces' => 1,
								'Description' => $itemName,
                                'CountryOfManufacture' => $rateRequest->getOrigCountry(),
                                'Weight' => array(
                                    'Units' => $weightUnit,
                                    'Value' => $itemWeight
                                ),
                                'Quantity' => $item['qty_to_ship'],
                                'QuantityUnits' => 'EA',
                                'UnitPrice' => array(
                                    'Currency' => $this->getCurrencyCode(),
                                    'Amount' => sprintf('%01.2f', $item['value'])
                                ),
                                'CustomsValue' => array(
                                    'Currency' => $this->getCurrencyCode(),
                                    'Amount' => sprintf('%01.2f', $item['value'] * $item['qty_to_ship'])
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
						
                    } else if ($this->getConfigData('rating_insured_value')) {
						
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
                    
                    if ($this->getConfigData('enable_dimensions')) {
                        $request['RequestedShipment']['RequestedPackageLineItems'][$i]['Dimensions'] = array(
                            'Length' => $length,
                            'Width' => $width,
                            'Height' => $height,
                            'Units' => $this->getDimensionUnits()
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
        
        try {
            Mage::Helper('shipsync')->mageLog($request, 'rate');			
            $response = $rateServiceClient->getRates($request);            
			Mage::Helper('shipsync')->mageLog($response, 'rate');
        }
        catch (SoapFault $ex) {
            $this->_rateResultError = $ex->getMessage();
            return $rateResult;
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
            } elseif (($response->Notifications->Message == 'General Error') && $this->getConfigData('test_mode')) {
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
        
        $rateType = $this->getConfigData('rate_type');
        
        $i = 0;
        
        foreach ($rateReplyDetails as $rateReply) {
            if (isset($rateReply->ServiceType) && in_array($rateReply->ServiceType, $this->getAllowedMethods())) {
                $rateResultMethod = Mage::getModel('shipping/rate_result_method');
                
                $rateResultMethod->setCarrier('fedex');
                $rateResultMethod->setCarrierTitle($this->getConfigData('title'));
                $rateResultMethod->setMethod($rateReply->ServiceType);
                $rateResultMethod->setMethodTitle($this->getCode('method', $rateReply->ServiceType));
                
                if (isset($rateReply->DeliveryDayOfWeek) && $rateReply->DeliveryDayOfWeek == 'SAT') {
                    $rateResultMethod->setMethodTitle($rateResultMethod->getMethodTitle() . ' - Saturday Delivery');
                }
                
                if ($this->getConfigData('show_timestamp')) {
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
                
                if (!$this->getConfigData('test_mode') && ($rateType == 'PREFERRED')) {
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
                    
				if (isset($shipmentRateDetail)) {
                	$rate = $shipmentRateDetail->TotalNetCharge->Amount;
				} else {
					$rate = 0;
				}
                
                if ($this->getConfigData('subtract_vat') > 0) {
                    $rate = $rate / (1 + ($this->getConfigData('subtract_vat') / 100));
                }
                
                if ($this->getConfigData('handling_fee') > 0) {
                    $handling_fee = $this->getConfigData('handling_fee');
                    
                    if ($this->getConfigData('handling_action') == 'P') {
                        $handling_fee = $handling_fee * $this->getPackageCount();
                    }
                    
                    if ($this->getConfigData('handling_type') == 'F') {
                        $rate = $rate + $handling_fee;
                    } else {
                        if ($this->getConfigData('handling_shelf') && $rate >= 10) {
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
                $freeMethodLimit = $this->getConfigData('free_shipping_subtotal');
                $destCode        = $this->_rateRequest->getDestRegionCode();
                $destContinental = ($destCode == 'AK' || $destCode == 'HI') ? false : true;
                $continentalTest = (($freeMethodLimit && $destContinental) || !$freeMethodLimit) ? true : false;
                
                if ($this->getConfigData('free_shipping_enable') && $continentalTest) {
                    if ($this->getConfigData('free_shipping_discounts')) {
                        $value = $this->_rateRequest->getValue();
                    } else {
                        $value = $this->_rateRequest->getValueWithDiscount();
                    }
                    
                    $freeMethods = array(
                        $this->getConfigData('free_method')
                    );
                    
                    $freeMethods = explode(",", $freeMethods[0]);
                    
                    if (in_array($rateResultMethod->getMethod(), $freeMethods) && $value > $this->getConfigData('free_shipping_subtotal')) {
                        if ($this->getConfigData('free_shipping_enable_all')) {
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
