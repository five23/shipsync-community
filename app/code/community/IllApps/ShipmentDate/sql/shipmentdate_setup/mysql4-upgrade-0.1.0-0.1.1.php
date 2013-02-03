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

$this->_conn->addColumn($this->getTable('sales_flat_quote'), 'shipping_shipment_date', 'datetime');

$installer->endSetup();