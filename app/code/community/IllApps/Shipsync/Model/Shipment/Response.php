<?php

/**
 * ShipSync
 *
 * @category   IllApps
 * @package    IllApps_Shipsync
 * @author     David Kirby (d@kernelhack.com) / Jonathan Cantrell (j@kernelhack.com)
 * @copyright  Copyright (c) 2014 EcoMATICS, Inc. DBA IllApps (http://www.illapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * IllApps_Shipsync_Model_Shipment_Response
 */
class IllApps_Shipsync_Model_Shipment_Response extends Varien_Object
{
    protected $_response;
    protected $_errors = '';

    const INCOMPLETE_API = 'Error: Incomplete API response. Please check your request and try again.';

    /*
     * Set response
     * 
     * @return IllApps_Shipsync_Model_Shipment_Response
     */
    public function setResponse($response)
    {
        $this->_response = $response;
        return $this;
    }

    /*
     * Return response
     * 
     * @return stdClass Object
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /*
     * Set notifications errors
     * 
     * @return bool
     */
    public function setNotificationsErrors()
    {
        $this->setHighestSeverity($this->_response->HighestSeverity);
        
        if ($this->_response->HighestSeverity == 'FAILURE' || $this->_response->HighestSeverity == 'ERROR')
        {
            $this->collectErrors($this->_response->Notifications);
            $this->setErrors($this->_errors);
            return true;
        }
        return false;
    }

    /*
     * Collect response errors, recursively (you know, for style points and all)
     * 
     * @param stdClass Object
     */
    public function collectErrors($notification)
    {
        if(!is_array($notification))
        {
            $this->_errors .= $notification->Severity . ': ' . $notification->Message . '<br />';
        }
        else
        {
            foreach ($notification as $notice) { $this->collectErrors($notice); }
        }
    }

    /*
     * Is (response highest severity a) warning?
     * 
     * @return bool
     */
    public function isWarning()
    {
        return $this->getHighestSeverity() == 'WARNING' ? true : false;
    }

    /*
     * Incomplete Api
     * 
     * @return String
     */
    public function incompleteApi()
    {
        return Mage::helper('usa')->__(self::INCOMPLETE_API);
    }

    /*
     * Finds structure of response for the key given. Valid args
     *
     * @param String
     * @return String
     */
    public function findStructure($var)
    {
        if ($obj = $this->getPackageRateDetails())
        {
            return isset($obj->NetCharge->$var) ? $obj->NetCharge->$var : $obj->BillingWeight->$var;
        }
        elseif ($obj = $this->getShipmentRateDetails())
        {
            return isset($obj->NetCharge->$var) ? $obj->TotalNetCharge->$var : $obj->TotalBillingWeight->$var;
        }
		/*
		if (!Mage::getStoreConfig('carriers/fedex/test_mode') && ($rateType == 'PREFERRED')) {
			if (is_array($this->getShipmentRateDetails())) {                        
				foreach ($this->getShipmentRateDetails() as $ratedShipmentDetail) {                            
					if ($ratedShipmentDetail->ShipmentRateDetail->RateType == 'PREFERRED_ACCOUNT_SHIPMENT') {                            
						$shipmentRateDetail = $ratedShipmentDetail->ShipmentRateDetail;                       
					}
				}
			}
		}
		else {
			if (is_array($this->getShipmentRateDetails())) {
			{
				foreach ($this->getShipmentRateDetails() as $ratedShipmentDetail)
				{
					$_rateType = $ratedShipmentDetail->ShipmentRateDetail->RateType;

					if (($_rateType == 'PAYOR_' . $rateType . '_SHIPMENT') || ($_rateType == 'RATED_' . $rateType . '_SHIPMENT') ||
						($_rateType == 'PAYOR_' . $rateType . '_PACKAGE')  || ($_rateType == 'RATED_' . $rateType . '_PACKAGE'))
					{
						$shipmentRateDetail = $ratedShipmentDetail->ShipmentRateDetail; break;
					}
				}
			}
		}

		$rate = $shipmentRateDetail->TotalNetCharge->Amount;*/
                
    }

    /*
     * Tests if path is available in response.
     *
     * @return stdClass Object || false
     */
    public function getPackageRateDetails()
    {
        if (isset($this->_response->CompletedShipmentDetail->CompletedPackageDetails->PackageRating->PackageRateDetails))
        {
            $var = $this->_response->CompletedShipmentDetail->CompletedPackageDetails->PackageRating->PackageRateDetails;
            return is_array($var) ? $this->_response->CompletedShipmentDetail->CompletedPackageDetails->PackageRating->PackageRateDetails[0] : $var;
        }
        return false;
    }

    /*
     * Tests if path is available in response.
     *
     * @return stdClass Object || false
     */
    public function getShipmentRateDetails()
    {
        if (isset($this->_response->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails))
        {
            $var = $this->_response->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails;
            return is_array($var) ? $this->_response->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails[0] : $var;
        }
        return false;
    }

    /*
     *
     */
    public function getTrackingNumber()
    {
        return !is_array($this->_response->CompletedShipmentDetail->CompletedPackageDetails->TrackingIds) ?
            $this->_response->CompletedShipmentDetail->CompletedPackageDetails->TrackingIds->TrackingNumber :
            $this->_response->CompletedShipmentDetail->CompletedPackageDetails->TrackingIds[1]->TrackingNumber;
    }

    /*
     *
     */
    public function getMasterTrackingId()
    {
        return isset($this->_response->CompletedShipmentDetail->MasterTrackingId) ?
            $this->_response->CompletedShipmentDetail->MasterTrackingId : false;
    }

    /*
     *
     */
    public function getCodLabelImage()
    {
        return isset($this->_response->CompletedShipmentDetail->CodReturnDetail->Label->Parts->Image) ?
            base64_encode($this->_response->CompletedShipmentDetail->CodReturnDetail->Label->Parts->Image) : null;
    }

    /*
     *
     */
    public function getSequenceNumber()
    {
        return isset($this->_response->CompletedShipmentDetail->CompletedPackageDetails->SequenceNumber) ?
            $this->_response->CompletedShipmentDetail->CompletedPackageDetails->SequenceNumber : 1;
    }
}