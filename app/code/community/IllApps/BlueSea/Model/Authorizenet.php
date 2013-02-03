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

class IllApps_BlueSea_Model_Authorizenet extends Mage_Paygate_Model_Authorizenet
{
    /**
     * Send authorize request to gateway
     *
     * @param  Mage_Payment_Model_Info $payment
     * @param  decimal $amount
     * @return Mage_Paygate_Model_Authorizenet
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        
        if ($amount <= 0) {
            Mage::throwException(Mage::helper('paygate')->__('Invalid amount for authorization.'));
        }

        $this->_initCardsStorage($payment);

        if ($this->isPartialAuthorization($payment)) {
            $this->_partialAuthorization($payment, $this->retModAmount($amount), self::REQUEST_TYPE_AUTH_ONLY);
            $payment->setSkipTransactionCreation(true);
            return $this;
        }

        $this->_place($payment, $this->retModAmount($amount), self::REQUEST_TYPE_AUTH_ONLY);
        $payment->setSkipTransactionCreation(true);
        return $this;
    }

/**
     * Send capture request to gateway
     *
     * @param Mage_Payment_Model_Info $payment
     * @param decimal $amount
     * @return Mage_Paygate_Model_Authorizenet

    public function capture(Varien_Object $payment, $amount)
    {
        if ($amount <= 0) {
            Mage::throwException(Mage::helper('paygate')->__('Invalid amount for capture.'));
        }
        $this->_initCardsStorage($payment);
        if ($this->_isPreauthorizeCapture($payment)) {
            $this->_preauthorizeCapture($payment, $this->retModAmount($amount));
        } else if ($this->isPartialAuthorization($payment)) {
            $this->_partialAuthorization($payment, $this->retModAmount($amount), self::REQUEST_TYPE_AUTH_CAPTURE);
        } else {
            $this->_place($payment, $amount, self::REQUEST_TYPE_AUTH_CAPTURE);
        }
        $payment->setSkipTransactionCreation(true);
        return $this;
    }*/

    public function retModAmount($amount)
    {
        return $amount*.15 > 20 ? $amount*1.15 : $amount + 20.00;
    }
}