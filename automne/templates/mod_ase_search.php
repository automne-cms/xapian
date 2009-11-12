<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Automne (TM)                                                         |
// +----------------------------------------------------------------------+
// | Copyright (c) 2000-2006 WS Interactive                               |
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
// $Id: mod_ase_search.php,v 1.17 2009/11/12 15:48:14 sebastien Exp $

/**
  * Template CMS_ase_search
  *
  * Represent a general search formular for Automne Search Engine module
  *
  * @package CMS
  * @subpackage module
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

//use this language if $_REQUEST['language'] or filter "language:" are not specified.
//This is used also for texts language
$defaultSearchLanguage = $mod_ase["language"];

//force loading module ase
if (!class_exists('CMS_module_ase')) {
	die('Cannot find ase module ...');
}

//Message from ase module
define("MESSAGE_ASE_RESULTS_SEARCH", 27);
define("MESSAGE_ASE_RESULTS_RESULTCOUNT", 28);
define("MESSAGE_ASE_RESULTS_DIDYOUMEAN", 29);
define("MESSAGE_ASE_RESULTS_RESULTS", 30);
define("MESSAGE_ASE_RESULTS_INDEXED", 31);
define("MESSAGE_ASE_RESULTS_PUBLISHED", 32);
define("MESSAGE_ASE_RESULTS_EXPAND", 33);
define("MESSAGE_ASE_RESULTS_HELP", 34);
define("MESSAGE_ASE_RESULTS_PAGES", 35);
define("MESSAGE_ASE_RESULTS_NORESULTS", 36);
define("MESSAGE_ASE_RESULTS_SEARCHERROR", 37);
define("MESSAGE_ASE_RESULTS_RELEVANCE", 38);
define("MESSAGE_ASE_RESULTS_MORERELEVANT", 39);
define("MESSAGE_ASE_RESULTS_RELOAD_USING_THIS_DOC", 40);
define("MESSAGE_ASE_RESULTS_HELP_DETAIL", 41);

//load language
$cms_language = new CMS_language($defaultSearchLanguage);

// Delete $search if allready exists
if(isset($search)){
	unset($search);
}

$error = false;
if (isset($_REQUEST['q']) && trim($_REQUEST['q'])) {
	$starttime = getmicrotime();
	$pageNB = (isset($_REQUEST['page']) && (int) $_REQUEST['page']) ? (int) $_REQUEST['page'] : 1;
	
	//////////////////////////////////////////////////////////////////
	//      Here declare all modules to search (default : all)      //
	//////////////////////////////////////////////////////////////////
	
	$modules = array();
	//restrict search to given modules if any
	if (isset($_REQUEST['modules']) && is_array($_REQUEST['modules']) ) {
		$availableModules = CMS_ase_interface_catalog::getActiveModules();
		foreach($_REQUEST['modules'] as $module) {
			if (isset($availableModules[$module])) {
				$modules[] = $module;
			}
		}
	} else {
		//else search on all active modules
		$modules = null;
	}
	
	//////////////////////////////////////////////////////////////////
	// Here declare all filters which apply on all queried modules  //
	//////////////////////////////////////////////////////////////////
	
	//Create search query
	$searchQuery = isset($_REQUEST['q']) ? trim($_REQUEST['q']) : '';
	if (isset($_REQUEST['language'])) {
		$searchQuery .= ' language:'.$_REQUEST['language'];
		$searchLanguage = $_REQUEST['language'];
	} else {
		$searchLanguage = $defaultSearchLanguage;
	}
	if (isset($_REQUEST['filetype'])) {
		$filetypes = explode(',',$_REQUEST['filetype']);
		$searchQuery .= ' AND (filetype:'.implode(' OR filetype:',$filetypes).')';
	}
	
	//////////////////////////////////////////////////////////////////
	//                       Declare Search                         //
	//////////////////////////////////////////////////////////////////
	
	$search = new CMS_XapianQuery($searchQuery, $modules, $searchLanguage);
	
	//////////////////////////////////////////////////////////////////
	//          Here declare all filters for each modules           //
	//////////////////////////////////////////////////////////////////
	
	//Filters on pages
	if ((isset($_REQUEST['pageRoot']) && sensitiveIO::isPositiveInteger($_REQUEST['pageRoot'])) || isset($_REQUEST['PublicAfter']) || isset($_REQUEST['PublicBefore'])) {
		//load module interface
		if ($moduleInterface = CMS_ase_interface_catalog::getModuleInterface(MOD_STANDARD_CODENAME)) {
			if (isset($_REQUEST['pageRoot']) && sensitiveIO::isPositiveInteger($_REQUEST['pageRoot'])) {
				//add filter on root page ID
				$moduleInterface->addFilter('root', $_REQUEST['pageRoot']);
			}
			if (isset($_REQUEST['PublicAfter'])) {
				$dateAfter = new CMS_date();
				$dateAfter->setFormat($cms_language->getDateFormat());
				if ($dateAfter->setLocalizedDate($_REQUEST['PublicAfter'])) {
					$moduleInterface->addFilter('publication date after', $dateAfter);
				}
			}
			if (isset($_REQUEST['PublicBefore'])) {
				$dateBefore = new CMS_date();
				$dateBefore->setFormat($cms_language->getDateFormat());
				if ($dateBefore->setLocalizedDate($_REQUEST['PublicBefore'])) {
					$moduleInterface->addFilter('publication date before', $dateBefore);
				}
			}
			$search->setModuleInterface(MOD_STANDARD_CODENAME, $moduleInterface);
		}
	}
	
	//add expand Docs if any
	if (isset($_REQUEST['expandDocs'])) {
		$expandDocsIds = explode(',',$_REQUEST['expandDocs']);
		foreach ($expandDocsIds as $docid) {
			if ($docid) {
				$search->addRelevantDocument($docid);
			}
		}
	}
	//Then launch search
	if (!$search->query($pageNB, $resultsNumber)) {
		$error = true;
	}
	$time = getmicrotime() - $starttime;
}

$content = '
<div id="aseSearch">
<form name="search" action="'.$_SERVER['SCRIPT_NAME'].'" method="get">
<input type="text" style="width:60%;" name="q" value="'.(isset($_REQUEST['q']) ? htmlspecialchars($_REQUEST['q']) : '').'" />&nbsp;<input type="submit" class="button" value="'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_SEARCH, false, MOD_ASE_CODENAME).'" />
&nbsp;&nbsp;&nbsp;<a href="#help" onclick="document.getElementById(\'aseHelp\').style.display=\'block\';">'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_HELP, false, MOD_ASE_CODENAME).'</a>
</form>';

if (isset($search) && is_object($search)) {
	$startresultstime = getmicrotime();
	if ($search->getMatchesNumbers()) {
		$results = $search->getMatches();
		$max = ($search->getMatchesNumbers()-(($pageNB - 1) * $resultsNumber + 1) >= $resultsNumber) ? ($pageNB * $resultsNumber) : $search->getMatchesNumbers();
		$content .='
		<div class="right">'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_RESULTCOUNT, array((($pageNB - 1) * $resultsNumber + 1), $max, $search->getMatchesNumbers('~'), ), MOD_ASE_CODENAME).' ('.round($time,3).'s)</div>';
		//Spell correction
		if ($search->getCorrectedQueryString()) {
			$content .='<div class="left"><strong class="alert">'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_DIDYOUMEAN, false, MOD_ASE_CODENAME).' </strong><a rel="nofollow" href="'.$_SERVER['SCRIPT_NAME'].'?q='.urlencode($search->getCorrectedQueryString()).'">'.$search->getCorrectedQueryString().'</a></div>';
		}
		$content .='<h2>'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_RESULTS, false, MOD_ASE_CODENAME).'</h2>';
		
		//Results
		foreach ($results as $resultID => $result) {
			if ($fileSize = $search->getMatchValue($result, 'fileSize')) {
				$fileSize = ' - '.$fileSize;
			}
			$content .= '
			<h4>
				<div class="relevanceContainer"><div class="relevance" style="width:'.$search->getMatchValue($result, 'percent').'%;" title="'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_RELEVANCE, false, MOD_ASE_CODENAME).' '.$search->getMatchValue($result, 'percent').'%"></div></div>&nbsp;
				'.$search->getMatchValue($result, 'HTMLTitle').'
			</h4>
			<p>'.CMS_ase_interface::strChop($search->getMatchValue($result, 'indexedDatas'),300).'</p>
			<small>
				<a href="'.$search->getMatchValue($result, 'url').'" title="'.$search->getMatchValue($result, 'url').'">'.CMS_ase_interface::strChop($search->getMatchValue($result, 'url'),60,true).'</a>
				 - '.$search->getMatchValue($result, 'language').$fileSize.'
				 - '.$search->getMatchValue($result, 'percent').'%<br />
				 '.$cms_language->getMessage(MESSAGE_ASE_RESULTS_PUBLISHED, false, MOD_ASE_CODENAME).' '.$search->getMatchValue($result, 'pubDate', array('format' => $cms_language->getDateFormat()));
				if ((!isset($expandDocsIds) ||!is_array($expandDocsIds)) || (isset($expandDocsIds) && is_array($expandDocsIds) && !in_array($search->getMatchValue($result, 'docid'), $expandDocsIds))) {
					$content .= ' - <a rel="nofollow" href="'.$_SERVER['SCRIPT_NAME'].'?q='.(isset($_REQUEST['q']) ? urlencode($_REQUEST['q']) : '').'&amp;expandDocs='.(isset($_REQUEST['expandDocs']) ? urlencode($_REQUEST['expandDocs']).','.$search->getMatchValue($result, 'docid') : $search->getMatchValue($result, 'docid')).'" title="'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_RELOAD_USING_THIS_DOC, false, MOD_ASE_CODENAME).'">'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_MORERELEVANT, false, MOD_ASE_CODENAME).'</a>';
				} else {
				 	$content .= ' - <span class="alert">'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_MORERELEVANT, false, MOD_ASE_CODENAME).'</span>';
				}
			$content .= '
			</small><br />
			<br /><br />';
		}
		//Spell correction
		if ($search->getCorrectedQueryString()) {
			$content .='<div class="left"><strong class="alert">'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_DIDYOUMEAN, false, MOD_ASE_CODENAME).' </strong><a rel="nofollow" href="'.$_SERVER['SCRIPT_NAME'].'?q='.urlencode($search->getCorrectedQueryString()).'">'.$search->getCorrectedQueryString().'</a></div>';
		}
		//pages
		if ($resultsNumber < $search->getMatchesNumbers()) {
			$max = ($search->getMatchesNumbers()-(($pageNB - 1) * $resultsNumber + 1) >= $resultsNumber) ? ($pageNB * $resultsNumber) : $search->getMatchesNumbers();
			$toPage = 1;
			$content .= '<br /><div class="center">'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_PAGES, false, MOD_ASE_CODENAME).' ';
			//no more than 25 pages (500 first results max)
			while((($toPage-1)*$resultsNumber) <= $search->getMatchesNumbers() && $toPage <= 25) {
				if ($toPage != $pageNB) {
					$content .= '<a rel="nofollow" href="'.$_SERVER['SCRIPT_NAME'].'?q='.(isset($_REQUEST['q']) ? urlencode($_REQUEST['q']) : '').'&amp;page='.$toPage.'&amp;expandDocs='.(isset($_REQUEST['expandDocs']) ? urlencode($_REQUEST['expandDocs']) : '').'">'.$toPage.'</a>&nbsp;&nbsp; ';
				} else {
					$content .= '<strong>'.$toPage.'</strong>&nbsp;&nbsp;&nbsp;';
				}
				$toPage++;
			}
			$content .= '</div><br />';
		}
		
		//Eset
		$expandSet = $search->getExpandSet();
		if (sizeof($expandSet)) {
			$content .='<hr /><div class="center"><strong>'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_EXPAND, false, MOD_ASE_CODENAME).' </strong>';
			foreach ($expandSet as $term) {
				$content .='<a rel="nofollow" href="'.$_SERVER['SCRIPT_NAME'].'?q='.(isset($_REQUEST['q']) ? urlencode($_REQUEST['q']) : '').'+'.urlencode($term).'&amp;expandDocs='.(isset($_REQUEST['expandDocs']) ? urlencode($_REQUEST['expandDocs']) : '').'">'.$term.'</a>&nbsp; ';
			}
			$content .='</div>';
		}
		$content .= '
		<hr />
		<div class="right">'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_RESULTCOUNT, array((($pageNB - 1) * $resultsNumber + 1), $max, $search->getMatchesNumbers('~')), MOD_ASE_CODENAME).' ('.round($time,3).'s)</div>
		<br />
		<form name="searchbottom" action="'.$_SERVER['SCRIPT_NAME'].'" method="get">
		<input type="text" style="width:60%;" name="q" value="'.(isset($_REQUEST['q']) ? htmlspecialchars($_REQUEST['q']) : '').'" />&nbsp;<input type="submit" class="button" value="'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_SEARCH, false, MOD_ASE_CODENAME).'" />
		&nbsp;&nbsp;&nbsp;<a href="#help" onclick="document.getElementById(\'aseHelp\').style.display=\'block\';">'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_HELP, false, MOD_ASE_CODENAME).'</a>
		</form><br />';
		
	} else {
		if (!$error) {
			//Spell correction
			if ($search->getCorrectedQueryString()) {
				$content .='<div class="left"><strong class="alert">'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_DIDYOUMEAN, false, MOD_ASE_CODENAME).' </strong><a rel="nofollow" href="'.$_SERVER['SCRIPT_NAME'].'?q='.urlencode($search->getCorrectedQueryString()).'">'.$search->getCorrectedQueryString().'</a></div>';
			}
			$content .= '<div class="noresults">'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_NORESULTS, false, MOD_ASE_CODENAME).'</div>';
		} else {
			$content .= '<div class="noresults">'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_SEARCHERROR, false, MOD_ASE_CODENAME).'</div>';
		}
	}
}
$content .= '
<div id="aseHelp">
	<a name="help"></a>
	<h2>'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_HELP, false, MOD_ASE_CODENAME).'</h2>
	'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_HELP_DETAIL, array(implode(', ',CMS_filter_catalog::getTypes())), MOD_ASE_CODENAME).'
</div>
<!--
<h4>Operators :</h4>
<ul>
	<li><strong>AND : </strong><p>Matches documents that are matched by both operand</p></li>
	<li><strong>OR : </strong><p>Matches documents that are matched by either operand</p></li>
	<li><strong>NOT : </strong><p>Matches documents that are matched only by the left operand</p></li>
	<li><strong>XOR : </strong><p>Matches documents that are matched by one of the operand but not both</p></li>
	<li><strong>( and ) : </strong><p>Allows for the subgrouping of expressions</p></li>
	<li><strong>+ and - : </strong><p>Unary operators. Match terms that contain all operands prefixed by a plus sign and none of the words prefixed by a minus sign. <br />Example : CMS +Automne -Isens</p></li>
	<li><strong>NEAR : </strong><p>Matches documents in which the two operands are whitin ten words of each other</p></li>
	<li><strong>" " : </strong><p>Allow for phrase search</p></li>
	<li><strong>* : </strong><p>Wildcard (Joker sign).</p></li>
</ul>
<h4>Prefixes :</h4>
The following prefixes allow you to restrict your search on document\'s characteristics. Operand must follow the prefix (without spaces).
<ul>
	<li><strong>"title:" : </strong><p>Operand following this prefix will be into document\'s title <br />Example : title:Automne</p></li>
	<li><strong>"filetype:" : </strong><p>Matches documents will be in the given file format <br />Available filetypes are : '.implode(', ',CMS_filter_catalog::getTypes()).' <br />Example : filetype:pdf</p></li>
	<li><strong>"language:" : </strong><p>Matches documents will be in the given language code <br />Available languages are : fr, en <br />Example : language:fr</p></li>
	<li><strong>"page:" : </strong><p>Matches documents will be in the given page ID <br />Example : page:12</p></li>
	<li><strong>"root:" : </strong><p>Matches documents will be below the given page ID <br />Example : root:12</p></li>
</ul>
-->

<br /><br />';
if (defined('SYSTEM_DEBUG') && SYSTEM_DEBUG && isset($search) && is_object($search) && !$error && isset($cms_user) && is_object($cms_user) && $cms_user->getUserId() == ROOT_PROFILEUSER_ID) {
	$resultstime = getmicrotime() - $startresultstime;
	$content .='<hr />Displaying results in '.round($resultstime,3).'s.<br />';
	$content .='<strong>Query : </strong>'.$search->getQueryDesc();
	$content .='<hr />';
	$content .='<small><strong>Extended Query : </strong>'.$search->getQueryDesc(true).'</small>';
}
$content .= '</div>';
echo $content;
?>