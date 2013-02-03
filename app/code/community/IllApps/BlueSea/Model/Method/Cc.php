<?php
/**
 * BlueSea
 *
 * @category   IllApps
 * @package    IllApps_BlueSea
 * @author     Jonathan Cantrell (j@kernelhack.com)
 * @copyright  Copyright (c) 2011 EcoMATICS, Inc. DBA IllApps (http://www.illapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class IllApps_BlueSea_Model_Method_Cc extends Mage_Payment_Model_Method_Cc
{
    /**
     * Grand total getter
     *
     * @return string
     */
    public function _getAmount()
    {
        $info = $this->getInfoInstance();
        Mage::log($info); Mage::log('info');
        if ($this->_isPlaceOrder()) {
            return (double)$info->getOrder()->getQuoteBaseGrandTotal();
        } else {
            return (double)$this->retModAmount($info->getQuote()->getBaseGrandTotal());
        }
    }

    /**
     * Whether current operation is order placement
     *
     * @return bool
     */
    public function _isPlaceOrder()
    {
        $info = $this->getInfoInstance();
        if ($info instanceof Mage_Sales_Model_Quote_Payment) {
            return false;
        } elseif ($info instanceof Mage_Sales_Model_Order_Payment) {
            return true;
        }
    }

    public function retModAmount($amount)
    {
        return $amount*.15 > 20 ? $amount*1.15 : 20.00;
    }
}