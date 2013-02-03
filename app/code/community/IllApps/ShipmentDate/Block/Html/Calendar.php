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

class IllApps_ShipmentDate_Block_Html_Calendar extends Mage_Core_Block_Html_Calendar
{
    /*public function getDisabledDatesJs()
    {
        $exemptions = array(0 => array('instore' => true, 'yyyymmdd' => '20111225'));
        Mage::log(json_encode($exemptions));
        return json_encode($exemptions);
    }*/

    public function getCalendarNotice()
    {
        return Mage::getStoreConfig('shipmentdate/' . (Mage::getSingleton('core/session')->getInstorePickup() ? 'instore' : 'delivery') . '/calendar_notice');
    }
}
