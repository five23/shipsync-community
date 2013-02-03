<?php
/**
 * ZoneRates
 *
 * @category   IllApps
 * @package    IllApps_ZoneRates
 * @author     Jonathan Cantrell (j@kernelhack.com)
 * @copyright  Copyright (c) 2011 EcoMATICS, Inc. DBA IllApps (http://www.illapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

 class IllApps_ZoneRates_Model_Backend_Zone_Alpha extends Mage_Core_Model_Config_Data
{
    public function _afterSave()
    {
        Mage::getResourceModel('zonerates/zone_alpha')->uploadAndImport($this);
    }
}