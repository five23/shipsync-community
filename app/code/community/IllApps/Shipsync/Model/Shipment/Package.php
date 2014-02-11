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
class IllApps_Shipsync_Model_Shipment_Package extends Mage_Core_Model_Abstract
{
    /**
     * _construct
     */
    protected function _construct()
    {
        $this->_init('shipping/shipment_package');
    }
    
    /*
     * Returns parsed item details for ship request
     * 
     * @return array
     */
    public function returnPackageContents()
    {
        foreach ($this->getItems() as $_item) {
            $item = Mage::getModel('sales/order_item')->load($_item['id']);
            
            $contents[] = array(
                'ItemNumber' => $item['id'],
                'Description' => $item->getName(),
                'ReceivedQuantity' => 1
            );
        }
        
        return $contents;
    }
    
    /*
     * Returns weight converted into KG if applicable
     *
     * @return float
     */
    public function getFormattedWeight()
    {
        return $this->getRoundedWeight();
    }
    
    /*
     * Round and return
     *
     * @return float
     */
    public function getRoundedWeight()
    {
        return round($this->getWeight(), 1) > 0 ? $this->getWeight() : 0.1;
    }
    
    /*
     * Round and return
     *
     * @return float
     */
    public function getRoundedLength()
    {
        return round($this->getLength(), 1) > 0 ? $this->getLength() : 0.1;
    }
    
    /*
     * Round and return
     *
     * @return float
     */
    public function getRoundedWidth()
    {
        return round($this->getWidth(), 1) > 0 ? $this->getWidth() : 0.1;
    }
    
    /*
     * If you haven't picked it up by now, there's no hope for you
     *
     * @return float
     */
    public function getRoundedHeight()
    {
        return round($this->getHeight(), 1) > 0 ? $this->getHeight() : 0.1;
    }
    
    /*
     * Sequence number for  shipment
     *
     * @return int
     */
    public function getSequenceNumber()
    {
        return Mage::getStoreConfig('carriers/fedex/mps_shipments') ? ($this->getPackageNumber() + 1) : 1;
    }
    
    /*
     *
     */
    public function getPackageValue()
    {
        return $this->_packageValue > 0 ? $this->_packageValue : $this->calculateValue();
    }
    
    /*
     *
     */
    public function calculateValue()
    {
        foreach ($this->getItems as $item) {
            $this->_packageValue += $item->getPrice();
        }
        return $this->_packageValue;
    }
}