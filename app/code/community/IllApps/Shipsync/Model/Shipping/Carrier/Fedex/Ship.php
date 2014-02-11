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
    
    
    /**
     * Set shipment request
     *
     * @param object
     * @return IllApps_Shipsync_Model_Shipping_Carrier_Fedex
     */
    public function setShipRequest($request)
    {
        $shipRequest = Mage::getModel('shipsync/shipment_request');

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
        $shipRequest->setShipperStateOrProvinceCode($request->getOrigRegionCode());        
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
        
        // Shipment request
        $request['RequestedShipment'] = array(
            'ShipTimestamp' => date('c'),
            'DropoffType' => $shipRequest->getDropoffType(),
            'ServiceType' => $shipRequest->getServiceType(),
            'PackagingType' => $this->getFedexBoxType($package->getContainerCode()),
            'TotalWeight' => array(
                'Value' => $weight,
                'Units' => $weightUnits
            ),
            'Shipper' => $shipRequest->getShipperDetails(),
            'Recipient' => $shipRequest->getRecipientDetails(),
            'LabelSpecification' => $shipRequest->getLabelSpecification(),
            'RateRequestTypes' => array(
                'ACCOUNT'
            ),
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
        if (!Mage::getStoreConfig('carriers/fedex/shipping_dimensions_disable')) {
            $request['RequestedShipment']['RequestedPackageLineItems']['Dimensions'] = array(
                'Length' => $package->getRoundedLength(),
                'Width' => $package->getRoundedWidth(),
                'Height' => $package->getRoundedHeight(),
                'Units' => $dimensionUnits
            );
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
                        'Amount' => sprintf('%01.2f', ($item->getPrice() * $item->getQty())),
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
                    'Amount' => sprintf('%01.2f', $item->getPackageValue()),
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
    
    public function getFedexBoxType($code)
    {
        switch ($code) {
            case 'FEDEX_BOX_SMALL':
                return 'FEDEX_BOX';
            case 'FEDEX_BOX_MED':
                return 'FEDEX_BOX';
            case 'FEDEX_BOX_LARGE':
                return 'FEDEX_BOX';
            default:
                return $code;
        }
    }
}
