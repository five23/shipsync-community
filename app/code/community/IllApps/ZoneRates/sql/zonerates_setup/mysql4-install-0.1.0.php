<?php
/**
 * ZoneRates
 *
 * @category   IllApps
 * @package    IllApps_ZoneRates
 * @author     Jonathan Cantrell (j@kernelhack.com)
 * @copyright  Copyright (c) 2011 EcoMATICS, Inc. DBA IllApps (http://www.illapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$installer = $this;

$installer->startSetup();

$installer->run("
    DROP TABLE IF EXISTS {$this->getTable('zonerates/zones')};
    CREATE TABLE {$this->getTable('zonerates/zones')} (
        `id` INT( 15 ) NOT NULL auto_increment,
        `zipcode` CHAR(15) NOT NULL,
        `zone` CHAR(4) NOT NULL,
        PRIMARY KEY ( `id` ),
        INDEX ( `zipcode`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8; ");

$installer->run("
    DROP TABLE IF EXISTS {$this->getTable('zonerates/rates')};
    CREATE TABLE {$this->getTable('zonerates/rates')} (
        `id` INT( 15 ) NOT NULL auto_increment,
        `zone` CHAR(15) NOT NULL,
        `free_shipping_minimum` DECIMAL(12,4) NOT NULL,
        `free_shipping_method` CHAR(30) NOT NULL,
        `free_shipping_modifier` DECIMAL(12,4) NOT NULL,
        PRIMARY KEY ( `id` ),
        INDEX ( `zone`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8; ");

$installer->endSetup();