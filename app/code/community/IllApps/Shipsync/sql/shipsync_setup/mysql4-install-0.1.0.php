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
$installer->startSetup();

$installer->run("
    DROP TABLE IF EXISTS {$this->getTable('shipping_shipment_package')};
    CREATE TABLE {$this->getTable('shipping_shipment_package')} (
        `package_id` INT( 15 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        `order_increment_id` INT( 15 ) NOT NULL ,
        `order_shipment_id` INT (15) NOT NULL,
        `carrier` ENUM( 'dhl', 'fedex', 'ups', 'usps' ) NOT NULL ,
        `carrier_shipment_id` VARCHAR( 50 ) NOT NULL ,
        `weight_units` VARCHAR (3) NOT NULL,
        `weight` DECIMAL(12,4) NOT NULL,
        `tracking_number` VARCHAR( 50 ) NOT NULL ,
        `currency_units` VARCHAR(5) NOT NULL,
        `transporation_charge` DECIMAL(12,4) NULL,
        `service_option_charge` DECIMAL(12,4) NULL,
        `shipping_total` DECIMAL(12,4) NOT NULL,
        `negotiated_total` DECIMAL(12,4) NULL,
        `label_format` VARCHAR( 5 ) NOT NULL ,
        `label_image` MEDIUMBLOB NOT NULL ,
        `html_label_image` BLOB NULL ,
        `date_shipped` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
        INDEX ( `package_id` ),
        INDEX ( `order_increment_id`,
        `order_shipment_id` )
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8; ");

$installer->endSetup();