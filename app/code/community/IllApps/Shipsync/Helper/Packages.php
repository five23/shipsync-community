<?php
/**
 * ShipSync
 *
 * @category   IllApps
 * @package    IllApps_Shipsync
 * @author     Jonathan Cantrell (j@kernelhack.com)
 * @copyright  Copyright (c) 2011 EcoMATICS, Inc. DBA IllApps (http://www.illapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Helper data
 */
class IllApps_Shipsync_Helper_Packages extends Mage_Core_Helper_Abstract
{
    protected $pkg = array();

    function __construct()
    {
        $this->pkg = array(
                            array(  'title'     => 'FEDEX_ENVELOPE',
                                    'weight'    => 1.1,
                                    'length'    => 13.5,
                                    'width'     => 9.875,
                                    'height'    => 0.1,
                                    'baseline'  => 0.1125),
                            array(  'title'     => 'FEDEX_PAK',
                                    'weight'    => 5.5,
                                    'length'    => 15.5,
                                    'width'     => 12,
                                    'height'    => 0.1,
                                    'baseline'  => 0.0625),
                            array(  'title'     =>  'FEDEX_TUBE',
                                    'weight'    => 20,
                                    'length'    => 38,
                                    'width'     => 3.95,
                                    'height'    => 3.95,
                                    'baseline'  => 1),
                            array(  'title'     => 'FEDEX_BOX_SMALL',
                                    'weight'    => 20,
                                    'length'    => 12.25,
                                    'width'     => 10.9,
                                    'height'    => 1.5,
                                    'baseline'  => 0.28125),
                            array(  'title'     => 'FEDEX_BOX_MED',
                                    'weight'    => 20,
                                    'length'    => 13.25,
                                    'width'     => 11.5,
                                    'height'    => 2.38,
                                    'baseline'  => 0.40625),
                            array(  'title'     => 'FEDEX_BOX_LARGE',
                                    'weight'    => 20,
                                    'length'    => 17.88,
                                    'width'     => 12.38,
                                    'height'    => 3,
                                    'baseline'  => 0.90625),
                            array(  'title'     => 'FEDEX_10KG_BOX',
                                    'weight'    => 22,
                                    'length'    => 15.81,
                                    'width'     => 12.94,
                                    'height'    => 10.19,
                                    'baseline'  => 1.9375),
                            array(  'title'     => 'FEDEX_25KG_BOX',
                                    'weight'    => 55,
                                    'length'    => 21.56,
                                    'width'     => 16.56,
                                    'height'    => 13.19,
                                    'baseline'  => 3.5625),
                        );



        if ($this->getDimensionUnits() == 'CM')
        {
            foreach($this->pkg as &$package)
            {
                $this->toCM($package['length']);
                $this->toCM($package['width']);
                $this->toCM($package['height']);
            }
        }

        if ($this->getWeightUnits() == 'KG')
        {
            foreach($this->pkg as &$package)
            {
                $this->toKG($package['weight']);
                $this->toKG($package['baseline']);
            }
        }

        if ($this->getWeightUnits() == 'G')
        {
            foreach($this->pkg as &$package)
            {
                $this->toG($package['weight']);
                $this->toG($package['baseline']);
            }
        }

        $this->setVolume();

    }
    
     /**
     * Get dimension units
     *
     * @return string
     */
    public function getDimensionUnits()
    {
	return Mage::getStoreConfig('carriers/fedex/dimension_units');
    }

    /**
     * Get Weight units
     *
     * @return string
     */
    public function getWeightUnits()
    {
	return Mage::getStoreConfig('carriers/fedex/weight_units');
    }

    public function asArray($test = null)
    {
        
        
        if(!isset($test)) { return $this->pkg; }
        
        else
        {
            $test = explode(',',$test);

            foreach($this->pkg as $package)
            {
                if(in_array($package['title'], $test)) {$packages[] = $package;}
            }

            return (isset($packages) ? $packages : false);
        }
    }

    public function isFedexPackage($packageCode)
    {
        return ($this->getPackageName($packageCode) == $packageCode ? false : true);
    }

    public function getPackageCodes()
    {
        foreach ($this->pkg as $key => $type)
        {
            $var[] = $type['title'];
        }
        return $var;
    }
    public function getPackageName($packageType)
    {
        switch ($packageType)
        {
            case 'FEDEX_ENVELOPE':
                return 'Fedex Envelope';

            case 'FEDEX_PAK':
                return 'Fedex Pak';

            case 'FEDEX_BOX_SMALL':
                return 'Fedex Box - Small';

            case 'FEDEX_BOX_MED':
                return 'Fedex Box - Medium';

            case 'FEDEX_BOX_LARGE':
                return 'Fedex Box - Large';

            case 'FEDEX_TUBE':
                return 'Fedex Tube';

            case 'FEDEX_10KG_BOX':
                return 'Fedex 10kg Box';

            case 'FEDEX_25KG_BOX':
                return 'Fedex 25kg Box';

            default:
                return $packageType;
        }
    }

    public function getPackageMaxWeight($packageType)
    {
        return $this->pkg[$packageType]['weight'];
    }

    public function getPackageLength($packageType)
    {
        return $this->pkg[$packageType]['length'];
    }

    public function getPackageWidth($packageType)
    {
        return $this->pkg[$packageType]['width'];
    }

    public function getPackageHeight($packageType)
    {
        return $this->pkg[$packageType]['height'];
    }

    public function getPackageBaseline($packageType)
    {
        return $this->pkg[$packageType]['baseline'];
    }

    public function setVolume()
    {
        foreach($this->pkg as $key => &$package)
        {
            $this->pkg[$key]['volume'] = round($package['width'] * $package['height'] * $package['length'], 1);
        }
    }

    public function toCM(&$val)
    {
        $val = round($val * 2.54, 1);
    }

    public function toG(&$val)
    {
        $val = round($val * 453.59237, 1);
    }

    public function toKG(&$val)
    {
        $val = round($val * 0.45359237, 1);
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

        foreach ($items as $key => $item)
        {
            if($new)
            {

                $ret = $ret.$previous;
                $previous = $item[$field];
                $new = false;
            }
            elseif ($key + 1 == count($items))
            {
                if ($item[$field] != $previous + 1)
                {
                    $ret = $ret.$previous.','.$item[$field];
                }
                else
                {
                    $ret = $ret.$item[$field];
                    $previous = $item[$field];
                }
            }

            elseif ($item[$field] == $previous + 1)
            {
                $ret = $ret.'-';
                $previous = $item[$field];
            }
            elseif ($item[$field] != $previous + 1)
            {
                
                $ret = $ret.$previous.',';
                $previous = $item[$field];
                $new = true;
            }
            
        }
        return preg_replace('/[\-]+/', '-', $ret);
    }
}
