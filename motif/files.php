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
	var $doc					= null;
	var $context				= '';
	var $theme					= '';
	var $path					= '';
	var $paths					= array();
	var $url					= '';
	var $urls					= array();
	var $debug					= 0;
	var $images				= array();

	function __construct( &$document, $context='index', $theme='', $debug=0 )
	{
		$mainframe = JFactory::getApplication();

		$this->doc			= $document;
		$this->context		= $context;
		$this->theme		= $theme;
		$this->path			= JPATH_THEMES.'/'.$this->doc->template;
		$this->paths		= $this->_getPaths();
		$this->url			= $this->doc->baseurl.'/templates/'.$this->doc->template;
		$this->urls			= $this->_getUrls();
		$this->debug		= $debug;

	}

	// Get 2 paths: theme and template
	function _getPaths()
	{
		$paths = array();
		if($this->theme != '') $paths['theme'] = $this->path.'/themes/'.$this->theme;
		$paths['template'] = $this->path;
		return $paths;
	}

	// Get 2 URLs: theme and template
	function _getUrls()
	{
		$urls = array();
		if($this->theme != '') $urls['theme'] = $this->url.'/themes/'.$this->theme;
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
	function getFiles($ext, $files_list=array())
	{
		$files = array();
		$returnfiles = array();
		$ext_folder = $ext;

		if($this->paths && count($this->paths))
		{
			// STEP 1: Get all files with extension $ext from each path - template, theme, and core theme
			foreach($this->paths as $key => $path) {
				$files[$key] = array();
				if (JFolder::exists($path.'/'.$ext_folder))
					$files[$key] = JFolder::files($path.'/'.$ext_folder, '.'.$ext.'$', false, false);
			}

			// STEP 2: Loop through all of the files from step 1, and remove overridden files. Theme > Template
			foreach($files as $key => $pathfiles)
			{
				$file_location = $this->urls[$key].'/'.$ext_folder.'/';
				if($ext == 'less') $file_location = $this->paths[$key].'/'.$ext_folder.'/';
				foreach($pathfiles as $file)
				{
					if(!in_array($file, $returnfiles))
					{
						$returnfiles[] = $file_location.JFile::getName($file);
					}
				}
			}

		}
		return $returnfiles;

	}

	function getImage( $name, $ext = 'any' )
	{
		if (isset($this->images[$name]) && $this->images[$name] != '') return $this->images[$name];

		if (substr($ext, 0, 1) == '.') $ext = substr($ext, 1, strlen($ext)-1);
		$exts = array('gif', 'jpg', 'png');

		foreach($this->paths as $key=>$path)
		{
			$ImagesPath = $path.'/images/';
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

	function getFile( $name, $path_key='', $ignoredebug = 0 )
	{
		$loadfile = '';
		if($path_key != '') {
			if($this->hasFile($name, $path_key)) $loadfile = $this->paths[$path_key].'/'.$name;
		} else {
			if($this->paths && count($this->paths)) {
				foreach($this->paths as $key=>$path)
				{
					if ($this->hasFile($name, $key))
					{
						$loadfile = $path.'/'.$name;
						break;
					}
				}
			}
		}

		if ($loadfile != '') {
			if ($this->debug && !$ignoredebug) echo '<div class="outlinefile"><h3 class="outlinelabel">'.$loadfile.'</h3><div class="outlineoverlay"></div>';
			require_once($loadfile);
			if ($this->debug && !$ignoredebug) echo '</div>';
		}
	}
	
	function hasFile( $name, $path_key='' ) {
		if($path_key != '') {
			if($this->paths && isset($this->paths[$path_key])) {
				return( JFile::exists($this->paths[$path_key].'/'.$name) );
			}
		} else {
			if($this->paths && count($this->paths)) {
				foreach($this->paths as $path)
				{
					if (JFile::exists($path.'/'.$name))
					{
						return true;
					}
				}
			}
		}
		
		return false;
	}

}
?>