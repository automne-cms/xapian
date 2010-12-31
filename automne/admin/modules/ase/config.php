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
// $Id: config.php,v 1.2 2010/02/18 16:54:16 sebastien Exp $

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
define("MESSAGE_PAGE_MODULES", 999);
define("MESSAGE_PAGE_NO", 1083);
define("MESSAGE_PAGE_YES", 1082);
define("MESSAGE_PAGE_NONE", 195);
define("MESSAGE_PAGE_PAGE", 1328);
define("MESSAGE_PAGE_WEBSITE", 1511);

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
define("MESSAGE_PAGE_XAPIAN_JAPANESE_SUPPORT", 45);
define("MESSAGE_PAGE_MISSING_BINARY", 46);
define("MESSAGE_PAGE_XAPIAN_EXCLUDED_ROOTS", 55);
define("MESSAGE_PAGE_OPEN_SEARCH", 54);

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
//show version number
if (file_exists(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/VERSION')) {
	$content .= '<div style="position:absolute;top:2px;right:2px;font-size:8px;">v'.file_get_contents(PATH_MODULES_FS.'/'.MOD_ASE_CODENAME.'/VERSION').'</div>';
}
if (!($xapianVersion = $module->getXapianVersion())) {
	$content .= '<span class="atm-text-alert">'.$cms_language->getMessage(MESSAGE_PAGE_ERROR_XAPIAN_NOT_FOUND, array(APPLICATION_MAINTAINER_EMAIL), MOD_ASE_CODENAME).'</span>';
} else {
	//show version n°
	if (version_compare($xapianVersion, MOD_ASE_XAPIAN_MIN_VERSION , '>=' )) {
		$content .= '<h2>'.$cms_language->getMessage(MESSAGE_PAGE_XAPIAN_VERSION, array('<span style="color:green;">'.$xapianVersion.'</span>'), MOD_ASE_CODENAME).'</h2>';
	} else {
		$content .= '<h2>'.$cms_language->getMessage(MESSAGE_PAGE_XAPIAN_VERSION, array('<span class="atm-text-alert">'.$xapianVersion.'</span>'), MOD_ASE_CODENAME).' - '.$cms_language->getMessage(MESSAGE_PAGE_XAPIAN_MINVERSION, array(MOD_ASE_XAPIAN_MIN_VERSION), MOD_ASE_CODENAME).'</h2>';
	}
	//get active filters
	$content .= '<br /><h2>'.$cms_language->getMessage(MESSAGE_PAGE_ACTIVE_FILTERS, false, MOD_ASE_CODENAME).'</h2>
	<table>
		<tr class="atm-odd">
			<th>'.$cms_language->getMessage(MESSAGE_PAGE_FILTER_LABEL,false,MOD_ASE_CODENAME).'</th>
			<th>'.$cms_language->getMessage(MESSAGE_PAGE_FILTER_EXTENSIONS,false,MOD_ASE_CODENAME).'</th>
			<th>'.$cms_language->getMessage(MESSAGE_PAGE_MISSING_BINARY, false, MOD_ASE_CODENAME).'</th>
		</tr>';
	$filters = CMS_filter_catalog::getFilters();
	$count = 0;
	foreach ($filters as $filter) {
		$count++;
		$tr_class = ($count % 2 == 0) ? ' class="atm-odd"' : '';
		$content .= '<tr'.$tr_class.'><td>'.$filter->getLabel($cms_language).'</td><td>';
		if ($filter->isActive()) {
			$content .= implode(', ',$filter->getSupportedExtensions());
			$content .= '</td><td>-</td>';
		} else {
			$content .= '<strong>'.$cms_language->getMessage(MESSAGE_PAGE_INACTIVE_FILTER,false,MOD_ASE_CODENAME).'</strong>';
			$content .= '</td><td><em>'.implode(', ', $filter->getMissingBinaries()).'</em></td>';
		}
		$content .= '</tr>';
	}
	$error = '';
	$content .= '</table><br />';
	if (io::substr(CMS_patch::executeCommand('which chasen 2>&1', $error),0,1) == '/' && !$error) {
		$content .= '<h2>'.$cms_language->getMessage(MESSAGE_PAGE_XAPIAN_JAPANESE_SUPPORT, false, MOD_ASE_CODENAME).' : '.$cms_language->getMessage(MESSAGE_PAGE_YES).'</h2>';
	} else {
		$content .= '<h2>'.$cms_language->getMessage(MESSAGE_PAGE_XAPIAN_JAPANESE_SUPPORT, false, MOD_ASE_CODENAME).' : '.$cms_language->getMessage(MESSAGE_PAGE_NO).', '.$cms_language->getMessage(MESSAGE_PAGE_MISSING_BINARY, false, MOD_ASE_CODENAME).' : <em>Chasen</em></h2>';
	}
	$content .= '<br /><h2>'.$cms_language->getMessage(MESSAGE_PAGE_XAPIAN_EXCLUDED_ROOTS, false, MOD_ASE_CODENAME).'</h2>';
	$excludedPages = '';
	$excludedRoots = preg_split('#[,;]#', $module->getParameters('XAPIAN_RESULTS_EXCLUDED_ROOTS'));
	if (isset($excludedRoots) && is_array($excludedRoots) && sizeof($excludedRoots)) {
		foreach ($excludedRoots as $excludedRoot) {
			$page = CMS_tree::getPageById($excludedRoot);
			if ($page) {
				$excludedPages .= '<li><a href="'.$page->getURL().'" target="_blank">'.$cms_language->getMessage(MESSAGE_PAGE_PAGE).' \''.$page->getTitle().'\' ('.$page->getID().')</a></li>';
			}
		}
	}
	if ($excludedPages) {
		$content .= '<ul>'.$excludedPages.'</ul>';
	} else {
		$content .= $cms_language->getMessage(MESSAGE_PAGE_NONE).'<br />';
	}
	$content .= '<br /><h2>'.$cms_language->getMessage(MESSAGE_PAGE_OPEN_SEARCH, false, MOD_ASE_CODENAME).'</h2>';
	if ($opensearch = $module->getParameters('XAPIAN_SEARCH_OPENSEARCH_PAGES')) {
		//extract open search options
		//allowed format is /search.php or websiteID,/search.php or websiteID,pageID
		//you can add more couple of values separated with semi-colon
		$websitesSearch = explode(';',$opensearch);
		$searchs = array();
		$websites = CMS_websitesCatalog::getAll('order');
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
				if ($website && ($page || $url)) {
					$searchs[$website] = ($page) ? $page : $url;
				}
			} elseif(sizeof($search) == 1) {
				if (sensitiveIO::isPositiveInteger($search[0])) {
					$page = $search[0];
				} elseif(substr($search[0],0,1) == '/') {
					$url = $search[0];
				}
				foreach ($websites as $website) {
					$searchs[$website->getID()] = ($page) ? $page : $url;
				}
			}
		}
		$content .= '
		<table>
		<tr class="atm-odd">
			<th>'.$cms_language->getMessage(MESSAGE_PAGE_WEBSITE).'</th>
			<th>'.$cms_language->getMessage(MESSAGE_PAGE_PAGE).'</th>
		</tr>';
		$count = 0;
		foreach ($websites as $website) {
			$count++;
			$tr_class = ($count % 2 == 0) ? ' class="atm-odd"' : '';
			$content .= '<tr'.$tr_class.'><td>'.$website->getLabel().' <small>(id: '.$website->getID().')</small></td>';
			if (isset($searchs[$website->getID()]) && $searchs[$website->getID()]) {
				if (sensitiveIO::isPositiveInteger($searchs[$website->getID()])) {
					$page = CMS_tree::getPageById($searchs[$website->getID()]);
					if ($page) {
						$content .= '<td><a href="'.$page->getURL().'" target="_blank">'.$cms_language->getMessage(MESSAGE_PAGE_PAGE).' \''.$page->getTitle().'\' ('.$page->getID().')</a></td>';
					} else {
						$content .= '<td>'.$cms_language->getMessage(MESSAGE_PAGE_NONE).'</td>';
					}
				} elseif (substr($searchs[$pageWebsite->getID()],0,1) == '/') {
					$content .= '<td><a href="'.$website->getURL().$searchs[$website->getID()].'" target="_blank">'.$searchs[$website->getID()].'</a></td>';
				}
			} else {
				$content .= '<td>'.$cms_language->getMessage(MESSAGE_PAGE_NONE).'</td>';
			}
			$content .= '</tr>';
		}
		$content .= '</table>';
	} else {
		$content .= $cms_language->getMessage(MESSAGE_PAGE_NONE).'<br />';
	}
}

$content = sensitiveIO::sanitizeJSString($content);

$jscontent = <<<END
	var moduleObjectWindow = Ext.getCmp('{$winId}');
	
	var indexPanel = new Ext.Panel({
		autoScroll:			true,
		region:				'center',
		border:				false,
		bodyStyle: 			'padding:5px',
		cls:				'atm-help-panel',
		html:				'{$content}'
	});
	moduleObjectWindow.add(indexPanel);
	
	//redo windows layout
	moduleObjectWindow.doLayout();
	
	setTimeout(function(){
		//resultsPanel.syncSize();
		moduleObjectWindow.syncSize();
	}, 500);
END;
$view->addJavascript($jscontent);
$view->show();
?>