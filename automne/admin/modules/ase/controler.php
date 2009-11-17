<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Automne (TM)														  |
// +----------------------------------------------------------------------+
// | Copyright (c) 2000-2009 WS Interactive								  |
// +----------------------------------------------------------------------+
// | Automne is subject to version 2.0 or above of the GPL license.		  |
// | The license text is bundled with this package in the file			  |
// | LICENSE-GPL, and is available through the world-wide-web at		  |
// | http://www.gnu.org/copyleft/gpl.html.								  |
// +----------------------------------------------------------------------+
// | Author: Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>      |
// +----------------------------------------------------------------------+
//
// $Id: controler.php,v 1.1 2009/11/17 12:33:26 sebastien Exp $

/**
  * PHP page : Load polymod item interface
  * Used accross an Ajax request. Render a polymod item for edition
  *
  * @package CMS
  * @subpackage admin
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

require_once($_SERVER["DOCUMENT_ROOT"]."/cms_rc_admin.php");

//Controler vars
$action = sensitiveIO::request('action', array('reindex', 'update'));
$codename = sensitiveIO::request('module', CMS_modulesCatalog::getAllCodenames());

//load interface instance
$view = CMS_view::getInstance();
//set default display mode for this page
$view->setDisplayMode(CMS_view::SHOW_JSON);
//This file is an admin file. Interface must be secure
$view->setSecure();

$content = array('success' => false);

//instanciate module
$cms_module = CMS_modulesCatalog::getByCodename($codename);

//CHECKS user has module clearance
if (!$cms_user->hasModuleClearance(MOD_ASE_CODENAME, CLEARANCE_MODULE_EDIT)) {
	CMS_grandFather::raiseError('Error, user has no rights on module : '.MOD_ASE_CODENAME);
	$view->setContent($content);
	$view->show();
}

$cms_message = '';
switch ($action) {
	case 'reindex':
		if ($cms_user->hasAdminClearance(CLEARANCE_ADMINISTRATION_EDITVALIDATEALL)) {
			$db = new CMS_XapianDB($codename);
			if (!$db->hasError() && $db->reindex()) {
				$cms_message = $cms_language->getMessage(MESSAGE_ACTION_OPERATION_DONE);
				$content = array('success' => true, 'doccount' => '-', 'dbsize' => '-');
			}
		}
	break;
	case 'update':
		$db = new CMS_XapianDB($codename);
		if (!$db->hasError()) {
			$cms_message = $cms_language->getMessage(MESSAGE_ACTION_OPERATION_DONE);
			$content = array('success' => true, 'doccount' => $db->getDocCount(), 'dbsize' => $db->getDBSize().'o');
		}
	break;
}
//set user message if any
if ($cms_message) {
	$view->setActionMessage($cms_message);
}
//beware, here we add content (not set) because object saving can add his content to (uploaded file infos updated)
$view->addContent($content);
$view->show();
?>