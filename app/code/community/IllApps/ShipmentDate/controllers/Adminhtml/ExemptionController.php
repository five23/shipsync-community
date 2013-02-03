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

class IllApps_ShipmentDate_Adminhtml_ExemptionController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $id = $this->getRequest()->getParam('exemption_id', null);
        $model = Mage::getModel('shipmentdate/exemption');
        if ($id) {
            $model->load($id);
            if ($model->getExemptionId()) {
                $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
                if ($data) {
                    $model->setData($data)->setExemptionId($id);
                }
            } else {
                Mage::getSingleton('adminhtml/session')->addError($this->__('Exemption does not exist'));
                $this->_redirect('*/*/');
            }
            Mage::register('exemption_data', $model);
        } else {
            Mage::register('exemption_data', $model);
        }

        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        $this->renderLayout();
    }

    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost())
        {
            $exemption     = Mage::getModel('shipmentdate/exemption');
            $exemptionId   = $this->getRequest()->getParam('exemption_id');

            if($exemptionId) { $exemption->load($exemptionId); }

            $exemption->setData($data);

            Mage::getSingleton('adminhtml/session')->setFormData($data);
            try {
                if ($exemptionId) {
                    $exemption->setExemptionId($exemptionId);
                }
                $exemption->save();

                if (!$exemption->getExemptionId()) {
                    Mage::throwException($this->__('Error saving exemption'));
                }

                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Exemption was successfully saved.'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('exemption_id' => $exemption->getExemptionId()));
                } else {
                    $this->_redirect('*/*/', array('exemption_id' => $exemption->getExemptionId()));
                }

            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                if ($exemption && $exemption->getExemptionId()) {
                    $this->_redirect('*/*/edit', array('exemption_id' => $exemption->getExemptionId()));
                } else {
                    $this->_redirect('*/*/');
                }
            }

            return;
        }
        Mage::getSingleton('adminhtml/session')->addError($this->__('No data found to save'));
        $this->_redirect('*/*/');
    }

    public function massDeleteAction()
    {
        $exemptionIds = $this->getRequest()->getParam('exemption');

        if(!is_array($exemptionIds))
        {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select packages(s)'));
        }
        else
        {
            try {
                foreach ($exemptionIds as $exemptionId)
                {
                    $ex = Mage::getModel('shipmentdate/exemption')->load($exemptionId);
                    $id = $ex->getExemptionsId();
                    $ex->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    $this->__(
                        'Total of %d record(s) were successfully deleted', count($exemptionIds)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');

    }

    public function deleteAction()
    {
        if($exemptionId = $this->getRequest()->getParam('exemption_id'))
        {
            try {
                Mage::getModel('shipmentdate/exemption')->load($exemptionId)->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('shipmentdate')->__('Deletion successful'));
                $this->_redirect('*/*/index');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('exemption_id' => $this->getRequest()->getParam('exemption_id')));
            }
        }
        $this->_redirect('*/*/index');
    }
}