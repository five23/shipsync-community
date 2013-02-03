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
    protected $_shipServiceVersion = '9';
    protected $_shipServiceWsdlPath = 'ShipService_v9.wsdl';


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

        foreach ($itemsByOrigin as $key => $items)
        {
            if(isset($packagesByOrigin)) {
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
	$shipRequest = new Varien_Object();

	$shipRequest->setOrderId($request->getOrderId());
        $shipRequest->setOrder(Mage::getModel('sales/order')->loadByIncrementId($shipRequest->getOrderId()));
        $shipRequest->setShipmentObject($request->getShipmentObject());
        $shipRequest->setStore($shipRequest->getOrder()->getStore());
        $shipRequest->setPackages($request->getPackages());
	$shipRequest->setMethodCode($request->getMethodCode());
	$shipRequest->setServiceType($this->getUnderscoreCodeFromCode($shipRequest->getMethodCode()));
	$shipRequest->setDropoffType($this->getUnderscoreCodeFromCode(Mage::getStoreConfig('carriers/fedex/dropoff')));
        
	if (Mage::getStoreConfig('carriers/fedex/shipper_company'))
		{ $shipRequest->setShipperCompany($shipRequest->getOrder()->getStoreName(1)); }
	else	{ $shipRequest->setShipperCompany(Mage::app()->getStore()->getName()); }

	$shipRequest->setShipperStreetLines($request->getOrigStreet());
	$shipRequest->setShipperCity($request->getOrigCity());
	$shipRequest->setShipperPostalCode($request->getOrigPostcode());
	$shipRequest->setShipperCountryCode($request->getOrigCountryId());
	$shipRequest->setShipperPhone(Mage::getStoreConfig('shipping/origin/phone'));
	$shipRequest->setShipperStateOrProvinceCode($request->getOrigRegionCode());

	$shipRequest->setRecipientAddress($request->getRecipientAddress());
	$shipRequest->setInsureShipment($request->getInsureShipment());

	if ($request->getInsureAmount() != '')
		{ $shipRequest->setInsureAmount($request->getInsureAmount()); } //TODO how to handle this across multiple origins???? Algorithm to decide?
	else { $shipRequest->setInsureAmount(100); }

	if ($request->getRequireSignature())
		{ $shipRequest->setRequireSignature('DIRECT'); }

	else { $shipRequest->setRequireSignature('SERVICE_DEFAULT'); }

        if ($request->getSaturdayDelivery()) { $shipRequest->setSaturdayDelivery(true); }
        if ($request->getCod()) { $shipRequest->setCod(true); }

	if (Mage::getStoreConfig('carriers/fedex/address_validation') && ($shipRequest->getRecipientAddress()->getCountryId() == 'US')) {
	    $shipRequest->setResidential($this->getResidential($shipRequest->getRecipientAddress()->getStreet(), $shipRequest->getRecipientAddress()->getPostcode()));
	}
	else {
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
        foreach ($shipRequest->getPackages() as $packageToShip)
        {
            $shipResponse = $this->_sendShipmentRequest($packageToShip);    /** Send shipment request */
	    $shipResult   = $this->_parseShipmentResponse($shipResponse);   /** Parse response */

            /** Iterate through shipped packages */
	    foreach ($shipResult->getPackages() as $packageShipped)
            {
		$convertor = Mage::getModel('sales/convert_order');

                $shipment = $convertor->toShipment($shipRequest->getOrder());

                $itemsById = array();
                
                foreach ($packageToShip->getItems() as $itemToShip)
                {
                    $id = $itemToShip['id'];
                    $count = isset($itemsById[$id]['qty_to_ship']) ? $itemsById[$id]['qty_to_ship'] : 0;                  
                    $itemToShip['qty_to_ship'] = 1 + $count;
                    $itemsById[$id] = $itemToShip;
                }

		foreach ($itemsById as $itemToShip)
                {
                    $orderItem = $shipRequest->getOrder()->getItemById($itemToShip['id']);      // Get order item.
		    $item = $convertor->itemToShipmentItem($orderItem);				// Convert order item to shipment item
                    $item->setQty($itemToShip['qty_to_ship']);								// Set qty to 1 (..one item at a time)
                    $shipment->addItem($item);							// Add item to shipment
                }

                $track = Mage::getModel('sales/order_shipment_track')
                    ->setTitle($this->getCode('method', $shipRequest->getServiceType(), true))
                    ->setCarrierCode('fedex')
                    ->setNumber($packageShipped['tracking_number'])
                    ->setShipment($shipment);
		
                $shipment->addTrack($track);
                $shipment->register();
		$shipment->getOrder()->setIsInProcess(true);

                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($shipment)
                    ->addObject($shipment->getOrder())
                    ->save();

                $this->sendEmail($shipment, $packageToShip, $packageShipped);

                $pkg = Mage::getModel('shipping/shipment_package')
                    ->setOrderIncrementId($shipRequest->getOrder()->getIncrementId())
                    ->setOrderShipmentId($shipment->getId())
                    ->setCarrier('fedex')
                    ->setCarrierShipmentId($shipResult->getShipmentIdentificationNumber())
                    ->setWeightUnits($shipResult->getBillingWeightUnits())
                    ->setWeight($shipResult->getBillingWeight())
                    ->setTrackingNumber($packageShipped['tracking_number'])
                    ->setCurrencyUnits($shipResult->getCurrencyUnits())
                    ->setTransportationCharge($shipResult->getTransportationShippingCharges())
                    ->setServiceOptionCharge($shipResult->getServiceOptionsShippingCharges())
                    ->setShippingTotal($shipResult->getTotalShippingCharges())
                    ->setNegotiatedTotal($shipResult->getNegotiatedTotalShippingCharges())
                    ->setLabelFormat($packageShipped['label_image_format'])
                    ->setLabelImage($packageShipped['label_image'])
                    ->setCodLabelImage($packageShipped['cod_label_image'])
                    ->setDateShipped(date('Y-m-d H:i:s'))->save();

                $retval[] = $pkg;
            }
        }
        return $retval;
    }

    public function sendEmail($shipment, $packageToShip, $packageShipped)
    {        
        if ($packageToShip->getData('confirmation'))
        {
            $shipment->sendEmail();
        }
        return $shipment;
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

	$request['WebAuthenticationDetail'] = array(
	    'UserCredential' => array(
		'Key'      => $this->getFedexKey(),
		'Password' => $this->getFedexPassword()));

	$request['ClientDetail'] = array(
	    'AccountNumber' => $this->getFedexAccount(),
	    'MeterNumber'   => $this->getFedexMeter());

        /** Iterate through package items */
	foreach ($package->getItems() as $_item)
        {
            /** Load item by order item id */
	    $item = Mage::getModel('sales/order_item')->load($_item['id']);

	    /** Set contents to ship request */
            $contents[] = array(
                'ItemNumber' => $item['id'],
                'Description' => $item->getName(),
                'ReceivedQuantity' => 1);
        }

	/** Check if shipment is international */
	if ($shipRequest->getStore()->getConfig('shipping/origin/country_id') != $shipRequest->getRecipientAddress()->getCountryId())
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
	if ($shipRequest->getServiceType() == 'GROUND_HOME_DELIVERY' || $shipRequest->getServiceType() == 'FEDEX_GROUND')
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
		'CompanyName' => $shipRequest->getShipperCompany(),
		'PhoneNumber' => $shipRequest->getShipperPhone()),
	    'Address'     => array(
		'StreetLines'	      => $shipRequest->getShipperStreetLines(),
		'City'		      => $shipRequest->getShipperCity(),
		'StateOrProvinceCode' => $shipRequest->getShipperStateOrProvinceCode(),
		'PostalCode'	      => $shipRequest->getShipperPostalCode(),
		'CountryCode'	      => $shipRequest->getShipperCountryCode()));

	/** Recipient address */
	$recipient = array(
	    'Contact' => array(
		'PersonName'  => $shipRequest->getRecipientAddress()->getName(),
		'PhoneNumber' => $shipRequest->getRecipientAddress()->getTelephone()),
		'Address' => array(
		    'StreetLines'	  => $shipRequest->getRecipientAddress()->getStreet(),
		    'City'		  => $shipRequest->getRecipientAddress()->getCity(),
		    'StateOrProvinceCode' => $shipRequest->getRecipientAddress()->getRegionCode(),
		    'PostalCode'	  => $shipRequest->getRecipientAddress()->getPostcode(),
		    'CountryCode'	  => $shipRequest->getRecipientAddress()->getCountryId(),
		    'Residential'	  => $shipRequest->getResidential()));

	if ($shipRequest->getRecipientAddress()->getCompany() != '')
	{
	    $recipient['Contact']['CompanyName'] = $shipRequest->getRecipientAddress()->getCompany();
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

	$package['weight'] = (round($package['weight'], 1) > 0) ? $package['weight'] : 0.1;
        $package['length'] = (round($package['length']) > 0)    ? $package['length'] : 1;
	$package['width']  = (round($package['width']) > 0)     ? $package['width']  : 1;
	$package['height'] = (round($package['height']) > 0)    ? $package['height'] : 1;

        $docTabContent = array( 'DocTabContent' =>
                                array('DocTabContentType' => 'STANDARD'));

	/** Shipment request */
	$request['RequestedShipment'] = array(
	    'ShipTimestamp'	 => date('c'),
	    'DropoffType'	 => $shipRequest->getDropoffType(),
	    'ServiceType'	 => $shipRequest->getServiceType(),
	    'PackagingType'	 => $this->getFedexBoxType($package['container_code']),
	    'TotalWeight'	 => array(
		'Value' => $package['weight'],
		'Units' => $weightUnit),
	    'Shipper'		 => $shipper,
	    'Recipient'		 => $recipient,
	    'LabelSpecification' => array(
		'LabelFormatType'	   => 'COMMON2D',
                'ImageType'		   => Mage::getStoreConfig('carriers/fedex/label_image'),
                'LabelStockType'	   => Mage::getStoreConfig('carriers/fedex/label_stock'),
                'LabelPrintingOrientation' => Mage::getStoreConfig('carriers/fedex/label_orientation'),
                'CustomerSpecifiedDetail'  => $docTabContent),
	    'RateRequestTypes' => array('ACCOUNT'),
	    'PackageCount' => 1,
	    'PackageDetail' => 'INDIVIDUAL_PACKAGES',
            'RequestedPackageLineItems' => array(
                'Weight' => array(
                    'Value' => $package['weight'],
                    'Units' => $weightUnit),
                'CustomerReferences' => array(
                    '0' => array(
                        'CustomerReferenceType' => 'CUSTOMER_REFERENCE',
                        'Value' => $shipRequest->getOrder()->getIncrementId() . '_pkg' . $package['package_number']),
		    '1' => array(
			'CustomerReferenceType' => 'INVOICE_NUMBER',
			'Value' => 'INV' . $shipRequest->getOrder()->getIncrementId())),
                'ContentRecords' => $contents));


        if (!Mage::getStoreConfig('carriers/fedex/shipping_dimensions_disable'))
        {
            $request['RequestedShipment']['RequestedPackageLineItems']['Dimensions'] = array(
                        'Length' => $package['length'],
                        'Width'  => $package['width'],
                        'Height' => $package['height'],
                        'Units'  => $this->getDimensionUnits());
        }
        
	/** Check if shipment needs to be insured and if insurance amount is available */
	if ($shipRequest->getInsureShipment())
	{
	    /** Request shipment insurance */
	    $request['RequestedShipment']['RequestedPackageLineItems']['InsuredValue'] = array(
		'Currency' => $this->getCurrencyCode(),
    		'Amount'   => $shipRequest->getInsureAmount());
	}

        /** Set require signature */
        $request['RequestedShipment']['SignatureOptionDetail'] = array(
            'OptionType' => $shipRequest->getRequireSignature());


        /** Ship on Saturday if applicable **/
        if ($shipRequest->getSaturdayDelivery())
        {
            $specialServiceTypes[] = 'SATURDAY_DELIVERY';
        }

	/** If package is dangerous */
        if ($package['dangerous'])
        {
            $request['RequestedShipment']['RequestedPackageLineItems']['SpecialServicesRequested'] = array(
                'DangerousGoodsDetail' => array('Accessibility' => 'ACCESSIBLE', 'Options' => 'ORM_D'));
            $specialServiceTypes[] = 'DANGEROUS_GOODS';
	}

        if ($shipRequest->getCod())
        {
	    $request['RequestedShipment']['SpecialServicesRequested'] = array(
		'CodDetail'	       => array(
		    'CodCollectionAmount' => array(
			'Amount' => $package['cod_amount'],
			'Currency' => $this->getCurrencyCode()),
		    'CollectionType' => 'ANY'));
            $specialServiceTypes[] = 'COD';
        }

        if (isset($specialServiceTypes))
        {
            $request['RequestedShipment']['SpecialServicesRequested']['SpecialServiceTypes'] = $specialServiceTypes;
        }

	/** If third party payer */
        if (Mage::getStoreConfig('carriers/fedex/third_party'))
        {
	     /** Set third party payment type */
	    $request['RequestedShipment']['ShippingChargesPayment']['PaymentType'] = 'THIRD_PARTY';

	    /** Set third party account and country */
	    $request['RequestedShipment']['ShippingChargesPayment']['Payor'] = array(
		'AccountNumber' => Mage::getStoreConfig('carriers/fedex/third_party_fedex_account'),
		'CountryCode'   => Mage::getStoreConfig('carriers/fedex/third_party_fedex_account_country'));
        }
        else
        {
	    /** Set sender payment type */
	    $request['RequestedShipment']['ShippingChargesPayment']['PaymentType'] = 'SENDER';

	    /** Set payor account and country */
	    $request['RequestedShipment']['ShippingChargesPayment']['Payor'] = array(
		'AccountNumber' => $this->getFedexAccount(),
		'CountryCode'   => $this->getFedexAccountCountry());
        }

        if (Mage::getStoreConfig('carriers/fedex/enable_smartpost'))
        {
            $request['RequestedShipment']['SmartPostDetail']['Indicia'] = Mage::getStoreConfig('carriers/fedex/smartpost_indicia_type');
            $request['RequestedShipment']['SmartPostDetail']['AncillaryEndorsement'] = Mage::getStoreConfig('carriers/fedex/smartpost_endorsement');
            $request['RequestedShipment']['SmartPostDetail']['SpecialServices'] = 'USPS_DELIVERY_CONFIRMATION';
            $request['RequestedShipment']['SmartPostDetail']['HubId'] = Mage::getStoreConfig('carriers/fedex/smartpost_hub_id');

            if (Mage::getStoreConfig('carriers/fedex/smartpost_customer_manifest_id'))
            {
                $request['RequestedShipment']['SmartPostDetail']['CustomerManifestId'] = Mage::getStoreConfig('carriers/fedex/smartpost_customer_manifest_id');
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
		$item = Mage::getModel('sales/order_item')->load($_item['id']);

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

                $itemdetails[] = array(
                    'NumberOfPieces' => 1,
                    'Description' => $item->getName(),
                    'CountryOfManufacture' => $shipRequest->getStore()->getConfig('shipping/origin/country_id'),
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
			'AccountNumber' => $this->getFedexAccount(),
			'CountryCode'   => Mage::getStoreConfig('carriers/fedex/account_country'))),
                'DocumentContent' => 'NON_DOCUMENTS',
                'CustomsValue' => array(
                    'Amount'   => sprintf('%01.2f', $itemtotal),
		    'Currency' => $this->getCurrencyCode()),
                'Commodities' => $itemdetails,
		'ExportDetail' => array(
		    'B13AFilingOption' => 'NOT_REQUIRED'));
        }

	try
	{
            Mage::Helper('shipsync')->mageLog($request, 'ship');
	    $response = $this->_shipServiceClient->processShipment($request);
            Mage::Helper('shipsync')->mageLog($response, 'ship');
	}
        catch (SoapFault $ex)
	{
	    throw Mage::exception('Mage_Shipping', $ex->getMessage());
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
	$shipRequest = $this->_shipRequest;

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
            $result = new Varien_Object();
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
                'label_image_format'	  => Mage::getStoreConfig('carriers/fedex/label_image'),
                'label_image'		  => base64_encode($response->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image),
                'cod_label_image'         =>
                    (isset($response->CompletedShipmentDetail->CodReturnDetail->Label->Parts->Image)) ?
                    base64_encode($response->CompletedShipmentDetail->CodReturnDetail->Label->Parts->Image) : null,
                'html_image'		  => '');

            $result->setPackages($packages);

	    return $result;
	}
        else
        {
	    $result = new Varien_Object();

	    /** Todo: Add support for third party shipment creation */
            if (!Mage::getStoreConfig('carriers/fedex/third_party'))
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
                'label_image_format'	  => Mage::getStoreConfig('carriers/fedex/label_image'),
                'label_image'		  => base64_encode($response->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image),
                'cod_label_image'         =>
                    (isset($response->CompletedShipmentDetail->CodReturnDetail->Label->Parts->Image)) ?
                    base64_encode($response->CompletedShipmentDetail->CodReturnDetail->Label->Parts->Image) : null,
                'html_image'		  => '');

            $result->setPackages($packages);

	    return $result;
        }
    }

    public function getFedexBoxType($code)
    {
        switch ($code)
        {
            case 'FEDEX_BOX_SMALL': return 'FEDEX_BOX';
            case 'FEDEX_BOX_MED': return 'FEDEX_BOX';
            case 'FEDEX_BOX_LARGE': return 'FEDEX_BOX';
            default: return $code;
        }
    }
}