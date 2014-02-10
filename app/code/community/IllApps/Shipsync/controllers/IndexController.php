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
 * IllApps_Shipsync_IndexController
 */
class IllApps_Shipsync_IndexController extends Mage_Adminhtml_Controller_Action
{
    
    
    /**
     * Index action
     */
    public function indexAction()
    {
        $this->loadLayout();
        
        $this->_setActiveMenu('sales');
        
        $this->_addBreadcrumb($this->__('Sales'), $this->__('Sales'));
        $this->_addBreadcrumb($this->__('Orders'), $this->__('Orders'));
        $this->_addBreadcrumb($this->__('ShipSync'), $this->__('ShipSync'));
        
        $this->renderLayout();
    }
    
    
    /**
     * optionAction
     */
    public function optionAction()
    {
        // Get post request
        $post = $this->getRequest()->getPost();
        
        // Load layout
        $this->loadLayout();
        
        $layout = $this->getLayout();
        
        // Get shipsync option block
        $block = $layout->getBlock('shipsync_option');
        
        $block->setData('i', $post['i']);
        $block->setData('z', $post['i']);
        
        echo $block->toHtml();
    }
    
    /**
     * optionAction
     */
    public function packagesAction()
    {
        $id = $this->getRequest()->getParam('shipment_id');
        
        Mage::register('current_shipment', Mage::getModel('sales/order_shipment')->load($id));
        
        $this->loadLayout();
        
        Mage::getSingleton('shipsync/sales_order_shipment_package')->setShipmentId($id);
        
        $this->renderLayout();
    }
    
    
    /**
     * Attributes Action
     * 
     * Parses ajax call in the event that a package is changed in Actual Shipment Request.
     * Returns json object containing the relevant information.
     * 
     * @return json_object
     */
    public function attributesAction()
    {
        // Get post request
        $post = $this->getRequest()->getPost();
        
        // Get default packages
        $defaultPackages = Mage::getModel('shipsync/shipping_package')->getDefaultPackages(array(
            'fedex'
        ));
        
        foreach ($post['packages'] as $num => $package) {
            foreach ($defaultPackages as $defaultPackage) {
                if ($package['value'] == $defaultPackage['value']) {
                    foreach ($defaultPackage as $key => $element) {
                        $key = 'packages_' . $num . '_' . $key;
                        
                        $returnArray[$key] = $element;
                    }
                    
                    echo json_encode($returnArray);
                }
            }
        }
    }
    
    
    
    /**
     * Rate Action
     *
     * Ajax backend action, handling an ad hoc rate request in shipment creation.
     *
     * @return json_object
     */
    public function rateAction()
    {
        // Get post data
        $post = $this->getRequest()->getPost();
        
        // Get fedex model
        $fedex = Mage::getModel('usa/shipping_carrier_fedex');
        
        // Get rate request model
        $rateRequest = Mage::getModel('shipping/rate_request');
        
        // Get order model
        $order = Mage::getModel('sales/order')->loadByIncrementId($post['order_id']);
        
        // Set order model
        $rateRequest->setOrder($order);
        
        // Set request data
        $rateRequest->setAllItems($order->getAllItems());
        
        // Streets
        $streets[] = $post['recipient_street1'];
        
        if ($post['recipient_street2'] != '') {
            $streets[] = $post['recipient_street2'];
        }
        if ($post['recipient_street3'] != '') {
            $streets[] = $post['recipient_street3'];
        }
        
        // Set destination data
        $rateRequest->setDestStreet($streets);
        $rateRequest->setDestCity($post['recipient_city']);
        $rateRequest->setDestPostcode($post['recipient_postcode']);
        $rateRequest->setDestRegionCode($post['recipient_region']);
        $rateRequest->setDestCountryId($post['recipient_country']);
        
        // Set insure shipment
        if (isset($post['insure_shipment']) && ($post['insure_shipment'] == 'on')) {
            $rateRequest->setInsureShipment(true)->setInsureAmount($post['insure_amount']);
        } else {
            $rateRequest->setInsureShipment(false);
        }
        
        // Get all items to ship
        $items = Mage::getModel('shipsync/shipping_package')->getParsedItems($rateRequest->getAllItems(), true);
        
        // Sort items by sku, removing duplicates
        foreach ($items as $item) {
            $_itemsBySku[$item['sku']] = $item;
        }
        
        // Iterate packages for rating, and loads items details from getParsedItems based on sku
        foreach ($post['packages'] as $package) {
            $error = false;
            
            unset($_items);
            
            $itemsToPack = explode(',', $package['items']);
            
            foreach ($itemsToPack as $key => $itemToPack) {
                $_items[] = $_itemsBySku[$post['items'][$itemToPack - 1]['sku']];
                if ($_items[$key]['alt_origin'] != $package['altOrigin']) {
                    $error = true;
                }
            }
            
            if ($error) {
                unset($post);
                $post['error'] = 'Some items do not match package origin';
                
                $encoded = json_encode($post);
                
                $this->getResponse()->setBody($encoded);
                
                return false;
            }
            
            $_packages[] = array(
                'volume' => $package['width'] * $package['height'] * $package['length'],
                'value' => $package['value'],
                'label' => $package['value'],
                'items' => $_items,
                'weight' => $package['weight'],
                'length' => $package['length'],
                'width' => $package['width'],
                'height' => $package['height'],
                'alt_origin' => $package['altOrigin']
                
            );
        }
        
        $rateRequest->setPackages($_packages);
        
        $rateResult = Mage::getModel('shipsync/shipping_carrier_fedex_rate')->collectRates($rateRequest);
        
        $possibleMethods = $rateResult->asArray();
        
        $methods = $possibleMethods['fedex']['methods'];
        
        if ($rateResult->getError()) {
            unset($post);
            
            $post['error'] = $rateResult->getRateById(0)->getErrorMessage();
        } else {
            if (isset($post['method'])) {
                $arrResult = $rateResult->asArray();
                
                if (isset($arrResult['fedex']['methods'][$post['method']]['price_formatted'])) {
                    $post['shipping_amount'] = $arrResult['fedex']['methods'][$post['method']]['price_formatted'];
                } else {
                    $post['shipping_amount']          = null;
                    $post['shipping_allowed_methods'] = $arrResult;
                }
            } else {
                unset($post);
                $post['error'] = 'Invalid Request';
            }
        }
        $encoded = json_encode($post);
        
        $this->getResponse()->setBody($encoded);
    }
    
    
    /**
     * Post action
     *
     * @return IllApps_Shipsync_IndexController
     */
    public function postAction()
    {
        $message = "";
        
        /** Retrieve post data */
        $post = $this->getRequest()->getPost();
        
        /** Throw exception if post data is empty */
        if (empty($post)) {
            Mage::throwException($this->__('Invalid form data.'));
        }
        
        /** Load order model */
        $order = Mage::getModel('sales/order')->loadByIncrementId($post['order_id']);
        
        /** If orderEntityId is null, throw error */
        if ($order->getEntityId() == null) {
            /** Set error message */
            Mage::getSingleton('adminhtml/session')->addError("Error: Invalid Order ID");
            
            /** Redirect */
            $this->_redirectReferer();
            
            return $this;
        }
        
        /** If order is not shippable */
        if (!$order->canShip()) {
            /** Set error message */
            Mage::getSingleton('adminhtml/session')->addError("Error: Order unable to be shipped");
            
            /** Redirect */
            $this->_redirectReferer();
            
            return $this;
        }
        
        $i = 0;
        
        
        // Get all items to ship
        $items = Mage::getModel('shipsync/shipping_package')->getParsedItems($order->getAllItems(), true);
        
        // Sort items by sku, removing duplicates
        foreach ($items as $item) {
            $_itemsById[$item['id']] = $item;
        }
        
        /** Iterate through packages */
        foreach ($post['packages'] as $package) {
            unset($_items);
            
            $itemsToPack = explode(',', $package['items']);
            
            $e_origin = false;
            
            foreach ($itemsToPack as $key => $itemToPack) {
                $_items[] = $_itemsById[$post['items'][$itemToPack - 1]['item_id']];
                if ($_items[$key]['alt_origin'] != $package['altOrigin']) {
                    $e_origin = true;
                }
            }
            
            if ($e_origin) {
                /** Set error message */
                Mage::getSingleton('adminhtml/session')->addError("Some items do not match package origin");
                
                /** Redirect */
                $this->_redirectReferer();
                
                return $this;
            }
            
            $package['dangerous']    = false;
            $package['cod']          = false;
            $package['cod_amount']   = null;
            $package['confirmation'] = false;
            $package['saturday']     = false;
            
            
            if (isset($post['cod']) && ($post['cod'] == 'on')) {
                $package['cod']        = true;
                $package['cod_amount'] = $post['cod_amount'];
            }
            
            if (isset($post['confirmation']) && ($post['confirmation'] == 'on')) {
                $package['confirmation'] = true;
            }
            
            if (isset($post['saturday']) && $post['saturday'] == 'on') {
                $package['saturday'] = true;
            }
            
            $itemIds          = $package['items'];
            $package['items'] = $_items;
            
            foreach ($package['items'] as $items) {
                if (isset($item['dangerous']) && $item['dangerous']) {
                    $package['dangerous'] = true;
                }
            }
            
            /** If package items are not empty */
            if (isset($package['items'])) {
                /** Set package object */
                $_package = Mage::getModel('shipping/shipment_package')->setPackageNumber($i)->setItems($package['items'])->setCod($package['cod'])->setCodAmount($package['cod_amount'])->setConfirmation($package['confirmation'])->setSaturdayDelivery($package['saturday'])->setDangerous($package['dangerous'])->setWeight($package['weight'])->setDescription('Package ' . $i + 1 . ' for order id ' . $post['order_id'])->setContainerCode(Mage::getModel('usa/shipping_carrier_fedex_package')->isFedexPackage($package['value']) ? $package['value'] : 'YOUR_PACKAGING')->setContainerDescription('')->setWeightUnitCode($post['weight_units'])->setDimensionUnitCode($post['dimension_units'])->setHeight($package['height'])->setWidth($package['width'])->setLength($package['length'])->setOrigin($package['altOrigin'])->setIsChild($package['isChild'])->setPackageItemIds($itemIds);
                ;
                
                /** Add package object to packages array */
                $packages[] = $_package;
            }
            /** If package is empty, throw error */
            else {
                /** Set error message */
                Mage::getSingleton('adminhtml/session')->addError("Error: Please include all ordered items when creating shipments.");
                
                /** Redirect */
                $this->_redirectReferer();
                
                return $this;
            }
            
            $i++;
            /** Increment package counter */
        }
        
        /** Set carrier */
        $carrier = Mage::getModel('usa/shipping_carrier_fedex');
        
        $order->setShippingDescription("Federal Express - " . $carrier->getCode('method', $post['method']));
        $order->setShippingMethod("fedex_" . $post['method'])->save();
        
        $recipientAddress = new Varien_Object();
        
        $streets[] = $post['recipient_street1'];
        
        if ($post['recipient_street2'] != '') {
            $streets[] = $post['recipient_street2'];
        }
        if ($post['recipient_street3'] != '') {
            $streets[] = $post['recipient_street3'];
        }
        
        $recipientAddress->setName($post['recipient_name']);
        $recipientAddress->setCompany($post['recipient_company']);
        $recipientAddress->setStreet($streets);
        $recipientAddress->setCity($post['recipient_city']);
        $recipientAddress->setRegionCode($post['recipient_region']);
        $recipientAddress->setPostcode($post['recipient_postcode']);
        $recipientAddress->setCountryId($post['recipient_country']);
        $recipientAddress->setTelephone($post['recipient_telephone']);
        
        if (isset($post['insure_shipment']) && ($post['insure_shipment'] == 'on')) {
            $insureShipment = true;
        } else {
            $insureShipment = false;
        }
        
        if (isset($post['insure_amount']) && ($post['insure_amount'] != "")) {
            $insureAmount = $post['insure_amount'];
        } else {
            $insureAmount = 0.0;
        }
        
        if (isset($post['require_signature']) && ($post['require_signature'] == 'on')) {
            $requireSignature = true;
        } else {
            $requireSignature = false;
        }
        
        $request = new Varien_Object();
        
        $request->setOrderId($post['order_id'])->setMethodCode($post['method'])->setRecipientAddress($recipientAddress)->setPackages($packages)->setInsureShipment($insureShipment)->setInsureAmount($insureAmount)->setRequireSignature($requireSignature)->setSaturdayDelivery($package['saturday'])->setCod($package['cod']);
        
        try {
            $results = $carrier->createShipment($request);
        }
        /** Catch exception */
        catch (Exception $e) {
            /** Set error message */
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            
            /** Redirect */
            $this->_redirectReferer();
            
            return $this;
        }
        
        /** If results are empty */
        if (empty($results)) {
            /** Set error message */
            Mage::getSingleton('adminhtml/session')->addError("Error: Empty API response");
            
            /** Redirect */
            $this->_redirectReferer();
            
            return $this;
        }
        
        $tracks     = array();
        $packageids = '';
        
        /** Iterate through results */
        $i = 0;
        foreach ($results as $res) {
            /** Set tracking number */
            $tracks[$i]['number'] = $res->getTrackingNumber();
            
            /** Set label URL */
            $tracks[$i]['url'] = '<a target="shipping_label" href="' . Mage::getSingleton('adminhtml/url')->getUrl('shipsync/index/label/', array(
                'id' => $res->getPackageId()
            )) . '">Print Shipping Label</a>';
            
            /** Set package id */
            $tracks[$i]['id'] = $res->getPackageId();
            
            $i++;
        }
        
        /** Set success message */
        $message = '<p>SUCCESS: ' . $i . ' shipment(s) created</p>';
        
        /** Iterate through tracking #s */
        foreach ($tracks as $track) {
            /** Set tracking message */
            $message .= "<p>Package ID: " . $track['id'] . "<br /> Tracking Number: " . $track['number'] . "<br />" . $track['url'] . "</p>";
        }
        
        /** Add success message */
        Mage::getSingleton('adminhtml/session')->addSuccess($message);
        
        /** Get order url */
        $url = $this->getUrl('adminhtml/sales_order/view', array(
            'order_id' => $order->getId()
        ));
        
        /** Redirect */
        $this->_redirectUrl($url);
        
        return $this;
    }
    
    
    
    /**
     * Show label
     */
    public function labelAction()
    {
        if ($packageId = $this->getRequest()->getParam('id')) {
            $package = Mage::getModel('shipping/shipment_package')->load($packageId);
            
            $carrier = strtoupper($package->getCarrier());
            
            $labelFormat = strtolower($package->getLabelFormat());
            $labelImage  = $package->getLabelImage();
            
            $this->labelPrint($labelImage, $labelFormat, $carrier, $package);
        }
    }
    
    
    /**
     * Show COD Label if present
     */
    public function codlabelAction()
    {
        if ($packageId = $this->getRequest()->getParam('id')) {
            $package = Mage::getModel('shipping/shipment_package')->load($packageId);
            $carrier = strtoupper($package->getCarrier());
            
            $labelFormat = strtolower($package->getLabelFormat());
            $labelImage  = $package->getCodLabelImage();
            
            $this->labelPrint($labelImage, $labelFormat, $carrier . '_COD_', $package);
        }
    }
    
    
    
    public function labelPrint($labelImage, $labelFormat, $carrier, $package)
    {
        switch ($labelFormat) {
            case 'pdf':
                
                $labelImage = mb_convert_encoding($labelImage, 'UTF-8', 'BASE64');
                $labelPath  = $carrier . $package->getTrackingNumber() . '.' . substr($labelFormat, 0, 3);
                
                $this->getResponse()->setHeader('Content-type', 'application/pdf', true)->setHeader('Content-Disposition', 'inline' . '; filename="' . $labelPath . '"')->setBody($labelImage);
                
                break;
            
            case 'png':
                
                $labelImage = mb_convert_encoding($labelImage, 'UTF-8', 'BASE64');
                $labelPath  = $carrier . '_COD_' . $package->getTrackingNumber() . '.' . substr($labelFormat, 0, 3);
                $this->getResponse()->setHeader('Content-type', 'application/octet-stream', true)->setHeader('Content-Disposition', 'inline' . '; filename="' . $labelPath . '"')->setBody($labelImage);
                
                break;
            
            
            default:
                $this->thermalPrint($labelImage, $labelFormat);
                break;
        }
    }
    
    
    public function thermalPrint($labelImage, $labelFormat)
    {
        $javaUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . 'java/jZebra/jzebra.jar';
        
        $printerName = Mage::getStoreConfig('carriers/fedex/printer_name');
        
        $htmlBody = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
            <head>
                <script>
                  function print() {
                     var applet = document.jZebra;
                     if (applet != null) {
                        applet.append64("' . $labelImage . '");
                        applet.print();
                        while (!applet.isDonePrinting()) {
                            // Wait
                        }
                        var e = applet.getException();
                        alert(e == null ? "Printed Successfully" : "Printing Failed");
                     }
                     else {
                        alert("Applet not loaded!");
                     }
                  }
                  function chr(i) {
                     return String.fromCharCode(i);
                  }
                </script>
            </head>
            <body style="background-color: #ccc; padding: 20px;"><form>
                <p><strong>ShipSync Thermal Printing</strong></p>
                    <div style="border: 1px #000 solid; padding: 10px; background-color: #fff; margin: 20px; ">
                        <applet name="jZebra" code="jzebra.RawPrintApplet.class" archive="' . $javaUrl . '" width="0" height="0">
                            <param name="sleep" value="200">
                            <param name="printer" value="' . $printerName . '">
                        </applet>
                        <input type="button" onClick="print()" value="Print">
                    </div>
                </form>
            </body>
        </html>';
        
        $this->getResponse()->setBody($htmlBody);
        
    }
    
    
    
    
    /**
     * _isAllowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/ship');
    }
}
