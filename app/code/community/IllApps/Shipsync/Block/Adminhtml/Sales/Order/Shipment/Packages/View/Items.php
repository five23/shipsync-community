<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class IllApps_Shipsync_Block_Adminhtml_Sales_Order_Shipment_Packages_View_Items extends Mage_Adminhtml_Block_Sales_Order_Abstract
{
    public function getShipmentId()
    {
        return $this->getModel()->getShipmentId();
    }

    public function getActivePackage()
    {
        return $this->getModel()->getActivePackage();
    }

    public function getActiveItems()
    {
        return $this->collectItems();
    }

    public function collectItems()
    {
        return $this->getModel()->collectItems()->getAllItems();
    }

    public function getModel()
    {
        return Mage::getSingleton('shipsync/sales_order_shipment_package');
    }
}