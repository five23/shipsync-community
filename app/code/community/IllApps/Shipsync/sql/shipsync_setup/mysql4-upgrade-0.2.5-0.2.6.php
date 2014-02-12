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

/** TODO: add a check to make sure these columns don't exist */
$installer->run("ALTER TABLE `{$this->getTable('shipping_shipment_package')}` ADD `shipping_method` TEXT AFTER carrier;
                 ALTER TABLE `{$this->getTable('shipping_shipment_package')}` ADD `dimension_units` VARCHAR(3) NOT NULL AFTER weight_units;
                 ALTER TABLE `{$this->getTable('shipping_shipment_package')}` ADD `package_items` TEXT AFTER order_shipment_id;
		 ALTER TABLE `{$this->getTable('shipping_shipment_package')}` ADD `length` DECIMAL(12,2) AFTER weight;
		 ALTER TABLE `{$this->getTable('shipping_shipment_package')}` ADD `width` DECIMAL(12,2) AFTER length;
		 ALTER TABLE `{$this->getTable('shipping_shipment_package')}` ADD `height` DECIMAL(12,2) AFTER width;");

$installer->endSetup();