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
 * IllApps_Shipsync_Model_Shipping_Package_Sort
 */
class IllApps_Shipsync_Model_Shipping_Package_Sort
{

    protected $_key;
    
    
    /**
     * sortByKey
     *
     * @param array $items
     * @return array
     */
    public function sortByKey($key, $array)
    {
        $this->_key = $key;
        
	uasort($array, array($this, '_sortByKey'));

	return $array;
    }
    
    

    

    /**
     * _sortByKey
     *
     * @param array $a
     * @param array $b
     * @return array
     */
    protected function _sortByKey($a, $b)
    {
        $key = $this->_key;
                
	$a_key = (is_array($a) && isset($a[$key])) ? $a[$key] : 0;
	$b_key = (is_array($b) && isset($b[$key])) ? $b[$key] : 0;

	if ($a_key == $b_key) { return 0; }

	return ($a_key > $b_key) ? -1 : 1;
    }
    

    
    /**
     * Find Best Fit
     *
     * Finds package in which item fits with least amount of remaining space
     *
     * @param array $packages
     * @param array $item
     *
     * @return index
     */
    public function findBestFit($packages, $item)
    {
        foreach ($packages as $key => $package)
        {
            if ($this->findFit($package, $item))
            {
                $free_weights[$key] = $package['free_weight'];
                $free_volumes[$key] = $package['free_volume'];
            }
        }

        return (isset($free_weights) && isset($free_volumes) ? $this->minKey($free_weights, $free_volumes) : null);
    }



    /**
     * Min Key
     *
     * Compares two arrays, and returns the key where boths arrays have a minimum value, otherwise returns null
     *
     * @param array $array
     * @param array $cmpArray
     *
     * @return index
     */
    public function minKey($array, $cmpArray)
    {
        foreach ($array as $key => $val)
        {
            if ($val == min($array) && $cmpArray[$key] == min($cmpArray)) { return $key; }
            else { return null; }
        }
    }



    /**
     * Find Fit
     *
     * Finds if item fits in package, handles items with dimension and without
     *
     * @param array $package
     * @param array $item
     *
     * @return bool
     */
    public function findFit($package, $item)
    {
        if ($item['dimensions'])
        {
            if (($this->findFitWeight($package, $item)) && ($this->findFitVolume($package, $item)) && ($this->itemFits($package, $item)))
	    {
		return true;
	    }
            return false;
        }
        else { return $this->findFitWeight($package, $item); }
    }



    /**
     * Find Fit Volume
     *
     * Determines if item with dimension fits in package
     *
     * @param array $item
     * @param array $package
     * @return bool
     */
    public function findFitVolume($package, $item)
    {
        if ($item['volume'] <= $package['free_volume']) { return true; }
        else { return false; }
    }



    /**
     * Find Fit Weight
     *
     * Determines if item with weight fits in package
     *
     * @param array $item
     * @param array $package
     * @return bool
     */
    public function findFitWeight($package, $item)
    {
        if ($item['weight'] <= $package['free_weight']) { return true; }
        else { return false; }
    }

    /**
     * Item Fits
     * 
     * Test if item fits in the package if it were empty.
     * Simply tests the length of each side to see if some element is too long.
     * 
     * @param array $package
     * @param array $item
     * @return bool 
     * 
     */
    public function itemFits($package, $item)
    {        
        $arr = array($package['length'], $package['width'], $package['height']);
        $cmp = array($item['length'], $item ['width'], $item['height']);

        sort($arr); sort($cmp);
        
        return $this->dimensionCheck($arr, $cmp);
    }

    public function getPackageLongestSide($package)
    {
        $arr = array($package['length'], $package['width'], $package['height']);
        sort($arr);
        return $arr;
    }

    public function getItemLongestSide($items)
    {
        foreach ($items as $item)
        {
            $longest_l[] = ($item['dimensions']) ? $item['length'] : 0;
            $longest_w[] = ($item['dimensions']) ? $item['width'] : 0;
            $longest_h[] = ($item['dimensions']) ? $item['height'] : 0;
        }
        $arr = array(max($longest_l), max($longest_w), max($longest_h));
        sort($arr);
        return $arr;
    }

    public function dimensionCheck($arr, $cmp)
    {
        foreach($arr as $key => $el)
        {
            if($cmp[$key] > $el) { return false; }
        }
        return true;
    }
}