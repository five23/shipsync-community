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

class IllApps_BlueSea_Model_Sales_Order extends Mage_Sales_Model_Order
{
    public function updateOrderTotals()
    {
        $subtotal = $baseSubtotal = $taxAmount = $discount = 0;
        
        foreach ($this->getAllItems() as $item)
        {
            $baseSubtotal   += $item->getBaseRowTotal();
            $subtotal       += $item->getRowTotal();
            $taxAmount      += $item->getTaxAmount();
            $discount       -= $item->getDiscountAmount();
        }

        $this->setBaseSubtotal($baseSubtotal)
            ->setSubtotal($subtotal)
            ->setTaxAmount($taxAmount)
            ->setBaseTaxAmount($taxAmount)
            ->setDiscountAmount($discount)
            ->setBaseDiscountAmount($discount)
            ->setBaseGrandTotal($baseSubtotal + $this->getBaseShippingAmount() + $taxAmount + $discount)
            ->setGrandTotal($subtotal + $this->getShippingAmount() + $taxAmount + $discount);

        return $this;
    }
}
