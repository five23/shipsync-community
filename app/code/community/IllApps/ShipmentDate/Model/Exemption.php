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

class IllApps_ShipmentDate_Model_Exemption extends IllApps_ShipmentDate_Model_Abstract
{
    protected $_isInstore;
    
    protected function _construct()
    {
        $this->_init('shipmentdate/exemption');
    }
    
    protected function _isDelivery()
    {
        return $this->_isInstore ? 0 : 1;
    }
    
    public function _beforeSave()
    {
        $this->rawMethodArrayToJson();
    }

    public function rawMethodArrayToJson()
    {
        $this->setShippingMethod(json_encode($this->getShippingMethodRaw()));
        $this->unsetData('shipping_method_raw');
        return $this;
    }

    public function rawDateToFormat()
    {
        $this->setDate(str_replace('/', '', $this->getDateRaw()));
        $this->unsetData('date_raw');
        return $this;
    }

    public function getDateRaw()
    {
        return $this->getDate();
    }

    public function getDateFormattedToJs()
    {        
        $dateArr = explode('/', $this->getDate());
        
        $month = (int) $dateArr[0] - 1;
        $date  = ltrim($dateArr[1], '0');

        $ret = $dateArr[2] . $month  . $date;

        return $ret;
    }
    
    /*
     * Return all exemptions for the appropriate method
     * 
     * @return array
     */
    public function getAllMethodExemptions()
    {
        return $this->getCollection()->getItemsByColumnValue('instore', $this->isInstore());
    }
    
    /*
     * Get closed offset, returns 1 if after cutoff time
     * 
     * @return int
     */
    public function getClosedOffset()
    {
        $closeTime = explode(',', Mage::getStoreConfig('shipmentdate/' . $this->getMethod() . '/close_time'));
        return ((int) $this->localDate('G') >= (int) $closeTime[0] && (int) $this->localDate('i') > (int) $closeTime[1]) ? 1 : 0;
    }

    /*
     * Get last available date to ship for calendar
     * 
     * @return array
     */
    public function getLastDeliveryDate()
    {
        $interval   = Mage::getStoreConfig('shipmentdate/' . $this->getMethod() . '/interval');
        $initDate   = $this->_initDate($interval, $this->getClosedOffset());
        $endDateArr = explode('/', date("m/d/Y", $initDate));
        
        return array('y' => $endDateArr[2], 'm' => (int) $endDateArr[0] - 1, 'd' => $endDateArr[1]);
    }

    /*
     * Get initial date formatted in plain text
     * 
     * @return string
     */
    public function getInitDateFormatted()
    {
        if($this->_canShipToday())
        {
            $this->_initDate = $this->_initDate($this->_isDelivery(), $this->getClosedOffset());
            return date("m/d/Y", $this->_initDate);
        }
        else
        {
            $this->_initDate = $this->_initDate($this->getFirstDayOffset(), 0);
            return date("m/d/Y", $this->_initDate);
        }
    }
    
    /*
     * initialize date for calendar
     * 
     * @return string: datetime
     */
    public function getInitDate($isInstore = 0)
    {
        $this->_isInstore = $isInstore;
        
        $initDate = $this->getInitDateFormatted();
        
        while($this->searchExemptionDate($initDate, $isInstore) == $initDate) {
            $this->_initDate = $this->iterateInitDate();
            $initDate = date("m/d/Y", $this->_initDate);
        
            if(!$this->_canShipToday(date('w', $this->_initDate), 0)) {
                $this->_initDate = $this->iterateInitDate($this->getFirstDayOffset(date('w', $this->_initDate)));
                $initDate = date("m/d/Y", $this->_initDate);
            }
        }
        
        return $initDate;
    }
    
    /*
     * searchExemptionDate
     * 
     * search if paramater has extension for matching day
     * 
     * @return bool
     */
    public function searchExemptionDate($date, $isInstore)
    {
        return $this->getCollection()->addFilter('instore', $isInstore)->getItemByColumnValue('date', $date) ? true : false;
    }
    
    /*
     * iterateInitDate
     * 
     * @param int
     * @return datetime
     */
    public function iterateInitDate($offset = 1)
    {
        return mktime(0, 0, 0, date('m', $this->_initDate), date('d', $this->_initDate) + $offset, date('Y', $this->_initDate));
    }   

    /*
     * get selected date
     * 
     * @mixed (string || false)
     */
    public function getSelectedDate()
    {
        $date = $this->getSessionDateUpdated();
        return $date ? $date : '';
    }

    /*
     * Get init date formated as array
     * 
     * @return array
     */
    public function getInitDateArray()
    {
        if(!$this->_initDate) { $this->getInitDateFormatted(); }
        return array('y' => date("Y", $this->_initDate), 'm' => (int) date("m", $this->_initDate) - 1, 'd' => date("d", $this->_initDate));
    }

    /*
     * Get init date
     * 
     * @return int:unix timestamp
     */
    protected function _initDate($first, $closed)
    {
        return mktime(0, 0, 0, $this->localDate('m'), $this->localDate('d') + $first + $closed, $this->localDate('Y'));
    }

    /*
     * Test if can ship today
     * 
     * return bool
     */
    protected function _canShipToday($day = null, $closedOffset = null)
    {
        if(is_null($day)) { $day = $this->localDate('w'); }
        if(is_null($closedOffset)) { $closedOffset = $this->getClosedOffset(); }
        if(in_array($day, $this->getWeekendDaysArray())) { return false; }
        
        return in_array(($day + $this->_isDelivery() + $closedOffset) % 7, $this->getActiveDays());
    }

    /*
     * Get Active Days (!weekends)
     * 
     * @return array
     */
    public function getActiveDays()
    {
        $weekendDays = $this->getWeekendDaysArray();

        for($i=0; $i<7; $i++)
        {
            if(!in_array($i, $weekendDays)) { $active[] = $i; }
        }
        
        return $active;
    }

    /*
     * Get first day offset - time until first avialable arrival day
     * 
     * @return int
     */
    public function getFirstDayOffset($dayOfWeek = null)
    {        
        if(is_null($dayOfWeek)) { $dayOfWeek = $this->localDate('w'); }
        
        $active = $this->getActiveDays();
        
        foreach($active = $this->getActiveDays() as $activeDay)
        {
            if($activeDay > $dayOfWeek) { return abs($activeDay - $dayOfWeek); }
        }
        return $active[0] - $dayOfWeek + 7;
    }
}