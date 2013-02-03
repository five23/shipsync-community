<?php

/**
 * ShipSync
 *
 * @category   IllApps
 * @package    IllApps_Shipsync
 * @author     David Kirby (d@kernelhack.com) / Jonathan Cantrell (j@kernelhack.com)
 * @copyright  Copyright (c) 2011 EcoMATICS, Inc. DBA IllApps (http://www.illapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * IllApps_Shipsync_Block_Adminhtml_Shipsync_Option
 */
class IllApps_Shipsync_Block_Adminhtml_Shipsync_Option extends Mage_Adminhtml_Block_Widget
{


    /**
     * __construct
     */
    public function __construct()
    {
        parent::__construct();
        
        $shippingPackage = Mage::getModel('shipsync/shipping_package');
        
        $this->setDefaultPackages($shippingPackage->getDefaultPackages(array('fedex')));
    }
    
}
