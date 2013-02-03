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

class IllApps_BlueSea_Model_Sales_Order_Item extends Mage_Sales_Model_Order_Item
{
    public function updateItemTotals()
    {
        $this->setBaseRowTotal($this->getBasePrice() * $this->getQtyOrdered())
            ->setRowTotal($this->getPrice() * $this->getQtyOrdered())
            ->setDiscountAmount($this->getDiscountPercent() * $this->getRowTotal() * .01)
            ->setBaseDiscountAmount($this->getDiscountPercent() * $this->getRowTotal() * .01)
            ->setRowWeight($this->getWeight() * $this->getQtyOrdered())
            ->setTaxAmount(round($this->getRowTotal() * $this->getTaxPercent() * .01, 2))
            ->setRowTotalInclTax($this->getRowTotal() + $this->getTaxAmount() - $this->getDiscountAmount())
            ->setBaseRowTotalInclTax($this->getBaseRowTotal() + $this->getTaxAmount() - $this->getDiscountAmount());
            
        return $this;
    }
}