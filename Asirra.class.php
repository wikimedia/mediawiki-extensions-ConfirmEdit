<?php
/**
 * @author Bachsau
 */

class Asirra extends SimpleCaptcha
{
	// Asirra URLs
	public $asirra_localpath = '';
	public $asirra_clientscript = 'http://challenge.asirra.com/js/AsirraClientSide.js';
	public $asirra_apiscript = 'http://challenge.asirra.com/cgi/Asirra';

	// As we don't have to store anything but some other things to do,
	// we're going to replace that constructor completely.
	function __construct()
	{
		global $wgScriptPath, $wgAsirraScriptPath;

		// WTF isn't this in ConfirmEdit_body.php?
		wfLoadExtensionMessages('ConfirmEdit');
		wfLoadExtensionMessages('Asirra');

		// Try to find $asirra_localpath if not set
		if (!$this -> asirra_localpath = $wgAsirraScriptPath)
		{
			if (strpos(__FILE__, $_SERVER['DOCUMENT_ROOT']) === 0)
			{
				$this -> asirra_localpath = preg_replace('/^' . preg_quote($_SERVER['DOCUMENT_ROOT'], '/') . '(\\/*)/s', '/', dirname(__FILE__));
			}
			else
			{
				$this -> asirra_localpath = $wgScriptPath . '/extensions/ConfirmEdit';
			}
		}
	}

	function getForm()
	{
		global $wgAsirraEnlargedPosition, $wgAsirraCellsPerRow, $wgOut;

		return '
			<script type="text/javascript" src="' . $this -> asirra_clientscript . '"></script>
			<script type="text/javascript" src="' . $this -> asirra_localpath . '/asirra_contentloaded.js"></script>
			<script type="text/javascript" src="' . $this -> asirra_localpath . '/asirra_humanverify.js"></script>
			<script type="text/javascript">
				asirraState.SetEnlargedPosition("' . $wgAsirraEnlargedPosition . '");
				asirraState.SetCellsPerRow(' . $wgAsirraCellsPerRow . ');
				var asirra_js_failed = "' . $this -> getMessage('createaccount-fail') . '"
			</script>
			<noscript>' . $wgOut -> parse($this -> getMessage('nojs')) . '</noscript>
		';
	}

	function getMessage( $action )
	{
		$name = 'asirra-' . $action;
		$text = wfMsg($name);
		// Obtain a more tailored message, if possible, otherwise, fall back to
		// the default for edits
		return wfEmptyMsg($name, $text) ? wfMsg('asirra-edit') : $text;
	}

	// This is where the party goes on...
	function passCaptcha()
	{
		global $wgRequest, $wgAsirra;

		$ticket = $wgRequest -> getVal('Asirra_Ticket');
		$url = $this -> asirra_apiscript . '?action=ValidateTicket&ticket=' . $ticket;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		$resultXml = curl_exec($ch);
		curl_close($ch);

		$xml_parser = xml_parser_create();
		xml_set_element_handler($xml_parser, 'AsirraXmlParser::startElement', 'AsirraXmlParser::endElement');
		xml_set_character_data_handler($xml_parser, 'AsirraXmlParser::characterData');
		xml_parse($xml_parser, $resultXml, 1);
		xml_parser_free($xml_parser);

		if ($wgAsirra['passed'])
		{
			return true;

		}
		return false;
	}
}

class AsirraXmlParser
{
	static function startElement($parser, $name, $attrs)
	{
		global $wgAsirra;

		$wgAsirra['inResult'] = ($name=="RESULT");
	}

	static function endElement($name)
	{
		global $wgAsirra;

		$wgAsirra['inResult'] = 0;
	}

	static function characterData($parer, $data)
	{
		global $wgAsirra;

		if ($wgAsirra['inResult'] && $data == "Pass")
		{
			$wgAsirra['passed'] = 1;
		}
	}
}
