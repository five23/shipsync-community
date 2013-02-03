<?php

/**
 * ShipSync
 *
 * @category   IllApps
 * @package    IllApps_Shipsync
 * @author     David Kirby (d@kernelhack.com)
 * @copyright  Copyright (c) 2011 EcoMATICS, Inc. DBA IllApps (http://www.illapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Shipsync adminhtml block
 */
class IllApps_Shipsync_Block_Adminhtml_Shipsync extends Mage_Adminhtml_Block_Widget
{

    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct();
        
	$this->setOrderId($this->getRequest()->getParam('order_id'));						    // Set order ID
	$this->setOrder(Mage::getModel('sales/order')->load($this->getOrderId()));				    // Set order model
	$this->setOrderUrl($this->getUrl('adminhtml/sales_order/view', array('order_id'=> $this->getOrderId())));   // Set order URL
		
	$method = explode('_', $this->getOrder()->getShippingMethod());						    // Get shipping method

	$this->setCarrier(Mage::getModel('usa/shipping_carrier_fedex'));					    // Set carrier model
	$this->setCarrierTitle(Mage::getStoreConfig('carriers/fedex/title'));					    // Set carrier title
	$this->setCarrierCode(strtoupper($method[0]));								    // Set carrier code	
	$this->setMethodCode($method[1]);									    // Set method code
	$this->setMethod($this->getCarrier()->getCode('method', $this->getMethodCode()));			    // Set method
	$this->setAllowedMethods(explode(",", Mage::getStoreConfig('carriers/fedex/allowed_methods')));		    // Set allowed methods
	$this->setDimensionUnits($this->getCarrier()->getDimensionUnits());					    // Set dimension units
	$this->setWeightUnits($this->getCarrier()->getWeightUnits());						    // Set weight units
	$this->setItems($this->_getItemsToShip($this->getOrder()->getAllItems()));				    // Set items to ship
	$this->setDefaultPackages($this->getCarrier()->getDefaultPackages());					    // Set default packages
	$this->setPackages($this->getCarrier()->estimatePackages($this->getItems(), $this->getDefaultPackages()));  // Set packages

	$packageOptions = "";

	// Get package options
	foreach ($this->getDefaultPackages() as $defaultPackage) {
	    $packageOptions .= '<option value="' . $defaultPackage['id'] . '">' . 
		htmlentities($defaultPackage['title'], ENT_QUOTES) . '</option>';
	}
	
	$this->setDefaultPackageOptions($packageOptions);   // Set package options
    }


    /**
     * Get items
     * 
     * @param object $items
     * @return array
     */
    protected function _getItemsToShip($items)
    {
	/** Check if dimensions are enabled */
	$enable_dimensions = Mage::getStoreConfig('carriers/fedex/enable_dimensions');

	$i=0; /** Master item counter */

        /** Iterate through items */
        foreach ($items as $item)
        {
	    /** If the item is a child, and is not set to ship separately */
	    if ($item->getParentItem() && !$item->isShipSeparately()) { continue; }

	    /** If the item is a parent, and is set to ship separately */
	    if ($item->getHasChildren() && $item->isShipSeparately()) { continue; }

	    /** If item is virtual */
	    if ($item->getIsVirtual()) { continue; }

	    /** Load associated product */
	    $product = Mage::getModel('catalog/product')->load($item->getProductId());	    

	    /** Get item quantity */
	    $qty = ($item->getQtyToShip() > 0) ? $item->getQtyToShip() : 1;

	    /** While quantity is greater than 0 */
	    while ($qty > 0)
	    {
		$_items[$i]['id']	  = $item->getItemId();
		$_items[$i]['product_id'] = $item->getProductId();
		$_items[$i]['name']       = $item->getName();		 /** Set item name */
		$_items[$i]['status']     = $item->getStatus();
		$_items[$i]['sku']	  = $item->getSku();
		$_items[$i]['weight']     = round($item->getWeight(), 2) > 0 ? round($item->getWeight(), 2) : 0.1;
		$_items[$i]['special']    = $product->getSpecialPackaging(); /** Set special packaging flag */
                $_items[$i]['dangerous']  = $product->getDangerousGoods();   /** Set dangerous goods */

		/** If dimensions are enabled and present for this item */
		if ($enable_dimensions && $product->getWidth() && $product->getHeight() && $product->getLength())
		{
		    $_items[$i]['dimensions'] = true;		/** Dimensions true */
		    $_items[$i]['length'] = round($product->getLength(), 2) > 0 ? round($product->getLength(), 2) : 1;   /** Set length */
		    $_items[$i]['width']  = round($product->getWidth(),  2) > 0 ? round($product->getWidth(), 2)  : 1;   /** Set width */
		    $_items[$i]['height'] = round($product->getHeight(), 2) > 0 ? round($product->getHeight(), 2) : 1;   /** Set height */
		    $_items[$i]['volume'] = round($product->getLength() * $product->getWidth() * $product->getHeight()); /** Set volume */
		}
		else
		{
		    $_items[$i]['dimensions'] = false;				 /** Dimensions false */
		    $_items[$i]['length'] = null;
		    $_items[$i]['width'] = null;
		    $_items[$i]['height'] = null;
		    $_items[$i]['volume'] = null;
		}

		$qty--; /** Decrement item quantity */
		$i++;   /** Increment master item counter */
	    }
        }

	/** If no items are found, return false */
	if (!isset($_items) || !is_array($_items)) { return false; }

        /** Set items array, sorted by protuct id */
        foreach ($_items as $item)
        {
            $quantity_count[$item['product_id']][] = $item;
        }

	/** Sort items by weight */
	usort($quantity_count, array($this, '_sortByWeight'));

        /** Parse separated items array, return $_items */
        unset($_items);

        foreach ($quantity_count as $items)
        {
            foreach ($items as $item)
            {
                $_items[] = $item;
            }
        }

	/** Return items array */
	return $_items;
    }


    /**
     * Sort by weight compare function
     *
     * @param array $a
     * @param array $b
     * @return array
     */
    protected function _sortByWeight($a, $b)
    {
	$a_weight = (is_array($a) && isset($a['weight'])) ? $a['weight'] : 0;
	$b_weight = (is_array($b) && isset($b['weight'])) ? $b['weight'] : 0;

	if ($a_weight == $b_weight) { return 0; }

	return ($a_weight > $b_weight) ? -1 : 1;
    }
}