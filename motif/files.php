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

jimport( 'joomla.filesystem.file' );
jimport( 'joomla.filesystem.folder' );

class MotifFiles extends JObject
{
	var $_doc					= null;
	var $_context				= '';
	var $_themes				= true;
	var $_theme					= 'core';
	var $_coretheme				= 'core';
	var $path					= '';
	var $paths					= array();
	var $url					= '';
	var $urls					= array();
	var $_browser				= null;
	var $_debug					= 0;
	var $images				= array();

	function __construct( &$document, $browser, $context='index', $themes=true, $theme='core', $coretheme='core', $debug=0 )
	{
		$mainframe = JFactory::getApplication();
		
		$this->_doc			= $document;
		$this->_browser		= $browser;
		$this->_context		= $context;
		$this->_themes		= $themes;
		$this->_theme		= $theme;
		$this->_coretheme	= $coretheme;
		$this->path		= JPATH_THEMES.DS.$this->_doc->template;
		$this->paths		= $this->_getPaths();
		$this->url			= $this->_doc->baseurl.'/templates/'.$this->_doc->template;
		$this->urls		= $this->_getUrls();
		$this->_debug		= $debug;

	}
	
	// Get 3 paths: theme, core, and template
	function _getPaths()
	{
		$paths = array();
		if($this->_themes)
		{
			$paths['theme'] = $this->path.DS.'themes'.DS.$this->_theme;
			if($this->_theme != $this->_coretheme) $paths['core'] = $this->path.DS.'themes'.DS.$this->_coretheme;
		}
		$paths['template'] = $this->path;
		return $paths;
	}
	
	// Get 3 URLs: theme, core and template
	function _getUrls()
	{
		$urls = array();
		if($this->_themes)
		{
			$urls['theme'] = $this->url.'/themes/'.$this->_theme;
			if($this->_theme != $this->_coretheme) $urls['core'] = $this->url.'/themes/'.$this->_coretheme;
		}
		$urls['template'] = $this->url;
		return $urls;
	}

	// Get files
	function get($ext, $filename='')
	{
		switch($ext)
		{
			case 'css':
			case 'js':
			case 'less':
				return $this->getFiles($ext);
				break;
			case 'jpg':
			case 'gif':
			case 'png':
			case 'any':
				return $this->getImage($filename, $ext);
				break;
			case 'php':
			case 'inc':
				return $this->getFile($filename.'.'.$ext);
				break;
		}
		return '';
	}
	
	// Get CSS, JS or less files
	function getFiles($ext)
	{
		$files = array();
		$returnfiles = array();
		$ext_folder = ($ext == 'less' ? 'css' : $ext);
		
		if($this->paths && count($this->paths))
		{
			// STEP 1: Get all files with extension $ext from each path - template, theme, and core theme
			foreach($this->paths as $key => $path) {
				$files[$key] = array();
				if (JFile::exists($path.DS.$ext_folder.DS.'order.php'))
				{
					include($path.DS.$ext_folder.DS.'order.php');
					$files[$key] = $ordered;
				}
				else
				{
					if (JFolder::exists($path.DS.$ext_folder))
						$files[$key] = JFolder::files($path.DS.$ext_folder, '.'.$ext.'$', false, false);
				}
			}
			
			// STEP 2: Loop through all of the files from step 1, and remove overridden files. Theme > Core Theme > Template
			if($ext == 'less')
			{
				foreach($files as $key=>$pathfiles)
				{
					foreach($pathfiles as $file)
					{
						$returnfiles[] = $this->paths[$key].DS.'css'.DS.JFile::getName($file);
					}
				}
			}
			else
			{
				$browserfiles = array();
				foreach($files as $key => $pathfiles)
				{
					foreach($pathfiles as $file)
					{
						if(!in_array($file, $returnfiles))
						{
							if (!strstr(JFile::getName($file), 'browser_ie'))
							{
								$returnfiles[] = $this->urls[$key].'/'.$ext_folder.'/'.JFile::getName($file);
							} else {
								if ($this->_browsermatch(JFile::getName($file), $ext)) $browserfiles[] = $this->urls[$key].'/'.$ext_folder.'/'.JFile::getName($file);
							}
						}
					}
				}
				
				
				// STEP 3: Add browser-specific files to the array if they match the current browser.
				if ( count($browserfiles) )
				{
					foreach($broserfiles as $browserfile)
					{
						$returnfiles[] = $browserfile;
					}
				}
			}
		}
		return $returnfiles;

	}
	
	function _browserMatch( $filename, $ext )
	{
		if (strpos($filename, 'browser_ie') === 0) // file name starts with 'browser_ie'
		{
			if ($this->_browser->_browser != 'msie') return false;
			if ($filename == 'browser_ie.'.$ext) return true;
			if ($this->_browser->_majorVersion == substr($filename, 10, 1)) return true;
			return false;
		}
		return true;
	}
	
	function getImage( $name, $ext = 'any' )
	{
		if (isset($this->images[$name]) && $this->images[$name] != '') return $this->images[$name];
		
		if (substr($ext, 0, 1) == '.') $ext = substr($ext, 1, strlen($ext)-1);
		$exts = array('gif', 'jpg', 'png');
		
		foreach($this->paths as $key=>$path)
		{
			$ImagesPath = $path.DS.'images'.DS;
			$Images = $this->urls[$key].'/images/';
			foreach($exts as $myext)
			{
				if (($ext == $myext || $ext == 'any') && JFile::exists($ImagesPath.$name.'.'.$myext))
				{
					$this->images[$name] = $Images.$name.'.'.$myext;
					return $this->images[$name];
				}
			}
		}
		
		return '';
	}
	
	function setImage( $name, $location )
	{
		$this->images[$name] = $location;
	}
	
	function getFile( $name, $ignoredebug = 0 )
	{
		$loadfile = '';
		if($this->paths && count($this->paths))
		{
			foreach($this->paths as $path)
			{
				if (JFile::exists($path.DS.$name))
				{
					$loadfile = $path.DS.$name;
					break;
				}
			}
		}
		
		if ($loadfile != '') {
			if ($this->_debug && !$ignoredebug) echo '<div class="outlinefile"><h3 class="outlinelabel">'.$loadfile.'</h3><div class="outlineoverlay"></div>';
			require_once($loadfile);
			if ($this->_debug && !$ignoredebug) echo '</div>';
		}
	}

}
?>