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

if (version_compare(Mage::getVersion(), '1.4.0.0', '>=') && version_compare(Mage::getVersion(), '1.4.1.0', '<'))
{
    $installer = $this;

    $typeId = Mage::getModel('eav/entity_type')->loadByCode('order')->getEntityTypeId();

    $installer->startSetup();

    $installer->run("
    DELETE FROM `{$this->getTable('eav/attribute')}` WHERE `attribute_code`='shipping_shipment_date' limit 1;

    INSERT INTO `{$this->getTable('eav/attribute')}` (entity_type_id, attribute_code, backend_type, frontend_input, frontend_label) VALUES ($typeId, 'shipping_shipment_date', 'datetime', 'text', 'Arrival Date');
    ");

    $installer->endSetup();
}

    $installer = $this;
    $installer->startSetup();

    $newFields = array(
        'shipping_shipment_date' => array(
            'type'              => 'datetime',
            'label'             => 'Shipment Date'
        )
    );

    $entities = array('order');

    #$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
    foreach($newFields as $attributeName => $attributeDefs) {
        foreach ($entities as $entity) {
            $installer->addAttribute($entity, $attributeName, array(
                'type'              => $attributeDefs['type'],
                'label'             => $attributeDefs['label'],
                'grid'          => true,
                'visible'       => true,
                'required'      => false,
                'user_defined'  => true,
                'searchable'    => true,
                'filterable'    => true,
                'comparable'    => false
            ));
        }
    }
    
    $installer->endSetup();