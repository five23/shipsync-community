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
		$shipRequest->setCustomerReference($shipRequest->getOrderId() . '_pkg2' . $request->getPackageId());
		$shipRequest->setInvoiceNumber('INV' . $shipRequest->getOrderId());
					
		// Shipper region id
		$shipperRegionId = Mage::getStoreConfig('shipping/origin/region_id');				
        
		// Shipper region code
		if (is_numeric($shipperRegionId)) {
            $shipRequest->setShipperRegionCode(Mage::getModel('directory/region')->load($shipperRegionId)->getCode());
        }
		
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
		$shipRequest->setSignature($request->getSignature());

		// Saturday delivery
		$shipRequest->setSaturdayDelivery($request->getSaturdayDelivery());
		
		// COD
        $shipRequest->setCod($request->getCod());       
        
		// Rate types
		$shipRequest->setRateType(Mage::getStoreConfig('carriers/fedex/rate_type'));
        
		// Timestamp
		$shipRequest->setShipTimestamp(date('c'));
		
        if (($request->getAddressValidation() == 'ENABLED') && ($request->getResidenceDelivery() == 'VALIDATE')) {
            $shipRequest->setResidential($this->getResidential($shipRequest->getRecipientAddress()->getStreet(), $shipRequest->getRecipientAddress()->getPostcode()));
        }
		else if ($request->getResidenceDelivery() == 'ENABLED') {
			$shipRequest->setResidential(true);
		}
		else if ($request->getResidenceDelivery() == 'DISABLED') {
			$shipRequest->setResidential(false);
		}

		$shipRequest->setLabelStockType($request->getLabelStockType());
		$shipRequest->setLabelImageType($request->getLabelImageType());
		$shipRequest->setLabelPrintingOrientation($request->getLabelPrintingOrientation());
		$shipRequest->setEnableJavaPrinting($request->getEnableJavaPrinting());
		$shipRequest->setPrinterName($request->getPrinterName());
		$shipRequest->setPackingList($request->getPackingList());
		$shipRequest->setShipperCompany($request->getShipperCompany());
		$shipRequest->setReturnLabel($request->getReturnLabel());
        $shipRequest->setB13AFilingOption(Mage::getStoreConfig('carriers/fedex/b13a_filing_option'));

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

			$request = $this->_prepareShipmentHeader();

			// Shipment request
			$request['RequestedShipment'] = array(
				'ShipTimestamp' => $shipRequest->getShipTimestamp(),
				'DropoffType' => $shipRequest->getDropoffType(),
				'ServiceType' => $shipRequest->getServiceType(),
				'PackagingType' => $packageToShip->getContainerCode(),
				'TotalWeight' => array(
					'Value' => $packageToShip->getWeight(),
					'Units' => $shipRequest->getWeightUnits()
				),
				'Shipper' => $shipRequest->getShipperDetails(),
				'Recipient' => $shipRequest->getRecipientDetails(),
				'LabelSpecification' => array(
					'LabelFormatType' => 'COMMON2D',
					'ImageType' => $shipRequest->getLabelImageType(),
					'LabelStockType' => $shipRequest->getLabelStockType(),
					'LabelPrintingOrientation' => $shipRequest->getLabelPrintingOrientation(),
					'CustomerSpecifiedDetail' => array(
						'DocTabContent' => array(
							'DocTabContentType' => 'STANDARD'
						)
					)
				),
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
					'SequenceNumber' => $packageToShip->getSequenceNumber(),
					'Weight' => array(
						'Value' => $packageToShip->getWeight(),
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
					'ContentRecords' => $packageToShip->getContents()
				)
			);

			$request['RequestedShipment'] = array_merge($request['RequestedShipment'], $shipRequest->getMPSData());

			// Saturday delivery
			if ($shipRequest->getSaturdayDelivery()) {
				$specialServiceTypes[] = 'SATURDAY_DELIVERY';
			}

			// Dangerous goods
			if ($packageToShip['dangerous']) {
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
							'Amount' => $packageToShip['cod_amount'],
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
			if (($packageToShip->getContainerCode() == "YOUR_PACKAGING") && (!$packageToShip->getEnableDimensions())
				&& ($packageToShip->getRoundedLength() && $packageToShip->getRoundedWidth() && $packageToShip->getRoundedHeight()))
			{
				 $request['RequestedShipment']['RequestedPackageLineItems']['Dimensions'] = array(
					'Length' => $packageToShip->getRoundedLength(),
					'Width' => $packageToShip->getRoundedWidth(),
					'Height' => $packageToShip->getRoundedHeight(),
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
				foreach ($packageToShip->getItems() as $_item) {

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
						'B13AFilingOption' => $shipRequest->getB13AFilingOption()
					)
				);
			}

			$returnLabelImage = '';

			if ($shipRequest->getReturnLabel()) {

				$returnRequest = $request;

				$returnRequest['RequestedShipment']['Recipient'] = $shipRequest->getShipperDetails();
				$returnRequest['RequestedShipment']['Shipper']   = $shipRequest->getRecipientDetails();

				try {

					Mage::Helper('shipsync')->mageLog($returnRequest, 'ship');

					$returnResponse = $this->_shipServiceClient->processShipment($returnRequest);

					Mage::Helper('shipsync')->mageLog($returnResponse, 'ship');
				}
				catch (SoapFault $ex) {
					throw Mage::exception('Mage_Shipping', $ex->getMessage());
				}

				$returnLabelImage = base64_encode($returnResponse->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image);
			}

			try {

				Mage::Helper('shipsync')->mageLog($request, 'ship');

				$shipResponse = $this->_shipServiceClient->processShipment($request);

				Mage::Helper('shipsync')->mageLog($shipResponse, 'ship');
			}
			catch (SoapFault $ex) {
				throw Mage::exception('Mage_Shipping', $ex->getMessage());
			}

			$response = Mage::getModel('shipsync/shipment_response')->setResponse($shipResponse);

			$shipResult = Mage::getModel('shipsync/shipment_result');

			if ($response->setNotificationsErrors()) {
				throw Mage::exception('Mage_Shipping', $response->getErrors());
			} else {

				if (!Mage::getStoreConfig('carriers/fedex/third_party')) {
					$shipResult->setBillingWeightUnits($response->findStructure('Units'));
					$shipResult->setBillingWeight($response->findStructure('Value'));
				}

				$_packages = array();

				$_packages[] = array(
					'package_number' => $response->getSequenceNumber(),
					'tracking_number' => $response->getTrackingNumber(),
					'masterTrackingId' => $response->getMasterTrackingId(),
					'label_image_format' => $shipRequest->getLabelImageType(),
					'label_image' => base64_encode($shipResponse->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image),
					'return_label_image' => $returnLabelImage,
					'cod_label_image' => $response->getCodLabelImage()
				);

				$shipResult->setPackages($_packages);
			}


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
                    
                    $shipment->sendEmail();

                } else {
                    $shipment->addTrack($track);
                }

				// Append packing list
				if ($shipRequest->getLabelImageType() == 'PDF' &&
					$shipRequest->getLabelStockType() == 'PAPER_7X4.75' &&
					$shipRequest->getLabelPrintingOrientation() == 'BOTTOM_EDGE_OF_TEXT_FIRST' &&
					$shipRequest->getPackingList()) {
                
					// Create Zend PDF object
					$pdf = Zend_Pdf::parse(base64_decode($packageShipped['label_image']));

					// Set font style
					$font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);

					// Set font size and apply to current page
					$pdf->pages[0]->setFont($font, 8);

					// Set Y Position (## pixels from bottom of page)
					$yPosition = 360;

					// Draw header text (text, xPosition, yPosition)
					$pdf->pages[0]->drawText('ID', 30, $yPosition);
					$pdf->pages[0]->drawText('SKU', 60, $yPosition);
					$pdf->pages[0]->drawText('Name', 180, $yPosition);
					$pdf->pages[0]->drawText('Qty', 560, $yPosition);

					// Draw separator line (xPosition1, yPosition1, xPosition2, yPosition2)
					$pdf->pages[0]->drawLine(30, $yPosition-3, 575, $yPosition-3);

					// Set cursor (ie, line height)
					$cursor = 12;

					// Retrieve shipment items
					$items = $this->getItemsById($packageToShip);

					foreach ($items as $item) {

						// New line
						$yPosition -= $cursor;

						// Draw item text (text, xPosition, yPosition)
						$pdf->pages[0]->drawText($item['product_id'], 30, $yPosition);
						$pdf->pages[0]->drawText($item['sku'], 60, $yPosition);
						$pdf->pages[0]->drawText($item['name'], 180, $yPosition);
						$pdf->pages[0]->drawText($item['qty_to_ship'], 560, $yPosition);

						// Draw separator line (xPosition1, yPosition1, xPosition2, yPosition2)
						$pdf->pages[0]->drawLine(30, $yPosition-3, 575, $yPosition-3);
					}

					// Render & encode label image
					$labelImage = base64_encode($pdf->render());
				}
				else {
					$labelImage = $packageShipped['label_image'];
				}

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
					->setLabelImage($labelImage)
					->setReturnLabelImage($packageShipped['return_label_image'])
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

}
