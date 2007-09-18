<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Automne (TM)                                                         |
// +----------------------------------------------------------------------+
// | Copyright (c) 2000-2007 WS Interactive                               |
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
// $Id: ase.php,v 1.7 2007/09/18 10:03:35 sebastien Exp $

/**
  * Class CMS_module_ase
  *
  * Represent Automne Search Engine module.
  *
  * @package CMS
  * @subpackage module
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

//Polymod Codename
define("MOD_ASE_CODENAME", "ase");
define("MOD_ASE_XAPIAN_MIN_VERSION", '1.0.2');

//Check for Xapian librarie before module loading
$xapianExists = false;
/*if(!class_exists('XapianDatabase')) {
	@dl ("xapian.so");
	if(class_exists('XapianDatabase')) {
		$xapianExists = true;
	}
} else {
	$xapianExists = true;
}*/


// Try to load Xapian extension if it's not already loaded.
if (!extension_loaded("xapian")) {
	if (strtolower(substr(PHP_OS, 0, 3)) === 'win') {
		if (dl('php_xapian.dll')) $xapianExists = true;
	} else {
		// PHP_SHLIB_SUFFIX is available as of PHP 4.3.0, for older PHP assume 'so'.
		// It gives 'dylib' on MacOS X which is for libraries, modules are 'so'.
		if (PHP_SHLIB_SUFFIX === 'PHP_SHLIB_SUFFIX' || PHP_SHLIB_SUFFIX === 'dylib') {
			if (dl('xapian.so')) $xapianExists = true;
		} else {
			if (dl('xapian.'.PHP_SHLIB_SUFFIX)) $xapianExists = true;
		}
	}
} else {
	$xapianExists = true;
}

/**
  * ASE requirements
  */
require_once(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/document.php');
require_once(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/xapianIndexer.php');
require_once(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/xapianSearch.php');
require_once(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/xapianDB.php');

//Filters objects
require_once(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/filters/common.php');
//Automatic filters includes
$filters_dir = dir(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/filters/');
while (false !== ($file = $filters_dir->read())) {
	if (substr($file, strlen($file) - 3) == "php") {
		require_once(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/filters/'.$file);
	}
}
require_once(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/interfaces/catalog.php');

//if Xapian exists, load module
if ($xapianExists) {
	//Message from ase module
	define("MESSAGE_TASK_QUERY_MODULE", 23);
	define("MESSAGE_TASK_INDEX_MODULE_DOCUMENT", 24);
	define("MESSAGE_TASK_DELETE_MODULE_DOCUMENT", 25);
	define("MESSAGE_MOD_ASE_ROWS_EXPLANATION", 42);
	
	//Interfaces objects
	require_once(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/interfaces/common.php');
	//Automatic interfaces includes
	$interfaces_dir = dir(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/interfaces/');
	while (false !== ($file = $interfaces_dir->read())) {
		if (substr($file, strlen($file) - 3) == "php") {
			require_once(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/interfaces/'.$file);
		}
	}
	
	class CMS_module_ase extends CMS_moduleValidation
	{
		/**
		  * Get Xapian librairie version
		  *
		  * @return String : the complete version number
		  * @access public
		  */
		function getXapianVersion() {
			if (function_exists('xapian_version_string')) {
				return xapian_version_string();
			} elseif (class_exists('Xapian')) {
				return Xapian::version_string();
			} else {
				$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : can\'t get Xapian version');
				return false;
			}
		}
		
		/** 
		  * Get the tags to be treated by this module for the specified treatment mode, visualization mode and object.
		  * @param integer $treatmentMode The current treatment mode (see constants on top of CMS_modulesTags class for accepted values).
		  * @param integer $visualizationMode The current visualization mode (see constants on top of cms_page class for accepted values).
		  * @return array of tags to be treated.
		  * @access public
		  */
		function getWantedTags($treatmentMode, $visualizationMode) 
		{
			$return = array();
			switch ($treatmentMode) {
				case MODULE_TREATMENT_PAGECONTENT_TAGS :
					$return = array (
						"atm-meta-tags" => array("selfClosed" => true, "parameters" => array()),
					);
				break;
				case MODULE_TREATMENT_BLOCK_TAGS :
					//Call module clientspace content
					$return = array (
						"block" => array("selfClosed" => false, "parameters" => array("module" => MOD_ASE_CODENAME)),
					);
				break;
			}
			return $return;
		}
		
		/** 
		  * Treat given content tag by this module for the specified treatment mode, visualization mode and object.
		  *
		  * @param string $tag The CMS_XMLTag.
		  * @param string $tagContent previous tag content.
		  * @param integer $treatmentMode The current treatment mode (see constants on top of CMS_modulesTags class for accepted values).
		  * @param integer $visualizationMode The current visualization mode (see constants on top of cms_page class for accepted values).
		  * @param object $treatedObject The reference object to treat.
		  * @param array $treatmentParameters : optionnal parameters used for the treatment. Usually an array of objects.
		  * @return string the tag content treated.
		  * @access public
		  */
		function treatWantedTag(&$tag, $tagContent, $treatmentMode, $visualizationMode, &$treatedObject, $treatmentParameters)
		{
			switch ($treatmentMode) {
				case MODULE_TREATMENT_BLOCK_TAGS:
					if (!is_a($treatedObject,"CMS_row")) {
						$this->_raiseError('CMS_module_'.MOD_ASE_CODENAME.' : treatWantedTag : $treatedObject must be a CMS_row object');
						return false;
					}
					if (!is_a($treatmentParameters["page"],"CMS_page")) {
						$this->_raiseError('CMS_module_'.MOD_ASE_CODENAME.' : treatWantedTag : $treatmentParameters["page"] must be a CMS_page object');
						return false;
					}
					if (!is_a($treatmentParameters["language"],"CMS_language")) {
						$this->_raiseError('CMS_module_'.MOD_ASE_CODENAME.' : treatWantedTag : $treatmentParameters["language"] must be a CMS_language object');
						return false;
					}
					//Call module clientspace content
					$cs = new CMS_moduleClientspace($tag->getAttributes());
					//save the page ID who need this clientspace as a block, so we can add the header code of the module later.
					$this->moduleUsage($treatmentParameters["page"]->getID(), MOD_ASE_CODENAME, true);
					return $cs->getClientspaceData(MOD_ASE_CODENAME, $treatmentParameters["language"], $treatmentParameters["page"], $visualizationMode);
				break;
				case MODULE_TREATMENT_PAGECONTENT_TAGS:
					if (!is_a($treatedObject,"CMS_page")) {
						$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : $treatedObject must be a CMS_page object');
						return false;
					}
					
					if ($visualizationMode == PAGE_VISUALMODE_HTML_PUBLIC && sensitiveIO::isPositiveInteger($this->getParameters(XAPIAN_SEARCH_OPENSEARCH_PAGES))) {
						//get page website
						$pageWebsite = $treatedObject->getWebsite();
						//search parameters
						if ($opensearch = $this->getParameters(XAPIAN_SEARCH_OPENSEARCH_PAGES)) {
							//extract open search options
							//allowed format is /search.php or websiteID,/search.php or websiteID,pageID
							//you can add more couple of values separated with semi-colon
							$websitesSearch = explode(';',$opensearch);
							foreach ($websitesSearch as $websiteSearch) {
								$website = $page = $url = '';
								$search = explode(',',$websiteSearch);
								if (sizeof($search) == 2 && sensitiveIO::isPositiveInteger($search[0])) {
									$website = $search[0];
									if (sensitiveIO::isPositiveInteger($search[1])) {
										$page = $search[1];
									} elseif(substr($search[1],0,1) == '/') {
										$url = $search[1];
									}
								} elseif(sizeof($search) == 1) {
									$website = $pageWebsite->getID();
									if (sensitiveIO::isPositiveInteger($search[0])) {
										$page = $search[0];
									} elseif(substr($search[0],0,1) == '/') {
										$url = $search[0];
									}
								}
								if ($website && ($page || $url)) {
									$searchs[$website] = ($page) ? $page : $url;
								}
							}
							//if a parameter exists for page website, add link tag to metas
							if ($searchs[$pageWebsite->getID()]) {
								$title = APPLICATION_LABEL;
								if (!$pageWebsite->isMain()) {
									$title .= ' ('.$pageWebsite->getLabel().')';
								}
								$tagContent .= "\n".
								'	<link rel="search" href="'.$pageWebsite->getURL().PATH_MODULES_FILES_WR.'/'.MOD_ASE_CODENAME.'/opensearch.php?website='.$pageWebsite->getID().'&amp;search='.urlencode($searchs[$pageWebsite->getID()]).'" type="application/opensearchdescription+xml" title="'.htmlspecialchars($title).'" />'."\n";
							}
						}
					}
					//Add module CSS after atm-meta-tags content if page use module rows/templates
					if ($this->moduleUsage($treatedObject->getID(), MOD_ASE_CODENAME)) {
						if (file_exists(PATH_REALROOT_FS.'/css/modules/'.MOD_ASE_CODENAME.'.css')) {
							$tagContent .= 
							'	<!-- load the style of '.MOD_ASE_CODENAME.' module -->'."\n".
							'	<link rel="stylesheet" type="text/css" href="/css/modules/'.MOD_ASE_CODENAME.'.css" />'."\n";
						}
					}
					return $tagContent;
				break;
			}
			//in case of no tag treatment, simply return it
			return $tag->getContent();
		}
		
		/**
		  * Return the module code for the specified treatment mode, visualization mode and object.
		  * 
		  * @param mixed $modulesCode the previous modules codes (usually string)
		  * @param integer $treatmentMode The current treatment mode (see constants on top of this file for accepted values).
		  * @param integer $visualizationMode The current visualization mode (see constants on top of cms_page class for accepted values).
		  * @param object $treatedObject The reference object to treat.
		  * @param array $treatmentParameters : optionnal parameters used for the treatment. Usually an array of objects.
		  *
		  * @return string : the module code to add
		  * @access public
		  */
		function getModuleCode($modulesCode, $treatmentMode, $visualizationMode, &$treatedObject, $treatmentParameters)
		{
			switch ($treatmentMode) {
				case MODULE_TREATMENT_BEFORE_VALIDATION_TREATMENT :
					//if validation is accepted and in case of ressource deletion/archive
					if ($treatmentParameters['result'] == VALIDATION_OPTION_ACCEPT
						&& ($treatedObject->getProposedLocation() == RESOURCE_LOCATION_DELETED || $treatedObject->getProposedLocation() == RESOURCE_LOCATION_ARCHIVED)) {
						//check for module interface existence
						if (CMS_ase_interface_catalog::moduleHasInterface($treatmentParameters['module'])) {
							//get Interface
							if (!($moduleInterface = CMS_ase_interface_catalog::getModuleInterface($treatmentParameters['module']))) {
								$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : no interface for module '.$treatmentParameters['module']);
								return false;
							}
							$deleteInfos = array();
							//query interface for delete infos for resource id
							$deleteInfos = $moduleInterface->getDeleteInfos($treatedObject->getID());
							//for each deleteInfos returned by module, create an delete script
							if (is_array($deleteInfos) && sizeof($deleteInfos)) {
								foreach ($deleteInfos as $deleteInfo) {
									//add script to query object for precise indexable content
									CMS_scriptsManager::addScript(MOD_ASE_CODENAME, array('task' => 'delete', 'uid' => $deleteInfo['uid'], 'module' => $deleteInfo['module'], 'deleteInfos' => $deleteInfo['deleteInfos']));
								}
								//then launch scripts execution
								CMS_scriptsManager::startScript();
							}
						}
					}
				break;
				case MODULE_TREATMENT_AFTER_VALIDATION_TREATMENT :
					if (!is_a($treatedObject, 'CMS_resource')) {
						$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : $treatmentParameters[\'module\'] must be set');
						return false;
					}
					if (!$treatmentParameters['module']) {
						$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : $treatmentParameters[\'module\'] must be set');
						return false;
					}
					//if validation is accepted and in case of ressource publication
					if ($treatmentParameters['result'] == VALIDATION_OPTION_ACCEPT
						 && ($treatedObject->getLocation() != RESOURCE_LOCATION_DELETED && $treatedObject->getLocation() != RESOURCE_LOCATION_ARCHIVED)) {
						//check for module interface existence
						if (CMS_ase_interface_catalog::moduleHasInterface($treatmentParameters['module'])) {
							//get Interface
							if (!($moduleInterface = CMS_ase_interface_catalog::getModuleInterface($treatmentParameters['module']))) {
								$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : no interface for module '.$treatmentParameters['module']);
								return false;
							}
							$indexInfos = array();
							//check if module use an indexation in two times to get index infos
							if (method_exists($moduleInterface, 'getIndexInfos')) {
								//query interface for indexable content for resource id
								$indexInfos = $moduleInterface->getIndexInfos($treatedObject->getID());
							} else {
								$indexInfos[] = array('uid' => $treatedObject->getID(), 'module' => $treatmentParameters['module']);
							}
							//for each indexInfos returned by module, create an index script
							if (is_array($indexInfos) && sizeof($indexInfos)) {
								foreach ($indexInfos as $indexInfo) {
									if (!isset($indexInfo['task'])) {
										//add script to query object for precise indexable content
										CMS_scriptsManager::addScript(MOD_ASE_CODENAME, array('task' => 'reindex', 'uid' => $indexInfo['uid'], 'module' => $indexInfo['module']));
									} else {
										//add script to query object for precise indexable content
										CMS_scriptsManager::addScript(MOD_ASE_CODENAME, $indexInfo);
									}
								}
								//then launch scripts execution
								CMS_scriptsManager::startScript();
							}
						}
					}
					return $modulesCode;
				break;
				case MODULE_TREATMENT_ROWS_EDITION_LABELS :
					$modulesCode[MOD_ASE_CODENAME] = $treatmentParameters["language"]->getMessage(MESSAGE_MOD_ASE_ROWS_EXPLANATION, false, MOD_ASE_CODENAME);
					return $modulesCode;
				break;
			}
			return $modulesCode;
		}
		
		/**
		  * Module script task : 
		  *		- query a module for indexable documents relative to a given uid a page
		  *		- (re)index a module document with his uid
		  *
		  * @param array $parameters the task parameters
		  *		task : string task to execute
		  *		module : string module codename for the task
		  *		uid : string module uid
		  * @return Boolean true/false
		  * @access public
		  */
		function scriptTask($parameters) {
			if ($parameters['task'] == 'queryModule') {
				//get Interface
				if (!($moduleInterface = CMS_ase_interface_catalog::getModuleInterface($parameters['module']))) {
					$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : no interface for module '.$parameters['module']);
					return false;
				}
				$indexInfos = $moduleInterface->getIndexInfos($parameters['uid']);
				//for each indexInfos returned by module, create an index script
				if (is_array($indexInfos) && sizeof($indexInfos)) {
					foreach ($indexInfos as $indexInfo) {
						if (!isset($indexInfo['task'])) {
							//add script to query object for precise indexable content
							CMS_scriptsManager::addScript(MOD_ASE_CODENAME, array('task' => 'reindex', 'uid' => $indexInfo['uid'], 'module' => $indexInfo['module']));
						} else {
							//add script to query object for precise indexable content
							CMS_scriptsManager::addScript(MOD_ASE_CODENAME, $indexInfo);
						}
					}
					//then launch scripts execution
					//CMS_scriptsManager::startScript();
				}
				return true;
			} elseif ($parameters['task'] == 'reindex') {
				if (!CMS_ase_interface_catalog::reindexModuleDocument($parameters)) {
					$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : cannot index document ... add task to queue list again');
					//add script to indexation
					CMS_scriptsManager::addScript(MOD_ASE_CODENAME, $parameters);
				}
				return true;
			} elseif ($parameters['task'] == 'delete') {
				if (!CMS_ase_interface_catalog::deleteModuleDocument($parameters)) {
					$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : cannot delete document ... add task to queue list again');
					//add script to indexation
					CMS_scriptsManager::addScript(MOD_ASE_CODENAME, $parameters);
				}
				return true;
			} else {
				return parent::scriptTask($parameters);
			}
		}
		
		/**
		  * Module script info : get infos for a given script parameters
		  *
		  * @param array $parameters the task parameters
		  *		task : string task to execute
		  *		module : string module codename for the task
		  *		uid : string module uid
		  * @return string : HTML scripts infos
		  * @access public
		  */
		function scriptInfo($parameters) {
			global $cms_language;
			if (!is_object($cms_language)) {
				return parent::scriptInfo($parameters);
			}
			//get Interface
			if (!($moduleInterface = CMS_ase_interface_catalog::getModuleInterface($parameters['module']))) {
				$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : no interface for module '.$parameters['module']);
				return false;
			}
			if ($parameters['task'] == 'queryModule') {
				return $cms_language->getMessage(MESSAGE_TASK_QUERY_MODULE, array($moduleInterface->getTitle($parameters['uid'])), MOD_ASE_CODENAME);
			} elseif ($parameters['task'] == 'reindex') {
				return $cms_language->getMessage(MESSAGE_TASK_INDEX_MODULE_DOCUMENT, array($moduleInterface->getTitle($parameters['uid'])), MOD_ASE_CODENAME);
			} elseif ($parameters['task'] == 'delete') {
				return $cms_language->getMessage(MESSAGE_TASK_DELETE_MODULE_DOCUMENT, array($moduleInterface->getTitle($parameters['uid'])), MOD_ASE_CODENAME);
			} else {
				return parent::scriptInfo($parameters);
			}
		}
		
		/**
		  * is module active ?
		  *
		  * @return boolean
		  * @access public
		  */
		function isActive() {
			return true;
		}
	}
} else {
	//load fake class to fill Automne requirements
	class CMS_module_ase extends CMS_moduleValidation {
		/**
		  * Get Xapian librairie version
		  *
		  * @return String : the complete version number
		  * @access public
		  */
		function getXapianVersion() {
			return false;
		}
		
		/**
		  * is module active ?
		  *
		  * @return boolean
		  * @access public
		  */
		function isActive() {
			return false;
		}
	}
}
?>