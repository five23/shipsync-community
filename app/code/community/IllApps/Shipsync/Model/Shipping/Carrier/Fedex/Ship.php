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
        $origins = Mage::getModel('shipsync/shipping_package_origins');
        
        $this->_shipServiceClient = $this->_initWebServices($this->_shipServiceWsdlPath);
        
        $origins->prepareShipRequest($request, $itemsByOrigin, $packagesByOrigin);
        
        foreach ($itemsByOrigin as $key => $items) {
            if (isset($packagesByOrigin)) {
                $request->setPackages($packagesByOrigin[$key]);
            }
            $origins->setOrigins($request, (int) $items[0]['alt_origin']);
            
            $this->setShipRequest($request);
            
            $this->_shipResult = $this->_createShipment();
            
            $shipResultCollection[] = $this->_shipResult;
        }
        return $origins->collectMultipleShipments($shipResultCollection);
    }
    
    
	/*

    public function requestToShipment(Mage_Sales_Model_Order_Shipment $orderShipment)
    {
        $admin = Mage::getSingleton('admin/session')->getUser();
        $order = $orderShipment->getOrder();
        $address = $order->getShippingAddress();
        $shippingMethod = $order->getShippingMethod(true);
        $shipmentStoreId = $orderShipment->getStoreId();
        $shipmentCarrier = $order->getShippingCarrier();
		        
		$baseCurrencyCode = Mage::app()->getStore($shipmentStoreId)->getBaseCurrencyCode();
        
		if (!$shipmentCarrier) {
            Mage::throwException('Invalid carrier: ' . $shippingMethod->getCarrierCode());
        }
        

        $recipientRegionCode = Mage::getModel('directory/region')->load($address->getRegionId())->getCode();

        $originStreet1 = Mage::getStoreConfig(self::XML_PATH_STORE_ADDRESS1, $shipmentStoreId);
        $originStreet2 = Mage::getStoreConfig(self::XML_PATH_STORE_ADDRESS2, $shipmentStoreId);
        $storeInfo = new Varien_Object(Mage::getStoreConfig('general/store_information', $shipmentStoreId));

        if (!$admin->getFirstname() || !$admin->getLastname() || !$storeInfo->getName() || !$storeInfo->getPhone()
            || !$originStreet1 || !Mage::getStoreConfig(self::XML_PATH_STORE_CITY, $shipmentStoreId)
            || !$shipperRegionCode || !Mage::getStoreConfig(self::XML_PATH_STORE_ZIP, $shipmentStoreId)
            || !Mage::getStoreConfig(self::XML_PATH_STORE_COUNTRY_ID, $shipmentStoreId)
        ) {
            Mage::throwException(
                Mage::helper('sales')->__('Insufficient information to create shipping label(s). Please verify your Store Information and Shipping Settings.')
            );
        }

        $request = Mage::getModel('shipping/shipment_request');
        $request->setOrderShipment($orderShipment);
        $request->setShipperContactPersonName($admin->getName());
        $request->setShipperContactPersonFirstName($admin->getFirstname());
        $request->setShipperContactPersonLastName($admin->getLastname());
        $request->setShipperContactCompanyName($storeInfo->getName());
        $request->setShipperContactPhoneNumber($storeInfo->getPhone());
        $request->setShipperEmail($admin->getEmail());
        $request->setShipperAddressStreet(trim($originStreet1 . ' ' . $originStreet2));
        $request->setShipperAddressStreet1($originStreet1);
        $request->setShipperAddressStreet2($originStreet2);
        $request->setShipperAddressCity(Mage::getStoreConfig(self::XML_PATH_STORE_CITY, $shipmentStoreId));
        $request->setShipperAddressStateOrProvinceCode($shipperRegionCode);
        $request->setShipperAddressPostalCode(Mage::getStoreConfig(self::XML_PATH_STORE_ZIP, $shipmentStoreId));
        $request->setShipperAddressCountryCode(Mage::getStoreConfig(self::XML_PATH_STORE_COUNTRY_ID, $shipmentStoreId));
        $request->setRecipientContactPersonName(trim($address->getFirstname() . ' ' . $address->getLastname()));
        $request->setRecipientContactPersonFirstName($address->getFirstname());
        $request->setRecipientContactPersonLastName($address->getLastname());
        $request->setRecipientContactCompanyName($address->getCompany());
        $request->setRecipientContactPhoneNumber($address->getTelephone());
        $request->setRecipientEmail($address->getEmail());
        $request->setRecipientAddressStreet(trim($address->getStreet1() . ' ' . $address->getStreet2()));
        $request->setRecipientAddressStreet1($address->getStreet1());
        $request->setRecipientAddressStreet2($address->getStreet2());
        $request->setRecipientAddressCity($address->getCity());
        $request->setRecipientAddressStateOrProvinceCode($address->getRegionCode());
        $request->setRecipientAddressRegionCode($recipientRegionCode);
        $request->setRecipientAddressPostalCode($address->getPostcode());
        $request->setRecipientAddressCountryCode($address->getCountryId());
        $request->setShippingMethod($shippingMethod->getMethod());
        $request->setPackageWeight($order->getWeight());
        $request->setPackages($orderShipment->getPackages());
        $request->setBaseCurrencyCode($baseCurrencyCode);
        $request->setStoreId($shipmentStoreId);

        return $shipmentCarrier->requestToShipment($request);
    }
	*/
	
    /**
     * Set shipment request
     *
     * @param object
     * @return IllApps_Shipsync_Model_Shipping_Carrier_Fedex
     */
    public function setShipRequest($request)
    {		
		
        $shipRequest = Mage::getModel('shipsync/shipment_request');

		$shipperRegionCode = $request->getOrigRegionId();
        
		if (is_numeric($shipperRegionCode)) {
            $shipperRegionCode = Mage::getModel('directory/region')->load($shipperRegionCode)->getCode();
        }
				
        $shipRequest->setShipperCompany(Mage::app()->getStore()->getFrontendName());		
        $shipRequest->setOrderId($request->getOrderId());
        $shipRequest->setOrder(Mage::getModel('sales/order')->loadByIncrementId($shipRequest->getOrderId()));
        $shipRequest->setShipmentObject($request->getShipmentObject());
        $shipRequest->setStore($shipRequest->getOrder()->getStore());
        $shipRequest->setPackages($request->getPackages());
        $shipRequest->setMethodCode($request->getMethodCode());
        $shipRequest->setServiceType($this->getUnderscoreCodeFromCode($shipRequest->getMethodCode()));
        $shipRequest->setDropoffType($this->getUnderscoreCodeFromCode(Mage::getStoreConfig('carriers/fedex/dropoff')));                
        $shipRequest->setShipperStreetLines($request->getOrigStreet());
        $shipRequest->setShipperCity($request->getOrigCity());
        $shipRequest->setShipperPostalCode($request->getOrigPostcode());
        $shipRequest->setShipperCountryCode($request->getOrigCountryId());
        $shipRequest->setShipperPhone(Mage::getStoreConfig('shipping/origin/phone'));
        $shipRequest->setShipperStateOrProvinceCode($shipperRegionCode);        
        $shipRequest->setRecipientAddress($request->getRecipientAddress());
        $shipRequest->setInsureShipment($request->getInsureShipment());
        
        if ($request->getInsureAmount() != '') {
            $shipRequest->setInsureAmount($request->getInsureAmount());
        } else {
            $shipRequest->setInsureAmount(100);
        }
        
        if ($request->getRequireSignature()) {
            $shipRequest->setRequireSignature('DIRECT');
        } else {
            $shipRequest->setRequireSignature('SERVICE_DEFAULT');
        }
        
        if ($request->getSaturdayDelivery()) {
            $shipRequest->setSaturdayDelivery(true);
        }
		
        if ($request->getCod()) {
            $shipRequest->setCod(true);
        }
        
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
        
		$dimensionUnits = $package->getDimensionUnits() == Zend_Measure_Length::INCH ? 'IN' : 'CM';
		$weightUnits = $package->getWeightUnits() == Zend_Measure_Weight::POUND ? 'LB' : 'KG';
		$weight = $package->getFormattedWeight();		
        $request = $this->_prepareShipmentHeader();
		$customsValue = $shipRequest->getInsureAmount();
        
        // Shipment request
        $request['RequestedShipment'] = array(
            'ShipTimestamp' => date('c'),
            'DropoffType' => $shipRequest->getDropoffType(),
            'ServiceType' => $shipRequest->getServiceType(),
            'PackagingType' => $package->getContainerCode(),
            'TotalWeight' => array(
                'Value' => $weight,
                'Units' => $weightUnits
            ),
            'Shipper' => $shipRequest->getShipperDetails(),
            'Recipient' => $shipRequest->getRecipientDetails(),
            'LabelSpecification' => $shipRequest->getLabelSpecification(),
            'RateRequestTypes' => Mage::getStoreConfig('carriers/fedex/rate_type'),
			'PreferredCurrency' => $this->getCurrencyCode(),        
            'PackageDetail' => 'INDIVIDUAL_PACKAGES',
            'SignatureOptionDetail' => array(
                'OptionType' => $shipRequest->getRequireSignature()
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
                    'Value' => $weight,
                    'Units' => $weightUnits
                ),
                'CustomerReferences' => array(
                    '0' => array(
                        'CustomerReferenceType' => 'CUSTOMER_REFERENCE',
                        'Value' => $shipRequest->getOrder()->getIncrementId() . '_pkg' . $package->getPackageNumber()
                    ),
                    '1' => array(
                        'CustomerReferenceType' => 'INVOICE_NUMBER',
                        'Value' => 'INV' . $shipRequest->getOrder()->getIncrementId()
                    )
                ),
                'ContentRecords' => $package->returnPackageContents()
            )
        );
        
        $request['RequestedShipment'] = array_merge($request['RequestedShipment'], $shipRequest->getMPSData());
        
        $request = $this->_setSpecialServices($request, $package);
        
        // If Dimensions enabled for Shipment
		if (($package->getContainerCode() == "YOUR_PACKAGING" 
			 || $package->getContainerCode() == "SPECIAL_PAKAGING") 
			&& !Mage::getStoreConfig('carriers/fedex/shipping_dimensions_disable')) 
		{
            $request['RequestedShipment']['RequestedPackageLineItems']['Dimensions'] = array(
                'Length' => $package->getRoundedLength(),
                'Width' => $package->getRoundedWidth(),
                'Height' => $package->getRoundedHeight(),
                'Units' => $dimensionUnits);
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
            
            $itemdetails = array();            
				
            // Iterate through package items
            foreach ($package->getItems() as $_item) {
				
                /** Load item by order item id */
                $item = Mage::getModel('sales/order_item')->load($_item['id']);                
				
                $itemdetails[] = array(
                    'NumberOfPieces' => 1,
                    'Description' => $item->getName(),
                    'CountryOfManufacture' => $shipRequest->getStore()->getConfig('shipping/origin/country_id'),
                    'Weight' => array(
                        'Value' => $item->getWeight(),
                        'Units' => $weightUnits
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
                        		'CountryCode' => Mage::getStoreConfig('carriers/fedex/account_country')
							)
						)
                    )
                ),
                'DocumentContent' => 'NON_DOCUMENTS',
                'CustomsValue' => array(
                    'Amount' => sprintf('%01.2f', $customsValue),
                    'Currency' => $this->getCurrencyCode()
                ),
                'Commodities' => $itemdetails,
                'ExportDetail' => array(
                    'B13AFilingOption' => 'NOT_REQUIRED'
                )
            );
        }
        		
        try {
            
			Mage::Helper('shipsync')->mageLog($request, 'ship');
            
			$response = $this->_shipServiceClient->processShipment($request);
            
			//Mage::Helper('shipsync')->mageLog($this->_shipServiceClient->__getLastRequest(), 'soap_ship');
            //Mage::Helper('shipsync')->mageLog($this->_shipServiceClient->__getLastResponse(), 'soap_ship');
            
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
        if ($this->_shipRequest->getSaturdayDelivery()) {
            $specialServiceTypes[] = 'SATURDAY_DELIVERY';
        }
        
        if ($package['dangerous']) {
            $request['RequestedShipment']['RequestedPackageLineItems']['SpecialServicesRequested'] = array(
                'DangerousGoodsDetail' => array(
                    'Accessibility' => 'ACCESSIBLE',
                    'Options' => 'ORM_D'
                )
            );
            $specialServiceTypes[]                                                                 = 'DANGEROUS_GOODS';
        }
		
		if ($this->_shipRequest->getCod()) {
            $request['RequestedShipment']['SpecialServicesRequested'] = array(
                'CodDetail' => array(
                    'CodCollectionAmount' => array(
                        'Amount' => $package['cod_amount'],
                        'Currency' => $this->getCurrencyCode()
                    ),
                    'CollectionType' => 'ANY'
                )
            );
            $specialServiceTypes[]                                    = 'COD';
        }
        
        if (isset($specialServiceTypes))
        {
            $request['RequestedShipment']['RequestedPackageLineItems']['SpecialServicesRequested']['SpecialServiceTypes'] = $specialServiceTypes;
        }
        
        return $request;
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
                $result->setCurrencyUnits($response->findStructure('Currency'));
                $result->setTotalShippingCharges($response->findStructure('Amount'));
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
