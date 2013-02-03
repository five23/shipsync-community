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


require_once 'Mage/Checkout/controllers/OnepageController.php';

class IllApps_ShipmentDate_OnepageController extends Mage_Checkout_OnepageController
{

    public function saveBillingAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if ($this->getRequest()->isPost()) {
//            $postData = $this->getRequest()->getPost('billing', array());
//            $data = $this->_filterPostData($postData);
            $data = $this->getRequest()->getPost('billing', array());
            $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);

            if (isset($data['email'])) {
                $data['email'] = trim($data['email']);
            }
            $result = $this->getOnepage()->saveBilling($data, $customerAddressId);

            if (!isset($result['error'])) {
                if ($this->getOnepage()->getQuote()->isVirtual()) {
                    $result['goto_section'] = 'payment';
                    $result['update_section'] = array(
                        'name' => 'payment-method',
                        'html' => $this->_getPaymentMethodsHtml()
                    );
                } elseif (isset($data['use_for_shipping']) && $data['use_for_shipping'] == 1) {
                    $this->loadLayout('checkout_onepage_shipmentoptions');
                    $result['goto_section'] = 'shipmentoptions';
                    $result['update_section'] = array(
                        'name' => 'shipmentoptions',
                        'html' => $this->_getShipmentOptionsHtml()
                        );

                } else {
                    $result['goto_section'] = 'shipping';
                }
            }

            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }

    /**
     * Shipping address save action
     */
    public function saveShippingAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('shipping', array());
            $customerAddressId = $this->getRequest()->getPost('shipping_address_id', false);
            $result = $this->getOnepage()->saveShipping($data, $customerAddressId);

            if (!isset($result['error'])) {
                $this->loadLayout('checkout_onepage_shipmentoptions');
                $result['goto_section'] = 'shipmentoptions';
                $result['update_section'] = array(
                        'name' => 'shipmentoptions',
                        'html' => $this->_getShipmentOptionsHtml()
                        );
            }
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }

    public function saveShipmentOptionsAction()
    {
        $this->_expireAjax();
        if ($post = $this->getRequest()->isPost()) {
            
            Mage::getSingleton('core/session')->unsDateUpdate();
            
            if($this->getRequest()->getParam('shipping_arrival_date'))
            {
                Mage::getSingleton('core/session')->setDateUpdate($this->getRequest()->getParam('shipping_arrival_date'));
            }

            $_instore = $this->getRequest()->getParam('instore_pickup');

            Mage::getSingleton('core/session')->unsInstorePickup();
            Mage::getSingleton('core/session')->setInstorePickup($_instore);

            $result = array();

            $result['goto_section'] = 'shipping_method';
            $result['update_section'] = array(
                'name' => 'shipping-method',
                'html' => $this->_getShippingMethodsHtml()
            );

            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }

    public function exemptionsAction()
    {
        $instore = $this->getRequest()->getParam('instore');

        $exemptions = Mage::getModel('shipmentdate/exemption');

        $isInstore = Mage::getSingleton('core/session')->getInstorePickup();

        $args = array('delivery', 'instore');

        foreach($args as $key => $arg)
        {
            $exemptions->setMethod($arg);
            
            $exemptionsCollection = $exemptions->getCollection()
                ->getItemsByColumnValue('instore', $key);

            if(isset($exemptionsCollection))
            {
                foreach($exemptionsCollection as $exemption)
                {
                    $result[$arg]['special'][] = array('date' => $exemption->getDateFormattedToJs(), 'recurring' => $exemption->getRecurring());
                }
            } else {
                $result[$arg]['special'] = false;
            }

            $result[$arg]['today']      = $exemptions->getTodayArray();
            $result[$arg]['weekend']    = $exemptions->getWeekendDaysArray();
            $result[$arg]['init_date']  = $exemptions->getInitDate($key);
            $result[$arg]['end_date']   = $exemptions->getLastDeliveryDate();
            $result[$arg]['init_arr']   = $exemptions->getInitDateArray();
            $result[$arg]['date_sel']   = $exemptions->getSelectedDate();
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    protected function _getShipmentOptionsHtml()
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('checkout_onepage_shipmentoptions');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }

    protected function _getShippingMethodsHtml()
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('checkout_onepage_shippingmethod');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }

    /*public function logAction()
    {
        echo '<pre>'; print_r(Mage::getSingleton('core/session')->debug()); echo '</pre>';
    }*/
}
