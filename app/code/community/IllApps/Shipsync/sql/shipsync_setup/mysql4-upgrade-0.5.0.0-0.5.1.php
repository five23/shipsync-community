<?php

/**
 * ShipSync
 *
 * @category   IllApps
 * @package    IllApps_Shipsync
 * @copyright  Copyright (c) 2014 EcoMATICS, Inc. DBA IllApps (http://www.illapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$installer = $this;
/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */

$installer->startSetup();

/** TODO: add a check to make sure these columns don't exist */
$installer->run("ALTER TABLE `{$this->getTable('shipping_shipment_package')}` ADD `return_label_image` MEDIUMBLOB NOT NULL AFTER cod_label_image;");

$installer->endSetup();
