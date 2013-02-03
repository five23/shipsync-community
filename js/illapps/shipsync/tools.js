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
 * Event.observe
 */
Event.observe(window, 'load', function(event)
{

    var price = $('shipping_amount_div').innerHTML;

    price = removeHTMLTags(price);

    $('shipping_amount_div').update(price);

    var i = 0;
    var j = i + 1;

    while($('product_row_' + i) != undefined && $('product_row_' + j) != undefined)
    {
	if($F('items_' + i + '_product_id') != $F('items_' + j + '_product_id')) { $('product_row_' + i).show(); }

	i++; j++;
    }
    
    if($('product_row_' + i) != undefined) { $('product_row_' + i).show(); }
            
    Event.stop(event);

});


/**
 * updateShipment
 */
function updateShipment(thisurl)
{
    var form = $('edit_form');
    
    new Ajax.Request
    (
	thisurl,
	{
	    parameters : $(form).serialize(true),
	    method : 'post',
	    insertion: 'top',
	    onSuccess : function(transport)
	    {
		var response = transport.responseText.evalJSON();
		parseMulti(response, new Array());
	    },
	    onFailure : function()
	    { alert('Invalid request'); }
	}
    )
}


/**
 * parseMulti
 */
function parseMulti(obj, path)
{
   //setAllowedMethods(obj['shipping_allowed_methods']['fedex']['methods']);

   for (n in obj)
    {
	v = obj[n]; t = typeof(v);

	if (n == 'error') { var e = removeHTMLTags(v); alert(e); break; }

	if (n == 'shipping_amount' && (v == '' || v == null)) 
        {
            alert('The shipping method you selected is not supported!');
            console.log(obj['shipping_allowed_methods']['fedex']['methods']);
            setAllowedMethods(obj['shipping_allowed_methods']['fedex']['methods']);
        }

	if (t == 'string' || t == 'number' || t == null)
	{
	    if (path.length > 1) { n = path[0] + "[" + path[1] + "][" + n + "]"; }

	    updateHTML(n, v);
	    updateValue(n, v);
	}
	else if (t == 'object')
	{
	    path.push(n);
	    parseMulti(v, path);
	    path.length = 0;
	}
    }
}


/**
 * updateHTML
 */
function updateHTML(id, content)
{
    var v = id;
    v = removeBrackets(v);
    v = v + "_div";
    if($(v) != undefined && content != '')
    {
	$(v).update(content);
    }
    return false;
}


/**
 * updateValue
 */
function updateValue(name, content)
{
    var v = name;
    v = removeBrackets(v);

    if($(v) != undefined && content != '')
    {
	$(v).setValue(content);
    }
    return false;
}


/**
 * removeBrackets
 */
function removeBrackets(str)
{
    str = str.replace(/[[]/g, '_');
    str = str.replace(/[\]]/g, '');
    return str;
}


/**
 * removeHTMLTags
 */
function removeHTMLTags(htstring)
{
    return htstring.replace(/(<([^>]+)>)/ig,"");
}


/**
 * setSelectValue
 */
function setSelectValue(element, content, thisurl)
{
    $(element).setValue(element.value);
    new Ajax.Request
    (
	thisurl,
	{
	    parameters: $(element).serialize(true),
	    loaderArea: false,
	    onSuccess : function(transport)
	    {
		var response = transport.responseText.evalJSON();
		parseMulti(response, new Array());
	    }
	}
    )
}



/**
 * addPackage
 */
function addPackage(optionurl)
{
    new Ajax.Request
    (
	optionurl,
	{
	    parameters: {i : $('number_of_packages').value},
	    loaderArea: false,
	    onSuccess : function(transport)
	    {
		$('package_table_rows').insert(transport.responseText);
		$('number_of_packages').setValue(parseInt($('number_of_packages').value) + 1);
	    }
	}
    );
    return true;
}


/**
 * deletePackge
 */
function deletePackage()
{
    var numpackages = parseInt($('number_of_packages').value);
    var pkgtable = $('package_table');
    if (numpackages > 1) {
    pkgtable.deleteRow(pkgtable.rows.length-1);
    $('number_of_packages').value = numpackages - 1; }
    return true;
}

/**
 * set allowed shipping methods in select menu
 */
function setAllowedMethods(methods)
{
    var children = $A($('method').childElements());
    children.each(function(child) {
       child.setAttribute('disabled', 'disabled');
    });
    for (var method in methods)
    {
        children.each(function(child) {
            if(method == $(child).readAttribute('value')) {
                child.removeAttribute('disabled'); }
        });
    }
}