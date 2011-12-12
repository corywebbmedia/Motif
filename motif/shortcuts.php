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

/* A shortcut for $this->countModules($position) */
function cm( $positions )
{
	global $motif;
	return $motif->countModules( $positions );
}

/* Returns a 1 if the position has modules or a 0 if the position does not have modules. */
function hasModules( $positions )
{
	global $motif;
	return $motif->hasModules( $positions );
}

/* Loads the modules in the selected position. You can set the module style and HTML to get loaded before and after the position.
The function determines whether or not the position has modules before loading preHtml and postHtml. */
function modules( $name, $style='raw', $preHtml='', $postHtml='', $attribs=array() )
{
	global $motif;
	$motif->loadModules( $name, $style, $preHtml, $postHtml, $attribs );
}
function modulePosition( $position )
{
	global $motif;
	$motif->loadModulePosition( $position );
}
/* Loads a single module. */
function module( $module, $preHtml='', $postHtml='', $params=array() )
{
	global $motif;
	$motif->loadModule( $module, $preHtml, $postHtml, $params );
}
function position( $name, $style='', $preHtml='', $postHtml='', $attribs=array() )
{
	global $motif;
	return $motif->getPosition( $name, $style, $preHtml, $postHtml, $attribs );
}
function positions( $positions, $style='none' )
{
	global $motif;
	$motif->loadPositions( $positions, $style );
}
/* Setting $style in the modules function is optional and defaults to "raw". You can override the default with this function. */
function setDefaultModuleStyle( $style )
{
	global $motif;
	$motif->setDefaultModuleStyle( $style );
}

/* Loads the component with HTML before and after the component. */
function component( $preHtml = '', $postHtml = '' )
{
	global $motif;
	$motif->loadComponent( $preHtml, $postHtml );
}

/* Determines if there is a message in the buffer. */
function hasMessage()
{
	global $motif;
	return $motif->hasMessage();
}
/* Loads the message with preHtml and postHtml if there is a message in the buffer. Makes the hasMessage function mostly unnecessary. */
function message( $preHtml = '', $postHtml = '' )
{
	global $motif;
	$motif->loadMessage($preHtml, $postHtml);
}

/* Loads a php file of a given name if it exists. You have to include the file extension in the name.
The function will search the default them and the core theme to see if the file exists. */
function getFile( $filename )
{
	global $motif;
	$files = $motif->getMotifFiles();
	$files->getFile( $filename );
}
/* Loads sidebar.php or sidebar_$name.php if you enter a value for $name. This function is the same as calling getFile('sidebar.php') */
function getSidebar( $name = '' )
{
	getFile('sidebar'.($name != '' ? '_'.$name : '').'.php');
}

/* Returns true if the user is currently on the home page, false if not. */
function isHome()
{
	global $motif;
	return $motif->isHome();
}
/* Loads the search module. */
function getSearchForm( $preHtml='', $postHtml='', $params=array() )
{
	module( 'search', $preHtml, $postHtml, $params );
}
/* Returns true if hte user is logged in, false if not */
function isUserLoggedIn()
{
	global $motif;
	return $motif->isUserLoggedIn();
}
/* Returns the name of the site as set in the global configuraiton. */
function getSiteName()
{
	global $motif;
	return $motif->getSiteName();
}
/* Returns the page title */
function getPageTitle()
{
	global $motif;
	return $motif->getPageTitle();
}
/* Returns the location of an image. Do not include the extension in $imagename.
The function will look for all possible extensions (.png, .gif, and .jpg) unless  you specify $extension.
It first searches in themes/DEFAULT_THEME/images/ for the existance of the image.
If it is not there, it searches themes/core/images. If it's not there, it returns a null value. */
function getImage( $imagename, $extension = 'any' )
{
	global $motif;
	$files = $motif->getMotifFiles();
	return $files->getImage( $imagename, $extension );
}
function getLogo()
{
	return getImage('logo');
}
/* Returns a link to the homg page of the site. */
function getHomeLink()
{
	global $motif;
	return $motif->getHomeLink();
}
/* Loads a template parameter. */
function getParameter( $name )
{
	global $motif;
	return $motif->getParameter( $name );
}
/* Returns the JBrowser object with the user's browser info. */
function getBrowser()
{
	global $motif;
	return $motif->getBrowser();
}

/*
 * Wordpress Template Tag Equivalents
 * All of these functions are duplicated elsewhere. The only reason for these is that they are named the same as Wordpress Template Tags.
 * The get_file() function is not a Wordpress Template Tag, but it should be. It's basically the same as the get_sidebar() function, but you
 * can specify any file name to get included. It doesn't just have to start with 'sidebar_'.
 */
 /* Returns true if user is logged in, false if not. */
function is_user_logged_in()
{
	return isUserLoggedIn();
}
/* Loads the search module. */
function get_search_form( $preHtml='', $postHtml='', $params=array() )
{
	getSearchForm( 'search', $preHtml, $postHtml, $params );
}
/* Returns true if on home page, false if not */
function is_home()
{
	return isHome();
}
/* Includes sidebar.php */
function get_sidebar( $name='' )
{
	getSidebar( $name );
}
/* Includes $filename */
function get_file( $filename )
{
	getFile( $filename );
}

?>