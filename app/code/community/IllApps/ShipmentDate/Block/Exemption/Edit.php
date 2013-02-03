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

class IllApps_ShipmentDate_Block_Exemption_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        
        $this->_objectId = 'exemption_id';
        $this->_blockGroup = 'shipmentdate';
        $this->_controller = 'exemption';
        $this->_mode = 'edit';

        if (Mage::registry('exemption_data')->getExemptionId()) {
            $this->_addButton('delete', array(
                'label'     => Mage::helper('adminhtml')->__('Delete'),
                'class'     => 'delete',
                'onclick'   => 'deleteConfirm(\''. Mage::helper('adminhtml')->__('Are you sure you want to do this?')
                    .'\', \'' . $this->getDeleteUrl() . '\')',
            ));
        }

        $this->_addButton('save_and_continue', array(
                  'label' => Mage::helper('adminhtml')->__('Save And Continue Edit'),
                  'onclick' => 'saveAndContinueEdit()',
                  'class' => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('form_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'edit_form');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'edit_form');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        $registry = Mage::registry('exemption_data');
        if ($registry && $registry->getExemptionId() && $id = $registry->getExemptionId())
        {
            return $this->__('Edit Exemption %s - %s',
                $this->htmlEscape($registry->getExemptionId()),
                $this->htmlEscape($registry->getTitle())
            );
        } else if ($registry && $id = $registry->getExemptionId()) {
            return $this->__('Create New Exemption');
        }
        else {
            return $this->__('New Exemption');
        }
    }


    public function getBackUrl()
    {
        return Mage::getSingleton('adminhtml/url')->getUrl('*/*/index');
    }

    public function getDeleteUrl()
    {
        return Mage::getSingleton('adminhtml/url')->getUrl('*/*/delete', array('exemption_id'=>$this->getRequest()->getParam('exemption_id')));
    }
}