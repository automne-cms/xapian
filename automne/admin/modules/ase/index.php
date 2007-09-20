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
// $Id: index.php,v 1.3 2007/09/20 09:30:11 sebastien Exp $

/**
  * PHP page : module polymod admin
  * Presents one module resource
  *
  * @package CMS
  * @subpackage polymod
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

require_once($_SERVER["DOCUMENT_ROOT"]."/cms_rc_admin.php");
require_once(PATH_ADMIN_SPECIAL_SESSION_CHECK_FS);

/**
  * Messages from standard module 
  */
define("MESSAGE_PAGE_TITLE_MODULE", 248);
define("MESSAGE_PAGE_STATUS", 160);
define("MESSAGE_PAGE_MODULES", 999);

//Message from ase module
define("MESSAGE_PAGE_TITLE", 2);
define("MESSAGE_PAGE_ERROR_XAPIAN_NOT_FOUND", 3);
define("MESSAGE_PAGE_XAPIAN_VERSION", 4);
define("MESSAGE_PAGE_ACTIVE_FILTERS", 5);
define("MESSAGE_PAGE_FILTER_LABEL", 14);
define("MESSAGE_PAGE_FILTER_EXTENSIONS", 15);
define("MESSAGE_PAGE_INACTIVE_FILTER", 16);
define("MESSAGE_PAGE_ACTIVE", 17);
define("MESSAGE_PAGE_INACTIVE", 18);
define("MESSAGE_PAGE_DB_SIZE", 19);
define("MESSAGE_PAGE_DB_DOCUMENTS", 20);
define("MESSAGE_PAGE_ACTION_REINDEX", 21);
define("MESSAGE_PAGE_ACTION_REINDEXCONFIRM", 22);
define("MESSAGE_PAGE_XAPIAN_MINVERSION", 26);

//CHECKS user has module clearance
if (!$cms_user->hasModuleClearance(MOD_ASE_CODENAME, CLEARANCE_MODULE_EDIT)) {
	header("Location: ".PATH_ADMIN_SPECIAL_ENTRY_WR."?cms_message_id=".MESSAGE_PAGE_CLEARANCE_ERROR."&".session_name()."=".session_name());
	exit;
}

//instanciate module
$cms_module = CMS_modulesCatalog::getByCodename(MOD_ASE_CODENAME);

// +----------------------------------------------------------------------+
// | Actions                                                              |
// +----------------------------------------------------------------------+
switch ($_REQUEST['cms_action']) {
	case 'reindex':
		if ($cms_user->hasAdminClearance(CLEARANCE_ADMINISTRATION_EDITVALIDATEALL)) {
			$db = new CMS_XapianDB($_REQUEST['module']);
			if ($db->reindex()) {
				$cms_message = $cms_language->getMessage(MESSAGE_ACTION_OPERATION_DONE);
			}
		}
	break;
}

// +----------------------------------------------------------------------+
// | Render                                                               |
// +----------------------------------------------------------------------+

$dialog = new CMS_dialog();
$content = '';
$dialog->setTitle($cms_language->getMessage(MESSAGE_PAGE_TITLE_MODULE, array($cms_module->getLabel($cms_language)))." :: ".$cms_language->getMessage(MESSAGE_PAGE_TITLE, false, MOD_ASE_CODENAME));

if ($cms_message) {
	$dialog->setActionMessage($cms_message);
}
//show version number
if (file_exists(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/VERSION')) {
	$content .= '<div style="position:absolute;top:2px;right:2px;font-size:8px;">v'.file_get_contents(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/VERSION').'</div>';
}
$content .= '<dialog-title type="admin_h2">'.$cms_language->getMessage(MESSAGE_PAGE_STATUS).' :</dialog-title><br />';
if (!($xapianVersion = $cms_module->getXapianVersion())) {
	$content .= '<span class="admin_text_alert">'.$cms_language->getMessage(MESSAGE_PAGE_ERROR_XAPIAN_NOT_FOUND, array(APPLICATION_MAINTAINER_EMAIL), MOD_ASE_CODENAME).'</span>';
} else {
	//show version n°
	if (version_compare($xapianVersion, MOD_ASE_XAPIAN_MIN_VERSION , '>=' )) {
		$content .= '<dialog-title type="admin_h3">'.$cms_language->getMessage(MESSAGE_PAGE_XAPIAN_VERSION, array('<span style="color:green;">'.$xapianVersion.'</span>'), MOD_ASE_CODENAME).'</dialog-title>';
	} else {
		$content .= '<dialog-title type="admin_h3">'.$cms_language->getMessage(MESSAGE_PAGE_XAPIAN_VERSION, array('<span style="color:red;font-weight:bold;">'.$xapianVersion.'</span>'), MOD_ASE_CODENAME).' - '.$cms_language->getMessage(MESSAGE_PAGE_XAPIAN_MINVERSION, array(MOD_ASE_XAPIAN_MIN_VERSION), MOD_ASE_CODENAME).'</dialog-title>';
	}
	//get active filters
	$content .= '<br /><dialog-title type="admin_h3">'.$cms_language->getMessage(MESSAGE_PAGE_ACTIVE_FILTERS, false, MOD_ASE_CODENAME).'</dialog-title>
	<table border="0" cellpadding="2" cellspacing="2">
		<tr>
			<th class="admin">'.$cms_language->getMessage(MESSAGE_PAGE_FILTER_LABEL,false,MOD_ASE_CODENAME).'</th>
			<th class="admin">'.$cms_language->getMessage(MESSAGE_PAGE_FILTER_EXTENSIONS,false,MOD_ASE_CODENAME).'</th>
		</tr>';
	$filters = CMS_filter_catalog::getFilters();
	$count = 0;
	foreach ($filters as $filter) {
		$count++;
		$td_class = ($count % 2 == 0) ? "admin_lightgreybg" : "admin_darkgreybg";
		$content .= '<tr><td class="'.$td_class.'">'.$filter->getLabel($cms_language).'</td><td class="'.$td_class.'">';
		if ($filter->isActive()) {
			$content .= implode(', ',$filter->getSupportedExtensions());
		} else {
			$content .= $cms_language->getMessage(MESSAGE_PAGE_INACTIVE_FILTER,false,MOD_ASE_CODENAME);
		}
		$content .= '</td></tr>';
	}
	$content .= '</table><br />';
	$content .= '<dialog-title type="admin_h2">'.$cms_language->getMessage(MESSAGE_PAGE_MODULES).' :</dialog-title>';
	//get all active modules
	$modules = CMS_ase_interface_catalog::getActiveModules();
	foreach ($modules as $module) {
		$content .= '<br /><dialog-title type="admin_h3">'.$module->getLabel($cms_language).' : '.(CMS_ase_interface_catalog::moduleHasInterface($module->getCodename()) ? '<strong>'.$cms_language->getMessage(MESSAGE_PAGE_ACTIVE, false, MOD_ASE_CODENAME).'</strong>' : $cms_language->getMessage(MESSAGE_PAGE_INACTIVE, false, MOD_ASE_CODENAME)).'</dialog-title>';
		$db = new CMS_XapianDB($module->getCodename());
		$content .= '<table cellspacing="5" cellpadding="5">
		<tr>
			<td class="admin">
			- '.$cms_language->getMessage(MESSAGE_PAGE_DB_SIZE, false, MOD_ASE_CODENAME).' : '.$db->getDBSize().'<br />
			- '.$cms_language->getMessage(MESSAGE_PAGE_DB_DOCUMENTS, false, MOD_ASE_CODENAME).' : '.$db->getDocCount().'
			</td>';
			//reindex module
			if ($cms_user->hasAdminClearance(CLEARANCE_ADMINISTRATION_EDITVALIDATEALL)) {
				$content .= '
				<form action="'.$_SERVER["SCRIPT_NAME"].'" method="post" onSubmit="return confirm(\''.addslashes($cms_language->getMessage(MESSAGE_PAGE_ACTION_REINDEXCONFIRM, array(htmlspecialchars($module->getLabel($cms_language))), MOD_ASE_CODENAME)) . '\')">
					<input type="hidden" name="module" value="'.$module->getCodename().'" />
					<input type="hidden" name="cms_action" value="reindex" />
					<td><input type="submit" class="admin_input_submit" value="'.$cms_language->getMessage(MESSAGE_PAGE_ACTION_REINDEX, false, MOD_ASE_CODENAME).'" /></td>
				</form>
				';
			}
		$content .= '
			</tr>
		</table>';
	}
}
$dialog->setContent($content);
$dialog->show();
?>