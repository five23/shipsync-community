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
 * IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Address
 */
class IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Address extends IllApps_Shipsync_Model_Shipping_Carrier_Fedex
{
    
    
    protected $_addressRequest;
    protected $_addressResult;
    protected $_addressResultError;
    protected $_addressServiceClient;
    protected $_addressServiceVersion = '2';
    protected $_addressServiceWsdlPath = 'AddressValidationService_v2.wsdl';
    
    
    
    /**
     * validate
     *
     * @param Mage_Sales_Model_Quote_Address $request
     * @return mixed
     */
    public function validate(Mage_Sales_Model_Quote_Address $request)
    {
        $this->_addressServiceClient = $this->_initWebServices($this->_addressServiceWsdlPath);
        
        $this->setAddressRequest($request);
        
        $this->_addressResult = $this->_validateAddress();
        
        return $this->_addressResult;
    }
    
    
    
    /**
     * setAddressRequest
     *
     * @param Mage_Sales_Model_Quote_Address $request
     * @return mixed
     */
    public function setAddressRequest(Mage_Sales_Model_Quote_Address $request)
    {
        // Clean null streets
        foreach ($request->getStreet() as $street) {
            if ($street != '') {
                $streets[] = $street;
            }
        }
        
        // Set street
        $request->setStreet($streets);
        
        // Set address request
        $this->_addressRequest = $request;
        
        // Return this
        return $this;
    }
    
    
    
    /**
     * getAddressResultError
     *
     * @return mixed
     */
    public function getAddressResultError()
    {
        // If error is set, return msg
        if ($this->_addressResultError) {
            return array(
                Mage::helper('customer')->__($this->_addressResultError)
            );
        }
        
        return false;
    }
    
    
    
    /**
     * getResidential
     * 
     * @param array $street
     * @param string $postcode
     * @return bool
     */
    public function getResidential($street, $postcode)
    {
        $addressServiceClient  = $this->_initWebServices($this->_addressServiceWsdlPath);
        $addressServiceVersion = $this->_addressServiceVersion;
        
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
        
        $request['TransactionDetail']['CustomerTransactionId'] = '*** Address Validation Request v' . $addressServiceVersion . ' Using PHP ***';
        
        $request['Version'] = array(
            'ServiceId' => 'aval',
            'Major' => $addressServiceVersion,
            'Intermediate' => '0',
            'Minor' => '0'
        );
        
        $request['RequestTimestamp'] = date('c');
        
        $request['Options'] = array(
            'CheckResidentialStatus' => 1,
            'MaximumNumberOfMatches' => 1,
            'StreetAccuracy' => 'LOOSE',
            'DirectionalAccuracy' => 'LOOSE',
            'CompanyNameAccuracy' => 'LOOSE',
            'ConvertToUpperCase' => 1,
            'RecognizeAlternateCityNames' => 1,
            'ReturnParsedElements' => 1
        );
        
        $request['AddressesToValidate'] = array(
            0 => array(
                'AddressId' => 'Destination',
                'Address' => array(
                    'StreetLines' => $street,
                    'PostalCode' => $postcode,
                    'CountryCode' => 'US'
                )
            )
        );
        
        try {
            Mage::Helper('shipsync')->mageLog($request, 'address');
            $response = $addressServiceClient->addressValidation($request);
            Mage::Helper('shipsync')->mageLog($response, 'address');
        }
        catch (SoapFault $ex) {
            $this->_addressResultError = $ex->getMessage();
        }
        
        if (isset($response->AddressResults->ProposedAddressDetails->DeliveryPointValidation) && $response->AddressResults->ProposedAddressDetails->DeliveryPointValidation == 'CONFIRMED') {
            if ($response->AddressResults->ProposedAddressDetails->ResidentialStatus == 'RESIDENTIAL') {
                return true;
            }
            
            return false;
        } else {
            return true;
        }
    }
    
    
    /**
     * isPostOfficeBox
     *
     * param string $street
     * return bool
     */
    public function isPostOfficeBox($street)
    {
        if (preg_match('/^(POB\s+|P\s*O\s*Box|Post Office Box|Postal Box|Box|Boite Postal)\s*[0-9a-z]+(-[0-9a-z]+)*/i', (string) $street)) {
            return true;
        }
        return false;
    }
    
    
    
    /**
     * _validateAddress
     *
     * @return bool
     */
    protected function _validateAddress()
    {
        return $this->validateAddress();
    }
    
    
    
    /**
     * validateAddress
     * 
     * @return bool
     */
    public function validateAddress()
    {
        $addressRequest        = $this->_addressRequest;
        $addressServiceClient  = $this->_addressServiceClient;
        $addressServiceVersion = $this->_addressServiceVersion;
        
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
        
        $request['TransactionDetail']['CustomerTransactionId'] = '*** Address Validation Request v' . $addressServiceVersion . ' Using PHP ***';
        
        $request['Version'] = array(
            'ServiceId' => 'aval',
            'Major' => $addressServiceVersion,
            'Intermediate' => '0',
            'Minor' => '0'
        );
        
        $request['RequestTimestamp'] = date('c');
        
        $request['Options'] = array(
            'CheckResidentialStatus' => 1,
            'MaximumNumberOfMatches' => 5,
            'StreetAccuracy' => 'LOOSE',
            'DirectionalAccuracy' => 'LOOSE',
            'CompanyNameAccuracy' => 'LOOSE',
            'ConvertToUpperCase' => 1,
            'RecognizeAlternateCityNames' => 1,
            'ReturnParsedElements' => 1
        );
        
        $request['AddressesToValidate'] = array(
            0 => array(
                'AddressId' => 'Destination',
                'Address' => array(
                    'StreetLines' => $addressRequest->getStreet(),
                    'City' => $addressRequest->getCity(),
                    'StateOrProvinceCode' => $addressRequest->getRegionCode(),
                    'CountryCode' => $addressRequest->getCountryCode(),
                    'PostalCode' => $addressRequest->getPostcode()
                )
            )
        );
        
        try {
            Mage::Helper('shipsync')->mageLog($request, 'address');
            $response = $addressServiceClient->addressValidation($request);
            Mage::Helper('shipsync')->mageLog($response, 'address');
        }
        catch (SoapFault $ex) {
            $this->_addressResultError = $ex->getMessage();
        }
        
        return $this->_parseWsdlResponse($response);
    }
    
    
    
    /**
     * _parseWsdlResponse
     *
     * @param object $response
     * @return bool
     */
    protected function _parseWsdlResponse($response)
    {
        $addressResult = $this->_addressRequest;
        
        if ($response->HighestSeverity == 'ERROR' || $response->HighestSeverity == 'FAILURE') {
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
            
            $this->_addressResultError = $errorMsg;
            
            return true;
        } elseif (is_array($response->AddressResults->ProposedAddressDetails)) {
            $address = "";
            
            foreach ($response->AddressResults->ProposedAddressDetails as $detail) {
                $address .= $detail->Address->StreetLines . "\n" . $detail->Address->City . ", " . $detail->Address->StateOrProvinceCode . " " . $detail->ParsedAddress->ParsedPostalCode->Elements[0]->Value . "\n\n";
            }
            
            $this->_addressResultError = "We're sorry, but your address was not found.  However, we found a few close matches :\n\n" . $address . "Please correct your address and resubmit.";
        } elseif ($response->AddressResults->ProposedAddressDetails->DeliveryPointValidation == 'CONFIRMED') {
            if (is_array($response->AddressResults->ProposedAddressDetails->ParsedAddress->ParsedPostalCode->Elements)) {
                $postcode = $response->AddressResults->ProposedAddressDetails->ParsedAddress->ParsedPostalCode->Elements[0]->Value;
            } else {
                $postcode = $response->AddressResults->ProposedAddressDetails->ParsedAddress->ParsedPostalCode->Elements->Value;
            }
            
            $addressResult->setStreet($response->AddressResults->ProposedAddressDetails->Address->StreetLines);
            $addressResult->setCity($response->AddressResults->ProposedAddressDetails->Address->City);
            $addressResult->setPostcode($postcode);
            $addressResult->setCountryId($response->AddressResults->ProposedAddressDetails->Address->CountryCode);
            $addressResult->setRegionCode($response->AddressResults->ProposedAddressDetails->Address->StateOrProvinceCode);
        } elseif ($response->AddressResults->ProposedAddressDetails->DeliveryPointValidation == 'UNCONFIRMED' && $response->AddressResults->ProposedAddressDetails->ResidentialStatus == 'INSUFFICIENT_DATA') {
            if (is_array($response->AddressResults->ProposedAddressDetails->ParsedAddress->ParsedPostalCode->Elements)) {
                $postcode = $response->AddressResults->ProposedAddressDetails->ParsedAddress->ParsedPostalCode->Elements[0]->Value;
            } else {
                $postcode = $response->AddressResults->ProposedAddressDetails->ParsedAddress->ParsedPostalCode->Elements->Value;
            }
            
            $addressResult->setStreet($response->AddressResults->ProposedAddressDetails->Address->StreetLines);
            $addressResult->setCity($response->AddressResults->ProposedAddressDetails->Address->City);
            $addressResult->setPostcode($postcode);
            $addressResult->setCountryId($response->AddressResults->ProposedAddressDetails->Address->CountryCode);
            $addressResult->setRegionCode($response->AddressResults->ProposedAddressDetails->Address->StateOrProvinceCode);
        } else {
            $this->_addressResultError = 'Invalid address. Please check to make sure it is correct and try again.';
        }
        
        return $addressResult;
    }
    
}