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
}