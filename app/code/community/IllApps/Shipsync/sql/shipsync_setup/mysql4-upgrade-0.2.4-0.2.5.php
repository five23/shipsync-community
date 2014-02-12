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

$installer = $this;
/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */

$installer->startSetup();

if (!$installer->getAttribute('catalog_product', 'free_shipping')) {
    $installer->addAttribute('catalog_product', 'free_shipping', array(
        'group'             => 'General',
        'type'              => 'int',
        'backend'           => '',
        'frontend'          => '',
        'label'             => 'Free Shipping',
        'input'             => 'boolean',
        'class'             => '',
        'source'            => '',
        'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
        'visible'           => true,
        'required'          => false,
        'user_defined'      => false,
        'default'           => '',
        'searchable'        => false,
        'filterable'        => false,
        'comparable'        => false,
        'visible_on_front'  => false,
        'unique'            => false,
        'is_configurable'   => true,
    ));
}

if (!$installer->getAttribute('catalog_product', 'dangerous_goods')) {
    $installer->addAttribute('catalog_product', 'dangerous_goods', array(
        'group'             => 'General',
        'type'              => 'int',
        'backend'           => '',
        'frontend'          => '',
        'label'             => 'Dangerous Goods',
        'input'             => 'boolean',
        'class'             => '',
        'source'            => '',
        'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
        'visible'           => true,
        'required'          => false,
        'user_defined'      => false,
        'default'           => '',
        'searchable'        => false,
        'filterable'        => false,
        'comparable'        => false,
        'visible_on_front'  => false,
        'unique'            => false,
        'is_configurable'   => true,
    ));
}

if (!$installer->getAttribute('catalog_product', 'dangerous_goods_options')) {
    $installer->addAttribute('catalog_product', 'dangerous_goods_options', array(
        'group'             => 'General',
        'type'              => 'text',
        'backend'           => '',
        'frontend'          => '',
        'label'             => 'Dangerous Goods Options',
        'input'             => 'select',
        'class'             => '',
        'source'            => '',
        'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
        'visible'           => true,
        'required'          => false,
        'user_defined'      => false,
        'searchable'        => false,
        'filterable'        => false,
        'comparable'        => false,
        'visible_on_front'  => false,
        'unique'            => false,
        'is_configurable'   => true,
        'option'            => array(
            'value' => array(
		'lithium_battery_exception' => array('Lithium Battery Exception'),
                'orm_d'			    => array('ORM-D'),
                'small_quantitiy_exception' => array('Small Quantity Exception'),
                'reportable_quantities'	    => array('Reportable Quantities')
            )
        )
    ));
}

/** TODO: add a check to make sure these columns don't exist */
$installer->run("ALTER TABLE `{$this->getTable('shipping_shipment_package')}` ADD `cod_label_image` MEDIUMBLOB NOT NULL AFTER label_image;
		 ALTER TABLE `{$this->getTable('shipping_shipment_package')}` ADD `insure_shipment` BOOLEAN AFTER cod_label_image;
		 ALTER TABLE `{$this->getTable('shipping_shipment_package')}` ADD `insure_amount` BOOLEAN AFTER insure_shipment;
		 ALTER TABLE `{$this->getTable('shipping_shipment_package')}` ADD `require_signature` BOOLEAN AFTER insure_amount;");

$installer->endSetup();