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

require_once 'Mage/Adminhtml/controllers/Sales/OrderController.php';

class IllApps_BlueSea_Sales_OrderController extends Mage_Adminhtml_Sales_OrderController
{
    public function viewAction()
    {
        $this->_title($this->__('Sales'))->_title($this->__('Orders'));

        if ($order = $this->_initOrder()) {
            $this->_initAction();

            $block = $this->getLayout()->createBlock('bluesea/sales_order_view_edit','bluesea_sales_edit');

            $this->getLayout()->getBlock('order_tab_info')->setTemplate('bluesea/sales/order/view/tab/info.phtml');
            $this->getLayout()->getBlock('order_tab_info')->append($block);

            $this->_title(sprintf("#%s", $order->getRealOrderId()));

            $this->renderLayout();
        }
    }

    public function qtyeditAction()
    {
        #Mage::log($this->getRequest()->getPost());
        
        $post = $this->getRequest()->getPost();

        $bluesea = Mage::getSingleton('bluesea/sales_order_view_edit_qty')->setOrderId($post['order_id']);

        foreach($post['item'] as $productId => $item)
        {
            $bluesea->updateQty($productId, $item['qty'])->updateTotals();
        }

        $this->getResponse()->setBody('<p>true</p>');

        /*$this->loadLayout();

        $response = $this->getLayout()->getBlock('order_tab_info')->toHtml();

        $this->getResponse()->setBody($response);*/
    }
}