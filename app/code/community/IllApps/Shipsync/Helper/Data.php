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


/**
 * IllApps_Shipsync_Helper_Data
 *
 * @license    Code adapted from Netresearch_Debug (http://www.opensource.org/licenses/osl-3.0.php (OSL 3.0))
 */
class IllApps_Shipsync_Helper_Data extends Mage_Core_Helper_Abstract
{
    
    
    /**
     * storage for log messages
     *
     * @access private
     * @var array
     */
    static private $__log = array();
    
    
    
    /**
     * if you call the log-funtion via Mage::helpers("debug")->log(...) set this to 0
     * if you created a shortcut like
     *
     *   public static function debug($message, $level=7)
     *   {
     *       Mage::helper("debug")->log($message, $level);
     *   }
     *
     * set this to 1
     *
     * @var int
     */
    static private $__shiftupStackLevel = 1;
    
    
    
    /**
     * translation of the log error names
     *
     * @access private
     * @var array
     */
    static private $__levelNames = array(0 => '[Error]', 1 => '[Error]', 2 => '[Error]', 3 => '[Error]', 4 => '[Warn]', 5 => '[Log]', 6 => '[Log]', 7 => '[Debug]');
    
    
    
    /**
     * logs a debug message
     *
     * @access public
     * @param mixed $message item to log (can be basic type, array or object)
     * @param int $level log level
     * @return void
     */
    public static function log($message, $level = 7)
    {
        $backtrace = debug_backtrace();
        $codeFile  = str_replace(Mage::getRoot(), "", $backtrace[self::$__shiftupStackLevel]['file']);
        $codeLine  = $backtrace[self::$__shiftupStackLevel]['line'];
        
        $log = array(
            'caption' => self::$__levelNames[$level] . ": " . $codeFile . " in line " . $codeLine,
            'message' => self::__convertToStructure($message),
            'stack' => self::__generateStack($backtrace)
        );
        
        self::$__log[] = $log;
        
        return Mage::getBlockSingleton('shipsync/firebug')->getLastLoggedAsHtml();
    }
    
    
    
    /**
     * returns all currently logged messages
     *
     * @access public
     * @return array
     */
    public static function getLog()
    {
        return self::$__log;
    }
    
    
    
    /**
     * generates logging structure for current stack
     *
     * @access private
     * @param stack $backtrace
     * @return array
     */
    private static function __generateStack($backtrace)
    {
        $i = self::$__shiftupStackLevel * (-1);
        foreach ($backtrace as $stackLevel) {
            if (++$i < 2)
                continue;
            $message          = str_replace(Mage::getRoot(), "", array_key_exists('file', $stackLevel) ? $stackLevel['file'] : "") . " in line " . (array_key_exists('line', $stackLevel) ? $stackLevel['line'] : "") . " at function " . $stackLevel['function'];
            $return[$message] = null;
        }
        return $return;
    }
    
    
    
    /**
     * generates logging structure for given variable
     *
     * @access private
     * @param var $var
     * @return array
     */
    private static function __convertToStructure($var)
    {
        if (is_array($var) or is_object($var))
            $var = print_r($var, true);
        
        $level = 0;
        $lines = array();
        foreach (preg_split("/\n/", $var) as $line) {
            if (preg_match("/^\s*\($/", $line)) {
                $level++;
                $lines[$level] = array();
            } else if (preg_match("/^\s*\)$/", $line)) {
                $lines[$level - 1][$lastAdded[$level - 1]] = $lines[$level];
                $level--;
            } else if ("" != ($line = trim($line))) {
                $lastAdded[$level]    = $line;
                $lines[$level][$line] = null;
            }
        }
        if (count($lines) > 0)
            return $lines[0];
    }
    
    public static function getNumberAsInt($str)
    {
        switch ($str) {
            case 'zero':
                return 0;
            case 'one':
                return 1;
            case 'two':
                return 2;
            case 'three':
                return 3;
            case 'four':
                return 4;
            case 'five':
                return 5;
            case 'six':
                return 6;
            case 'seven':
                return 7;
            case 'eight':
                return 8;
        }
    }
    
    public static function mageLog($var, $name)
    {
        if (Mage::getStoreConfig('carriers/fedex/mage_log')) {
            Mage::log($var, null, 'shipsync_' . $name . '.log');
        }
    }
	/**
     * Convert weight in different measure types
     *
     * @param  mixed $value
     * @param  string $sourceWeightMeasure
     * @param  string $toWeightMeasure
     * @return int|null|string
     */
    public function convertMeasureWeight($value, $sourceWeightMeasure, $toWeightMeasure)
    {
        if ($value) {
            $locale = Mage::app()->getLocale()->getLocale();
            $unitWeight = new Zend_Measure_Weight($value, $sourceWeightMeasure, $locale);
            $unitWeight->setType($toWeightMeasure);
            return $unitWeight->getValue();
        }
        return null;
    }

    /**
     * Convert dimensions in different measure types
     *
     * @param  mixed $value
     * @param  string $sourceDimensionMeasure
     * @param  string $toDimensionMeasure
     * @return int|null|string
     */
    public function convertMeasureDimension($value, $sourceDimensionMeasure, $toDimensionMeasure)
    {
        if ($value) {
            $locale = Mage::app()->getLocale()->getLocale();
            $unitDimension = new Zend_Measure_Length($value, $sourceDimensionMeasure, $locale);
            $unitDimension->setType($toDimensionMeasure);
            return $unitDimension->getValue();
        }
        return null;
    }

    /**
     * Get name of measure by its type
     *
     * @param  $key
     * @return string
     */
    public function getMeasureWeightName($key)
    {
        $weight = new Zend_Measure_Weight(0);
        $conversionList = $weight->getConversionList();
        if (!empty($conversionList[$key]) && !empty($conversionList[$key][1])) {
            return $conversionList[$key][1];
        }
        return '';
    }

    /**
     * Get name of measure by its type
     *
     * @param  $key
     * @return string
     */
    public function getMeasureDimensionName($key)
    {
        $weight = new Zend_Measure_Length(0);
        $conversionList = $weight->getConversionList();
        if (!empty($conversionList[$key]) && !empty($conversionList[$key][1])) {
            return $conversionList[$key][1];
        }
        return '';
    }

    /**
     * Define if we need girth parameter in the package window
     *
     * @param string $shippingMethod
     * @return bool
     */
    public function displayGirthValue($shippingMethod)
    {
        if (in_array($shippingMethod, array(
             'usps_0_FCLE', // First-Class Mail Large Envelope
             'usps_1',      // Priority Mail
             'usps_2',      // Priority Mail Express Hold For Pickup
             'usps_3',      // Priority Mail Express
             'usps_4',      // Standard Post
             'usps_6',      // Media Mail
             'usps_INT_1',  // Priority Mail Express International
             'usps_INT_2',  // Priority Mail International
             'usps_INT_4',  // Global Express Guaranteed (GXG)
             'usps_INT_7',  // Global Express Guaranteed Non-Document Non-Rectangular
             'usps_INT_8',  // Priority Mail International Flat Rate Envelope
             'usps_INT_9',  // Priority Mail International Medium Flat Rate Box
             'usps_INT_10', // Priority Mail Express International Flat Rate Envelope
             'usps_INT_11', // Priority Mail International Large Flat Rate Box
             'usps_INT_12', // USPS GXG Envelopes
             'usps_INT_14', // First-Class Mail International Large Envelope
             'usps_INT_16', // Priority Mail International Small Flat Rate Box
             'usps_INT_20', // Priority Mail International Small Flat Rate Envelope
             'usps_INT_26', // Priority Mail Express International Flat Rate Boxes
        ))) {
            return true;
        } else {
            return false;
        }
    }
}