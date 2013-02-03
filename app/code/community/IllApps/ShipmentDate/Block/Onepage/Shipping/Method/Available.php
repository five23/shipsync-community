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


class IllApps_ShipmentDate_Block_Onepage_Shipping_Method_Available extends Mage_Checkout_Block_Onepage_Shipping_Method_Available
{
    public function getCacheLifetime()
    {
        return null;
    } 
    
    public function getShippingRates()
    {
        $this->getAddress()->collectShippingRates()->save();

        $instore = Mage::getSingleton('core/session')->getInstorePickup();

        $groups = $this->getAddress()->getGroupedAllShippingRates();

        if (!empty($groups)) {
            foreach ($groups as $code => $groupItems) {
                if($instore && $code == 'zonerates')
                {
                    unset($groups[$code]);
                }
                elseif(!$instore && $code == IllApps_ShipmentDate_Model_Abstract::INSTORE_PICKUP_CARRIER_CODE)
                {
                    unset($groups[$code]);
                }
            }
        }

        return $this->_rates = $groups;
    }

    public function _toHtml()
    {
        $html = $this->renderView();
        return $html;
    }
}
