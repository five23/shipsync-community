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
 * IllApps_Shipsync_Model_Shipping_Package
 */
class IllApps_Shipsync_Model_Shipping_Package
{
    
    
    protected $_packageError;
    protected $_packageCollection;
    
    
    
    /**
     * getPackages
     *
     * @param array $carriers
     * @return array
     */
    public function getDefaultPackages($carriers = null)
    {
        // Merge packages
        $mergedPackages = array_merge($this->getSpecialPackage(), // Special package
            $this->getShipsyncPackages(), // ShipSync packages
            $this->getCarrierPackages($carriers) // Carrier specific packages
            );
        
        $sortedPackages = Mage::getModel('shipsync/shipping_package_sort')->sortByKey('max_weight', $mergedPackages);
        
        foreach ($sortedPackages as $key => $package) {
            $package['value']  = $key;
            $package['items']  = null;
            $defaultPackages[] = $package;
        }
        
        return $defaultPackages;
    }
    
    
    
    /**
     * getCarrierPackages
     *
     * @param array $carriers
     * @return array
     */
    public function getCarrierPackages($carriers)
    {
        $carrierPackages = array();
        
        // Check if carriers are set
        if (is_array($carriers)) {
            
            // Loop through carriers
            foreach ($carriers as $carrier) {
                if ($carrier == 'fedex') {
                    if ($fedexPackages = Mage::getModel('usa/shipping_carrier_fedex')->getPackages()) {
                        $carrierPackages = array_merge($fedexPackages, $carrierPackages);
                    }
                }
            }
        }
        
        return $carrierPackages;
    }
    
    
    
    
    /**
     * getSpecialPackage
     * 
     * @return array
     */
    public function getSpecialPackage()
    {
        // Return special package
        return array(
            'SPECIAL_PACKAGE' => array(
                'label' => Mage::helper('usa')->__('Special Packaging'),
                'length' => null,
                'width' => null,
                'height' => null,
                'max_weight' => null,
                'max_volume' => null,
                'baseline' => null
            )
        );
    }
    
    
    
    /**
     * getShipsyncPackages
     *
     * @return array
     */
    public function getShipsyncPackages()
    {
        
        $shipsyncPackages = array();
        
        // Iterate through package slots
        for ($i = 1; $i <= 20; $i++) {
            // Retrieve package data from config
            if (Mage::getStoreConfig('shipping/packages/pkg' . $i . 'enabled')) {
                $label      = Mage::getStoreConfig('shipping/packages/pkg' . $i . 'title');
                $length     = Mage::getStoreConfig('shipping/packages/pkg' . $i . 'length');
                $width      = Mage::getStoreConfig('shipping/packages/pkg' . $i . 'width');
                $height     = Mage::getStoreConfig('shipping/packages/pkg' . $i . 'height');
                $base       = Mage::getStoreConfig('shipping/packages/pkg' . $i . 'base');
                $max_weight = Mage::getStoreConfig('shipping/packages/pkg' . $i . 'weight');
                
                $max_volume = ($length * $width * $height);
                
                // Add package to array
                $shipsyncPackages["SHIPSYNC_PKG_$i"] = array(
                    'label' => $label,
                    'length' => $length,
                    'width' => $width,
                    'height' => $height,
                    'max_weight' => $max_weight,
                    'max_volume' => $max_volume,
                    'baseline' => $base
                );
            }
        }
        
        // Return packages
        return $shipsyncPackages;
    }
    
    
    
    /**
     * getParsedItems
     *
     * @param array $items
     * @param bool $toShip
     * @return array
     */
    public function getParsedItems($items, $toShip = false)
    {
        return Mage::getModel('shipsync/shipping_package_item')->getParsedItems($items, $toShip);
    }
    
    
    
    /**
     * Estimate packages
     *
     * @param array $items
     * @param array $defaultPackages
     * @return array
     */
    public function estimatePackages($itemsToShip, $defaultPackages)
    {
        $i = 0; // Package counter
        $s = 0; // Special packaging counter
        
        // Unset special package
        unset($defaultPackages['SPECIAL_PACKAGE']);
        
        $_items = $this->collectPackages($itemsToShip, $specialPackages, $s);
        
        if (!empty($_items)) {
            foreach ($_items as $alt => $items) {
                // Iterate through items to pack
                foreach ($items as $key => $item) {
                    // Check if item has dimensions
                    if (!isset($item['dimensions']) || $item['dimensions'] == null) {
                        // If not, set volume to 0
                        $item['volume'] = 0;
                    }
                    
                    // If package has not been started
                    if (!isset($packages[$i]['weight'])) {
                        // Set package volume to 0
                        $packages[$i]['volume'] = 0;
                        
                        // Init package
                        $this->initPackage($defaultPackages, $packages, $item, $i, true);
                    }
                    
                    // If a package has been started
                    else {
                        $key = Mage::getModel('shipsync/shipping_package_sort')->findBestFit($packages, $item);
                        
                        if (isset($key)) {
                            $packages[$key]['items'][] = $item;
                            $packages[$key]['weight'] += $item['weight'];
                            $packages[$key]['volume'] += $item['volume'];
                            $packages[$key]['free_weight'] = $packages[$key]['free_weight'] - $item['weight'];
                            $packages[$key]['free_volume'] = $packages[$key]['free_volume'] - $item['volume'];
                        } else {
                            $i++;
                            
                            $this->initPackage($defaultPackages, $packages, $item, $i);
                        }
                    }
                }
                
                $count = (isset($_packages)) ? count($_packages) : 0;
                
                foreach ($packages as $key => $package) {
                    $package['alt_origin']    = $alt;
                    $_packages[$key + $count] = $package;
                }
                unset($packages);
            }
        }
        
        if (isset($_packages)) {
            $this->optimizePackages($defaultPackages, $_packages);
        }
        
        if (isset($specialPackages) && is_array($specialPackages)) {
            foreach ($specialPackages as $specialPackage) {
                $_packages[] = $specialPackage;
            }
        }

        $this->setPackageOrigin($_packages);
        
        return $_packages;
    }
    
    
    
    /**
     * optimizePackages
     * 
     * @param array $defaultPackages
     * @param array $packages
     */
    public function optimizePackages($defaultPackages, &$packages)
    {
        $sort = Mage::getModel('shipsync/shipping_package_sort');
        
        foreach ($defaultPackages as $key => $defaultPackage) {
            if ($defaultPackage['value'] == 'SPECIAL_PACKAGE') {
                unset($defaultPackages[$key]);
                break;
            }
            $free_weights[] = $defaultPackage['max_weight'];
            $free_volumes[] = $defaultPackage['max_volume'];
            $longest_side[] = $sort->getPackageLongestSide($defaultPackage);
        }
        
        foreach ($packages as $key => &$package) {
            $i = 0;
            
            $package['max_dim'] = $sort->getItemLongestSide($package['items']);
            
            while ($i < count($free_weights)) {
                if (isset($package['weight']) && isset($package['volume']) && $package['weight'] < $free_weights[$i] && $package['volume'] < $free_volumes[$i] && $sort->dimensionCheck($longest_side[$i], $package['max_dim']) || isset($package['weight']) && !isset($package['volume']) && $package['weight'] < $free_weights[$i]) {
                    $j = $i;
                    
                    $packages[$key]['value']       = $defaultPackages[$j]['value'];
                    $packages[$key]['label']       = $defaultPackages[$j]['label'];
                    $packages[$key]['max_weight']  = $defaultPackages[$j]['max_weight'];
                    $packages[$key]['max_volume']  = $defaultPackages[$j]['max_volume'];
                    $packages[$key]['length']      = $defaultPackages[$j]['length'];
                    $packages[$key]['width']       = $defaultPackages[$j]['width'];
                    $packages[$key]['height']      = $defaultPackages[$j]['height'];
                    $packages[$key]['free_weight'] = $defaultPackages[$j]['max_weight'] - $package['weight'];
                    $packages[$key]['free_volume'] = $defaultPackages[$j]['max_volume'] - $package['volume'];
                    $packages[$key]['baseline']    = $defaultPackages[$j]['baseline'];
                }
                
                $i++;
            }
            if (isset($package['weight']) && isset($package['baseline'])) {
                $package['weight'] = $package['weight'] + $package['baseline'];
            }
        }
    }
    
    
    
    /**
     * initPackage
     *
     * If package has not been started, creates a new package to fill
     *
     * @param array $defaultPackages
     * @param array $packages
     * @param array $item
     * @param int $i
     * @param bool $init
     */
    public function initPackage(&$defaultPackages, &$packages, $item, $i, $init = false)
    {
        $packageFit = false;
        
        foreach ($defaultPackages as $key => &$defaultPackage) {
            if ($init) {
                $defaultPackage['free_weight'] = $defaultPackage['max_weight'] - $defaultPackage['baseline'];
                $defaultPackage['free_volume'] = $defaultPackage['max_volume'];
            }
            
            if (Mage::getModel('shipsync/shipping_package_sort')->findFit($defaultPackage, $item)) {
                $packages[$i]['free_weight'] = $defaultPackage['free_weight'];
                $packages[$i]['free_volume'] = $defaultPackage['free_volume'];
                
                $this->setPackage($defaultPackage, $packages, $item, $i);
                $packageFit = true;
                break;
            }
        }
        if (!$packageFit) {
            $this->_packageError = 'There are no configured packages large enough to ship this item.
		    Please contact the store administrator.';
        }
    }
    
    
    
    /**
     * setSpecial
     *
     * Sets the array containing special packaging packages
     *
     * @param array $special_packages
     * @param array $item
     * @param int   $s
     */
    public function setSpecial(&$specialPackages, &$item, &$s)
    {
        $specialPackages[$s]['value'] = 'SPECIAL_PACKAGE';
        $specialPackages[$s]['label'] = Mage::Helper('usa')->__('Special Packaging');
        
        $specialPackages[$s]['items'][0]   = $item;
        $specialPackages[$s]['weight']     = $item['weight'];
        $specialPackages[$s]['alt_origin'] = $item['alt_origin'];
        
        // If item has dimensions
        if ($item['dimensions']) {
            $specialPackages[$s]['length'] = $item['length']; // Set package length
            $specialPackages[$s]['width']  = $item['width']; // Set package width
            $specialPackages[$s]['height'] = $item['height']; // Set package height
            $specialPackages[$s]['volume'] = $item['volume']; // Set package volume
        } else {
            $specialPackages[$s]['length'] = null;
            $specialPackages[$s]['width']  = null;
            $specialPackages[$s]['height'] = null;
            $specialPackages[$s]['volume'] = null;
        }
        
        //Increment special package counter
        $s++;
    }
    
    /**
     * Set Package
     *
     * Sets package with default package attributes
     *
     * @param array $defaultPackage
     * @param array $packages
     * @param array $item
     * @param int   $i
     */
    public function setPackage($defaultPackage, &$packages, $item, $i)
    {
        $packages[$i]['value']       = $defaultPackage['value'];
        $packages[$i]['label']       = $defaultPackage['label'];
        $packages[$i]['max_weight']  = $defaultPackage['max_weight'];
        $packages[$i]['max_volume']  = $defaultPackage['max_volume'];
        $packages[$i]['items'][]     = $item;
        $packages[$i]['weight']      = $item['weight'] + $defaultPackage['baseline'];
        $packages[$i]['length']      = $defaultPackage['length'];
        $packages[$i]['width']       = $defaultPackage['width'];
        $packages[$i]['height']      = $defaultPackage['height'];
        $packages[$i]['free_weight'] = $packages[$i]['free_weight'] - $item['weight'];
        $packages[$i]['free_volume'] = $packages[$i]['free_volume'] - $item['volume'];
        
        if (isset($item['dimensions'])) {
            $packages[$i]['volume'] = +$item['volume'];
        } else {
            $packages[$i]['volume'] = 0;
        }
    }
    
    
    
    /**
     * Takes argument of the items in a package, and an array key name.
     * Parses them, and resorts their human-readable contents.
     * @param Array $items
     * @param String $field
     * @return String
     */
    public function asRange($items, $field)
    {
        $previous = $items[0][$field];
        
        $ret = '';
        
        $new = true;
        
        foreach ($items as $key => $item) {
            if ($new) {
                $ret      = $ret . $previous;
                $previous = $item[$field];
                $new      = false;
            } elseif ($key + 1 == count($items)) {
                if ($item[$field] != $previous + 1) {
                    $ret = $ret . ',' . $item[$field];
                } else {
                    $ret      = $ret . $item[$field];
                    $previous = $item[$field];
                }
            } elseif ($item[$field] == $previous + 1) {
                $ret = $ret . '-';
                
                $previous = $item[$field];
            } elseif ($item[$field] != $previous + 1) {
                $ret      = $ret . $previous . ',';
                $previous = $item[$field];
                $new      = true;
            }
        }
        return preg_replace('/[\-]+/u', '-', $ret);
    }
    
    public function setPackageOrigin(&$packages)
    {
        foreach ($packages as $key => &$package) {
            if (isset($package['alt_origin'])) {
                $origRegionCode = Mage::getModel('directory/region')->load(Mage::getStoreConfig('shipping/altorigin/region'))->getCode();
                
                $package['country']  = Mage::getStoreConfig('shipping/altorigin/country');
                $package['region']   = (strlen($origRegionCode) > 2) ? '' : $origRegionCode;
                $package['postcode'] = Mage::getStoreConfig('shipping/altorigin/postcode');
                $package['city']     = Mage::getStoreConfig('shipping/altorigin/city');
                $package['address1'] = Mage::getStoreConfig('shipping/altorigin/address1');
                if (Mage::getStoreConfig('shipping/altorigin/address2') != '') {
                    $package['address2'] = Mage::getStoreConfig('shipping/altorigin/address2');
                }
                if (Mage::getStoreConfig('shipping/altorigin/address3') != '') {
                    $package['address3'] = Mage::getStoreConfig('shipping/altorigin/address3');
                }
            } else {
                $origCountry    = Mage::getStoreConfig('shipping/origin/country_id');
                $origRegionCode = Mage::getModel('directory/region')->load(Mage::getStoreConfig('shipping/origin/region_id'))->getCode();
                
                $package['country']  = $origCountry;
                $package['region']   = (strlen($origRegionCode) > 2) ? '' : $origRegionCode;
                $package['postcode'] = Mage::getStoreConfig('shipping/origin/postcode');
                $package['city']     = Mage::getStoreConfig('shipping/origin/city');
                $package['address1'] = Mage::getStoreConfig('shipping/origin/address1');
                if (Mage::getStoreConfig('shipping/origin/address2') != '') {
                    $package['address2'] = Mage::getStoreConfig('shipping/origin/address2');
                }
                if (Mage::getStoreConfig('shipping/origin/address3') != '') {
                    $package['address3'] = Mage::getStoreConfig('shipping/origin/address3');
                }
            }
        }
    }
    
    
    
    /**
     * getPackageError
     *
     * @return mixed
     */
    public function getPackageError()
    {
        if ($this->_packageError != '') {
            return $this->_packageError;
        }
        return false;
    }
    
    public function getFreeMethodWeight($items)
    {
        $weight = 0;
        
        foreach ($items as $item) {
            if ($item['free_shipping']) {
                $weight += $item['weight'];
            }
        }
        return $weight;
    }
    
    public function getPackageWeight($items)
    {
        $weight = 0;
        
        foreach ($items as $item) {
            $weight += $item['weight'];
        }
        return $weight;
    }
    
    public function getPackageValue($items)
    {
        $value = 0.0;
        
        foreach ($items as $item) {
            if (isset($item['value'])) {
                $value += $item['value'];
            }
        }
        return $value;
    }
    
    public function getPackageDiscount($items, $order)
    {
        $discount = 0.0;
        foreach ($items as $item) {
            $discount += $order->getItemById($item['id'])->getDiscountAmount();
        }
        return $discount;
    }
    
    public function collectPackages($itemsToShip, &$specialPackages, &$s)
    {
        if (empty($itemsToShip)) {
            return false;
        }
        
        // Iterate through items
        foreach ($itemsToShip as $key => &$item) {
            // Set item number
            $item['item_number'] = $key + 1;
            
            // If item requires special packaging
            if ($item['special']) {
                // Add item to special package
                $this->setSpecial($specialPackages, $item, $s);
            }
            /*else if ($xpkgs = Mage::getModel('extrapackages/package')->getExtraPackages($item['product_id']))
            {
            $this->setExtraPackages($specialPackages, $xpkgs, $item, $s);
            }*/
            else {
                $_items[$item['alt_origin']][] = $item;
            }
            
        }
        return isset($_items) ? $_items : false;
    }
}