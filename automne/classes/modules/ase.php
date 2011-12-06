<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Automne (TM)                                                         |
// +----------------------------------------------------------------------+
// | Copyright (c) 2000-2007 WS Interactive                               |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license.       |
// | The license text is bundled with this package in the file            |
// | LICENSE-GPL, and is available at through the world-wide-web at       |
// | http://www.gnu.org/copyleft/gpl.html.                                |
// +----------------------------------------------------------------------+
// | Author: Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>      |
// +----------------------------------------------------------------------+
//
// $Id: ase.php,v 1.19 2009/12/17 14:52:19 sebastien Exp $

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

//load minimal class to fill Automne requirements
class CMS_module_ase_default extends CMS_moduleValidation {
	const MESSAGE_TASK_QUERY_MODULE = 23;
	const MESSAGE_TASK_INDEX_MODULE_DOCUMENT = 24;
	const MESSAGE_TASK_DELETE_MODULE_DOCUMENT = 25;
	const MESSAGE_MOD_ASE_ROWS_EXPLANATION = 42;
	const MESSAGE_MOD_ASE_INDEXED_MODULES = 47;
	const MESSAGE_MOD_ASE_INDEXED_MODULES_DESC = 48;
	const MESSAGE_MOD_ASE_CONFIG = 49;
	const MESSAGE_MOD_ASE_ENGINE_CONFIG = 50;
	const MESSAGE_MOD_ASE_ENGINE_CONFIG_DESC = 51;
	
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
	
	/**
	  * Return a list of objects infos to be displayed in module index according to user privileges
	  *
	  * @return string : HTML scripts infos
	  * @access public
	  */
	function getObjectsInfos($user) {
		$objectsInfos = array();
		$cms_language = $user->getLanguage();
		$objectsInfos[] = array(
			'label'			=> $cms_language->getMessage(self::MESSAGE_MOD_ASE_INDEXED_MODULES, false, MOD_ASE_CODENAME),
			'adminLabel'	=> $cms_language->getMessage(self::MESSAGE_MOD_ASE_INDEXED_MODULES, false, MOD_ASE_CODENAME),
			'description'	=> $cms_language->getMessage(self::MESSAGE_MOD_ASE_INDEXED_MODULES_DESC, false, MOD_ASE_CODENAME),
			'objectId'		=> 'modules',
			'url'			=> PATH_ADMIN_MODULES_WR.'/'.MOD_ASE_CODENAME.'/index.php',
			'class'			=> 'atm-modules',
		);
		$objectsInfos[] = array(
			'label'			=> $cms_language->getMessage(self::MESSAGE_MOD_ASE_CONFIG, false, MOD_ASE_CODENAME),
			'adminLabel'	=> $cms_language->getMessage(self::MESSAGE_MOD_ASE_ENGINE_CONFIG, false, MOD_ASE_CODENAME),
			'description'	=> $cms_language->getMessage(self::MESSAGE_MOD_ASE_ENGINE_CONFIG_DESC, false, MOD_ASE_CODENAME),
			'objectId'		=> 'config',
			'url'			=> PATH_ADMIN_MODULES_WR.'/'.MOD_ASE_CODENAME.'/config.php',
			'class'			=> 'atm-server',
		);
		return $objectsInfos;
	}
}

//Check if Xapian exists
$xapianExists = false;
// Try to load Xapian extension if it's not already loaded.
if (!extension_loaded("xapian")) {
	if (function_exists('dl')) {
		if (APPLICATION_IS_WINDOWS) {
			if (@dl('php_xapian.dll')) $xapianExists = true;
		} else {
			// PHP_SHLIB_SUFFIX is available as of PHP 4.3.0, for older PHP assume 'so'.
			// It gives 'dylib' on MacOS X which is for libraries, modules are 'so'.
			if (PHP_SHLIB_SUFFIX === 'PHP_SHLIB_SUFFIX' || PHP_SHLIB_SUFFIX === 'dylib') {
				if (@dl('xapian.so')) $xapianExists = true;
			} else {
				if (@dl('xapian.'.PHP_SHLIB_SUFFIX)) $xapianExists = true;
			}
		}
	}
} else {
	$xapianExists = true;
}

//if Xapian exists, load full module
if ($xapianExists) {
	//Interfaces objects
	require_once(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/interfaces/common.php');
	//Automatic interfaces includes
	$interfaces_dir = dir(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/interfaces/');
	while (false !== ($file = $interfaces_dir->read())) {
		if (substr($file, strlen($file) - 3) == "php") {
			require_once(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/interfaces/'.$file);
		}
	}
	
	class CMS_module_ase extends CMS_module_ase_default 
	{
		/**
		  * Module autoload handler
		  *
		  * @param string $classname the classname required for loading
		  * @return string : the file to use for required classname
		  * @access public
		  */
		function load($classname) {
			static $classes;
			if (!isset($classes)) {
				$classes = array(
					/**
					 * Module main classes
					 */
					'xapian' 					=> PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/xapian.php',
					'cms_ase_document' 			=> PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/document.php',
					'cms_xapiandb' 				=> PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/xapianDB.php',
					'cms_xapianindexer' 		=> PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/xapianIndexer.php',
					'cms_xapianquery' 			=> PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/xapianSearch.php',
					'cms_cjktokenizer'			=> PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/cjkTokenizer.php',
					'cms_filter_common' 		=> PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/filters/common.php',
					'cms_filter_catalog' 		=> PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/filters/catalog.php',
					'cms_ase_interface_catalog' => PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/interfaces/catalog.php',
					'cms_ase_interface' 		=> PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/interfaces/common.php',
				);
			}
			$file = '';
			if (isset($classes[io::strtolower($classname)])) {
				$file = $classes[io::strtolower($classname)];
			} elseif (io::substr(io::strtolower($classname), 0, 11) == 'cms_filter_') {
				$type = io::substr(io::strtolower($classname), 11);
				if (file_exists(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/filters/'.$type.'.php')) {
					$file = PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/filters/'.$type.'.php';
				}
			} else if (io::substr(io::strtolower($classname), 0, 6) == 'xapian') {
				$file = PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/xapian.php';
			}
			return $file;
		}
		
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
				$this->raiseError('can\'t get Xapian version');
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
				case MODULE_TREATMENT_PAGEHEADER_TAGS :
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
				case MODULE_TREATMENT_PAGECONTENT_TAGS :
					switch ($visualizationMode) {
						default:
							$return = array (
								"atm-noindex" 			=> array("selfClosed" => false, "parameters" => array()),
							);
						break;
					}
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
						$this->raiseError('$treatedObject must be a CMS_row object');
						return false;
					}
					if (!is_a($treatmentParameters["page"],"CMS_page")) {
						$this->raiseError('$treatmentParameters["page"] must be a CMS_page object');
						return false;
					}
					if (!is_a($treatmentParameters["language"],"CMS_language")) {
						$this->raiseError('$treatmentParameters["language"] must be a CMS_language object');
						return false;
					}
					//Call module clientspace content
					$cs = new CMS_moduleClientspace($tag->getAttributes());
					//save the page ID who need this clientspace as a block, so we can add the header code of the module later.
					$this->moduleUsage($treatmentParameters["page"]->getID(), MOD_ASE_CODENAME, true);
					return $cs->getClientspaceData(MOD_ASE_CODENAME, $treatmentParameters["language"], $treatmentParameters["page"], $visualizationMode);
				break;
				case MODULE_TREATMENT_PAGEHEADER_TAGS:
					if (!is_a($treatedObject,"CMS_page")) {
						$this->raiseError('$treatedObject must be a CMS_page object');
						return false;
					}
					
					if ($visualizationMode == PAGE_VISUALMODE_HTML_PUBLIC) {
						//get page website
						$pageWebsite = $treatedObject->getWebsite();
						//search parameters
						if ($opensearch = $this->getParameters('XAPIAN_SEARCH_OPENSEARCH_PAGES')) {
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
							if (isset($searchs[$pageWebsite->getID()]) && $searchs[$pageWebsite->getID()]) {
								$title = APPLICATION_LABEL;
								if (!$pageWebsite->isMain()) {
									$title .= ' ('.$pageWebsite->getLabel().')';
								}
								$tagContent .= "\n".
								'	<link rel="search" href="'.$pageWebsite->getURL().PATH_MAIN_WR.'/'.MOD_ASE_CODENAME.'/opensearch.php?website='.$pageWebsite->getID().'&amp;search='.urlencode($searchs[$pageWebsite->getID()]).'" type="application/opensearchdescription+xml" title="'.htmlspecialchars($title).'" />'."\n";
							}
						}
					}
					//Add module CSS after atm-meta-tags content if page use module rows/templates
					if ($this->moduleUsage($treatedObject->getID(), MOD_ASE_CODENAME)) {
						if (file_exists(PATH_REALROOT_FS.'/css/modules/'.MOD_ASE_CODENAME.'.css')) {
							$tagContent .= 
							'	<!-- load the style of '.MOD_ASE_CODENAME.' module -->'."\n".
							'	<link rel="stylesheet" type="text/css" href="'.PATH_REALROOT_WR.'/css/modules/'.MOD_ASE_CODENAME.'.css" />'."\n";
						}
					}
					return $tagContent;
				break;
				case MODULE_TREATMENT_PAGECONTENT_TAGS:
					if (!is_a($treatedObject,"CMS_page")) {
						$this->raiseError('$treatedObject must be a CMS_page object');
						return false;
					}
					switch ($tag->getName()) {
						case 'atm-noindex':
							if ($visualizationMode == PAGE_VISUALMODE_HTML_PUBLIC_INDEXABLE) {
								return '';
							} else {
								return $tag->getInnerContent();
							}
						break;
					}
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
						 && ((!$treatedObject->getStatus() && isset($treatmentParameters['action']) && $treatmentParameters['action'] == 'delete') || ($treatedObject->getStatus() && ($treatedObject->getProposedLocation() == RESOURCE_LOCATION_DELETED || $treatedObject->getProposedLocation() == RESOURCE_LOCATION_ARCHIVED)))) {
						//check for module interface existence
						if (CMS_ase_interface_catalog::moduleHasInterface($treatmentParameters['module'])) {
							//get Interface
							if (!($moduleInterface = CMS_ase_interface_catalog::getModuleInterface($treatmentParameters['module']))) {
								$this->raiseError('no interface for module '.$treatmentParameters['module']);
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
						$this->raiseError('$treatmentParameters[\'module\'] must be set');
						return false;
					}
					if (!$treatmentParameters['module']) {
						$this->raiseError('$treatmentParameters[\'module\'] must be set');
						return false;
					}
					//if validation is accepted and in case of ressource publication
					if ($treatmentParameters['result'] == VALIDATION_OPTION_ACCEPT
						 && ((!$treatedObject->getStatus() && isset($treatmentParameters['action']) && $treatmentParameters['action'] == 'update') || ($treatedObject->getStatus() && $treatedObject->getLocation() != RESOURCE_LOCATION_DELETED && $treatedObject->getLocation() != RESOURCE_LOCATION_ARCHIVED))) {
						//check for module interface existence
						if (CMS_ase_interface_catalog::moduleHasInterface($treatmentParameters['module'])) {
							//get Interface
							if (!($moduleInterface = CMS_ase_interface_catalog::getModuleInterface($treatmentParameters['module']))) {
								$this->raiseError('no interface for module '.$treatmentParameters['module']);
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
					$modulesCode[MOD_ASE_CODENAME] = $treatmentParameters["language"]->getMessage(self::MESSAGE_MOD_ASE_ROWS_EXPLANATION, false, MOD_ASE_CODENAME);
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
				}
				return true;
			} elseif ($parameters['task'] == 'reindex') {
				//get module interface
				if (!($moduleInterface = CMS_ase_interface_catalog::getModuleInterface($parameters['module']))) {
					$this->raiseError('no interface for module '.$parameters['module']);
					return false;
				}
				//reindex document
				if (!$moduleInterface->reindexModuleDocument($parameters)) {
					$this->raiseError('cannot index document. Module : '.$parameters['module'].', uid : '.$parameters['uid']);
				}
				return true;
			} elseif ($parameters['task'] == 'delete') {
				//get module interface
				if (!($moduleInterface = CMS_ase_interface_catalog::getModuleInterface($parameters['module']))) {
					$this->raiseError('no interface for module '.$parameters['module']);
					return false;
				}
				//delete document from index
				if (!$moduleInterface->deleteModuleDocument($parameters)) {
					$this->raiseError('cannot delete document. Module : '.$parameters['module'].', uid : '.$parameters['uid']);
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
				$this->_raiseError('no interface for module '.$parameters['module']);
				return false;
			}
			if ($parameters['task'] == 'queryModule') {
				return $cms_language->getMessage(self::MESSAGE_TASK_QUERY_MODULE, array($moduleInterface->getTitle($parameters['uid'])), MOD_ASE_CODENAME);
			} elseif ($parameters['task'] == 'reindex') {
				return $cms_language->getMessage(self::MESSAGE_TASK_INDEX_MODULE_DOCUMENT, array($moduleInterface->getTitle($parameters['uid'])), MOD_ASE_CODENAME);
			} elseif ($parameters['task'] == 'delete') {
				return $cms_language->getMessage(self::MESSAGE_TASK_DELETE_MODULE_DOCUMENT, array($moduleInterface->getTitle($parameters['uid'])), MOD_ASE_CODENAME);
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
	class CMS_module_ase extends CMS_module_ase_default{}
}
?>