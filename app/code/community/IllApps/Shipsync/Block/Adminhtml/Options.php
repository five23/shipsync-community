<?php
/**
 * ShipSync
 *
 * @category   IllApps
 * @package    IllApps_Shipsync
 * @author     David Kirby (d@kernelhack.com)
 * @copyright  Copyright (c) 2011 EcoMATICS, Inc. DBA IllApps (http://www.illapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class IllApps_Shipsync_Block_Adminhtml_Options extends Mage_Adminhtml_Block_Widget
{
    public function __construct()
    {
        parent::__construct();

	$this->setCarrier(Mage::getModel('usa/shipping_carrier_fedex'));
	$this->setDefaultPackages($this->getCarrier()->getDefaultPackages());

        $this->setTemplate('shipsync/options.phtml');
    }
}
