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
 * IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Ship
 */
class IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Ship extends IllApps_Shipsync_Model_Shipping_Carrier_Fedex
{
    
    
    protected $_shipRequest;
    protected $_shipResult;
    protected $_shipResultError;
    protected $_shipServiceClient;
    protected $_shipServiceVersion = '13';
    protected $_shipServiceWsdlPath = 'ShipService_v13.wsdl';
    protected $_activeShipment;
    
    
    /**
     * Create shipment
     *
     *
     * @param object
     * @return object
     */
    public function createShipment($request)
    {		
        $this->_shipServiceClient = $this->_initWebServices($this->_shipServiceWsdlPath);

		/** Set ship request */
		$this->setShipRequest($request);		

		/** Set result */
		$this->_shipResult = $this->_createShipment();

		/** Return result */
		return $this->_shipResult;
    }
    
	
    /**
     * Set shipment request
     *
     * @param object
     * @return IllApps_Shipsync_Model_Shipping_Carrier_Fedex
     */
    public function setShipRequest($request)
    {				
        $shipRequest = Mage::getModel('shipsync/shipment_request');
        		
        $shipRequest->setShipmentObject($request->getShipmentObject());		
		$shipRequest->setOrderId($request->getOrderId());		
		$shipRequest->setOrder(Mage::getModel('sales/order')->loadByIncrementId($shipRequest->getOrderId()));
		$shipRequest->setStore($shipRequest->getOrder()->getStore());				
		$shipRequest->setPackages($request->getPackages());	
		$shipRequest->setMethodCode($request->getMethodCode());
		$shipRequest->setServiceType($this->getUnderscoreCodeFromCode($shipRequest->getMethodCode()));
		$shipRequest->setDropoffType($this->getUnderscoreCodeFromCode(Mage::getStoreConfig('carriers/fedex/dropoff')));
		$shipRequest->setCustomerReference($shipRequest->getOrderId() . '_pkg' . $request->getPackageId());
		$shipRequest->setInvoiceNumber('INV' . $shipRequest->getOrderId());
					
		// Shipper region id
		$shipperRegionId = Mage::getStoreConfig('shipping/origin/region_id');				
        
		// Shipper region code
		if (is_numeric($shipperRegionId)) {
            $shipRequest->setShipperRegionCode(Mage::getModel('directory/region')->load($shipperRegionId)->getCode());
        }				
		
		
		// Shipper company
        $shipRequest->setShipperCompany(Mage::app()->getStore()->getFrontendName());
		
		// Shipper streetlines
		$shipperStreetLines = array(Mage::getStoreConfig('shipping/origin/street_line1'));
		
        if (Mage::getStoreConfig('shipping/origin/street_line2') != '') {
			$shipperStreetLines[] = Mage::getStoreConfig('shipping/origin/street_line2');
        }
		if (Mage::getStoreConfig('shipping/origin/street_line3') != '') {
        	$shipperStreetLines[] = Mage::getStoreConfig('shipping/origin/street_line3');
        }
        
        $shipRequest->setShipperStreetLines($shipperStreetLines);
        $shipRequest->setShipperCity(Mage::getStoreConfig('shipping/origin/city'));
        $shipRequest->setShipperPostalCode(Mage::getStoreConfig('shipping/origin/postcode'));
        $shipRequest->setShipperCountryCode(Mage::getStoreConfig('shipping/origin/country_id', $this->getStore()));
        $shipRequest->setShipperPhone(Mage::getStoreConfig('shipping/origin/phone'));
        $shipRequest->setShipperStateOrProvinceCode($shipRequest->getShipperRegionCode());
        
		$shipRequest->setRecipientAddress($request->getRecipientAddress());        
		$shipRequest->setInsureShipment($request->getInsureShipment());

		// Set weight units
		$shipRequest->setWeightUnits(Mage::getModel('shipsync/shipping_carrier_fedex')->getWeightUnits());
		
		// Set weight coefficient
		$shipRequest->setWeightCoefficient(1.0);
		
		// Convert G to KG, update coefficient
		if ($shipRequest->getWeightUnits() == 'G') {
			$shipRequest->setWeightUnits('KG');
			$shipRequest->setWeightCoefficient(0.001);
		}			   

		// Enable/disable dimensions
		$shipRequest->setEnableDimensions(Mage::getStoreConfig('carriers/fedex/shipping_dimensions_disable'));
		
		// Dimension units
		$shipRequest->setDimensionUnits(Mage::getModel('shipsync/shipping_carrier_fedex')->getDimensionUnits());				
		
		// Customs value				
		$shipRequest->setCustomsValue($shipRequest->getOrder()->getGrandTotal() - $shipRequest->getOrder()->getShippingAmount());
		
		// Insurance amount
        if ($request->getInsureAmount() != '') {
            $shipRequest->setInsureAmount($request->getInsureAmount());
        } else {
            $shipRequest->setInsureAmount($shipRequest->getCustomsValue());
        }
				
        // Set delivery signature type
		$shipRequest->setSignature(Mage::getStoreConfig('carriers/fedex/signature'));

		// Saturday delivery
		$shipRequest->setSaturdayDelivery($request->getSaturdayDelivery());
		
		// COD
        $shipRequest->setCod($request->getCod());       
        
		// Rate types
		$shipRequest->setRateType(Mage::getStoreConfig('carriers/fedex/rate_type'));
        
		// Timestamp
		$shipRequest->setShipTimestamp(date('c'));
		
        if (Mage::getStoreConfig('carriers/fedex/address_validation') && ($shipRequest->getRecipientAddress()->getCountryId() == 'US')) {
            $shipRequest->setResidential($this->getResidential($shipRequest->getRecipientAddress()->getStreet(), $shipRequest->getRecipientAddress()->getPostcode()));
        } else {
            $shipRequest->setResidential(Mage::getStoreConfig('carriers/fedex/residence_delivery'));
        }
		
        $this->_shipRequest = $shipRequest;
        
        return $this;
    }
    
    /**
     * Create shipment
     *
     * @return mixed
     */
    protected function _createShipment()
    {
        $shipRequest = $this->_shipRequest;
		
        /** Iterate through each package to ship */
        foreach ($shipRequest->getPackages() as $packageToShip) {
			            
			/** Send shipment request */
			$shipResponse = $this->_sendShipmentRequest($packageToShip);
			
			/** Parse response */
            $shipResult   = $this->_parseShipmentResponse($shipResponse);            			
            
            /** Iterate through shipped packages */
            foreach ($shipResult->getPackages() as $packageShipped) {
                $convertor = Mage::getModel('sales/convert_order');
                
                if ($packageShipped['masterTrackingId'] != false) {
                    $shipRequest->setMasterTrackingId($packageShipped['masterTrackingId']);
                }
                
                if ($packageShipped['package_number'] == 1) {
                    $shipment = $convertor->toShipment($shipRequest->getOrder());
                }
                
                foreach ($this->getItemsById($packageToShip) as $itemToShip) {
                    $orderItem = $shipRequest->getOrder()->getItemById($itemToShip['id']);
                    $item      = $convertor->itemToShipmentItem($orderItem);
                    $item->setQty($itemToShip['qty_to_ship']);
                    $shipment->addItem($item);
                }
                
                $track = Mage::getModel('sales/order_shipment_track')
					->setTitle($this->getCode('method', $shipRequest->getServiceType(), true))
					->setCarrierCode('fedex')
					->setNumber($packageShipped['tracking_number'])
					->setShipment($shipment);
                
                if ($packageShipped['package_number'] == $shipRequest->getPackageCount()) {
                    $shipment->addTrack($track);
                    $shipment->register();
                    $shipment->getOrder()->setIsInProcess(true);
                    
                    $transactionSave = Mage::getModel('core/resource_transaction')->addObject($shipment)->addObject($shipment->getOrder())->save();
                    
                    $this->sendEmail($shipment, $packageToShip, $packageShipped);
                } else {
                    $shipment->addTrack($track);
                }
                
                #Mage::log($shipment->debug());
                $pkg = Mage::getModel('shipping/shipment_package')
					->setOrderIncrementId($shipRequest->getOrder()->getIncrementId())
					->setOrderShipmentId($shipment->getEntityId())
					->setPackageItems($this->jsonPackageItems($packageToShip))
					->setCarrier('fedex')
					->setShippingMethod($track->getTitle())
					->setPackageType($this->getCode('packaging', $packageToShip->getContainerCode(), true))
					->setCarrierShipmentId($shipResult->getShipmentIdentificationNumber())
					->setWeightUnits($shipResult->getBillingWeightUnits())
					->setDimensionUnits($packageToShip->getDimensionUnitCode())
					->setWeight($shipResult->getBillingWeight())
					->setLength($packageToShip->getLength())
					->setWidth($packageToShip->getWidth())
					->setHeight($packageToShip->getHeight())
					->setTrackingNumber($packageShipped['tracking_number'])
					->setCurrencyUnits($shipResult->getCurrencyUnits())
					->setTransportationCharge($shipResult->getTransportationShippingCharges())
					->setServiceOptionCharge($shipResult->getServiceOptionsShippingCharges())
					->setShippingTotal($shipResult->getTotalShippingCharges())
					->setNegotiatedTotal($shipResult->getNegotiatedTotalShippingCharges())
					->setLabelFormat($packageShipped['label_image_format'])
					->setLabelImage($packageShipped['label_image'])
					->setCodLabelImage($packageShipped['cod_label_image'])
					->setDateShipped(date('Y-m-d H:i:s'))
					->save();
                
                $retval[] = $pkg;
            }
            
            if (Mage::getStoreConfig('carriers/fedex/mps_shipments')) {
                foreach ($retval as $pkg) {
                    $pkg->setOrderShipmentId($shipment->getEntityId())->save();
                }
            }
        }
        return $retval;
    }
    
    public function sendEmail($shipment, $packageToShip, $packageShipped)
    {
        if ($packageToShip->getData('confirmation')) {
            $shipment->sendEmail();
        }
        return $shipment;
    }
    
    public function jsonPackageItems($packageToShip)
    {
        $ret = array();
        
        foreach ($this->getItemsById($packageToShip) as $item) {
            $ret[] = array(
                'i' => $item['product_id'],
                'q' => $item['qty_to_ship']
            );
        }
        
        return json_encode($ret);
    }
    
    public function getItemsById($packageToShip)
    {
        $itemsById = array();

        foreach ($packageToShip->getItems() as $itemToShip) {
            $id                        = $itemToShip['id'];
            $count                     = isset($itemsById[$id]['qty_to_ship']) ? $itemsById[$id]['qty_to_ship'] : 0;
            $itemToShip['qty_to_ship'] = 1 + $count;
            $itemsById[$id]            = $itemToShip;        
        }
        
        return $itemsById;
    }
    
    /**
     * Send shipment request
     *
     * @param object $package
     * @return object
     */
    protected function _sendShipmentRequest($package)
    {
        $shipRequest = $this->_shipRequest;
		
        $request = $this->_prepareShipmentHeader();
		
		// Shipment request
        $request['RequestedShipment'] = array(
            'ShipTimestamp' => $shipRequest->getShipTimestamp(),
            'DropoffType' => $shipRequest->getDropoffType(),
            'ServiceType' => $shipRequest->getServiceType(),
            'PackagingType' => $package->getContainerCode(),
            'TotalWeight' => array(
                'Value' => $package->getWeight(),
                'Units' => $shipRequest->getWeightUnits()
            ),
            'Shipper' => $shipRequest->getShipperDetails(),
            'Recipient' => $shipRequest->getRecipientDetails(),
            'LabelSpecification' => $shipRequest->getLabelSpecification(),
            'RateRequestTypes' => $shipRequest->getRateType(),
			'PreferredCurrency' => $this->getCurrencyCode(),        
            'PackageDetail' => 'INDIVIDUAL_PACKAGES',
            'SignatureOptionDetail' => array(
                'OptionType' => $shipRequest->getSignature()
            ),
            'ShippingChargesPayment' => array(
                'PaymentType' => $shipRequest->getPayorType(),
                'Payor' => array(
					'ResponsibleParty' => array(
                    	'AccountNumber' => $shipRequest->getPayorAccount(),
						'Contact' => null,
						'Address' => array(
                    		'CountryCode' => $shipRequest->getPayorAccountCountry()
						)
					)
                )
            ),			
            'RequestedPackageLineItems' => array(
                'SequenceNumber' => $package->getSequenceNumber(),
                'Weight' => array(
                    'Value' => $package->getWeight(),
                    'Units' => $shipRequest->getWeightUnits()
                ),
                'CustomerReferences' => array(
                    '0' => array(
                        'CustomerReferenceType' => 'CUSTOMER_REFERENCE',
                        'Value' => $shipRequest->getCustomerReference()
                    ),
                    '1' => array(
                        'CustomerReferenceType' => 'INVOICE_NUMBER',
                        'Value' => $shipRequest->getInvoiceNumber()
                    )
                ),
                'ContentRecords' => $package->getContents()
            )
        );
        
        $request['RequestedShipment'] = array_merge($request['RequestedShipment'], $shipRequest->getMPSData());
                
		// Saturday delivery
        if ($shipRequest->getSaturdayDelivery()) {
            $specialServiceTypes[] = 'SATURDAY_DELIVERY';
        }
        
		// Dangerous goods
        if ($package['dangerous']) {
            $request['RequestedShipment']['RequestedPackageLineItems']['SpecialServicesRequested'] = array(
                'DangerousGoodsDetail' => array(
                    'Accessibility' => 'ACCESSIBLE',
                    'Options' => 'ORM_D'
                )
            );
            $specialServiceTypes[] = 'DANGEROUS_GOODS';
        }
		
		// COD
		if ($shipRequest->getCod()) {
            $request['RequestedShipment']['SpecialServicesRequested'] = array(
                'CodDetail' => array(
                    'CodCollectionAmount' => array(
                        'Amount' => $package['cod_amount'],
                        'Currency' => $this->getCurrencyCode()
                    ),
                    'CollectionType' => 'ANY'
                )
            );
            $specialServiceTypes[] = 'COD';
        }
        
        if (isset($specialServiceTypes)) {
            $request['RequestedShipment']['RequestedPackageLineItems']['SpecialServicesRequested']['SpecialServiceTypes'] = $specialServiceTypes;
        }        
        
		 // If Dimensions enabled for Shipment
		if (($package->getContainerCode() == "YOUR_PACKAGING") && (!$shipRequest->getEnableDimensions()) 
			&& ($package->getRoundedLength() && $package->getRoundedWidth() && $package->getRoundedHeight()))
		{
			 $request['RequestedShipment']['RequestedPackageLineItems']['Dimensions'] = array(
				'Length' => $package->getRoundedLength(),
				'Width' => $package->getRoundedWidth(),
				'Height' => $package->getRoundedHeight(),
				'Units' => $shipRequest->getDimensionUnits());		
		}
		
        // Check if shipment needs to be insured and if insurance amount is available
        if ($shipRequest->getInsureShipment()) {
            $request['RequestedShipment']['RequestedPackageLineItems']['InsuredValue'] = array(
                'Currency' => $this->getCurrencyCode(),
                'Amount' => $shipRequest->getInsureAmount()
            );
        }
        
        // If SmartPost is enabled
        if (Mage::getStoreConfig('carriers/fedex/enable_smartpost')) {
            $request['RequestedShipment']['SmartPostDetail'] = $shipRequest->getSmartPostDetails();
        }
        
        // International shipments
        if ($shipRequest->getTransactionType() == 'International') {
			
            // If tax ID number is present
            if ($this->getConfig('tax_id_number') != '') {
                $request['TIN'] = $this->getConfig('tax_id_number');
            }
            
            $itemDetails = array();            
				
            // Iterate through package items
            foreach ($package->getItems() as $_item) {
				
                /** Load item by order item id */
                $item = Mage::getModel('sales/order_item')->load($_item['id']);                
				
                $itemDetails[] = array(
                    'NumberOfPieces' => 1,
                    'Description' => $item->getName(),
                    'CountryOfManufacture' => $shipRequest->getShipperCountryCode(),
                    'Weight' => array(
                        'Value' => $item->getWeight() * $shipRequest->getWeightCoefficient(),
                        'Units' => $shipRequest->getWeightUnits()
                    ),
                    'Quantity' => $item->getQtyOrdered(),
                    'QuantityUnits' => 'EA',
                    'UnitPrice' => array(
                        'Amount' => sprintf('%01.2f', $item->getPrice()),
                        'Currency' => $this->getCurrencyCode()
                    ),
                    'CustomsValue' => array(
                        'Amount' => sprintf('%01.2f', ($item->getPrice())),
                        'Currency' => $this->getCurrencyCode()
                    )
                );
            }
			
            $request['RequestedShipment']['CustomsClearanceDetail'] = array(
                'DutiesPayment' => array(
                    'PaymentType' => 'SENDER',
                    'Payor' => array(
						'ResponsibleParty' => array(
                        	'AccountNumber' => $this->getFedexAccount(),
							'Contact' => null,
							'Address' => array(
                        		'CountryCode' => $shipRequest->getShipperCountryCode()
							)
						)
                    )
                ),
                'DocumentContent' => 'NON_DOCUMENTS',
                'CustomsValue' => array(
                    'Amount' => sprintf('%01.2f', $shipRequest->getCustomsValue()),
                    'Currency' => $this->getCurrencyCode()
                ),
                'Commodities' => $itemDetails,
                'ExportDetail' => array(
                    'B13AFilingOption' => 'NOT_REQUIRED'
                )
            );
        }
		
        try {
            
			Mage::Helper('shipsync')->mageLog($request, 'ship');
            
			$response = $this->_shipServiceClient->processShipment($request);
            
			Mage::Helper('shipsync')->mageLog($response, 'ship');
        }
		catch (SoapFault $ex) {
            throw Mage::exception('Mage_Shipping', $ex->getMessage());
        }
        
        return $response;
    }
    
    /*
     * Prepare shipment header
     *
     * @return array
     */
    protected function _prepareShipmentHeader()
    {
        $shipRequest = $this->_shipRequest;
        
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
        
        $transactionType = $shipRequest->getTransactionType();
        
        $transactionMethod = $shipRequest->getTransactionMethod();
        
        $request['TransactionDetail']['CustomerTransactionId'] = "*** $transactionMethod $transactionType Shipping Request v$this->_shipServiceVersion using PHP ***";
        $request['Version']                                    = array(
            'ServiceId' => 'ship',
            'Major' => $this->_shipServiceVersion,
            'Intermediate' => '0',
            'Minor' => '0'
        );
        
        return $request;
    }
    
    /*
     * Set special services
     * 
     * @param string
     * @param IllApps_Shipsync_Model_Shipment_Package
     * @return array
     */
    protected function _setSpecialServices($request, $package)
    {
    }
    
    /**
     * Parse shipment response
     *
     * @param stdclass Object $response
     * @return IllApps_Shipsync_Model_Shipping_Result
     */
    protected function _parseShipmentResponse($r)
    {
        $shipRequest = $this->_shipRequest;
        
        $response = Mage::getModel('shipsync/shipment_response')->setResponse($r);
        
        $result = Mage::getModel('shipsync/shipment_result');
        
        if ($response->setNotificationsErrors()) {
            throw Mage::exception('Mage_Shipping', $response->getErrors());
        } elseif ($response->isWarning()) {
            throw Mage::exception('Mage_Shipping', $response->incompleteApi());
        } else {
            
			if (!Mage::getStoreConfig('carriers/fedex/third_party')) {
                $result->setBillingWeightUnits($response->findStructure('Units'));
                $result->setBillingWeight($response->findStructure('Value'));
            }
            
            $packages = array();
            
            $packages[] = array(
                'package_number' => $response->getSequenceNumber(),
                'tracking_number' => $response->getTrackingNumber(),
                'masterTrackingId' => $response->getMasterTrackingId(),
                'service_option_currency' => '',
                'service_option_charge' => '',
                'label_image_format' => Mage::getStoreConfig('carriers/fedex/label_image'),
                'label_image' => base64_encode($r->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image),
                'cod_label_image' => $response->getCodLabelImage(),
                'html_image' => ''
            );
            
            $result->setPackages($packages);
            
            return $result;
        }
    }
    
	
	/**
     * Return container types of carrier
     *
     * @param Varien_Object|null $params
     * @return array|bool
     */
    public function getContainerTypes(Varien_Object $params = null)
    {
        if ($params == null) {
            return $this->_getAllowedContainers($params);
        }
        $method             = $params->getMethod();
        $countryShipper     = $params->getCountryShipper();
        $countryRecipient   = $params->getCountryRecipient();

        if (($countryShipper == self::USA_COUNTRY_ID && $countryRecipient == self::CANADA_COUNTRY_ID
            || $countryShipper == self::CANADA_COUNTRY_ID && $countryRecipient == self::USA_COUNTRY_ID)
            && $method == 'FEDEX_GROUND'
        ) {
            return array('YOUR_PACKAGING' => Mage::helper('usa')->__('Your Packaging'));
        } else if ($method == 'INTERNATIONAL_ECONOMY' || $method == 'INTERNATIONAL_FIRST') {
            $allTypes = $this->getContainerTypesAll();
            $exclude = array('FEDEX_10KG_BOX' => '', 'FEDEX_25KG_BOX' => '');
            return array_diff_key($allTypes, $exclude);
        } else if ($method == 'EUROPE_FIRST_INTERNATIONAL_PRIORITY') {
            $allTypes = $this->getContainerTypesAll();
            $exclude = array('FEDEX_BOX' => '', 'FEDEX_TUBE' => '');
            return array_diff_key($allTypes, $exclude);
        } else if ($countryShipper == self::CANADA_COUNTRY_ID && $countryRecipient == self::CANADA_COUNTRY_ID) {
            // hack for Canada domestic. Apply the same filter rules as for US domestic
            $params->setCountryShipper(self::USA_COUNTRY_ID);
            $params->setCountryRecipient(self::USA_COUNTRY_ID);
        }

        return $this->_getAllowedContainers($params);
    }

    /**
     * Return all container types of carrier
     *
     * @return array|bool
     */
    public function getContainerTypesAll()
    {
        return $this->getCode('packaging');
    }

    /**
     * Return structured data of containers witch related with shipping methods
     *
     * @return array|bool
     */
    public function getContainerTypesFilter()
    {
        return $this->getCode('containers_filter');
    }

}
