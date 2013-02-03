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

class IllApps_ZoneRates_Model_Zones extends Mage_Core_Model_Abstract
{
    protected $_defaultZone = 'C';

    protected function _construct()
    {
        $this->_init('zonerates/zones');
    }

    public function findZone($zip, $column)
    {
        if(strlen($zip) == 1) { return $this->_defaultZone; }
        elseif($this->load($zip, $column)->getZone() != '') { return $this->getZone(); }
        else { return $this->findZone(substr($zip,0,-1), $column); }
    }
}
