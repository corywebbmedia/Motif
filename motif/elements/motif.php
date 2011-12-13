<?php
/**
* Copyright:	Copyright (C) 2010 Cory Webb Media, LLC. All rights reserved.
* License:	GNU/GPL
* Motif is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
*/

class JFormFieldMotif extends JFormField
{
	protected $type = 'Motif';
	
	public function getInput() {
	
		jimport( 'joomla.filesystem.file' );
		jimport( 'joomla.filesystem.folder' );
		
		$document = JFactory::getDocument();
		$document->addStyleSheet('../libraries/motif/elements/motif.css');

				
/*		
		$name = $this->name;
		$value = $this->value;
		$id = $this->id;
		
		$value = str_replace('\\', '', $value); // Removes the "\" that the param system adds to the string
		// parse the parameter value into an array of arrays
		$valuearray = explode('|', $value);
		$themevalues = array();
		foreach($valuearray as $theme)
		{
			$themearray = explode('=', $theme);
			$themevalues[$themearray[0]] = explode(',', $themearray[1]);
		}

		$document = JFactory::getDocument();
	
		$themeparameters = '<input type="hidden" id="'.$id.'" name="'.$name.']" value="'.$value.'" />';

		$cid = JRequest::getVar('cid');
		$this->_templateName = $this->form->data->data->template;
		
		$this->_themesPath = str_replace('libraries'.DS.'motif'.DS.'elements', 'templates'.DS.$this->_templateName.DS.'themes', dirname(__FILE__));
		$this->_themes = JFolder::folders($this->_themesPath, '.', false, false);
		$this->_templatePath = str_replace(DS.'themes', '', $this->_themesPath);
		
		$this->_themesURL = '../libraries/motif/elements';

		$document->addStyleSheet($this->_themesURL.'/motif.css');
		
		$configxml = dirname(__FILE__).DS.'config.xml';
		$templateconfig = dirname(__FILE__).DS.$this->_templateName.'_config.xml';
		
		if(JFile::exists($configxml)) {
			if(!JFile::exists($templateconfig)) {
				JFile::copy($configxml, $templateconfig);
				$configcontent = JFile::read($configxml);
				$configcontent = str_replace('TEMPLATE_NAME', $this->_templateName, $configcontent);
				JFile::write($templateconfig, $configcontent);
			}

			$params = new JParameter($content, $templateconfig);
			$themeparameters .= '<h2>Template Parameters</h2>';
			$themeparameters .= $params->render();
		}
		
		$themeparameters .= '<h2>Theme-specific Parameters</h2>';
		if ($this->_themes)
		{
			$themeparameters .= '<div id="accordion">';
			$i = 1;
			foreach($this->_themes as $theme)
			{
				$themeparameters .= '<h3 class="toggler" id="toggler_'.$theme.'"><strong>'.$theme.'</strong> theme</h3>';
				$themeparameters .= '<div class="element" id="element_'.$theme.'">';
				$themevalue = '';
				if ($themevalues[$theme]) $themevalue=$themevalues[$theme];
				$themeparameters .= '<div id="theme'.$theme.'" class="themeparams">';
				if ($theme != 'core' && $theme != 'mobilecore') {
					$themeparameters .= '<div class="menulist"><h3>Menu Assignment</h3>'.$this->_getMenuList($theme, $themevalue, $node).'</div>';
				} else {
					$themeparameters .= '<h3>Menu Assignment</h3><p>The core theme is assigned by default if no other theme is assigned, so there is no need for core theme menu assignment.</p>';
				}

				$xml = $this->_themesPath.DS.$theme.DS.'config.xml';

				if (JFile::exists($xml)) {
					
					$params = new JParameter($content, $xml);
					$themeparameters .= '<div class="customparams"><h3>Parameters</h3>';
					$themeparameters .= $params->render();
					$themeparameters .= '</div>';

				} else {
					$themeparameters .= '<h3>Parameters</h3><p>This theme has no custom parameters.</p>';
				}
				$themeparameters .= '</div>';
				$i = 0;
				$themeparameters .= '</div>';
			}
			$themeparameters .= '</div>';
		}
		else
		{
			$themeparameters .= '<p><em>No themes available to assign to menu items.</em></p>';
		}
*/		
		$baseurl = JURI::base();
		$themeparameters .= '<div style="text-align: center; padding: 20px 0;"><a href="http://themeables.com" target="_blank" style="outline: none;"><img src="'.str_replace('administrator', '', $baseurl).'libraries/motif/elements/logo.gif" border="0" width="238" height="169" style="float: none;" alt="Themeables | Themeable Templates for Joomla | Powered by /motif" /></a></div>';
		
		
		// $document->addScriptDeclaration($this->_Scripts());
		
		return $themeparameters;

	}
	
	function _Scripts()
	{
		$mythemes = '';
		if ($this->_themes)
		{
			$i = 0;
			foreach ($this->_themes as $theme)
			{
				$mythemes .= "mythemes[$i] = '$theme';\n";
				$i++;
			}
		}
		$activebg = $this->_themesURL.'/bg_toggler_active.gif';
		$inactivebg = $this->_themesURL.'/bg_toggler.gif';

		$scripts = <<<EOD
	window.addEvent('domready', function() {
		var myAccordion = new Accordion($('accordion'), 'h3.toggler', 'div.element', {
			opacity: false,
			onActive: function(toggler, element){
				toggler.setStyle('background-image', 'url($activebg)');
				toggler.setStyle('color', '#fff');
			},
			onBackground: function(toggler, element){
				toggler.setStyle('background-image', 'url($inactivebg)');
				toggler.setStyle('color', '#000');
			}
		});
	});
	
	function getSelected(opt) {
	    var selected = new Array();
		var index = 0;
		for (var intLoop=0; intLoop < opt.length; intLoop++) {
			if (opt[intLoop].selected) {
				index = selected.length;
				selected[index] = new Object;
				selected[index].value = opt[intLoop].value;
				selected[index].index = intLoop;
			}
		}
		return selected;
	}

	function changeValue() {
		var mythemes = new Array();
		var selecteditems = new Array();
		$mythemes
		var menuassignment = '';
		var i=0;
		for (i=0;i<mythemes.length;i++) {
			var themelist = document.getElementById('list'+mythemes[i]);
			selecteditems = getSelected(themelist);
			if (selecteditems.length) {
				menuassignment+=mythemes[i]+'=';
				var j=0;
				for (j=0;j<selecteditems.length;j++) {
					menuassignment+=selecteditems[j].value;
					if (j != selecteditems.length - 1) menuassignment+=',';
				}
				if (i != mythemes.length - 1) menuassignment+='|';
			}
		}
		document.getElementById('paramsmenu_assignment').value = menuassignment;
	}

EOD;

		return $scripts;

	}
}
