ShipSync Community 5
====================
FedEx Web Services Integration for Magento
------------------------------------------

ShipSync is an extension that seeks to improve support for FedEx, providing accurate rate requests, address validation, shipment creation (from within Magento's admin panel), and label generation in a variety of formats (PDF, PNG, Thermal, etc.). This extension was born out of necessity, saving hours by eliminating the need to copy/paste addresses, tracking codes, and labels. Instead of swiveling between Magento and FedEx’s online site (or shipping manager), you can now perform the entire process from Magento’s admin panel.

Magento Requirements

  * Magento Community v1.8+
  * Magento Enterprise v1.6+

Supported Systems

  * Linux x86, x86-64 
  * Windows (with Apache, PHP, MySQL) 

Supported Web Servers

  * Apache 1.3.x 
  * Apache 2.0.x 
  * Apache 2.2.x 

PHP Requirements

  * 5.2.13+ 
  * OpenSSL 
  * PHP-SOAP 

MySQL Requirements

  * 4.1.20 or newer 
  * InnoDB storage engine 

Installation

  1. Upload to the root of your Magento installation (ex, "/home/yourdomain/magento/") and extract (it's important that it be extracted from the Magento root so that the correct directory structure is created. No files are overwritten by this app) 
  2. Login to the Magento admin panel 
  3. Navigate to "System-&gt;Cache Management" and refresh your cache 
  4. Navigate to "System-&gt;Configuration-&gt;Sales-&gt;Shipping Methods" and enter your FedEx "Test Account Number", "Test Password", "Test Key", and "Test Meter." Make sure "Test Mode" is set to "Yes"
  5. Navigate to "System-&gt;Configuration-&gt;Sales-&gt;Shipping Settings-&gt;Origin" and fill out your company's Country, Region/State, ZIP/Postal Code, City, Address, and Phone. Note: these fields are required for FedEx's API to work. 

Uninstallation

  * Remove '/app/code/community/IllApps/' (delete the entire directory) 
  * Remove '/app/etc/modules/IllApps_Shipsync.xml' 
  * Clear the Magento cache

FedEx Configuration

  * System-&gt;Configuration-&gt;Shipping Methods-&gt;FedEx

General

  * Enabled : Enable or disable the FedEx module 
  * Title : This is the title of the shipping method (visible to customers on the front-end) 
  * Dimension Units : Select "Inches" or "Centimeters." Note: product dimensions must be defined using selected dimension units
  * Weight Units : Select "Pounds" or "Kilograms." Note: product weight must be defined using the selected dimension units 
  * Max Package Weight : The maximum package weight allowed in your store (anything over 150lbs will be shipped as FedEx Freight) 

Credentials

  * Mode Enable : Set to "Yes" for testing, and "No" for production mode. "Test Mode" allows testing of all API features, such as rate requests, shipment creation, address validation (if enabled by FedEx), and label printing. Labels printed in "Test Mode" may not be used for actual shipments. Tracking is not available in "Test Mode"
  * Mode Account Number, Password, Key, and Meter : These are the FedEx developer credentials received from FedEx. These fields must be filled out for "Test Mode" to work properly. 
  * Production Account Number, Password, Key, and Meter : Once you have tested the FedEx module and have received production credentials, fill out these fields and set "Test Mode" to "No"
  * Account Country: Select account holder's country

Rating

  * Rating Rate Request Type : LIST rates retrieve FedEx's list rates. ACCOUNT rates retrieve account-specific rates (including any applicable discounts) 
  * Rating Enable Dimensions : Send dimensions when making rate requests: If height, width, and length are defined for your store's products, then select "Yes" to use these dimensions when making rate requests. Adding dimensions makes for much more accurate rate requests, so it is recommended to set this to "Yes."
  * Rating Show Delivery Date : Shows time in transit and estimated delivery date when making rate requests 
  * Rating Subtract VAT Percentage : Subtracts percentage off the returned rate request. It was implemented with VAT taxes in mind, but could be used for other scenarios. 
  * Rating Handling Fee/Percent : Set this field to a fixed dollar amount or percentage. Leave empty to disable handling fees
  * Rating Calculate Handling : Fixed Calculates handling by adding a fixed amount to each item or package. Percent calculates handling using percentage of each order or package's price
  * Rating Logarithmic Handling : Set to "Yes" if you would like handling fees to scale down logarithmically. For example: to match closely match FedEx's retail rates, set "Rate Request Type" to "List Rates", "Handling Fee" to "25", "Calculate Handling Fee" to "Percent", and this setting to "Yes."
  * Rating Handling Applied : Per Order applies handling per order, and Per Package applies handling per package 
  * Rating Residential Delivery : Enables residential surcharge for all addresses, and forces usage of the 'Home Delivery' method for 'Ground' shipments. This setting will be overridden if "Address Validation" is enabled. 
  * Rating Saturday Delivery : This enables FedEx's Saturday Delivery special service when making rate requests. Rates will only be returned for available and applicable services 

Shipping Configuration

  * Shipping Account Country : Select account country 
  * Shipping FedEx Dropoff : Select the appropriate FedEx dropoff method (see your FedEx representative for more details) 
  * Shipping Allowed Methods : Select allowed shipping methods (see your FedEx representative for more details) 
  * Shipping Enable Free Shipping : Enable free shipping 
  * Shipping Free Shipping Method : Select the free shipping method 
  * Shipping Minimum order amount for Free Shipping : Minimum order amount to qualify for free shipping 
  * Shipping Calculate Free Shipping before discounts : Calculate free shipping before or after discounts 
  * Shipping Filter PO Boxes : Filter PO boxes (customer will receive an alert box instructing them to choose another address)

SmartPost

  * SmartPost Enable : Enable SmartPost support (must be enabled by your FedEx representative -- allows shipping via USPS with FedEx) 
  * SmartPost Ancillary Endorsement
  * SmartPost Indicia Type : Select indicia type (Media Mail, Parcel Select, Presorted Bound Printer Matter, Presorted Standard) 
  * SmartPost Hub ID : Enter your SmartPost Hub ID 
  * SmartPost Customer Manifest ID : Enter your Customer Manifest ID (most customers will leave this blank) 

Printing

  * Printing Use Store Name for Shipper Company : Select this option to use store name instead of website name for shipper origin company when printing labels 
  * Printing Label Image Format : Select label format (Adobe PDF, PNG Image, Zebra EPL2, Datamax DPL) 
  * Printing Label Stock Type : Select label stock type. For thermal printers a STOCK method must be selected. International shippers should select a LABEL or DOC_TAB method. Changes to this setting will only apply to new shipments. 
  * Printing Label Orientation : Specify label orientation when printing 
  * Printing Enable jZebra : Enable jZebra support (allowing you to print directly to your thermal printer from a browser) 
  * Printing Thermal Printer Name : Enter the name of your thermal printer. (Note: this is not the network name, but the actual name of the printer. Example: 'zebra') 

Additional Options

  * Enable Address Validation : Enables address validation, which cleans addresses, corrects spelling errors, and determines residential status via FedEx's Address Validation service. This is not enabled by default for FedEx accounts, so you will need to ask your FedEx representative to turn this on for your account before using this feature. 
  * Enable API Cache : Improves speed of rate results using SOAP cache. Disable if you have trouble switching from Test Mode to Production Mode. 
  * Enable Firebug Debugging : Sends the FedEx Web Services Request and Response to the Firebug console. Do not enable on a live site. 
  * Show Method if Not Applicable : When this option is enabled FedEx will only be available to the customer if an applicable rate is returned 

Global Shipping Settings

  * System-&gt;Configuration-&gt;Shipping Settings 

Packages

  * Origin : Fill out your complete address. FedEx requires this for Web Services calls. 
  * ShipSync Packages : Specify your commonly used package types and ShipSync will attempt to dynamically pack these at time of purchase, providing extremely accurate rate results. 

Additional Product Attributes

ShipSync adds the following product attributes to Magento :

  * Height, Width, &amp; Length : Product dimensions 
  * Use Special Packaging? : This flag forces a product to be shipped in its own container using the dimensions defined for that product. 
  * Free Shipping : This is attribute allows you to assign free shipping to products using catalog and shopping cart rules. 
  * Dangerous Goods : Specify the product as dangerous goods 
  * Dangerous Goods Options : Dangerous goods options (Lithium Battery Exception, ORM-D, Reportable Quantities, Small Quantity Exception) 

Shipment Creation

  1. Select desired order ID 
  2. Click "Ship with ShipSync"
  3. The shipment data should be automatically filled out, but you may override ShipSync's estimation by filling out the following fields : . Shipping Method: Override shipping method here . Package Items: Enter desired items separated by comma (ex, "0,1,2"). Note: All 
  4. Click "Create shipment" and the request will be sent to FedEx. 
  5. Once the shipment has been created, you'll be redirected back to the order screen and will have the option to print the created shipments. Tracking numbers are automatically sent to the customer (if this is enabled in Magento's configuration). 

Notes: Labels can be reprinted by viewing individual shipments. Accounts are
not charged until shipment is picked up.

FedEx Test Environment

  * The FedEx Web Services Testing Environment is a functional, full run-time environment ideal for testing your Web Services solutions. 
  * The testing environment is intended for confirming functionality, however it should not be used for extreme stress testing, and doing so may result in a temporary IP ban. 
  * It is recommended that developers test and assure themselves that their code operates as desired before switching to Production Credentials

FedEx Test Credentials

  * Sign up for a FedEx Developer Account here : [http://fedex.com/us/developer](http://fedex.com/us/developer)
  * Once logged in, acquire Test Credentials here : [https://www.fedex.com/wpor/web/jsp/drclinks.jsp?links=wss/develop.html](https://www.fedex.com/wpor/web/jsp/drclinks.jsp?links=wss/develop.html)
  * After filling out the required info, you'll be given a "Developer Test Key", along with a "Test Account Number", and "Test Meter." Your "Test Password" will be mailed to you within a few minutes. Save this information, as you will need it when configuring ShipSync. 

FedEx Production Environment

  * Once the developer has completed the design, implementation, and testing of their projects, they must certify their applications with FedEx. 
  * Certification is the process of determining if your implementation meets a number of requirements to achieve the safe, secure, and effective operation of your solution in the FedEx production environment. 
  * For a synopsis of the basic process, see the certification outline below, or proceed directly to the full certification guidelines: 

FedEx Production Credentials

Standard Services (Rating, Tracking)

  * Corporate Developers (ie, Site Owner/Developer, or FedEx Account Holder) may self-certify for Standard Services 
  * Production Credentials for Standard Services are provisioned automatically, and will be available for use immediately 
  * Acquire Production Credentials for Standard Services here : [https://www.fedex.com/wpor/web/jsp/drclinks.jsp?links=wss/production.html](https://www.fedex.com/wpor/web/jsp/drclinks.jsp?links=wss/production.html)

Advanced Services (Shipment Creation, Address Validation)

  * Requires FedEx intervention and possible label certification; Call the FedEx Web Services Help Desk and request initiation of certification. Instructions will be emailed to you within one (1) business day 
  * All Advanced services require FedEx approval. Additionally for the shipment transaction, test shipping labels are required to be submitted and evaluated by FedEx 
  * Provide a signed End User License Agreement (EULA). You can review a copy of EULA here. 
  * For the transactions that require label certification: Once label evaluation is successfully completed, your FedEx Web Services profile will be authorized for label generation. You can then apply for Production Credentials. 

FedEx Freight LTL

  * To use FedEx Freight LTL using Web Services, you will need a FedEx Express in addition to a FedEx Freight account within your application. 
  * If you do not have a FedEx Freight account established, please call 1-866-393-4585.

Certification for Third-Party Consultants

  * Consultants developing on behalf of a corporate customer must ensure that their client provides their account information and a signed End User License Agreement (EULA) to FedEx in order to obtain production credentials.

