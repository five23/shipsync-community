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
 * IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Track
 */
class IllApps_Shipsync_Model_Shipping_Carrier_Fedex_Track extends IllApps_Shipsync_Model_Shipping_Carrier_Fedex
{


    protected $_trackRequest;
    protected $_trackResult;
    protected $_trackResultError;
    protected $_trackServiceClient;
    protected $_trackServiceVersion = '4';
    protected $_trackServiceWsdlPath = 'TrackService_v4.wsdl';



    /**
     * getTracking
     *
     * @param array $trackings
     * @return mixed
     */
    public function getTracking($trackings)
    {
        // Track service client
	$this->_trackServiceClient = $this->_initWebServices($this->_trackServiceWsdlPath);

        // Set the request
	$this->setTrackingRequest();

        if (!is_array($trackings)) { $_trackings = array($trackings); }
        else                       { $_trackings = $trackings; }
        
        foreach ($_trackings as $_tracking) { $this->_getWsdlTracking($_tracking); }                        
                
        return $this->_trackResult;
    }



    /**
     * setTrackingRequest
     */
    protected function setTrackingRequest()
    {	        
	$this->_trackRequest = new Varien_Object();
    }

            
        
        
    /**
     * _getWsdlTracking
     * 
     * @param string $tracking
     */
    protected function _getWsdlTracking($tracking)
    {
        $trackRequest	     = $this->_trackRequest;
	$trackServiceVersion = $this->_trackServiceVersion;
	
	$request['WebAuthenticationDetail'] = array(
	    'UserCredential' => array(
		'Key'      => $this->getFedexKey(),
		'Password' => $this->getFedexPassword()));

	$request['ClientDetail'] = array(
	    'AccountNumber' => $this->getFedexAccount(),
	    'MeterNumber'   => $this->getFedexMeter());

	$request['TransactionDetail']['CustomerTransactionId'] = '*** Track Request v' . $trackServiceVersion . ' Using PHP ***';

	$request['Version'] = array('ServiceId' => 'trck', 'Major' => $trackServiceVersion, 'Intermediate' => '0', 'Minor' => '0');

        $request['PackageIdentifier']['Value'] = $tracking;
	$request['PackageIdentifier']['Type']  = 'TRACKING_NUMBER_OR_DOORTAG';
        $request['IncludeDetailedScans'] = true;

	//if (Mage::getStoreConfig('carriers/fedex/debug_firebug')) {
        //    Mage::Helper('shipsync')->log($request);
	//}

        try
        {
            Mage::Helper('shipsync')->mageLog($request, 'track');
            $response = $this->_trackServiceClient->track($request);
            Mage::Helper('shipsync')->mageLog($response, 'track');

            //if (Mage::getStoreConfig('carriers/fedex/debug_firebug')) {
            //    Mage::Helper('shipsync')->log($response);
           // }

            $this->_parseWsdlTrackingResponse($tracking, $response);
        }
        catch (SoapFault $ex)
        {
            $this->_trackResultError = $ex->getMessage();
        }
    }



    /**
     * _parseWsdlTrackingResponse
     * 
     * @param string $tracking
     * @param object $response
     * @return array
     */
    protected function _parseWsdlTrackingResponse($tracking, $response)
    {
        if (!$this->_trackResult)
        {
            $this->_trackResult = Mage::getModel('shipping/tracking_result');
        }
        
        if ($response->HighestSeverity == 'FAILURE' || $response->HighestSeverity == 'ERROR')
        {
            $errorMsg = '';
            
	    if (is_array($response->Notifications)) {
                foreach ($response->Notifications as $notification) {
		    $errorMsg .= $notification->Severity . ': ' .  $notification->Message . '<br />';
		}
	    }
	    else { $errorMsg .= $response->Notifications->Severity . ': ' . $response->Notifications->Message . '<br />'; }

	    $error = Mage::getModel('shipping/tracking_result_error');
            $error->setCarrier('fedex');
            $error->setCarrierTitle(Mage::getStoreConfig('carriers/fedex/title'));
            $error->setTracking($tracking);
            $error->setErrorMessage($errorMsg);

            $this->_trackResult->append($error);
	}
        else
        {
            if (!is_array($response->TrackDetails)) 
                 { $trackDetails = array($response->TrackDetails); }
            else { $trackDetails = $response->TrackDetails; }

            foreach ($trackDetails as $trackDetail)
            {
                $trackResultArray = array();

                $trackResultArray['service'] = isset($trackDetail->ServiceInfo) ? $trackDetail->ServiceInfo : '';
                $trackResultArray['status']  = $trackDetail->StatusDescription;

                if (isset($trackDetail->EstimatedDeliveryTimestamp))
                {
                    $timestamp = explode('T', $trackDetail->EstimatedDeliveryTimestamp);

                    $trackResultArray['deliverydate'] = $timestamp[0];
                    $trackResultArray['deliverytime'] = $timestamp[1];
                }                                

                if (isset($trackDetail->PackageWeight))                   
                {
                    $weight = $trackDetail->PackageWeight->Value;
                    $unit   = $trackDetail->PackageWeight->Units;

                    $trackResultArray['weight'] = "{$weight} {$unit}";
                }

                $packageProgress = array();

                if (isset($trackDetail->Events))                    
                {
                    if (!is_array($trackDetail->Events))
                         { $events = array($trackDetail->Events); }
                    else { $events = $trackDetail->Events; }

                    foreach ($events as $event)
                    {
                         $detailArray = array();

                         $detailArray['activity'] = $event->EventDescription;

                         $timestamp = explode('T', $event->Timestamp);

                         $detailArray['deliverydate'] = $timestamp[0];
                         $detailArray['deliverytime'] = $timestamp[1];                    

                         $addressArray = array();

                          if (isset($event->Address->City)) {
                            $addressArray[] = (string)$event->Address->City;
                          }
                          if (isset($event->Address->StateProvinceCode)) {
                            $addressArray[] = (string)$event->Address->StateProvinceCode;
                          }
                          if (isset($event->Address->CountryCode)) {
                            $addressArray[] = (string)$event->Address->CountryCode;
                          }
                          if ($addressArray) {
                            $detailArray['deliverylocation'] = implode(', ', $addressArray);
                          }
                          
                          $packageProgress[] = $detailArray;                                                  
                    }                                              

                    $trackResultArray['progressdetail'] = $packageProgress;                
                }             
                
                if ($trackResultArray)
                {
                    $trackResultStatus = Mage::getModel('shipping/tracking_result_status');
                    $trackResultStatus->setCarrier('fedex');
                    $trackResultStatus->setCarrierTitle(Mage::getStoreConfig('carriers/fedex/title'));
                    $trackResultStatus->setTracking($tracking);
                    $trackResultStatus->addData($trackResultArray);

                    $this->_trackResult->append($trackResultStatus);
                }
                else
                {
                    $error = Mage::getModel('shipping/tracking_result_error');
                    $error->setCarrier('fedex');
                    $error->setCarrierTitle(Mage::getStoreConfig('carriers/fedex/title'));
                    $error->setTracking($tracking);
                    $error->setErrorMessage('No tracking results retrieved.  Please try again later.');

                    $this->_trackResult->append($error);
                }
            }                
        }
    }
    
}