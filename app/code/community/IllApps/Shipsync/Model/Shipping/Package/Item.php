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
 * IllApps_Shipsync_Model_Shipping_Package_Item
 */
class IllApps_Shipsync_Model_Shipping_Package_Item
{
    
    
    /**
     * getParsedItems
     *
     * @param object $items
     * @param bool $toShip
     * @return array $_items
     */
    public function getParsedItems($items, $toShip = false)
    {
        $i = 0;
        
        $packageSort = Mage::getModel('shipsync/shipping_package_sort');
        
        foreach ($items as $item) {
			
            if ($item->getParentItem() && !$item->isShipSeparately()) {
                continue;
            }
            if ($item->getHasChildren() && $item->isShipSeparately()) {
                continue;
            }
            if ($item->getIsVirtual()) {
                continue;
            }
            
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            
            if ($item->getIsQtyDecimal()) {
                $itemQty = 1;
            } elseif ($toShip) {
                $itemQty = $item->getQtyToShip();
            } else {
                $itemQty = $item->getQty() > 0 ? $item->getQty() : 1;
            }
            
            while ($itemQty > 0) {
                if ($toShip) {
                    
                    $_items[$i]['status'] = $item->getStatus();
                    $_items[$i]['sku']    = $item->getSku();
                    
                    $_items[$i]['weight'] = round($item->getWeight(), 2) > 0 ? round($item->getWeight(), 2) : 0.1;
                    
                    $_items[$i]['dangerous'] = $product->getDangerousGoods();
                }
                
                $_items[$i]['id']             = $item->getItemId();
                $_items[$i]['product_id']     = $item->getProductId();
                $_items[$i]['name']           = $item->getName();
                $_items[$i]['weight']         = $item->getIsQtyDecimal() ? $item->getQtyOrdered() * $item->getWeight() : $item->getWeight();
                $_items[$i]['qty']            = $item->getQtyOrdered();
                $_items[$i]['value']          = $product->getPrice();
                $_items[$i]['special']        = $product->getSpecialPackaging();
                $_items[$i]['free_shipping']  = $product->getFreeShipping();
                $_items[$i]['alt_origin']     = 0; //(int) $product->getProductOrigin();
                $_items[$i]['is_decimal_qty'] = $item->getIsQtyDecimal();
                
                if (Mage::getStoreConfig('carriers/fedex/enable_dimensions') && $product->getWidth() && $product->getHeight() && $product->getLength()) {
                    $_items[$i]['dimensions'] = true;
                    
                    if ($toShip) {
                        $itemLength = round($product->getLength(), 2) > 0 ? round($product->getLength(), 2) : 1;
                        $itemWidth  = round($product->getWidth(), 2) > 0 ? round($product->getWidth(), 2) : 1;
                        $itemHeight = round($product->getHeight(), 2) > 0 ? round($product->getHeight(), 2) : 1;
                        
                        $itemVolume = round($itemLength * $itemWidth * $itemHeight);
                    } else {
                        $itemLength = $product->getLength();
                        $itemWidth  = $product->getWidth();
                        $itemHeight = $product->getHeight();
                        
                        $itemVolume = $itemLength * $itemWidth * $itemHeight;
                    }
                    
                    $_items[$i]['length'] = $itemLength;
                    $_items[$i]['width']  = $itemWidth;
                    $_items[$i]['height'] = $itemHeight;
                    $_items[$i]['volume'] = $itemVolume;
                } else {
                    $_items[$i]['dimensions'] = false;
                    
                    $_items[$i]['length'] = null;
                    $_items[$i]['width']  = null;
                    $_items[$i]['height'] = null;
                    $_items[$i]['volume'] = null;
                }
                
                $itemQty--;
                
                $i++;
            }
			/*
        foreach ($items as $item)
        {            
	    if ($item->getParentItem()  && !$item->isShipSeparately()) { continue; }
	    if ($item->getHasChildren() &&  $item->isShipSeparately()) { continue; }
	    if ($item->getIsVirtual())				       { continue; }

	    $product = Mage::getModel('catalog/product')->load($item->getProductId());

            if($item->getIsQtyDecimal()) { $itemQty = 1; }
	    elseif ($toShip) { $itemQty = $item->getQtyToShip(); }
	    else	 { $itemQty = $item->getQty() > 0 ? $item->getQty() : 1; }

	    while ($itemQty > 0)
	    {				
		if ($toShip)
		{		    

		    $_items[$i]['status']     = $item->getStatus();
		    $_items[$i]['sku']	      = $item->getSku();

		    $_items[$i]['weight']     = round($item->getWeight(), 2) > 0 ? round($item->getWeight(), 2) : 0.1;

		    $_items[$i]['dangerous']  = $product->getDangerousGoods();
		}

                $_items[$i]['id']	     = $item->getItemId();
                $_items[$i]['product_id']    = $item->getProductId();
		$_items[$i]['name']	     = $item->getName();
		$_items[$i]['weight']	     = $item->getIsQtyDecimal() ? $item->getQtyOrdered() * $item->getWeight() : $item->getWeight();
                $_items[$i]['qty']           = $item->getQtyOrdered();
		$_items[$i]['value']	     = $product->getPrice();
		$_items[$i]['special']	     = $product->getSpecialPackaging();
                $_items[$i]['free_shipping'] = $product->getFreeShipping();
                $_items[$i]['alt_origin']    = 0; //(int) $product->getProductOrigin();
                $_items[$i]['is_decimal_qty']= $item->getIsQtyDecimal();

		if (Mage::getStoreConfig('carriers/fedex/enable_dimensions')
			&& $product->getWidth() && $product->getHeight() && $product->getLength())
		{
		    $_items[$i]['dimensions'] = true;

		    if ($toShip)
		    {
			$itemLength = round($product->getLength(), 2) > 0 ? round($product->getLength(), 2) : 1;
			$itemWidth  = round($product->getWidth(),  2) > 0 ? round($product->getWidth(),  2) : 1;
			$itemHeight = round($product->getHeight(), 2) > 0 ? round($product->getHeight(), 2) : 1;

			$itemVolume = round($itemLength * $itemWidth * $itemHeight);
		    }
		    else
		    {
			$itemLength = $product->getLength();
			$itemWidth  = $product->getWidth();
			$itemHeight = $product->getHeight();
			
			$itemVolume = $itemLength * $itemWidth * $itemHeight;
		    }		    

		    $_items[$i]['length'] = $itemLength;
		    $_items[$i]['width']  = $itemWidth;
		    $_items[$i]['height'] = $itemHeight;
		    $_items[$i]['volume'] = $itemVolume;
		}
		else
		{
		    $_items[$i]['dimensions'] = false;
		    
		    $_items[$i]['length']     = null;
		    $_items[$i]['width']      = null;
		    $_items[$i]['height']     = null;
		    $_items[$i]['volume']     = null;
		}

		$itemQty--;

		$i++;
	    }
*/
        }
        
        if (!isset($_items) || !is_array($_items)) {
            return false;
        }
        
        if ($toShip) {
            foreach ($_items as $item) {
                $quantity_count[$item['product_id']][] = $item;
            }
            
            $quantity_count = $packageSort->sortByKey('weight', $quantity_count);
            
            unset($_items);
            
            foreach ($quantity_count as $items) {
                foreach ($items as $item) {
                    $_items[] = $item;
                }
            }
        } else {
            $_items = $packageSort->sortByKey('weight', $_items);
        }
        
        return $_items;
    }
    
    /*public function getOrigins($items)
    {
    foreach ($items as $item)
    {
    $origin = (int) $item['alt_origin'];
    $col[$origin] = $origin;
    }
    return $col;
    }*/
    
    /*
     * By Origin
     * 
     * Sorts items by origin
     * 
     * @param array $items
     * @return array $items
     */
    public function byOrigin($items)
    {
        foreach ($items as $item) {
            $_items[(int) $item['alt_origin']][] = $item;
        }
        return $_items;
    }
    
    /*
     * Get Origin
     * 
     * @param int $id
     * @return array $origin
     */
    public function getOrigin($id)
    {
        if ($id != 0) {
            $origRegionCode = Mage::getModel('directory/region')->load(Mage::getStoreConfig('shipping/altorigin/region'))->getCode();
            
            $origin['country']    = Mage::getStoreConfig('shipping/altorigin/country');
            $origin['regionId']   = Mage::getStoreConfig('shipping/altorigin/region');
            $origin['regionCode'] = (strlen($origRegionCode) > 2) ? '' : $origRegionCode;
            $origin['postcode']   = Mage::getStoreConfig('shipping/altorigin/postcode');
            $origin['city']       = Mage::getStoreConfig('shipping/altorigin/city');
            
            $shipperStreetLines = array(
                Mage::getStoreConfig('shipping/altorigin/address1')
            );
            if (Mage::getStoreConfig('shipping/altorigin/address2') != '') {
                $shipperStreetLines[] = Mage::getStoreConfig('shipping/altorigin/address2');
            }
            if (Mage::getStoreConfig('shipping/altorigin/address3') != '') {
                $shipperStreetLines[] = Mage::getStoreConfig('shipping/altorigin/address3');
            }
            $origin['street'] = $shipperStreetLines;
        } else {
            $origRegionCode = Mage::getModel('directory/region')->load(Mage::getStoreConfig('shipping/origin/region_id'))->getCode();
            
            $origin['country']    = Mage::getStoreConfig('shipping/origin/country_id');
            $origin['regionId']   = Mage::getStoreConfig('shipping/origin/region_id');
            $origin['regionCode'] = (strlen($origRegionCode) > 2) ? '' : $origRegionCode;
            $origin['postcode']   = Mage::getStoreConfig('shipping/origin/postcode');
            $origin['city']       = Mage::getStoreConfig('shipping/origin/city');
            
            $shipperStreetLines = array(
                Mage::getStoreConfig('shipping/origin/address1')
            );
            if (Mage::getStoreConfig('shipping/origin/address2') != '') {
                $shipperStreetLines[] = Mage::getStoreConfig('shipping/origin/address2');
            }
            if (Mage::getStoreConfig('shipping/origin/address3') != '') {
                $shipperStreetLines[] = Mage::getStoreConfig('shipping/origin/address3');
            }
            $origin['street'] = $shipperStreetLines;
        }
        return $origin;
    }
}