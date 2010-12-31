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
// | Author: Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>	  |
// +----------------------------------------------------------------------+
//
// $Id: index.php,v 1.5 2009/11/17 12:33:26 sebastien Exp $

/**
  * PHP page : Load polymod items search window.
  * Used accross an Ajax request.
  * 
  * @package CMS
  * @subpackage admin
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

require_once(dirname(__FILE__).'/../../../../cms_rc_admin.php');

/**
  * Messages from standard module 
  */
define("MESSAGE_ERROR_MODULE_RIGHTS",570);
define("MESSAGE_PAGE_TITLE_MODULE", 248);
define("MESSAGE_PAGE_STATUS", 160);
define("MESSAGE_PAGE_NO", 1083);
define("MESSAGE_PAGE_YES", 1082);

//Message from ase module
define("MESSAGE_PAGE_INDEXED_MODULES", 47);
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
define("MESSAGE_PAGE_XAPIAN_JAPANESE_SUPPORT", 45);
define("MESSAGE_PAGE_MISSING_BINARY", 46);
define("MESSAGE_PAGE_REFRESH_INDEX", 52);
define("MESSAGE_PAGE_REINDEX_DESC", 53);

//load interface instance
$view = CMS_view::getInstance();
//set default display mode for this page
$view->setDisplayMode(CMS_view::SHOW_RAW);
//This file is an admin file. Interface must be secure
$view->setSecure();

$winId = sensitiveIO::request('winId');
$fatherId = sensitiveIO::request('fatherId');
$objectId = sensitiveIO::request('objectId');

if (!$winId) {
	CMS_grandFather::raiseError('Unknown window Id ...');
	$view->show();
}
//load module
$module = CMS_modulesCatalog::getByCodename(MOD_ASE_CODENAME);

if (!$module) {
	CMS_grandFather::raiseError('Unknown module : '.MOD_ASE_CODENAME);
	$view->show();
}
//CHECKS user has module clearance
if (!$cms_user->hasModuleClearance(MOD_ASE_CODENAME, CLEARANCE_MODULE_EDIT)) {
	CMS_grandFather::raiseError('User has no rights on module : '.MOD_ASE_CODENAME);
	$view->setActionMessage($cms_language->getmessage(MESSAGE_ERROR_MODULE_RIGHTS, array($module->getLabel($cms_language))));
	$view->show();
}

$content = '';
if (!($xapianVersion = $module->getXapianVersion())) {
	$content .= '<span class="atm-text-alert">'.$cms_language->getMessage(MESSAGE_PAGE_ERROR_XAPIAN_NOT_FOUND, array(APPLICATION_MAINTAINER_EMAIL), MOD_ASE_CODENAME).'</span>';
} else {
	$content .= '<h1>'.$cms_language->getMessage(MESSAGE_PAGE_INDEXED_MODULES, false, MOD_ASE_CODENAME).' :</h1>';
	//get all active modules
	$modules = CMS_ase_interface_catalog::getActiveModules();
	$content .= '<table id="atm-reindex">
	<tr class="atm-odd">
		<th>&nbsp;</th>
		<th>'.$cms_language->getMessage(MESSAGE_PAGE_DB_SIZE, false, MOD_ASE_CODENAME).'</th>
		<th>'.$cms_language->getMessage(MESSAGE_PAGE_DB_DOCUMENTS, false, MOD_ASE_CODENAME).'</th>';
	//reindex module
	if ($cms_user->hasAdminClearance(CLEARANCE_ADMINISTRATION_EDITVALIDATEALL)) {
		$content .= '<th>&nbsp;</th><th>&nbsp;</th>';
	}
	$content .= '</tr>';
	$count = 0;
	foreach ($modules as $module) {
		$count++;
		$tr_class = ($count % 2 == 0) ? ' class="atm-odd"' : '';
		$db = new CMS_XapianDB($module->getCodename());
		$content .= '
		<tr'.$tr_class.' class="">
			<th>'.$module->getLabel($cms_language).(!CMS_ase_interface_catalog::moduleHasInterface($module->getCodename()) ? ' : <strong>'.$cms_language->getMessage(MESSAGE_PAGE_INACTIVE, false, MOD_ASE_CODENAME).'</strong>' : '').'</th>
			<td class="atm-index-'.$module->getCodename().'-dbsize">'.$db->getDBSize().'o</td><td class="atm-index-'.$module->getCodename().'-doccount">'.$db->getDocCount().'</td>';
			//reindex module
			if ($cms_user->hasAdminClearance(CLEARANCE_ADMINISTRATION_EDITVALIDATEALL)) {
				$content .= '<td class="x-toolbar-cell atm-update-button" atm:module="'.$module->getCodename().'"></td><td class="x-toolbar-cell atm-reindex-button" atm:module="'.$module->getCodename().'"></td>';
			}
		$content .= '</tr>';
	}
	$content .='</table>';
}

$content = sensitiveIO::sanitizeJSString($content);

$controlerURL = PATH_ADMIN_MODULES_WR.'/'.MOD_ASE_CODENAME.'/controler.php';

$jscontent = <<<END
	var moduleObjectWindow = Ext.getCmp('{$winId}');
	
	var indexPanel = new Ext.Panel({
		autoScroll:			true,
		region:				'center',
		border:				false,
		bodyStyle: 			'padding:5px',
		cls:				'atm-help-panel',
		html:				'{$content}',
		listeners:			{'afterrender':function(){
			Ext.select('.atm-reindex-button', true, 'atm-reindex').each(function(el) {
				var button = new Ext.Toolbar.Button({
					renderTo:		el,
					text:			'{$cms_language->getJSMessage(MESSAGE_PAGE_ACTION_REINDEX, false, MOD_ASE_CODENAME)}',
					tooltip:		'{$cms_language->getJSMessage(MESSAGE_PAGE_REINDEX_DESC, false, MOD_ASE_CODENAME)}',
					handler:		function(button) {
						Automne.message.popup({
							msg: 				'{$cms_language->getJSMessage(MESSAGE_PAGE_ACTION_REINDEXCONFIRM, false, MOD_ASE_CODENAME)}',
							buttons: 			Ext.MessageBox.OKCANCEL,
							animEl: 			button.getEl(),
							closable: 			false,
							icon: 				Ext.MessageBox.WARNING,
							fn: 				function (button) {
								if (button == 'ok') {
									Automne.server.call({
										url:				'{$controlerURL}',
										params: 			{
											action:				'reindex',
											module:				this.getAttributeNS('atm', 'module')
										},
										fcnCallback: 		function(response, options, content) {
											if (content.success) {
												Ext.select('.atm-index-' + options.params.module + '-doccount', true, 'atm-reindex').first().update(content.doccount);
												Ext.select('.atm-index-' + options.params.module + '-dbsize', true, 'atm-reindex').first().update(content.dbsize);
											}
										},
										callBackScope:		this
									});
								}
							},
							scope:			this
						});
					},
					scope:			el
				});
			});
			Ext.select('.atm-update-button', true, 'atm-reindex').each(function(el) {
				var button = new Ext.Toolbar.Button({
					renderTo:		el,
					height:			21,
					tooltip:		'{$cms_language->getJSMessage(MESSAGE_PAGE_REFRESH_INDEX, false, MOD_ASE_CODENAME)}',
					iconCls:		'x-tbar-loading',
					handler:		function(button) {
						Automne.server.call({
							url:				'{$controlerURL}',
							params: 			{
								action:				'update',
								module:				this.getAttributeNS('atm', 'module')
							},
							fcnCallback: 		function(response, options, content) {
								if (content.success) {
									Ext.select('.atm-index-' + options.params.module + '-doccount', true, 'atm-reindex').first().update(content.doccount);
									Ext.select('.atm-index-' + options.params.module + '-dbsize', true, 'atm-reindex').first().update(content.dbsize);
								}
							},
							callBackScope:		this
						});
					},
					scope:			el
				});
			});
		}, scope:this}
	});
	moduleObjectWindow.add(indexPanel);
	
	//redo windows layout
	moduleObjectWindow.doLayout();
END;
$view->addJavascript($jscontent);
$view->show();
?>