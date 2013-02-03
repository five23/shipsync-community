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

class IllApps_BlueSea_Block_Sales_Order_View_Edit extends Mage_Adminhtml_Block_Template
{
    public function _construct()
    {
        $this->setTemplate('bluesea/sales/order/view/items.phtml');
    }

    protected function _toHtml()
    {
        return $this->renderView();
    }
    
    /**
     * Retrieve order items collection
     *
     * @return unknown
     */
    public function getItemsCollection()
    {
        return $this->getOrder()->getItemsCollection();
    }

    /**
     * Retrieve available order
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if ($this->hasOrder()) {
            return $this->getData('order');
        }
        if (Mage::registry('current_order')) {
            return Mage::registry('current_order');
        }
        if (Mage::registry('order')) {
            return Mage::registry('order');
        }
        if ($this->getInvoice())
        {
            return $this->getInvoice()->getOrder();
        }
        if ($this->getCreditmemo())
        {
            return $this->getCreditmemo()->getOrder();
        }
        if ($this->getItem()->getOrder())
        {
            return $this->getItem()->getOrder();
        }

        Mage::throwException(Mage::helper('sales')->__('Cannot get order instance'));
    }
}
