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

$installer->run("
    DROP TABLE IF EXISTS {$this->getTable('shipping_shipment_package_estimate')};
    CREATE TABLE {$this->getTable('shipping_shipment_package_estimate')} (
        `package_id` INT( 15 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        `serialized_packages` BLOB NOT NULL ,
        `dest_street` VARCHAR( 255 ) ,
        `dest_postal` INT( 15 ) ,
        `value` DECIMAL( 12,2 ) ,
        `weight` DECIMAL( 12,1 ) ,
        `estimate_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8; ");

if (!$installer->getAttribute('catalog_product', 'height')) {
    $installer->addAttribute('catalog_product', 'height', array(
        'group'             => 'General',
        'type'              => 'text',
        'backend'           => '',
        'frontend'          => '',
        'label'             => 'Height',
        'input'             => 'text',
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

if (!$installer->getAttribute('catalog_product', 'width')) {
    $installer->addAttribute('catalog_product', 'width', array(
        'group'             => 'General',
        'type'              => 'text',
        'backend'           => '',
        'frontend'          => '',
        'label'             => 'Width',
        'input'             => 'text',
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

if (!$installer->getAttribute('catalog_product', 'length')){
    $installer->addAttribute('catalog_product', 'length', array(
        'group'             => 'General',
        'type'              => 'text',
        'backend'           => '',
        'frontend'          => '',
        'label'             => 'Length',
        'input'             => 'text',
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

if (!$installer->getAttribute('catalog_product', 'special_packaging')) {
    $installer->addAttribute('catalog_product', 'special_packaging', array(
        'group'             => 'General',
        'type'              => 'int',
        'backend'           => '',
        'frontend'          => '',
        'label'             => 'Use special packaging?',
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

$installer->endSetup();