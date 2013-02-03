/**
 * BlueSea
 *
 * @category   IllApps
 * @package    IllApps_BlueSea
 * @author     Jonathan Cantrell (j@kernelhack.com)
 * @copyright  Copyright (c) 2011 EcoMATICS, Inc. DBA IllApps (http://www.illapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Event.observe
 */

/**
 * updateShipment
 */
function updateQty(form, thisurl)
{

    console.log(thisurl);
    new Ajax.Request
    (
	thisurl,
	{
	    parameters : $(form).serialize(true),
	    method : 'post',
	    insertion: 'top',
	    onSuccess : function()
	    {                
		javascript:location.reload(true)
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