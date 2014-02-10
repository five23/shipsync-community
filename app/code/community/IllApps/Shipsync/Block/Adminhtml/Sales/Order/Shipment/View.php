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
 * IllApps_Shipsync_Block_Adminhtml_Sales_Order_Shipment_View
 */
class IllApps_Shipsync_Block_Adminhtml_Sales_Order_Shipment_View extends Mage_Adminhtml_Block_Sales_Order_Shipment_View
{
    
    
    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct();
        
        // Get package model
        $shipmentPackage = Mage::getModel('shipping/shipment_package');
        
        // Get all packages
        $packages = $shipmentPackage->getCollection();
        
        if ($packages) {
            $this->_addButton('view_shipment_packages', array(
                'label' => Mage::helper('sales')->__('View Shipment Packages'),
                'onclick' => 'setLocation(\'' . $this->getUrl('shipsync/index/packages', array(
                    'shipment_id' => $this->getShipment()->getId()
                )) . '\')'
            ));
        }
        
        // Loop through available packages
        foreach ($packages->getData() as $package) {
            // If package matches order
            if ($shipmentPackage->load($package['package_id'])->getOrderShipmentId() == $this->getShipment()->getId()) {
                // Get label URL
                $url = $this->getUrl('shipsync/index/label/', array(
                    'id' => $package['package_id']
                ));
                
                // If package has COD label
                if ($package['cod_label_image'] != "") {
                    // Set COD label URL
                    $codurl = $this->getUrl('shipsync/index/codlabel/', array(
                        'id' => $package['package_id']
                    ));
                    
                    // Add button for COD label
                    $this->_addButton('reprint_cod_label_' . $package, array(
                        'label' => Mage::helper('sales')->__('Print COD Label'),
                        'onclick' => 'setLocation(\'' . $codurl . '\')'
                    ));
                }
                
                // Add print label button
                $this->_addButton('reprint_label_' . $package, array(
                    'label' => Mage::helper('sales')->__('Print Label'),
                    'onclick' => 'setLocation(\'' . $url . '\')'
                ));
            }
        }
    }
}