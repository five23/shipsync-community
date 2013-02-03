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

class IllApps_ShipmentDate_Block_Exemption extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * __construct
     */
    public function __construct()
    {
        parent::__construct();

        $this->_controller = 'exemption';
        $this->_blockGroup = 'shipmentdate';
        $this->_headerText = $this->__('Shipping Date Exemptions');

        $this->_addButton('add_new_exemption', array(
            'label'     => $this->__('Add New Exemption'),
            'onclick'   => 'setLocation(\'' . $this->getUrl('*/*/new/id') . '\')',
            'class'     => 'add'
        ));
        parent::__construct();

        $this->_removeButton('add');
    }

    public function _prepareLayout()
    {
        $this->setChild( 'grid',
            $this->getLayout()->createBlock('shipmentdate/exemption_grid')->setSaveParametersInSession(true) );
        return parent::_prepareLayout();
    }
}