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

$installer = $this;

$installer->startSetup();

$installer->run("
    DROP TABLE IF EXISTS {$this->getTable('shipmentdate/exemption')};
    CREATE TABLE {$this->getTable('shipmentdate/exemption')} (
        `exemption_id` INT( 15 ) NOT NULL auto_increment,
        `title` VARCHAR(30) NOT NULL,        
        `instore` BOOLEAN NOT NULL,
        `date` CHAR(15) NOT NULL,
        `recurring` BOOLEAN NOT NULL,
        PRIMARY KEY ( `exemption_id` ),
        INDEX ( `exemption_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8; ");

$installer->endSetup();