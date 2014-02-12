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
 * IllApps_Shipsync_Block_Adminhtml_Sales_Order_View
 */
class IllApps_Shipsync_Block_Adminhtml_Sales_Order_View extends Mage_Adminhtml_Block_Sales_Order_View
{

    
    /**
     * Construct
     */
    public function __construct()
    {
    	parent::__construct();
        
        // If package can be shipped
        if ($this->_isAllowedAction('ship') && $this->getOrder()->canShip())
        {
            // Add ship button
            $this->_addButton('ship_with_shipsync', array(
                'label'     => Mage::helper('sales')->__('Ship with ShipSync'),
                'onclick'   => 'setLocation(\'' . $this->getUrl('shipsync') . '\')',
            ));
        }	
    }    
}