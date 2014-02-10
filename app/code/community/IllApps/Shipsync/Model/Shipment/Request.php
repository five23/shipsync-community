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
 * IllApps_Shipsync_Model_Shipment_Package
 */
class IllApps_Shipsync_Model_Shipment_Request extends IllApps_Shipsync_Model_Shipment_Abstract
{
    protected $_masterTrackingId = false;
    protected $_isMasterPackage = true;
    
    /*
     * Returns formatted array for ship request
     * 
     * @return array
     */
    public function getShipperDetails()
    {
        return array(
            'Contact' => array(
                'CompanyName' => $this->getShipperCompany(),
                'PhoneNumber' => $this->getShipperPhone()
            ),
            'Address' => array(
                'StreetLines' => $this->getShipperStreetLines(),
                'City' => $this->getShipperCity(),
                'StateOrProvinceCode' => $this->getShipperStateOrProvinceCode(),
                'PostalCode' => $this->getShipperPostalCode(),
                'CountryCode' => $this->getShipperCountryCode()
            )
        );
    }
    
    /*
     * Returns formatted array for ship request
     *
     * @return array
     */
    public function getRecipientDetails()
    {
        $recipient = array(
            'Contact' => array(
                'PersonName' => $this->getRecipientAddress()->getName(),
                'PhoneNumber' => $this->getRecipientAddress()->getTelephone()
            ),
            'Address' => array(
                'StreetLines' => $this->getRecipientAddress()->getStreet(),
                'City' => $this->getRecipientAddress()->getCity(),
                'StateOrProvinceCode' => $this->getRecipientAddress()->getRegionCode(),
                'PostalCode' => $this->getRecipientAddress()->getPostcode(),
                'CountryCode' => $this->getRecipientAddress()->getCountryId(),
                'Residential' => $this->getResidential()
            )
        );
        
        if ($this->getRecipientAddress()->getCompany() != '') {
            $recipient['Contact']['CompanyName'] = $this->getRecipientAddress()->getCompany();
        }
        
        return $recipient;
    }
    
    /*
     * Returns formatted array for ship request
     * 
     * @return array
     */
    public function getLabelSpecification()
    {
        return array(
            'LabelFormatType' => 'COMMON2D',
            'ImageType' => Mage::getStoreConfig('carriers/fedex/label_image'),
            'LabelStockType' => Mage::getStoreConfig('carriers/fedex/label_stock'),
            'LabelPrintingOrientation' => Mage::getStoreConfig('carriers/fedex/label_orientation'),
            'CustomerSpecifiedDetail' => array(
                'DocTabContent' => array(
                    'DocTabContentType' => 'STANDARD'
                )
            )
        );
    }
    
    /*
     * Returns formatted array for ship request
     *
     * @return array
     */
    public function getSmartPostDetails()
    {
        $detail = array(
            'Indicia' => Mage::getStoreConfig('carriers/fedex/smartpost_indicia_type'),
            'AncillaryEndorsement' => Mage::getStoreConfig('carriers/fedex/smartpost_endorsement'),
            'SpecialServices' => 'USPS_DELIVERY_CONFIRMATION',
            'HubId' => Mage::getStoreConfig('carriers/fedex/smartpost_hub_id')
        );
        
        return Mage::getStoreConfig('carriers/fedex/smartpost_customer_manifest_id') ? array_merge($detail, Mage::getStoreConfig('carriers/fedex/smartpost_customer_manifest_id')) : $detail;
    }
    
    /*
     * Set Master Tracking Id
     * 
     * @return IllApps_Shipsync_Model_Shipment_Request
     */
    public function setMasterTrackingId($id)
    {
        foreach ($id as $key => $val) {
            $arr[$key] = $val;
        }
        $this->_masterTrackingId = $arr;
        return $this;
    }
    
    /*
     * Get Master Tracking Id
     * 
     * @return array
     */
    public function getMasterTrackingId()
    {
        return $this->_masterTrackingId;
    }
    
    /*
     * Return count of all packages in MPS Shipment
     *
     * @return int
     */
    public function getPackageCount()
    {
        return Mage::getStoreConfig('carriers/fedex/mps_shipments') ? count($this->getPackages()) : 1;
    }
    
    /*
     * Return total weight of shipment across all packages
     * 
     * @return float
     */
    public function getTotalShipmentWeight()
    {
        $total = 0;
        
        foreach ($this->getPackages() as $package) {
            $total += $package->getWeight();
        }
        
        return $total;
    }
    
    /*
     * Get transaction type
     *
     * @return string
     */
    public function getTransactionType()
    {
        return $this->getStore()->getConfig('shipping/origin/country_id') != $this->getRecipientAddress()->getCountryId() ? 'International' : 'Domestic';
    }
    
    /*
     * Get transaction method
     *
     * @return string
     */
    public function getTransactionMethod()
    {
        return ($this->getServiceType() == 'GROUND_HOME_DELIVERY' || $this->getServiceType() == 'FEDEX_GROUND') ? 'Ground' : 'Express';
    }
    
    /*
     * Return formatted MPS data if necessary
     *
     * @return array
     */
    public function getMpsData()
    {
        if (!Mage::getStoreConfig('carriers/fedex/mps_shipments')) {
            return array(
                'PackageCount' => 1
            );
        }
        
        else if ($this->getMasterTrackingId()) {
            return array(
                'MasterTrackingId' => $this->getMasterTrackingId(),
                'PackageCount' => $this->getPackageCount(),
                'TotalShipmentWeight' => $this->getTotalShipmentWeight()
            );
        } else {
            return array(
                'PackageCount' => $this->getPackageCount(),
                'TotalShipmentWeight' => $this->getTotalShipmentWeight()
            );
        }
    }
    
    /*
     * Toggle after Master package details retrieved
     *
     * @return IllApps_Shipsync_Model_Shipment_Request
     */
    public function setIsChildPackage()
    {
        $this->_isMasterPackage = false;
        return $this;
    }
    
    /*
     * Test for Master Package
     *
     * @return bool
     */
    public function isMasterBool()
    {
        return $this->_isMasterPackage;
    }
    
    public function getPayorAccount()
    {
        return Mage::getStoreConfig('carriers/fedex/third_party') ? Mage::getStoreConfig('carriers/fedex/third_party_fedex_account') : $this->getFedexAccount();
    }
    
    public function getPayorAccountCountry()
    {
        return Mage::getStoreConfig('carriers/fedex/third_party') ? Mage::getStoreConfig('carriers/fedex/third_party_fedex_account') : $this->getFedexAccountCountry();
    }
    
    public function getPayorType()
    {
        return Mage::getStoreConfig('carriers/fedex/third_party') ? 'THIRD_PARTY' : 'SENDER';
    }
}