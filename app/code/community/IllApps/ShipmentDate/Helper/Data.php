<?php
/**
 * ShipmentDate
 *
 * @category   IllApps
 * @package    IllApps_ShipmentDate
 * @author     Jonathan Cantrell (j@kernelhack.com)
 * @copyright  Copyright (c) 2011 EcoMATICS, Inc. DBA IllApps (http://www.illapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class IllApps_ShipmentDate_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_instore = 0;
    /**
     * Converts Date to local Date or returns a safe string
     *
     * @return string: localized date or no date message
     */
    public function getFormatedDeliveryDate($date = null)
    {
        if(empty($date) ||$date == null || $date == '0000-00-00 00:00:00'){
            return Mage::helper('deliverydate')->__("No Delivery Date Specified.");
        }

        $formatedDate = Mage::helper('core')->formatDate($date, 'medium');

        return $formatedDate;
    }

    /*
     * Get delivery date formatted for save. Offset applied to save shipping date.
     * 
     * @return string:datetime
     */
    public function getFormatedDeliveryDateToSave($date = null)
    {
        if(empty($date) ||$date == null || $date == '0000-00-00 00:00:00'){
            return null;
        }

        try{
            $dateArray = explode("/", $date);
            if(count($dateArray) != 3){
                return null;
            }
            $gmtDate = gmmktime($this->_getTimeOffset(), 0, 0, $dateArray[0], $dateArray[1] - $this->_getShipmentOffset(), $dateArray[2]);
            $formatedDate = date('Y-m-d H:i:s', $gmtDate);
        } catch(Exception $e){
            return null;
        }

        return $formatedDate;
    }

    /*
     * Placeholder for expanded functionality
     * 
     * @return int
     */
    protected function _getShipmentOffset()
    {
        return Mage::getSingleton('core/session')->getInstorePickup() ? 0 : 1;
    }

    protected function _getTimeOffset()
    {
        return $this->_getShipmentDepartDeadline() - Mage::getModel('core/date')->getGmtOffset('hours');
    }

    protected function _getShipmentDepartDeadline()
    {
        return Mage::getStoreConfig('shipmentdate/' . Mage::getModel('shipmentdate/exemption')->getMethod() . '/deadline');
    }
}