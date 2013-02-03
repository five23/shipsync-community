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

class IllApps_ShipmentDate_Model_Abstract extends Mage_Core_Model_Abstract
{
    const INSTORE_PICKUP_CARRIER_CODE = 'instorepickup';
    
    protected $_y;
    protected $_m;
    protected $_d;
    protected $_method;
    protected $_initDate;
    protected $_localTimestamp;

    /*
     * Get delivery method, set if not present
     * 
     * @return string
     */
    public function getMethod()
    {
        if($this->_method) { return $this->_method; }
        else {
            $this->_method = ($this->isInstore() ? 'instore' : 'delivery');
            return $this->_method;
        }
    }

    /*
     * set delivery method
     * 
     * @return IllApps_ShipmentDate_Model_Exemption
     */
    public function setMethod($method)
    {
        $this->_method = $method;
        return $this;
    }
    
    /*
     * return true if instore
     * 
     * @return bool
     */
    public function isInstore()
    {
        return Mage::getSingleton('core/session')->getInstorePickup();
    }

    /*
     * Get session date updated
     * 
     * @return mixed (string || false)
     */
    public function getSessionDateUpdated()
    {
        return Mage::getSingleton('core/session')->getDateUpdate() ? Mage::getSingleton('core/session')->getDateUpdate() : false;
    }

    /*
     * Get today array
     * 
     * @return array
     */
    public function getTodayArray()
    {
        return array('y' => $this->getTodayYear(), 'm' => $this->getTodayMonth(), 'd' => $this->getTodayDate());
    }

    /*
     * Get weekend days as array
     * 
     * @return array
     */
    public function getWeekendDaysArray()
    {
        return explode(',', Mage::getStoreConfig('shipmentdate/' . $this->getMethod() . '/weekend'));
    }
    
    /*
     * @return int
     */
    public function getTodayYear()
    {
        if($this->_y) { return $this->_y; }
        else { $this->_y = $this->localDate('Y'); return $this->_y; }
    }

    /*
     * @return int
     */
    public function getTodayMonth()
    {
        if($this->_m) { return $this->_m; }
        else { $this->_m = (int) $this->localDate('m') - 1; return $this->_m; }
    }

    /*
     * @return int
     */
    public function getTodayDate()
    {
        if($this->_d) { return $this->_d; }
        else { $this->_d = $this->localDate('d'); return $this->_d; }
    }

    /*
     * @return int
     */
    public function setTodayYear($var)
    {
        $this->_y = $var;
        return $this;
    }

    /*
     * @return int
     */
    public function setTodayMonth($var)
    {
        $this->_m = $var;
        return $this;
    }

    /*
     * @return int
     */
    public function setTodayDate($var)
    {
        $this->_d = $var;
        return $this;
    }
    
    /*
     * 
     */
    public function localDate($args)
    {
        return date($args, $this->getLocalTimestamp());
    }
    
    /*
     * 
     */
    public function getLocalTimestamp()
    {
        if($this->_localTimestamp) { return $this->_localTimestamp; }
        else { 
            $this->_localTimestamp = Mage::getModel('core/date')->timestamp(time()); 
            return $this->_localTimestamp; 
        }
    }
}