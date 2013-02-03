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
 * Index controller
 */
class IllApps_Shipsync_IndexController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Index action
     */
    public function indexAction()
    {
        /** Load layout */
	$this->loadLayout();

        /** Set active menu */
	$this->_setActiveMenu('sales');

        /** Add breadcrumbs */
	$this->_addBreadcrumb($this->__('Sales'), $this->__('Sales'));
        $this->_addBreadcrumb($this->__('Orders'), $this->__('Orders'));
        $this->_addBreadcrumb($this->__('ShipSync'), $this->__('ShipSync'));

	/** Render layout */
        $this->renderLayout();
    }

    public function optionsAction()
    {
        $post = $this->getRequest()->getPost();

        $this->loadLayout();

	$layout = $this->getLayout();
	
	$block = $layout->getBlock('shipsync_options');
	
	$block->setData('i', $post['i']);
	$block->setData('z', $post['i']);
	
        echo $block->toHtml();
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
        $post = $this->getRequest()->getPost();

        $defaultPackages = Mage::getModel('usa/shipping_carrier_fedex')->getDefaultPackages();
        
        foreach($post['packages'] as $num => $package) { $type = $package['type']; }

        foreach($defaultPackages as $defaultPackage)
        {
            if($type == $defaultPackage['title'])
            {
                
                foreach($defaultPackage as $key => $element)
                {
                    $key = 'packages_'.$num.'_'.$key;
                    $returnArray[$key] = $element;
                }
                $returnArray['packages_'.$num.'_defaultweight'] = $returnArray['packages_'.$num.'_weight'];
                unset($returnArray['packages_'.$num.'_weight']);
                
                echo json_encode($returnArray);
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
	$post = $this->getRequest()->getPost();

        $fedex = Mage::getModel('usa/shipping_carrier_fedex');

	$request = Mage::getModel('shipping/rate_request');

	$order = Mage::getModel('sales/order')->loadByIncrementId($post['order_id']);

	$streets[] = $post['recipient_street1'];

	if ($post['recipient_street2'] != '') { $streets[] = $post['recipient_street2']; }
	if ($post['recipient_street3'] != '') { $streets[] = $post['recipient_street3']; }

        $request->setAllItems($order->getAllItems());
	$request->setOrigCountry('US');
	$request->setDestStreet($streets);
	$request->setDestCity($post['recipient_city']);
	$request->setDestPostcode($post['recipient_postcode']);
	$request->setDestRegionCode($post['recipient_region']);
	$request->setDestCountryId($post['recipient_country']);

	$packageWeight = 0;

	/** Iterate through packages */
        foreach ($post['packages'] as $package)
        {
	    /** If package items are not empty */
            if (isset($package['items']))
            {
		$packageWeight += $package['weight'];
	    }
	}

	$request->setPackageWeight($packageWeight);
	$request->setPackages($post['packages']);	
        
        $result = $fedex->collectRates($request);

        $possibleMethods = $result->asArray();

        $methods = $possibleMethods['fedex']['methods'];

        if($result->getError())
        {
            unset($post);
            $post['error'] = $result->getRateById(0)->getErrorMessage();
        }
        else
        {
            if(isset($post['method']))
            {
                $arrResult = $result->asArray();
                $post['shipping_amount'] = $arrResult['fedex']['methods'][$post['method']]['price_formatted'];
            }
            else
            {
                unset($post);
                $post['error'] = 'Invalid Request';
            }
 
        $encoded = json_encode($post);

        echo $encoded;
        }
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
        if (empty($post)) { Mage::throwException($this->__('Invalid form data.')); }
        
        /** Load order model */
        $order = Mage::getModel('sales/order')->loadByIncrementId($post['order_id']);

        /** If orderEntityId is null, throw error */
        if ($order->getEntityId() == null)
        {
	    /** Set error message */
            Mage::getSingleton('adminhtml/session')->addError("Error: Invalid Order ID");

	    /** Redirect */
	    $this->_redirectReferer();

            return $this;
        }

        /** If order is not shippable */
        if (!$order->canShip())
        {
	    /** Set error message */
            Mage::getSingleton('adminhtml/session')->addError("Error: Order unable to be shipped");

	    /** Redirect */
	    $this->_redirectReferer();

            return $this;
        }	               

	$i=0;

	/** Iterate through packages */
        foreach ($post['packages'] as $package)
        {
            $package['dangerous']    = false;
            $package['cod']	     = false;
	    $package['cod_amount']   = null;
            $package['confirmation'] = false;

	    if (isset($post['cod']) && ($post['cod'] == 'on'))
            {
                $package['cod']	       = true;
		$package['cod_amount'] = $post['cod_amount'];
	    }

            if (isset($post['confirmation']) && ($post['confirmation'] == 'on'))
            {
                $package['confirmation'] = true;
            }

	    /***  Get package items */
	    $package_items = explode(",", $package['items']); unset($package['items']);

	    /** Iterate through package items */
            foreach ($package_items as $key=>$value)
            {
		foreach ($post['items'] as $item)
		{
		    if ($item['id'] == $value)
		    {
			$package['items'][] = $item;

                        if (isset($item['dangerous']) && $item['dangerous'])
                        {
                            $package['dangerous'] = true;
                        }
		    }
		}
	    }

	    /** If package items are not empty */
            if (isset($package['items']))
            {
		/** Set package object */
                $_package = Mage::getModel('shipping/shipment_package')
                    ->setPackageNumber($i)
                    ->setItems($package['items'])
                    ->setCod($package['cod'])
		    ->setCodAmount($package['cod_amount'])
                    ->setConfirmation($package['confirmation'])
                    ->setDangerous($package['dangerous'])
                    ->setWeight($package['weight'])
                    ->setDescription('Package ' . $i+1 . ' for order id ' . $post['order_id'])
                    ->setContainerCode(Mage::helper('shipsync/packages')->isFedexPackage($package['type']) ? $package['type'] : 'YOUR_PACKAGING')
                    ->setContainerDescription('')
                    ->setWeightUnitCode($post['weight_units'])
                    ->setDimensionUnitCode($post['dimension_units'])
                    ->setHeight($package['height'])
                    ->setWidth($package['width'])
                    ->setLength($package['length']);

		    /** Add package object to packages array */
                    $packages[] = $_package;		    
            }
	    /** If package is empty, throw error */
            else
            {
                /** Set error message */
                Mage::getSingleton('adminhtml/session')->addError("Error: Please include all ordered items when creating shipments.");
                
		/** Redirect */
		$this->_redirectReferer();

		return $this;
            }

            $i++; /** Increment package counter */
        }

        /** Set carrier */
	$carrier = Mage::getModel('usa/shipping_carrier_fedex');

	$order->setShippingDescription("Federal Express - " . $carrier->getCode('method', $post['method']));
	$order->setShippingMethod("fedex_" . $post['method'])->save();

	$recipientAddress = new Varien_Object();

	$streets[] = $post['recipient_street1'];

	if ($post['recipient_street2'] != '') { $streets[] = $post['recipient_street2']; }
	if ($post['recipient_street3'] != '') { $streets[] = $post['recipient_street3']; }

	$recipientAddress->setName($post['recipient_name']);
	$recipientAddress->setCompany($post['recipient_company']);
	$recipientAddress->setStreet($streets);
	$recipientAddress->setCity($post['recipient_city']);		
	$recipientAddress->setRegionCode($post['recipient_region']);	
	$recipientAddress->setPostcode($post['recipient_postcode']);	
	$recipientAddress->setCountryId($post['recipient_country']);	
	$recipientAddress->setTelephone($post['recipient_telephone']);

	if (isset($post['insure_shipment']) && ($post['insure_shipment'] == 'on'))
	     { $insureShipment = true; }
	else { $insureShipment = false; }

	if (isset($post['insure_amount']) && ($post['insure_amount'] != ""))
	     { $insureAmount = $post['insure_amount']; }
	else { $insureAmount = 0.0; }

	if (isset($post['require_signature']) && ($post['require_signature'] == 'on'))
	     { $requireSignature = true; }
	else { $requireSignature = false; }

	$request = new Varien_Object();

        $request->setOrderId($post['order_id'])
		->setMethodCode($post['method'])
		->setRecipientAddress($recipientAddress)
		->setPackages($packages)
		->setInsureShipment($insureShipment)
		->setInsureAmount($insureAmount)
		->setRequireSignature($requireSignature);

        try
        {
            $results = $carrier->createShipment($request);
        }
	/** Catch exception */
        catch (Exception $e)
        {
	    /** Set error message */
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());

	    /** Redirect */
	    $this->_redirectReferer();

	    return $this;
        }

	/** If results are empty */
        if (empty($results))
        {
            /** Set error message */
            Mage::getSingleton('adminhtml/session')->addError("Error: Empty API response");

	    /** Redirect */
	    $this->_redirectReferer();

	    return $this;
        }
       
        $tracks = array();
        $packageids = '';

        /** Iterate through results */
	$i=0; foreach ($results as $res)
        {
            /** Set tracking number */
	    $tracks[$i]['number'] = $res->getTrackingNumber();

	    /** Set label URL */
            $tracks[$i]['url'] = '<a target="shipping_label" href="' . Mage::getSingleton('adminhtml/url')->getUrl('shipsync/index/label/', 
		    array('id' => $res->getPackageId())) . '">Print Shipping Label</a>';

	    /** Set package id */
            $tracks[$i]['id'] = $res->getPackageId();

            $i++;
        }

	/** Set success message */
        $message = '<p>SUCCESS: ' . $i . ' shipment(s) created</p>';

	/** Iterate through tracking #s */
        foreach ($tracks as $track)
        {
            /** Set tracking message */
	    $message .= "<p>Package ID: " . $track['id'] . "<br /> Tracking Number: " . $track['number'] . "<br />" . $track['url'] . "</p>";
        }        

	/** Add success message */
        Mage::getSingleton('adminhtml/session')->addSuccess($message);

        /** Get order url */
	$url = $this->getUrl('adminhtml/sales_order/view', array('order_id'=> $order->getId()));

        /** Redirect */
        $this->_redirectUrl($url);

        return $this;
    }

    

    /**
     * Show label
     */
    public function labelAction()
    {
	/** Get package id */
        $pkgid = $this->getRequest()->getParam('id');

	/** If package id is empty */
        if (empty($pkgid))
        {
	    /** Echo message and exit TODO: add more stylish method of dealing with this */
            echo "Invalid package id";
            exit();
        }

        /** Get package */
        $pkg = Mage::getModel('shipping/shipment_package')->load($pkgid);	

	/** Get image label format */
	$imgtype = strtolower($pkg->getLabelFormat());

	/** Get label image */
	$img = $pkg->getLabelImage();

        $this->labelPrint($imgtype, $img, $pkg);

    }

    
    /**
     * Show COD Label if present
     */
    public function codlabelAction()
    {
        /** Get package id */
        $pkgid = $this->getRequest()->getParam('id');

	/** If package id is empty */
        if (empty($pkgid))
        {
	    /** Echo message and exit TODO: add more stylish method of dealing with this */
            echo "Invalid package id";
            exit();
        }

        /** Get package */
        $pkg = Mage::getModel('shipping/shipment_package')->load($pkgid);

	/** Get image label format */
	$imgtype = strtolower($pkg->getLabelFormat());

        $img = $pkg->getCodLabelImage();
        
        if ($pkg->getCodLabelImage())
        {            
            $this->labelPrint($imgtype, $img, $pkg, 'cod_label');
        }
    }

    
    public function labelPrint($imgtype, $img, $pkg, $type = 'label')
    {
        $filename = 'FEDEX_';

	if ($type == 'cod_label')
	{
	    $filename = 'FEDEX_COD_';
	}

	/** If imgtype or img is empty */
        if (empty($imgtype) || empty($img))
        {
        /** Echo message and exit */
            echo "Invalid package id";
            exit();
        }

	/** If image type is thermal */
	if ($imgtype == 'epl2' || $imgtype == 'dpl' || $imgtype == 'zplii')
	{
	    /** Set jzebra code */
	    $jzebra_code =
		'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		    <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
		    <head>
			<script>
			  function print() {
			     var applet = document.jZebra;
			     if (applet != null) {
				applet.append64("'. $img . '");
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
				<applet name="jZebra" code="jzebra.RawPrintApplet.class" archive="' . Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) .'java/jZebra/jzebra.jar" width="0" height="0">
				    <param name="sleep" value="200">
				    <param name="printer" value="'. Mage::getStoreConfig('carriers/fedex/printer_name') .'">
				</applet>
				<input type="button" onClick="print()" value="Print">
			    </div>
			</form>
		    </body>
		</html>';
	}

        switch($imgtype)
        {
	    /** PNG image */
            case "png" :
		header('Content-type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $filename . $pkg->getTrackingNumber() . '.png"');
                echo base64_decode($img);
                break;

	    /** PDF document */
            case "pdf" :
                header('Content-type: application/pdf');
		header('Content-Disposition: attachment; filename="' . $filename . $pkg->getTrackingNumber() . '.pdf"');
                echo base64_decode($img);
                break;

	    /** EPL2 thermal */
            case "epl2" :

		/** If java printing is enabled */
                if (Mage::getStoreConfig('carriers/fedex/enable_java_printing'))
                {
		    /** Echo jZebra code */
                    echo $jzebra_code;
                    break;
                }
                else
                {
                    header('Content-type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . $filename . $pkg->getTrackingNumber() . '.epl"');
                    echo base64_decode($img);
                    break;
                }

	    /** DPL thermal */
            case "dpl" :
                header('Content-type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $filename . $pkg->getTrackingNumber() . '.dpl"');
                echo base64_decode($img);
                break;

	    /** ZPLII thermal */
            case "zplii" :

		/** If java printing is enabled */
                if (Mage::getStoreConfig('carriers/fedex/enable_java_printing'))
                {
		    /** Echo jZebra code */
                    echo $jzebra_code;
                    break;
                }
                else
                {
                    header('Content-type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . $filename . $pkg->getTrackingNumber() . '.zpl"');
                    echo base64_decode($img);
                    break;
                }
            }
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
