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

class IllApps_ShipmentDate_Model_Step extends Mage_Core_Model_Abstract
{
    public function getFields($step, $storeId = null)
    {
        $hlp = Mage::helper('core');

        $form = new Varien_Data_Form(array(
            'field_name_suffix' => 'adj',
        ));
        $layout = Mage::app()->getFrontController()->getAction()->getLayout();

        //todo add logic for getting fields by step
        //$dateFormatIso = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
    	$form->addField('delivery_date', 'date', array(
            'name'   => 'delivery_date',
            'label'  => $hlp->__('Delivery Date'),
            'title'  => $hlp->__('Delivery Date'),
            'image'  => Mage::getDesign()->getSkinUrl('images/grid-cal.gif'),
            'input_format' => Varien_Date::DATE_INTERNAL_FORMAT,
            'format'       => 'MM/dd/yy',
            'no_span'      => 1,
            #'validator'    => 'adjdeliverydate/validator_deliverydate',
        ))->setRenderer($layout->createBlock('shipmentdate/renderer_shipmentdate'));

        /*if (Mage::getStoreConfig('checkout/adjdeliverydate/show_time')) // time field
        {
        	$form->addField('delivery_time', 'time', array(
                'name'   => 'delivery_time',
                'label'  => $hlp->__('Delivery Time'),
                'title'  => $hlp->__('Delivery Time'),
                'no_span'      => 1,
                ))->setRenderer($layout->createBlock('adjdeliverydate/renderer_time'));
        }

        if (Mage::getStoreConfig('checkout/adjdeliverydate/show_comment')){
        	$form->addField('delivery_comment', 'text', array(
                'name'     => 'delivery_comment',
                'label'    => $hlp->__('Comments'),
                'title'    => $hlp->__('Comments'),
                'no_span'  => 1,
                'class'    => 'input-text delivery-comment',
                //'required' => true,
            ))->setRenderer($layout->createBlock('adjdeliverydate/renderer_default'));
        }*/

        $form->setValues($this->getValues($step));

        return $form->getElements();
    }
}