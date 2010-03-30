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
// $Id: catalog.php,v 1.7 2009/11/13 17:31:13 sebastien Exp $

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
	
	function getModuleInterface($codename) {
		$interfaceName = 'CMS_'.$codename.'_ase';
	    //load each existing interface, corresponding to all existing files in directory
	    $packages_dir = dir(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/interfaces/');
	    $aExceptions = array("catalog.php", "common.php");
        while (false !== ($file = $packages_dir->read())) {
	        if (io::substr($file, io::strlen($file) - 3) == "php" && !in_array($file, $aExceptions)) {
		        require_once(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/interfaces/'.$file);		       
	        }
        }
		if (!class_exists($interfaceName, false)) {
			if (CMS_modulesCatalog::isPolymod($codename)) {
				$interfaceName = 'CMS_polymod_ase';
				if (class_exists($interfaceName)) {
					return  new $interfaceName($codename);
				}
			}
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : module '.$codename.' does have interface with ASE module ...');
			return false;
		}
		return  new $interfaceName($codename);
	}
	
	function getActiveModules() {
		static $activeModules;
		if (!isset($activeModules)) {
			$activeModules = array();
			$modules = CMS_modulesCatalog::getAll('codename');
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
	    //load each existing interface, corresponding to all existing files in directory
	    $packages_dir = dir(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/interfaces/');
	    $aExceptions = array("catalog.php", "common.php");
        while (false !== ($file = $packages_dir->read())) {
	        if (io::substr($file, io::strlen($file) - 3) == "php" && !in_array($file, $aExceptions)) {
		        require_once(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/interfaces/'.$file);		       
	        }
        }
		if (!class_exists('CMS_'.$codename.'_ase', false)) {
			if (CMS_modulesCatalog::isPolymod($codename)) {
				if (class_exists('CMS_polymod_ase')) {
					return true;
				}
			}
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
}
?>
