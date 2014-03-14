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
 * IllApps_Shipsync_Model_Shipping_Package_Origins
 */
class IllApps_Shipsync_Model_Shipping_Package_Origins
{
    /*
     * Prepare Request
     *
     * Takes request object, and <empty> arrays for items and packages.
     * Parses request packages and divides the items by package, or divides the items by origin
     * if request packages not set.
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @param array $itemsByOrigin
     * @param array $packagesByOrigin
     *
     * @return void
     *
     */
    public function prepareRequest (&$request, &$itemsByOrigin, &$packagesByOrigin)
    {        
        if($request->getPackages())
        {
            foreach($request->getPackages() as $package)
            {
                if(isset($itemsByOrigin[(int) $package['alt_origin']])) {
                    $itemsToMerge = $itemsByOrigin[(int) $package['alt_origin']];
                    $itemsByOrigin[(int) $package['alt_origin']] = array_merge($itemsToMerge, $package['items']);
                }
                else {
                    $itemsByOrigin[(int) $package['alt_origin']] = $package['items'];
                }
                $packagesByOrigin[(int) $package['alt_origin']][] = $package;
            }

            $request->setPackages(null);
        }
        else
        {
            $_items = Mage::getModel('shipsync/shipping_package')->getParsedItems($request->getAllItems());

            $itemsByOrigin = Mage::getModel('shipsync/shipping_package_item')->byOrigin($_items);
        }
    }

    /*
     * Prepare Ship Request...see above.
     * 
     * @param Mage_Shipping_Model_Shipping $request
     * @param $itemsByOrigin
     * @param $packagesByOrigin
     * 
     * @return void
     */
    public function prepareShipRequest(&$request, &$itemsByOrigin, &$packagesByOrigin)
    {
        if($request->getPackages())
        {
            foreach($request->getPackages() as $package)
            {
                if(isset($itemsByOrigin[(int) $package->getOrigin()])) {
                    $itemsToMerge = $itemsByOrigin[(int) $package->getOrigin()];
                    $itemsByOrigin[(int) $package->getOrigin()] = array_merge($itemsToMerge, $package->getItems());
                }
                else {
                    $itemsByOrigin[(int) $package->getOrigin()] = $package->getItems();
                }
                $packagesByOrigin[(int) $package->getOrigin()][] = $package;
            }

            $request->setPackages(null);
        }
        else
        {
            $_items = Mage::getModel('shipsync/shipping_package')->getParsedItems($request->getAllItems());

            $itemsByOrigin = Mage::getModel('shipsync/shipping_package_item')->byOrigin($_items);
        }
    }

    /*
     * Set Origins
     * Sets request object's origins based on key.
     * 
     * @param object $request
     * @param int $int
     * 
     * @return void
     */
    public function setOrigins(&$request, $int)
    {
        $origin = Mage::getModel('shipsync/shipping_package_item')->getOrigin($int);//(int) $item['alt_origin']);

        $request->setOrigCountryId($origin['country']);
        $request->setOrigRegionId($origin['regionId']);
        $request->setOrigRegionCode($origin['regionCode']);
        $request->setOrigPostcode($origin['postcode']);
        $request->setOrigCity($origin['city']);
        $request->setOrigStreet($origin['street']);
    }

    /*
     * Collect Multiple Shipments
     * 
     * @param array $shipResultCollection
     * 
     * @return array $retval
     */
    public function collectMultipleShipments($shipResultCollection)
    {
        foreach($shipResultCollection as $shipResult)
        {
            $retval = (isset($retval)) ? array_merge($retval, $shipResult) : $shipResult;
        }
	return $retval;
    }

    /*
     * Collect Multiple Responses
     * 
     * Collects and combines responses by origin
     * 
     * @param array $responseCollection
     * 
     * @return Mage_Shipping_Model_Rate_Result $responses
     */
    public function collectMultipleResponses($responseCollection)
    {        
        $_responses = new Mage_Shipping_Model_Rate_Result;
        
        $responses = $responseCollection[0];

        if($responses->getError()) {return $responses;}

        unset($responseCollection[0]);

        foreach ($responseCollection as $cmpResponses)
        {
            $i = count($cmpResponses->getAllRates());

            if(!isset($cmpResponses)) {return $responses;}

            while($i)
            {
                $i--;
                $match = false;

                foreach($cmpResponses->getAllRates() as $key => $cmpMethod)
                {
                    if($responses->getRateById($i)->getMethod() == $cmpMethod->getMethod())
                    {
                        $responses->getRateById($i)->setCost($responses->getRateById($i)->getCost() + $cmpMethod->getCost());
                        $responses->getRateById($i)->setPrice($responses->getRateById($i)->getPrice() + $cmpMethod->getPrice());
                        $match = true;
                    }
                }
                if($match) { $_responses->append($responses->getRateById($i)); }
            }
            $responses = $_responses;
        }
        return $responses;
    }

    public function collectSaturdayResponses ($responseCollection)
    {
        $_responses = new Mage_Shipping_Model_Rate_Result;

        if (isset($responseCollection[1])) { $responses = $responseCollection[1]; }

        $err = $responses->asArray();

        if(empty($err) || $responses->getError()) {return $responseCollection[0];}
        
        foreach ($responseCollection[0]->getAllRates() as $key => $method)
        {
            $methodKeys[$key] = $method->getMethod();
        }

        foreach ($responses->getAllRates() as $key => $method)
        {            
            if (in_array($method->getMethod(), $methodKeys))
            {
                unset($methodKeys[array_search($method->getMethod(), $methodKeys)]);
            }
            $_responses->append($method);
        }

        if (isset($methodKeys))
        {
            foreach ($methodKeys as $key => $method)
            {
                $_responses->append($responseCollection[0]->getRateById($key));
            }
        }
        
        return $_responses;

    }
}