<?php
/**
* Copyright:	Copyright (C) 2010 Cory Webb Media, LLC. All rights reserved.
* License:	GNU/GPL
* Motif is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
*/

defined('JPATH_BASE') or die();

class JElementThemeables extends JElement
{
	var	$_name = 'Themeables';
	var $_templatePath = '';
	var $_themesPath = '';
	var $_themesURL = '';

	function fetchElement($name, $value, &$node, $control_name)
	{
		
		$baseurl = JURI::base();
		$motif = '<div style="text-align: center; padding: 20px 0;"><a href="http://themeables.com" target="_blank" style="outline: none;"><img src="'.$baseurl.'/plugins/system/motif/elements/logo.gif" border="0" width="238" height="169" alt="Themeables | Themeable Templates for Joomla | Powered by /motif" /></a></div>';
		
		return $motif;
		
	}
	
}
