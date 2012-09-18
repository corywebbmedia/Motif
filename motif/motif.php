<?php
/**
* Copyright:	Copyright (C) 2010 Cory Webb Media, LLC. All rights reserved.
* License:	GNU/GPL
* Motif is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
*/

defined('_JEXEC') or die('Restricted access');

jimport( 'joomla.html.parameter' );
jimport( 'joomla.filesystem.file' );
jimport( 'joomla.filesystem.folder' );
jimport( 'motif.shortcuts' );
jimport( 'motif.files' );
jimport( 'motif.less' );

class Motif extends JObject
{
	var $_doc					= null;
	var $_usethemes				= 1;
	var $_theme					= 'core';
	var $_coretheme				= 'core';
	var $_paths					= array();
	var $_themespath			= '';
	var $_defaultModuleStyle	= 'raw';
	var $_context				= '';
	var $_activeItem			= null;
	var $_user					= null;
	var $_cfg					= null;
	var $_images				= null;
	var $_browser				= null;
	var $_debug					= 0;
	var $_bodyClass				= '';
	var $_plugins				= 0;
	var $_lessFormatter			= 'lessjs'; // DEFAULT VALUE
	var $files					= null;

	function __construct( $usethemes=1 )
	{
		$mainframe = JFactory::getApplication();
		$menu = $mainframe->getMenu();
		JPluginHelper::importPlugin('motif');
		
		$this->_doc = JFactory::getDocument();
		$this->_browser = $this->getBrowser();
		$this->_context = JRequest::getVar('tmpl', '') == 'component' ? 'component' : 'index';
		$user = JFactory::getUser();
		if($mainframe->getCfg('offline') && !$user->authorise('core.login.offline')) $this->_context = 'offline';
		$this->_usethemes = $usethemes;
		$this->_themespath = JPATH_THEMES.DS.$this->_doc->template.DS.'themes';
		$this->_activeItem = $menu->getActive();
		$this->_setCore();
		$this->_getTheme();
		$this->_user = JFactory::getUser();
		$this->_cfg = JFactory::getConfig();
		$this->_images = array();
		$this->_debug = $this->getParameter('debug') && JRequest::getVar('debug', 0);
		$this->_plugins = $this->getParameter('plugins');
		$this->_lessFormatter = $this->getParameter('lessformatter');
		if(!$this->_lessFormatter || $this->_lessFormatter == '') $this->_lessFormatter = 'lessjs';
		$this->files = new MotifFiles($this->_doc, $this->_browser, $this->_context, $this->_usethemes, $this->_theme, $this->_coretheme, $this->_debug);
		if ($this->getParameter('mode') == 'development') $this->compileLess();
		
		$this->triggerEvent('onAfterMotifLoad', array(&$this));
	}
	
	function getInstance( $usethemes=1 )
	{
		static $instance;
		
		if(!is_object($instance))
		{
			$instance = new Motif($usethemes);
		}
		
		return $instance;
	}

	function _setCore()
	{
		if ($this->_browser->isMobile() && JFolder::exists($this->_themespath.DS.'mobilecore')) $this->_coretheme = 'mobilecore';
	}
	
	function _getTheme()
	{
		$this->_theme = ($this->getParameter('theme') ? $this->getParameter('theme') : $this->_coretheme);

		$this->getBrowser();
		if ($this->_browser->isMobile() && $this->getParameter('theme_mobile') != $this->_coretheme) $this->_theme = $this->getParameter('theme_mobile');
		
		if (JRequest::getVar('theme', 0) && $this->getParameter('manual_theme')) $this->_theme = JRequest::getVar('theme');
		
	}
	function setTheme( $theme )
	{
		$this->_theme = (JRequest::getVar('theme', 0) && $this->getParameter('manual_theme')) ? JRequest::getVar('theme') : $this->_theme = $theme;
	}
	
	function load()
	{
		$mainframe = JFactory::getApplication();

		switch ($this->getParameter('doctype'))
		{
			case 'html 5':
				echo '<!DOCTYPE HTML>'."\n";
				echo '<html lang="'.$this->_doc->language.'">'."\n";
				break;
			case 'xhtml strict':
				echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n";
				echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.$this->_doc->language.'" lang="'.$this->_doc->language.'" dir="'.$this->_doc->direction.'">'."\n";
				break;
			case 'xhtml 1.1':
				echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'."\n";
				echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.$this->_doc->language.'" lang="'.$this->_doc->language.'" dir="'.$this->_doc->direction.'">'."\n";
				break;
			case 'xhtml transitional':
			default: // XHTML Transitional
				echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
				echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.$this->_doc->language.'" lang="'.$this->_doc->language.'" dir="'.$this->_doc->direction.'">'."\n";
				break;
		}
		echo "<head>\n";
		echo '<meta http-equiv="content-type" content="text/html; charset=utf-8" />'."\n";
		echo $this->loadHead()."\n";
		echo "</head>\n";
		echo '<body class="'.$this->getBodyClass().'">'."\n";

		$this->triggerEvent('onBeforeInclude', array(&$this, 'index.php'));
		require_once(JPATH_THEMES.DS.$this->_doc->template.DS.'themes'.DS.$this->getIndex());
		$this->triggerEvent('onAfterInclude', array(&$this, 'index.php'));
		
		if ($this->_context == 'index') $this->loadModules('debug'); // Load the debug module position automatically

		echo "</body>\n";
		echo "</html>";
	}
	
	// loads information in the <head></head> area of the document
	function loadHead( )
	{
		$this->files->getFile( 'beforehead.php', 1 );
		?>
		<jdoc:include type="head" />
		<?php
		echo '<!-- LOAD CSS -->';
		$this->_loadFiles('css');
		echo '<!-- LOAD JS -->';
		$this->_loadFiles('js');
		$this->files->getFile( 'head.php', 1 );
		if ($this->_debug) echo '<link rel="stylesheet" type="text/css" href="'.$this->_doc->baseurl.'/libraries/motif/css/debug.css" />'."\n";
	}
	
	// determines if default or selected theme should be used for loading $filename
	function getThemeName()
	{
		$filename = $this->_context.'.php';
		return (JFile::exists($this->_themespath.DS.$this->_theme.DS.$filename) ? $this->_theme : $this->_coretheme);
	}
	
	function getIndex()
	{
		if ($this->_context != 'index') return $this->getThemeName().DS.$this->_context.'.php';
		$index = $this->getThemeName().DS.'index.php';
		if ($this->isHome()) {
			if (JFile::exists($this->_themespath.DS.$this->_coretheme.DS.'home.php')) $index = $this->_coretheme.DS.'home.php';
			if ($this->_theme != $this->_coretheme && JFile::exists($this->_themespath.DS.$this->_theme.DS.'home.php')) $index = $this->_theme.DS.'home.php';
		}
		return $index;
	}
	
	// generates a body class based on these parameters of the active menu item: home, id, parent, and tree[0]
	function getBodyClass()
	{
		if ($this->_context != 'index') return $this->_context;
		if ($this->_bodyClass == '')
			$this->setbodyClass(
								'item'.$this->_activeItem->id
								. ' root'.$this->_activeItem->tree[0]
								. ' menu'.$this->_activeItem->menutype
								. ($this->isHome() ? ' onhome' : ' notonhome')
								. ' option'.str_replace('com_', '', JRequest::getVar('option', 'notdefined'))
								. ' view'.JRequest::getVar('view', 'notdefined')
								);
								
		return $this->_bodyClass;
	}
	
	function setBodyClass( $bodyClass, $append = 0 )
	{
		$this->_bodyClass = $append ? $this->_bodyClass.' '.$bodyClass : $bodyClass;
	}
	
	function isHome()
	{
		return $this->_activeItem->home;
	}
	
	// Loads CSS/JS files
	function _loadFiles($ext)
	{	
		$files = $this->files->get($ext);

		$fileload['css'][] = '<link rel="stylesheet" type="text/css" href="';
		$fileload['css'][] = '" />';
		$fileload['js'][] = '<script type="text/javascript" src="';
		$fileload['js'][] = '"></script>';
		foreach($files as $file)
		{
			echo $fileload[$ext][0].$file.$fileload[$ext][1]."\n";
		}

	}
	
	function isUserLoggedIn()
	{
		return !$this->_user->guest;
	}
	
	function countModules( $positions )
	{
		return $this->_doc->countModules($positions);
	}
	
	function hasModules( $positions )
	{
		if ($this->countModules($positions)) return 1;
		return 0;
	}
	
	function loadModules( $name, $style='', $preHtml='', $postHtml='', $attribs=array() )
	{
		$mainframe = JFactory::getApplication();
		if ($this->_plugins) $mainframe->triggerEvent( 'onBeforeLoadModulePosition', array( &$this, &$name, &$style, &$preHtml, &$postHtml, &$attribs ) );
		if ($style == '') $style = $this->_defaultModuleStyle;
		if(JFile::exists(JPATH_THEMES.DS.$this->_doc->template.DS.'html'.DS.'modules.php'))
		{
			require_once(JPATH_THEMES.DS.$this->_doc->template.DS.'html'.DS.'modules.php');
			if ($style == 'xhtml' && function_exists('modChrome_motifxhtml')) $style = 'motifxhtml';
			if ($style == 'rounded' && function_exists('modChrome_motifrounded')) $style = 'motifrounded';
		}
		if ($this->countModules($name))
		{
			$renderer	= $this->_doc->loadRenderer('modules');
			$params		= array('style'=>$style);

			foreach($attribs as $key=>$attrib) $params[$key] = $attrib;

			echo $preHtml.$renderer->render( $name, $params ).$postHtml;
		}
		if ($this->_plugins) $mainframe->triggerEvent( 'onAfterLoadModulePosition', array( &$this, &$name, &$style, &$preHtml, &$postHtml, &$attribs ) );
	}
	
	function loadModulePosition( $position )
	{
		$this->loadModules( $position['name'], $position['style'], $position['preHtml'], $position['postHtml'], $position['attribs'] );
	}
	
	function getPosition( $name, $style='', $preHtml='', $postHtml='', $attribs=array() )
	{
		$position = array();
		$position['name'] = $name;
		$position['style'] = $style;
		$position['preHtml'] = $preHtml;
		$position['postHtml'] = $postHtml;
		$position['attribs'] = $attribs;
		return $position;
	}
	
	function loadPositions( $positions, $style='none' )
	{
		foreach($positions as $position) {
			if (!isset($position['style'])) $position['style'] = '';
			if (!isset($position['preHTML'])) $position['preHTML'] = '';
			if (!isset($position['postHTML'])) $position['postHTML'] = '';
			if (!isset($position['attribs'])) $position['style'] = '';
		}
		require_once (JPATH_BASE.DS.'templates'.DS.$this->_doc->template.DS.'html'.DS.'positions.php');
		if(JFile::exists(JPATH_BASE.DS.'templates'.DS.$this->_doc->template.DS.$this->_theme.DS.'html'.DS.'positions.php'))
			require_once(JPATH_BASE.DS.'templates'.DS.$this->_doc->template.DS.$this->_theme.DS.'html'.DS.'positions.php');
		$positionsfunction = 'positions_'.$style;
		if(function_exists($positionsfunction))
		{
			$positionsfunction($positions);
		} else {
			$positionsfunction = 'positions_none';
			if (function_exists($positionsfunction)) $positionsfunction($positions);
		}
		
	}
	
	function loadModule( $module, $preHtml='', $postHtml='', $params=array() )
	{
		$renderer	= $this->_doc->loadRenderer('module');
		echo $preHtml.$renderer->render( $module, $params ).$postHtml;
	}
	
	function setDefaultModuleStyle( $style )
	{
		$this->_defaultModuleStyle = $style;
	}
	
	function loadComponent( $preHtml='', $postHtml='' )
	{
		echo $preHtml;
		//$this->_doc->getBuffer('component', null )
		?><jdoc:include type="component" /><?php
		echo $postHtml;
	}

	function hasMessage()
	{
		return $this->_doc->getBuffer('message');
	}
	
	function loadMessage( $preHtml='', $postHtml='' )
	{
		if ($this->hasMessage()) {
			//$renderer	= $this->_doc->loadRenderer('message');
			echo $preHtml;
			//$renderer->render
			?><jdoc:include type="message" /><?php
			echo $postHtml;
		}
	}
	
	function getSiteName()
	{
		return $this->_cfg->getValue( 'config.sitename' );
	}
	
	function getPageTitle()
	{
		return $this->_doc->title;
	}
	
	function getHomeLink()
	{
		return $this->_doc->baseurl;
	}
	
	function getParameter( $name )
	{
		return $this->_doc->params->get($name);
	}
	
	function triggerEvent($event, $params)
	{
		$mainframe = JFactory::getApplication();
		if ($this->_plugins && ($this->_context == 'index' || $this->_context == 'component'))
			$mainframe->triggerEvent( $event, $params);
	}

	function getBrowser()
	{
		if($this->_browser) return $this->_browser;
		jimport('joomla.environment.browser');
		
		$this->_browser = JBrowser::getInstance();
		
		return $this->_browser;
	}

	function loadModuleChrome()
	{
		if($this->_usethemes) $this->files->getFile('html'.DS.'modules.php');
	}
	
	function getMotifFiles()
	{
		return $this->files;
	}
	
	function compileLess($files=null)
	{
		$lessfiles = $files ? $files : $this->files->get('less');
		$less = new lessc;
		$less->setFormatter($this->_lessFormatter);
		
		if($lessfiles && count($lessfiles))
		{
			foreach($lessfiles as $lessfile)
			{
				$less->ccompile($lessfile, str_replace('.less', '.css', $lessfile));
			}
		}
	}

}
?>