<?php
/**
* Copyright:	Copyright (C) 2013, Manos. All rights reserved.
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
jimport( 'joomla.application.module.helper' );
jimport( 'motif.shortcuts' );
jimport( 'motif.files' );
jimport( 'motif.less' );

class Motif extends JObject
{
	var $doc					= null;
	var $theme					= '';
	var $paths					= array();
	var $themespath				= '';
	var $defaultModuleStyle		= 'raw';
	var $context				= '';
	var $sitename				= '';
	var $activeItem				= null;
	var $user					= null;
	var $cfg					= null;
	var $images					= null;
	var $debug					= 0;
	var $bodyClass				= '';
	var $plugins				= 0;
	var $lessFormatter			= 'lessjs';
	var $files					= null;
	var $jVersion				= '';

	function __construct()
	{
		$mainframe = JFactory::getApplication();
		$menu = $mainframe->getMenu();
		$this->sitename = $mainframe->getCfg('sitename');
		
		$this->doc = JFactory::getDocument();
		$this->context = JRequest::getVar('tmpl', '') == 'component' ? 'component' : 'index';

		$this->user = JFactory::getUser();
		if($mainframe->getCfg('offline') && !$user->authorise('core.login.offline')) $this->context = 'offline';

		$this->themespath = JPATH_THEMES.'/'.$this->doc->template.'/themes';
		$this->activeItem = $menu->getActive();

		$this->_getTheme();
		
		$this->cfg = JFactory::getConfig();
		$this->images = array();

		$this->_debug = $this->getParameter('debug') && JRequest::getVar('debug', 0);

		$this->lessFormatter = $this->getParameter('lessformatter');
		if(!$this->lessFormatter || $this->lessFormatter == '') $this->lessFormatter = 'lessjs';
		
		$this->files = new MotifFiles($this->doc, $this->context, $this->theme, $this->_debug);

		if ($this->getParameter('mode') == 'development') $this->compileLess();
	}
	
	public static function getInstance( $usethemes=1 )
	{
		static $instance;
		
		if(!is_object($instance))
		{
			$instance = new Motif($usethemes);
		}
		
		return $instance;
	}
	
	function _getJVersion() {
		$this->jVersion = JVERSION;
	}
		
	function _getTheme()
	{
		$this->theme = ($this->getParameter('theme') ? $this->getParameter('theme') : '');
		
		if (JRequest::getVar('theme', 0) && $this->getParameter('manualtheme')) $this->theme = JRequest::getVar('theme');
		
	}
	function setTheme( $theme )
	{
		$this->theme = (JRequest::getVar('theme', 0) && $this->getParameter('manualtheme')) ? JRequest::getVar('theme') : $theme;
	}
	
	function load()
	{

		if($this->files->hasFile('item'.JRequest::getVar('Itemid').'.php')) {
			$this->loadFile('item'.JRequest::getVar('Itemid').'.php');
		} elseif($this->isHome() && $this->files->hasFile('home.php')) {
			$this->loadFile('home.php');
		} else {
			if($this->files->hasFile('index.php', 'theme')) {
				$this->loadFile('index.php', 'theme');
			} else {
				$this->loadFile('index2.php', 'template');
			}
		}

				
		if ($this->context == 'index') $this->loadModules('debug'); // Load the debug module position automatically

	}
	
	// Loads CSS/JS files
	function loadCSS( $filenames = array() ) {
		$this->_loadFiles('css', $filenames);
	}
	function loadJS( $filenames = array() ) {
		$this->_loadFiles('js', $filenames);
	}
	function _loadFiles($ext, $filenames = array())
	{	
		$files = $this->files->get($ext, $filenames);

		$fileload['css'][] = '<link rel="stylesheet" type="text/css" href="';
		$fileload['css'][] = '" />';
		$fileload['js'][] = '<script type="text/javascript" src="';
		$fileload['js'][] = '"></script>';
		foreach($files as $file)
		{
			echo $fileload[$ext][0].$file.$fileload[$ext][1]."\n";
		}

	}
	
	function unsetStyles() {
		$this->doc->_style = array();
	}
	function unsetStyleSheets() {
		$this->doc->_styleSheets = array();
	}
	function unsetScripts() {
		$this->doc->_scripts = array();
	}
	function unsetScriptDeclarations() {
		$this->doc->_script = array();
	}
		
	
	// generates a body class based on these parameters of the active menu item: home, id, parent, and tree[0]
	function getBodyClass()
	{
		if ($this->context != 'index') return $this->context;
		if ($this->bodyClass == '')
			$this->setbodyClass(
								'item'.$this->activeItem->id
								. ' root'.$this->activeItem->tree[0]
								. ' menu'.$this->activeItem->menutype
								. ($this->isHome() ? ' onhome' : ' notonhome')
								. ' option'.str_replace('com_', '', JRequest::getVar('option', 'notdefined'))
								. ' view'.JRequest::getVar('view', 'notdefined')
								. ' '.$this->activeItem->params->get('pageclass_sfx')
								);
								
		return $this->bodyClass;
	}
	
	function setBodyClass( $bodyClass, $append = 0 )
	{
		$this->bodyClass = $append ? $this->bodyClass.' '.$bodyClass : $bodyClass;
	}
	
	function isHome()
	{
		return $this->activeItem->home;
	}
	
	function isUserLoggedIn()
	{
		return !$this->user->guest;
	}
	
	function countModules( $positions )
	{
		return $this->doc->countModules($positions);
	}
	
	function hasModules( $positions )
	{
		if ($this->countModules($positions)) return 1;
		return 0;
	}
	
	function loadModules( $name, $style='', $preHtml='', $postHtml='', $attribs=array() )
	{
		$modules = JModuleHelper::getModules($name);
		if(count($modules)) {
			if ($style == '') $style = $this->defaultModuleStyle;
			if(JFile::exists(JPATH_THEMES.'/'.$this->doc->template.'/html/modules.php'))
			{
				require_once(JPATH_THEMES.'/'.$this->doc->template.'/html/modules.php');
				if ($style == 'xhtml' && function_exists('modChrome_motifxhtml')) $style = 'motifxhtml';
				if ($style == 'rounded' && function_exists('modChrome_motifrounded')) $style = 'motifrounded';
			}
			$params = array( 'style' => $style );
			if($attribs && count($attribs)) {
				foreach($attribs as $key=>$attrib) {
					$params[$key] = $attrib;
				}
			}
			
			echo $preHtml;
			foreach($modules as $module) {
				echo JModuleHelper::renderModule($module, $params);
			}
			echo $postHtml;
		}

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
		require_once (JPATH_BASE.'/templates/'.$this->doc->template.'/html'.'/positions.php');
		if(JFile::exists(JPATH_BASE.'/templates/'.$this->doc->template.'/'.$this->theme.'/html'.'/positions.php'))
			require_once(JPATH_BASE.'/templates/'.$this->doc->template.'/'.$this->theme.'/html'.'/positions.php');
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
		$renderer	= $this->doc->loadRenderer('module');
		echo $preHtml.$renderer->render( $module, $params ).$postHtml;
	}
	
	function setDefaultModuleStyle( $style )
	{
		$this->defaultModuleStyle = $style;
	}
	
	function loadComponent( $preHtml='', $postHtml='' )
	{
		echo $preHtml;
		//$this->doc->getBuffer('component', null )
		?><jdoc:include type="component" /><?php
		echo $postHtml;
	}

	function hasMessage()
	{
		return $this->doc->getBuffer('message');
	}
	
	function loadMessage( $preHtml='', $postHtml='' )
	{
		if ($this->hasMessage()) {
			//$renderer	= $this->doc->loadRenderer('message');
			echo $preHtml;
			//$renderer->render
			?><jdoc:include type="message" /><?php
			echo $postHtml;
		}
	}
	
	function getSiteName()
	{
		return $this->sitename;
	}
	
	function getPageTitle()
	{
		return $this->doc->title;
	}
	
	function getHomeLink()
	{
		return $this->doc->baseurl;
	}
	
	function getParameter( $name )
	{
		return $this->doc->params->get($name);
	}
	
	function getMotifFiles()
	{
		return $this->files;
	}
	
	function loadFile($filename, $path_key='') {
		$loadfile = $this->files->getFileLocation($filename, $path_key);
		if ($loadfile != '') {
			if ($this->debug && !$ignoredebug) echo '<div class="outlinefile"><h3 class="outlinelabel">'.$loadfile.'</h3><div class="outlineoverlay"></div>';
			require_once($loadfile);
			if ($this->debug && !$ignoredebug) echo '</div>';
		}
	}
	
	function compileLess($files=null)
	{
		$lessfiles = $files ? $files : $this->files->getFiles('less');
		$less = new lessc;
		$less->setFormatter($this->lessFormatter);
		
		if($lessfiles && count($lessfiles))
		{
			foreach($lessfiles as $lessfile)
			{
				$less->checkedCompile($lessfile, str_replace('less', 'css', $lessfile));
			}
		}
	}

}
?>