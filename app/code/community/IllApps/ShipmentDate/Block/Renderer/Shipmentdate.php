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

class Illapps_ShipmentDate_Block_Renderer_Shipmentdate
    extends Mage_Core_Block_Template
    implements Varien_Data_Form_Element_Renderer_Interface
{
    protected $_element;
    
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $this->_element = $element;

        $html = $this->_element->getLabelHtml() . '<br />' . $this->_element->getElementHtml();

        $js = $this->_toHtml($html);
        $result = str_replace('Calendar.setup({', $js, $html);

        return $result;
    }
}