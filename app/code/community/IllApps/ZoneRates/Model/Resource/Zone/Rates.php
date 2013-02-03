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

class IllApps_ZoneRates_Model_Resource_Zone_Rates extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Errors in import process
     *
     * @var array
     */
    protected $_importErrors        = array();

    /**
     * Count of imported table rates
     *
     * @var int
     */
    protected $_importedRows        = 0;

    /**
     * Define main table and id field name
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('zonerates/rates', 'id');
    }

    public function uploadAndImport(Varien_Object $object)
    {
        if (empty($_FILES['groups']['tmp_name']['zonerates']['fields']['import_rates']['value'])) {
            return $this;
        }

        $csvFile = $_FILES['groups']['tmp_name']['zonerates']['fields']['import_rates']['value'];

        $io     = new Varien_Io_File();
        $info   = pathinfo($csvFile);
        $io->open(array('path' => $info['dirname']));
        $io->streamOpen($info['basename'], 'r');

        // check and skip headers
        $headers = $io->streamReadCsv();
        if ($headers === false || count($headers) < 4) {
            $io->streamClose();
            Mage::throwException(Mage::helper('shipping')->__('Invalid Zone Rates File Format'));
        }

        $adapter = $this->_getWriteAdapter();
        $adapter->beginTransaction();


        try {
            $rowNumber  = 1;
            $importData = array();

            while (false !== ($csvLine = $io->streamReadCsv())) {
                $rowNumber ++;

                if (empty($csvLine)) { continue; }

                $row = $this->_getImportRow($csvLine, $rowNumber);
                if ($row !== false) {
                    $importData[] = $row;
                }
            }
            $this->_saveImportData($importData);
            $io->streamClose();

        } catch (Mage_Core_Exception $e) {
            $adapter->rollback();
            $io->streamClose();
            Mage::throwException($e->getMessage());
        } catch (Exception $e) {
            $adapter->rollback();
            $io->streamClose();
            Mage::logException($e);
            Mage::throwException(Mage::helper('shipping')->__('An error occurred while importing zone data.'));
        }

        $adapter->commit();

        if ($this->_importErrors) {
            $error = Mage::helper('shipping')->__('%1$d records have been imported. See the following list of errors for each record that has not been imported: %2$s',
                $this->_importedRows, implode(" \n", $this->_importErrors));
            Mage::throwException($error);
        }

        return $this;
    }

    protected function _getImportRow($row, $rowNumber = 0)
    {
        // validate row
        if (count($row) < 4) {
            $this->_importErrors[] = Mage::helper('shipping')->__('Invalid Zone Rates format in the Row #%s',
                $rowNumber);
            return false;
        }

        // strip whitespace from the beginning and end of each row
        foreach ($row as $k => $v) {
            $row[$k] = trim($v);
        }

        return array(
            isset($row[0]) ? $row[0] : '',
            isset($row[1]) ? $row[1] : '',
            isset($row[2]) ? $row[2] : '',
            isset($row[3]) ? $row[3] : ''
        );
    }

/**
     * Save import data batch
     *
     * @param array $data
     * @return Mage_Shipping_Model_Resource_Zone_Rates
     */
    protected function _saveImportData(array $data)
    {
        if (!empty($data)) {
            $columns = array('zone', 'free_shipping_minimum', 'free_shipping_method', 'free_shipping_modifier');
            $this->_getWriteAdapter()->insertArray($this->getMainTable(), $columns, $data);
            $this->_importedRows += count($data);
        }

        return $this;
    }
}