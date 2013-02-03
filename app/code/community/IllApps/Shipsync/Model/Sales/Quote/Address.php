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
 * Address validation
 */
class IllApps_Shipsync_Model_Sales_Quote_Address extends Mage_Sales_Model_Quote_Address
{
    
    /**
     * Validate addresses
     *
     * @return bool
     */
    public function validate()
    {
	/** Filter PO Boxes */
        if ($this->getCollectShippingRates() && $this->getStreet(-1) && Mage::getStoreConfig('carriers/fedex/filter_po_boxes')
            && preg_match('/^(POB\s+|P\s*O\s*Box|Post Office Box|Postal Box|Box|Boite Postal)\s*[0-9a-z]+(-[0-9a-z]+)*/i', $this->getStreet(1)))
        {
	    /** Alert customer */
            return array(Mage::helper('customer')->__("We're sorry, we do not ship to PO Boxes."));
        }

	/** If country is US, street is present, and address validation is enabled */
        if ($this->getCollectShippingRates() && ($this->getCountryId() == 'US') && $this->getStreet(-1) && Mage::getStoreConfig('carriers/fedex/address_validation'))
        {
	    /** Set residential to false in anticipation of address validation */
	    $this->setResidential(false);

	    /** If test mode */
            if (Mage::getStoreConfig('carriers/fedex/test_mode'))
            {
                $request['WebAuthenticationDetail']['UserCredential']['Key'] = Mage::getStoreConfig('carriers/fedex/test_key');
                $request['WebAuthenticationDetail']['UserCredential']['Password'] = Mage::getStoreConfig('carriers/fedex/test_password');
                $request['ClientDetail']['AccountNumber'] = Mage::getStoreConfig('carriers/fedex/test_account');
                $request['ClientDetail']['MeterNumber'] = Mage::getStoreConfig('carriers/fedex/test_meter');

                $wsdl_path = dirname(__FILE__) . '/wsdl/test/AddressValidationService_v2.wsdl';
            }
	    /** Production mode */
            else
            {
                $request['WebAuthenticationDetail']['UserCredential']['Key'] = Mage::getStoreConfig('carriers/fedex/key');
                $request['WebAuthenticationDetail']['UserCredential']['Password'] = Mage::getStoreConfig('carriers/fedex/password');
                $request['ClientDetail']['AccountNumber'] = Mage::getStoreConfig('carriers/fedex/account');
                $request['ClientDetail']['MeterNumber'] = Mage::getStoreConfig('carriers/fedex/meter');

                $wsdl_path = dirname(__FILE__) . '/wsdl/AddressValidationService_v2.wsdl';
            }

            try { $client = new SoapClient($wsdl_path); }   /** Instantiate soap client */
            catch (Exception $ex) { return true; }	    /** Fail silently, since validation is non-essential */

            $request['TransactionDetail'] = array('CustomerTransactionId' => ' *** Address Validation Request v2 using PHP ***'); /** Transaction detail */
            $request['Version'] = array('ServiceId' => 'aval', 'Major' => '2', 'Intermediate' => '0', 'Minor' => '0');		  /** Set version */
            $request['RequestTimestamp'] = date('c');										  /** Set timestamp */

            $request['Options'] = array(
                'CheckResidentialStatus' => 1,
                'MaximumNumberOfMatches' => 5,
                'StreetAccuracy' => 'LOOSE',
                'DirectionalAccuracy' => 'LOOSE',
                'CompanyNameAccuracy' => 'LOOSE',
                'ConvertToUpperCase' => 1,
                'RecognizeAlternateCityNames' => 1,
                'ReturnParsedElements' => 1);

	    /** Strip null streetlines */
	    foreach ($this->getStreet() as $street)
            {
                if ($street != null) { $streets[] = $street; }
            }

	    /** Addresses to validate */
	    $request['AddressesToValidate'] = array(
                0 => array(
                    'AddressId' => 'Destination',
                    'Address' => array(
			'StreetLines'	      => $streets,
			'City'		      => $this->getCity(),
			'StateOrProvinceCode' => $this->getRegionCode(),
			'CountryCode'         => 'US',
			'PostalCode'	      => $this->getPostcode())));

            try
            {
		/** Get validation response */
                $_response = $client->addressValidation($request);

		/** If error response */
		if ($_response->HighestSeverity == 'ERROR' || $_response->HighestSeverity == 'FAILURE')
		{
		    $msg = '';

		    /** If multiple notifications */
		    if (is_array($_response->Notifications))
		    {
			/** Iterate through notifications and set msg */
			foreach ($_response->Notifications as $notification) { $msg .= $notification->Severity . ': ' . $notification->Message . '<br />'; }
		    }
		    else
		    {
			/** Set message */
			$msg .= $_response->Notifications->Severity . ': ' . $_response->Notifications->Message . $newline;
		    }

		    return array(Mage::helper('customer')->__($msg));
		}
		elseif (is_array($_response->AddressResults->ProposedAddressDetails))
		{
		    $address = "";

		    /** Iterate through proposed addresses */
		    foreach ($_response->AddressResults->ProposedAddressDetails as $detail)
		    {
			$address .= $detail->Address->StreetLines . "\n" . $detail->Address->City . ", " .
			$detail->Address->StateOrProvinceCode . " " . $detail->ParsedAddress->ParsedPostalCode->Elements[0]->Value . "\n\n";
		    }

		    /** Alert customer */
		    return array(Mage::helper('checkout')->__("We're sorry, but your address was not found.  However, we found a few close matches :\n\n"
			    . $address . "Please correct your address and resubmit."));
		}
		/** If address is confirmed */
		elseif ($_response->AddressResults->ProposedAddressDetails->DeliveryPointValidation == 'CONFIRMED')
		{
		    /** Get postal code */
		    if (is_array($_response->AddressResults->ProposedAddressDetails->ParsedAddress->ParsedPostalCode->Elements))
		    {
			$postcode = $_response->AddressResults->ProposedAddressDetails->ParsedAddress->ParsedPostalCode->Elements[0]->Value;
		    }
		    else
		    {
			$postcode = $_response->AddressResults->ProposedAddressDetails->ParsedAddress->ParsedPostalCode->Elements->Value;
		    }

		    $this->setStreet($_response->AddressResults->ProposedAddressDetails->Address->StreetLines);		    /** Set street */
		    $this->setCity($_response->AddressResults->ProposedAddressDetails->Address->City);			    /** Set city */
		    $this->setPostcode($postcode);									    /** Set postal code */
		    $this->setCountryId($_response->AddressResults->ProposedAddressDetails->Address->CountryCode);	    /** Set country code */
		    $this->setRegionCode($_response->AddressResults->ProposedAddressDetails->Address->StateOrProvinceCode); /** Set region code */

		    /** If residential */
		    if ($_response->AddressResults->ProposedAddressDetails->ResidentialStatus == 'RESIDENTIAL')
			    { $this->setResidential(true); }
		}
		else
		{
		    return array(Mage::helper('customer')->__("Invalid address. Please check to make sure it is correct and try again."));
		}
            }
            /** Suppress fault */
	    catch (SoapFault $exception) { return true; }
        }

	/** Return true */
        return true;
    }
    
}