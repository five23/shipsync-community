<?php

/**
 * ShipSync
 *
 * @category   IllApps
 * @package    IllApps_Shipsync
 * @copyright  Copyright (c) 2014 EcoMATICS, Inc. DBA IllApps (http://www.illapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * IllApps_Shipsync_Block_Adminhtml_Sales_Order_Shipment_Packages_View
 */
class IllApps_Shipsync_Block_Adminhtml_Sales_Order_Shipment_Packages_View extends Mage_Adminhtml_Block_Widget_Form_Container
{

    /**
     * Construct
     */
    public function __construct()
    {
        $this->_objectId    = 'shipment_id';
        $this->_controller  = 'sales_order_shipment_packages';
        $this->_mode        = 'view';
        $this->_headerText  = 'Shipment Package View';

        parent::__construct();

        $this->_removeButton('reset');
        $this->_removeButton('delete');
        $this->_removeButton('save');
    }

    /**
     * Get Back Url
     */
    public function getBackUrl()
    {
        return $this->getUrl(
            'adminhtml/sales_shipment/view',
            array(
                'shipment_id'  => $this->getShipment()->getId()
            ));
    }

    /**
     * Update Back Button Url
     */
    public function updateBackButtonUrl($flag)
    {
        if ($flag) {
            if ($this->getShipment()->getBackUrl()) {
                return $this->_updateButton('back', 'onclick', 'setLocation(\'' . $this->getShipment()->getBackUrl() . '\')');
            }
            return $this->_updateButton('back', 'onclick', 'setLocation(\'' . $this->getUrl('adminhtml/sales_shipment/') . '\')');
        }
        return $this;
    }
    
	/**
	 * Get Shipment
	 */
    public function getShipment()
    {
        return Mage::registry('current_shipment');
    }
}
