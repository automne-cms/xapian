<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Automne (TM)                                                         |
// +----------------------------------------------------------------------+
// | Copyright (c) 2000-2005 WS Interactive                               |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license,       |
// | or (at your discretion) to version 3.0 of the PHP license.           |
// | The first is bundled with this package in the file LICENSE-GPL, and  |
// | is available at through the world-wide-web at                        |
// | http://www.gnu.org/copyleft/gpl.html.                                |
// | The later is bundled with this package in the file LICENSE-PHP, and  |
// | is available at through the world-wide-web at                        |
// | http://www.php.net/license/3_0.txt.                                  |
// +----------------------------------------------------------------------+
// | Author: Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>      |
// +----------------------------------------------------------------------+
//
// $Id: catalog.php,v 1.1.1.1 2007/09/04 15:01:29 sebastien Exp $

/**
  * static Class CMS_filter_catalog
  *
  * catalog of filters objects
  *
  * @package CMS
  * @subpackage module
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

class CMS_filter_catalog
{
	/**
	  * Gets all filters
	  *
	  * @return array(string "CMS_filter_{type}" => object CMS_filter_{type})
	  * @access public
	  * @static
	  */
	function getFilters() {
		$filtersNames = CMS_filter_catalog::getFiltersNames();
		$return = array();
		foreach($filtersNames as $aFilterName) {
			$return[$aFilterName] = new $aFilterName();
		}
		return $return;
	}
	
	/**
	  * Gets all available filters class names
	  *
	  * @return array(string "CMS_filter_{type}")
	  * @access public
	  * @static
	  */
	function getFiltersNames() {
		//Automatic listing
		$excludedFiles = array(
			'catalog.php', //this file
			'common.php',  //filters common file
		);
		$packages_dir = dir(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/filters/');
		while (false !== ($file = $packages_dir->read())) {
			if (substr($file, - 4) == ".php" && !in_array($file, $excludedFiles) && class_exists('CMS_filter_'.substr($file, 0, -4))) {
				$filtersCatalog[] = 'CMS_filter_'.substr($file, 0, -4);
			}
		}
		return $filtersCatalog;
	}
	
	
	/**
	  * Return all supported type
	  *
	  * @return array(string type)
	  * @access public
	  * @static
	  */
	function getTypes() {
		static $supportedTypes;
		if(!isset($supportedTypes)) {
			$filters = CMS_filter_catalog::getFilters();
			foreach ($filters as $filterName => $filter) {
				if ($filter->isActive()) {
					$supportedTypes = array_merge($filter->getSupportedExtensions(), $supportedTypes);
				}
			}
		}
		return $supportedTypes;
	}
	
	/**
	  * Return a filter classname for a given document type
	  *
	  * @return string "CMS_filter_{type}" or false if none found
	  * @access public
	  * @static
	  */
	function getFilterForType($type) {
		static $filterForType;
		$type = strtolower($type);
		//if filter is already known, return it
		if (isset($filterForType[$type])) {
			return $filterForType[$type];
		}
		//first, try to get a classname like "CMS_filter_{type}"
		if (class_exists('CMS_filter_'.$type)) {
			$filterClass = 'CMS_filter_'.$type;
			$filter = new $filterClass();
			if ($filter->isActive() && in_array($type, $filter->getSupportedExtensions())) {
				$filterForType[$type] = $filterClass;
				return $filterForType[$type];
			}
		}
		//else search a filter for given type through  all active filters
		$filters = CMS_filter_catalog::getFilters();
		foreach ($filters as $filterName => $filter) {
			if ($filter->isActive() && in_array($type, $filter->getSupportedExtensions())) {
				$filterForType[$type] = $filterName;
				return $filterForType[$type];
			}
		}
		//filter not found so return false
		return false;
	}
}

?>