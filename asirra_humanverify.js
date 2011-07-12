/*======================================================================*\
|| #################################################################### ||
|| # Asirra module for ConfirmEdit by Bachsau                         # ||
|| # ---------------------------------------------------------------- # ||
|| # This code is released into public domain, in the hope that it    # ||
|| # will be useful, but without any warranty.                        # ||
|| # ------------ YOU CAN DO WITH IT WHATEVER YOU LIKE! ------------- # ||
|| #################################################################### ||
\*======================================================================*/

var asirra_js_failed = "Please correctly identify the cats.";
var asirraform = document.forms[document.forms.length - 1];
var submitButtonClicked = document.createElement("input");
var passThroughFormSubmit = false;

function PrepareSubmit()
{
	submitButtonClicked.type = "hidden";
	var inputFields = asirraform.getElementsByTagName("input");
	for (var i=0; i<inputFields.length; i++)
	{
		if (inputFields[i].type == "submit")
		{
			inputFields[i].onclick = function(event)
			{
				submitButtonClicked.name = this.name;
				submitButtonClicked.value = this.value;
			}
		}
	}

	asirraform.onsubmit = function(event)
	{
		return MySubmitForm();
	}
}

function MySubmitForm()
{
	if (passThroughFormSubmit)
	{
		return true;
	}
	Asirra_CheckIfHuman(HumanCheckComplete);
	return false;
}

function HumanCheckComplete(isHuman)
{
	if (!isHuman)
	{
		alert(asirra_js_failed);
	}
	else
	{
		asirraform.appendChild(submitButtonClicked);
		passThroughFormSubmit = true;
		asirraform.submit();
	}
}

contentLoaded(window,PrepareSubmit);
