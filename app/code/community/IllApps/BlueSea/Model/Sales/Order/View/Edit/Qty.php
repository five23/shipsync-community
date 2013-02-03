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

class IllApps_BlueSea_Model_Sales_Order_View_Edit_Qty extends Mage_Core_Model_Abstract
{
    protected $_totalQtyOrdered;

    protected function _getOrder()
    {
        return Mage::getModel('sales/order')->load($this->getOrderId());
    }

    protected function _getOrderItemsCollection()
    {
        return $this->_getOrder()->getItemsCollection();
    }

    protected function _getAllItems()
    {
        return $this->_getOrder()->getAllItems();
    }

    public function updateQty($productId, $qty)
    {
        Mage::log($this->_getOrder()->debug());
        Mage::log(Mage::getModel('salesrule/rule')->load(1)->debug());
        foreach($this->_getAllItems() as $_item)
        {
            Mage::log($_item->debug());
            if ($_item->getProductId() == $productId)
            {
                $this->_totalQtyOrdered += $qty;
                $_item->setQtyOrdered($qty)->updateItemTotals()->save();
            }
        }        
        return $this;
    }

    public function updateTotals()
    {
        $this->_getOrder()
            ->setTotalQtyOrdered($this->_totalQtyOrdered)
            ->updateOrderTotals()
            ->save();
    }
}