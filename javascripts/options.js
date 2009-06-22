/* This is a script taken from  http://www.quirksmode.org/js/options.html with script.aculo.us effects added. */

var store = new Array();

store[0] = new Array(
	'IP', 'ip',
	'name', 'name',
	'note', 'note',
	'last modified by', 'modified_by');

store[1] = new Array(
	'IP', 'ip',
	'name', 'name',
	'contact', 'contact',
	'note', 'note',
	'last modified by', 'modified_by',
  'failed scans count', 'failed_scans');
	
store[2] = new Array(
	'username', 'username',
	'level', 'level',
	'message', 'message');

function init()
{
	optionTest = true;
	if(document.forms[0])
	{
	lgth = document.forms[0].second.options.length - 1;
	document.forms[0].second.options[lgth] = null;
	if (document.forms[0].second.options[lgth]) optionTest = false;
	}
}


function populate()
{
	if (!optionTest) return;
	var box = document.forms[0].first;
	var number = box.options[box.selectedIndex].value;
	if (!number) return;
	var list = store[number];
	var box2 = document.forms[0].second;
	box2.options.length = 0;
	for(i=0;i<list.length;i+=2)
	{
		box2.options[i/2] = new Option(list[i],list[i+1]);
	}
}