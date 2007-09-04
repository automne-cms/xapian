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
// $Id: catalog.php,v 1.1.1.1 2007/09/04 15:01:29 sebastien Exp $

/**
  * Class CMS_ase_interface_catalog
  * 
  * Represent an interface between ase module and all others
  *
  * @package CMS
  * @subpackage module
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

class CMS_ase_interface_catalog extends CMS_grandFather {
	
	function &getModuleInterface($codename) {
		$interfaceName = (!CMS_modulesCatalog::isPolymod($codename)) ? 'CMS_'.$codename.'_ase' : 'CMS_polymod_ase';
		if (!class_exists($interfaceName)) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : module '.$codename.' does have interface with ASE module ...');
			return false;
		}
		return new $interfaceName($codename);
	}
	
	function getActiveModules() {
		static $activeModules;
		if (!isset($activeModules)) {
			$activeModules = array();
			$modules = CMS_modulesCatalog::getAll();
			foreach ($modules as $module) {
				if ($module->getCodename() != MOD_ASE_CODENAME) {
					if (CMS_ase_interface_catalog::moduleHasInterface($module->getCodename())) {
						//load interface and check for module activity
						$moduleInterface = CMS_ase_interface_catalog::getModuleInterface($module->getCodename());
						if ($moduleInterface->isActive()) {
							$activeModules[$module->getCodename()] = $module;
						}
					}
				}
			}
		}
		return $activeModules;
	}
	
	function moduleHasInterface($codename) {
		$interfaceName = (!CMS_modulesCatalog::isPolymod($codename)) ? 'CMS_'.$codename.'_ase' : 'CMS_polymod_ase';
		if (!class_exists($interfaceName)) {
			return false;
		}
		return true;
	}
	
	function getIndexablesModules() {
		$modules = CMS_modulesCatalog::getAll();
		$indexableModules = array();
		foreach ($modules as $module) {
			if (CMS_ase_interface_catalog::moduleHasInterface($module->getCodename())) {
				$indexableModules[$module->getCodename()] = $module->getCodename();
			}
		}
		return $indexableModules;
	}
	
	function reindexModule($codename) {
		//get Interface
		if (!($moduleInterface = CMS_ase_interface_catalog::getModuleInterface($codename))) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : no interface for module '.$codename);
			return false;
		}
		//query module interface to get all objects to reindex
		$indexableUIDs = $moduleInterface->getShortUIDList();
		//check if module use an indexation in two times 
		if (method_exists($moduleInterface, 'getIndexInfos')) {
			//Two times methods : the first is to get objects infos
			foreach ($indexableUIDs as $indexableUID) {
				//add script to query object for precise indexable content
				CMS_scriptsManager::addScript(MOD_ASE_CODENAME, array('task' => 'queryModule', 'uid' => $indexableUID, 'module' => $codename));
			}
		} else {
			//One times methods : directly index content
			foreach ($indexableUIDs as $indexableUID) {
				//add script to indexation
				CMS_scriptsManager::addScript(MOD_ASE_CODENAME, array('task' => 'reindex', 'uid' => $indexableUID, 'module' => $codename));
			}
		}
		//then launch scripts execution
		CMS_scriptsManager::startScript();
		return true;
	}
	
	function reindexModuleDocument($parameters) {
		$document = new CMS_ase_document(array('uid' => $parameters['uid'], 'module' => $parameters['module']));
		$indexer = new CMS_XapianIndexer($document);
		return $indexer->index();
	}
	
	function deleteModuleDocument($parameters) {
		$return = true;
		$db = new CMS_XapianDB($parameters['module'], true);
		if (!$db->isWritable()) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : can not get database ... add task to queue list again');
			return false;
		}
		if (sizeof($parameters['deleteInfos'])) {
			foreach ($parameters['deleteInfos'] as $name => $value) {
				$return = (!$db->deleteDocuments('__'.strtoupper($name).'__:'.$value)) ? false : $return;
			}
		} else {
			$return = (!$db->deleteDocuments('__XID__:'.$parameters['module'].$parameters['uid'])) ? false : $return;
		}
		$db->endTransaction();
		return $return;
	}
}
?>