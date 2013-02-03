<?php

/**
 * ExtraPackages
 *
 * @category   IllApps
 * @package    IllApps_ExtraPackages
 * @author     David Kirby (d@kernelhack.com) / Jonathan Cantrell (j@kernelhack.com)
 * @copyright  Copyright (c) 2011 EcoMATICS, Inc. DBA IllApps (http://www.illapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class IllApps_ZoneRates_Model_Mysql4_Zones extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('zonerates/zones', 'id');
    }
}