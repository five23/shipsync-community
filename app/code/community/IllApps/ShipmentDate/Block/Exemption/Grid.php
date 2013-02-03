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

class IllApps_ShipmentDate_Block_Exemption_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
     public function __construct()
    {
        parent::__construct();
        $this->setId('exemption_grid');
        $this->setDefaultSort('exemption_id');
        $this->setDefaultDir('asc');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareLayout()
    {
        $this->unsetChild('reset_filter_button');
        $this->unsetChild('search_button');
        $this->setFilterVisibility(false);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('shipmentdate/exemption')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('exemption_id', array(
            'header'    => $this->__('ID'),
            'align'     =>'right',
            'width'     => '50px',
            'index'     => 'exemption_id',
        ));

        $this->addColumn('title', array(
            'header'    => $this->__('Exemption Title'),
            'align'     =>'left',
            'index'     => 'title',
        ));

        $this->addColumn('instore', array(
            'header'    => $this->__('Applied to Delivery Method'),
            'align'     => 'left',
            'index'     => 'instore',
            'renderer'    => new IllApps_ShipmentDate_Block_Renderer_Instore(),
        ));

        $this->addColumn('date', array(
            'header'    => $this->__('Date'),
            'align'     => 'left',
            'index'     => 'date',
        ));

        $this->addColumn('recurring', array(
            'header'    => $this->__('Recurrence Frequency'),
            'align'     => 'left',
            'index'     => 'recurring',
            'renderer'    => new IllApps_ShipmentDate_Block_Renderer_Recurring(),
        ));


        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('exemption_id');
        $this->getMassactionBlock()->setFormFieldName('exemption');

        $this->getMassactionBlock()->addItem('delete', array(
             'label'    => $this->__('Delete'),
             'url'      => $this->getUrl('*/*/massDelete/', array('exemption_id' => $this->getExemptionId())),
             'confirm'  => $this->__('Are you sure?')
        ));

        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit/', array('exemption_id' => $row->getExemptionId()));
    }

    public function isReadonly()
    {
        return $this->getExemption()->getCrosssellReadonly();
    }
}