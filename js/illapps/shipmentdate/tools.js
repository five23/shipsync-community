/**
 * ShipmentDate
 *
 * @category   IllApps
 * @package    IllApps_ShipmentDate
 * @author     Jonathan Cantrell (j@kernelhack.com)
 * @copyright  Copyright (c) 2011 EcoMATICS, Inc. DBA IllApps (http://www.illapps.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

var _exemptionsResponse;

function localDateStatusFunc(date, y, m, d) {
  if (dateIsExemption(date.getDay(), y, m, d)) return true;
  else return false;
};

function dateIsExemption(day, y, m, d)
{
    var exemptions = window._exemptionsResponse;

    var method = getMethodTitle();
    
    if(exemptions[method]['init_arr']['y'] > y) return true;
    else if(exemptions[method]['init_arr']['m'] > m && exemptions[method]['init_arr']['y'] >= y) return true;
    else if(exemptions[method]['init_arr']['d'] > d && exemptions[method]['init_arr']['y'] >= y && exemptions[method]['init_arr']['m'] >= m) return true;
    
    if(exemptions[method]['end_date']['y'] < y) return true;
    else if(exemptions[method]['end_date']['m'] < m && exemptions[method]['end_date']['y'] == y) return true;
    else if(exemptions[method]['end_date']['d'] < d && exemptions[method]['end_date']['y'] == y && exemptions[method]['end_date']['m'] == m) return true;

    /*if(exemptions[method]['today']['y'] > y) return true;
    else if(exemptions[method]['today']['m'] > m && exemptions[method]['today']['y'] >= y) return true;
    else if(exemptions[method]['today']['d'] >= d && exemptions[method]['today']['y'] >= y && exemptions[method]['today']['m'] >= m) return true;*/

    for (var i = 0; i < exemptions[method]['weekend'].length; i++){
        if(exemptions[method]['weekend'][i] == day) return true;
    }
    
    if(exemptions[method]['special']) {
        for (var i = 0; i < exemptions[method]['special'].length; i++){
            if(exemptions[method]['special'][i]['date'] == y.toString() + m.toString() + d.toString()) return true;
            else if(exemptions[method]['special'][i]['date'].substr(4) == m.toString() + d.toString() && exemptions[method]['special'][i]['recurring']) return true;
        }
    }
    
    return false;
}

function getMethodTitle()
{
    return $('instore_value').value == 1 ? 'instore' : 'delivery';
}

function initDate()
{
    var exemptions = window._exemptionsResponse;
    var method = getMethodTitle();
    var selector = 'init_date';
    
    $('shipping_arrival_date').setValue(exemptions[method][selector]);
    $('shipping_arrival_date_display').setValue(exemptions[method][selector]);
}

function shippingArrivalDateOnChange()
{
    var date = $('shipping_arrival_date').getValue();
    $('shipping_arrival_date_display').setValue(date);
    $('date_update').setValue(date); 

    
}

/*
function validate(date)
{
    var dateArr = date.split('/');
    var m = parseInt(dateArr[0]);
    var d = parseInt(dateArr[1]);
    var y = parseInt(dateArr[2]);
    
    console.log(m); console.log(d); console.log(y);
    
    var exemptions = window._exemptionsResponse;
    
    console.log(exemptions);

    var method = getMethodTitle();
    
    if(exemptions[method]['init_arr']['y'] > y) return false;
    else if(exemptions[method]['init_arr']['m'] > m && exemptions[method]['init_arr']['y'] >= y) return false;
    else if(exemptions[method]['init_arr']['d'] > d && exemptions[method]['init_arr']['y'] >= y && exemptions[method]['init_arr']['m'] >= m) return false;
    
    if(exemptions[method]['end_date']['y'] < y) return false;
    else if(exemptions[method]['end_date']['m'] < m && exemptions[method]['end_date']['y'] == y) return false;
    else if(exemptions[method]['end_date']['d'] < d && exemptions[method]['end_date']['y'] == y && exemptions[method]['end_date']['m'] == m) return false;
    
    return true;
}*/

function resetArrivalDate()
{
    $('shipping_arrival_date').setValue('');
    $('shipping_arrival_date_display').setValue('');
}

function getExemptionsAjax(thisurl, instore)
{
    
    new Ajax.Request
    (
	thisurl,
	{
	    parameters : instore,
	    method : 'post',
	    onSuccess : function(transport)
	    {
                window._exemptionsResponse = transport.responseText.evalJSON();
                initDate();
	    },
            onFailure : function() {
                reportError('Request failed');
            },
            onException : function(req, ex) {
                reportError('Error: ' + ex);
            },
            onComplete : function() {
                Element.hide('loadingmask');
            }
	}
    );
}

function updateDeliveryMethod(el)
{
    $('instore_value').setValue(el.value);
    initDate();
}

