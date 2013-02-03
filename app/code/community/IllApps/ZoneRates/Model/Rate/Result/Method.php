<?php
/**
 * ZoneRates
 *
 * @category   IllApps
 * @package    IllApps_ZoneRates
 * @author     Jonathan Cantrell (j@kernelhack.com)
 * @copyright  Copyright (c) 2011 EcoMATICS, Inc. DBA IllApps (http://www.illapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class IllApps_ZoneRates_Model_Rate_Result_Method extends Mage_Shipping_Model_Rate_Result_Method
{
    protected $_shippable = false;

    /*
     * Inactive
     */
    public function setLocalResult($result)
    {
        $this->setData($result->getData());
        return $this;
    }
    
    /*
     * Is Available - test for ground shipments with TransitTime == 1
     * 
     * @return bool
     */
    public function isAvailable()
    {
        if($this->getTransitTime()) { return $this->getTransitTime() === 1 ? true : false; }
        else { return true;}
    }

    /*
     * Modify (Request) for Zone
     * 
     * @return IllApps_ZoneRates_Model_Rate_Result_Method
     */
    public function modifyForZone()
    {
        switch ($this->getZone()) {
            case 'A':
                if($this->getMethod() == 'FEDEXGROUND' || $this->getMethod() == 'GROUNDHOMEDELIVERY')
                {
                    $this->setZoneRatesPrice(5.00)
                        ->setMethodTitle('Ground Overnight')
                        ->setArrival('<i>Arrives ' . $this->getDate() . ' by ' . $this->getTime() . '</i>')
                        ->setShippable(true);
                }
                elseif($this->getMethod() == 'STANDARDOVERNIGHT')
                {
                    $this->setZoneRatesPrice(10.00)
                        ->setMethodTitle('Standard Overnight')
                        ->setArrival('<i>Arrives ' . $this->getDate() . ' by ' . $this->getTime() . '</i>')
                        ->setShippable(true);
                }
                elseif($this->getMethod() == 'PRIORITYOVERNIGHT')
                {
                    $this->setZoneRatesPrice(15.00)
                        ->setMethodTitle('Priority Overnight')
                        ->setArrival('<i>Arrives ' . $this->getDate() . ' by ' . $this->getTime() . '</i>')
                        ->setShippable(true);
                }
                break;
            case 'B':
                if($this->getMethod() == 'STANDARDOVERNIGHT')
                {
                    $this->setZoneRatesPrice(15.00)
                        ->setMethodTitle('Standard Overnight')
                        ->setArrival('<i>Arrives ' . $this->getDate() . ' by ' . $this->getTime() . '</i>')
                        ->setShippable(true);
                }
                elseif($this->getMethod() == 'PRIORITYOVERNIGHT')
                {
                    $this->setZoneRatesPrice(20.00)
                        ->setMethodTitle('Priority Overnight')
                        ->setArrival('<i>Arrives ' . $this->getDate() . ' by ' . $this->getTime() . '</i>')
                        ->setShippable(true);
                }
                break;
            case 'C':
                if($this->getMethod() == 'STANDARDOVERNIGHT')
                {
                    $this->setZoneRatesPrice(20.00)
                        ->setMethodTitle('Standard Overnight')
                        ->setArrival('<i>Arrives ' . $this->getDate() . ' by ' . $this->getTime() . '</i>')
                        ->setShippable(true);
                }
                elseif($this->getMethod() == 'PRIORITYOVERNIGHT')
                {
                    $this->setZoneRatesPrice(25.00)
                        ->setMethodTitle('Priority Overnight')
                        ->setArrival('<i>Arrives ' . $this->getDate() . ' by ' . $this->getTime() . '</i>')
                        ->setShippable(true);
                }
                break;
        }

        return $this;
    }

    /*
     * Get Delivery Date
     * 
     * @return String
     */
    public function getDate()
    {
        if($date = Mage::getSingleton('core/session')->getDateUpdate())
        {
            return $date;
        }
        return $this->getDeliveryTimestamp() ? date("m/d", strtotime($this->getDeliveryTimestamp())) : 'tomorrow';
    }

    /*
     * Get Delivery Time
     * 
     * @return String
     */
    public function getTime()
    {
        return $this->getDeliveryTimestamp() ? date("ga", strtotime($this->getDeliveryTimestamp())) : '8:00pm';
    }

    /*public function setShippable($bool)
    {
        $this->_shippable = $bool;
        return $this;
    }

    public function getShippable()
    {
        return $this->_shippable;
    }*/

    /*
     * Set Zone Rates Price
     * 
     * @param   float
     * @return  IllApps_ZoneRates_Model_Rate_Result_Method
     */
    public function setZoneRatesPrice($price)
    {
        $this->setPrice($price - $this->getFreeShippingModifier() + $this->getHandlingFee());
        return $this;
    }

    /*
     * Get Free Shipping Modifier
     * 
     * @return float
     */
    public function getFreeShippingModifier()
    {
        $model = Mage::getModel('zonerates/rates')->load($this->getZone(), 'zone');        
        return $model->getFreeShippingMinimum() < $this->getOrderPrice() ? $model->getFreeShippingModifier() : 0;
    }

    /*
     * Get Handling Fee Modifier based on max set in config
     *
     * @return int
     */
    public function getHandlingFee()
    {
        return Mage::getStoreConfig('carriers/zonerates/max_apply_fee') > $this->getOrderPrice() ? Mage::getStoreConfig('carriers/zonerates/handling_fee') : 0;
    }
}