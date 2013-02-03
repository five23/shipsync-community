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

class IllApps_ShipmentDate_Block_Exemption_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        if (Mage::getSingleton('adminhtml/session')->getExemptionData())
        {
            $data = Mage::getSingleton('adminhtml/session')->getExemptionData();
            Mage::getSingleton('adminhtml/session')->getExemptionData(null);
        }
        elseif (Mage::registry('exemption_data'))
        {
            $data = Mage::registry('exemption_data')->getData();
        }
        else
        {
            $data = array();
        }

        $form = new Varien_Data_Form(array(
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/save', array('exemption_id' => $this->getRequest()->getParam('exemption_id'))),
                'method' => 'post',
                'enctype' => 'multipart/form-data',
        ));

        $form->setUseContainer(true);

        $this->setForm($form);

        $fieldset = $form->addFieldset('exemption_form', array(
             'legend' => $this->__('Exemption Information')
        ));
    
        /*$fieldset->addField('shipping_method_raw', 'multiselect', array(
             'label'     => $this->__('Shipping Method'),
             'title'     => $this->__('Shipping Method'),
             #'name'      => 'shipping_method[]',
             'required'  => true,
             'values'   => $this->getShippingMethods(),
             'name'      => 'shipping_method_raw',
        ));*/

        $fieldset->addField('title', 'text', array(
             'label'     => $this->__('Exemption Title'),
             'title'     => $this->__('Exemption Title'),
             'required'  => true,
             'name'      => 'title',
        ));

        $fieldset->addField('instore', 'select', array(
             'label'     => $this->__('Apply to Delivery Method'),
             'class'     => 'required-entry',
             'required'  => true,
             'name'      => 'instore',
             'values'    => $this->getInstoreSource(),
        ));

        $fieldset->addField('recurring', 'select', array(
             'label'     => $this->__('Recurs Annually'),
             'class'     => 'required-entry',
             'required'  => true,
             'name'      => 'recurring',
             'values'    => $this->getRecurringSource(),
        ));

        $fieldset->addField('date', 'date', array(
             'label'     => $this->__('Date'),
             'class'     => 'required-entry',
             'required'  => true,
             'name'      => 'date',
             'image'     => $this->getSkinUrl('images/grid-cal.gif'),
             'input_format' => Varien_Date::DATE_INTERNAL_FORMAT,
             'format' => 'MM/dd/yyyy',
        ));
        $form->setValues($data);

        return parent::_prepareForm();
    }

    public function getShippingMethods()
    {
        $carriers = Mage::getStoreConfig('carriers', Mage::app()->getStore(true)->getId());

        foreach ($carriers as $code => $details)
        {
            if(isset($details['title'])) { $methods[] = array('value' => $code, 'label' => $details['title']); }
        }
        
        return $methods;
    }

    public function getInstoreSource()
    {
        return array(
                array(
                    'label' => $this->__('Instore'),
                    'value' =>  1
                ),
                array(
                    'label' => $this->__('Delivery'),
                    'value' =>  0
                ));
    }

    public function getRecurringSource()
    {
        return array(
                array(
                    'label' => $this->__('Annual'),
                    'value' =>  1
                ),
                array(
                    'label' => $this->__('Exclusive'),
                    'value' =>  0
                ));
    }
}