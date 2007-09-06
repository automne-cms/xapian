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
// $Id: mod_ase_search.php,v 1.2 2007/09/06 16:43:55 sebastien Exp $

/**
  * Template CMS_ase_search
  *
  * Represent a general search formular for Automne Search Engine module
  *
  * @package CMS
  * @subpackage module
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

require_once($_SERVER["DOCUMENT_ROOT"]."/cms_rc_frontend.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/automne/classes/aseFrontEnd.php");
//use this language if $_REQUEST['language'] or filter "language:" are not specified.
//This is used also for texts language
$defaultSearchLanguage = $mod_ase["language"];

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

//load language
$cms_language = new CMS_language($defaultSearchLanguage);

$error = false;
if (trim($_REQUEST['q'])) {
	$starttime = getmicrotime();
	$pageNB = ((int) $_REQUEST['page']) ? (int) $_REQUEST['page'] : 1;
	$modules = array();
	//restrict search to given modules if any
	if (sizeof($_REQUEST['modules'])) {
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
	//Create search query
	$searchQuery = trim($_REQUEST['q']);
	if ($_REQUEST['language']) {
		$searchQuery .= ' language:'.$_REQUEST['language'];
		$searchLanguage = $_REQUEST['language'];
	} else {
		$searchLanguage = $defaultSearchLanguage;
	}
	if ($_REQUEST['filetype']) {
		$filetypes = explode(',',$_REQUEST['filetype']);
		$searchQuery .= ' AND (filetype:'.implode(' OR filetype:',$filetypes).')';
	}
	$search = new CMS_XapianQuery($searchQuery, $modules, $searchLanguage);
	//Filters on pages
	if (sensitiveIO::isPositiveInteger($_REQUEST['pageRoot']) || $_REQUEST['PublicAfter'] || $_REQUEST['PublicBefore']) {
		//load module interface
		if ($moduleInterface = CMS_ase_interface_catalog::getModuleInterface(MOD_STANDARD_CODENAME)) {
			if (sensitiveIO::isPositiveInteger($_REQUEST['pageRoot'])) {
				//add filter on root page ID
				$moduleInterface->addFilter('root', $_REQUEST['pageRoot']);
			}
			if ($_REQUEST['PublicAfter']) {
				$dateAfter = new CMS_date();
				$dateAfter->setFormat($cms_language->getDateFormat());
				if ($dateAfter->setLocalizedDate($_REQUEST['PublicAfter'])) {
					$moduleInterface->addFilter('publication date after', $dateAfter);
				}
			}
			if ($_REQUEST['PublicBefore']) {
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
	if ($_REQUEST['expandDocs']) {
		$expandDocsIds = explode(',',$_REQUEST['expandDocs']);
		foreach ($expandDocsIds as $docid) {
			$search->addRelevantDocument($docid);
		}
	}
	//Then launch search
	if (!$search->query($pageNB, $resultsNumber)) {
		$error = true;
	}
	$time = getmicrotime() - $starttime;
}

$content = '';
$content .= '
<form name="search" action="'.$_SERVER['SCRIPT_NAME'].'" method="get">
<input type="text" style="width:60%;" name="q" value="'.htmlspecialchars($_REQUEST['q']).'" />&nbsp;<input type="submit" value="'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_SEARCH, false, MOD_ASE_CODENAME).'" />
&nbsp;&nbsp;&nbsp;<a href="#help">'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_HELP, false, MOD_ASE_CODENAME).'</a>
</form>';

if (is_object($search)) {
	$startresultstime = getmicrotime();
	if ($search->getMatchesNumbers()) {
		$results = $search->getMatches();
		$max = ($search->getMatchesNumbers()-(($pageNB - 1) * $resultsNumber + 1) >= $resultsNumber) ? ($pageNB * $resultsNumber) : $search->getMatchesNumbers();
		$content .='
		<div class="right">'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_RESULTCOUNT, array((($pageNB - 1) * $resultsNumber + 1), $max, $search->getMatchesNumbers('~'), ), MOD_ASE_CODENAME).' ('.round($time,3).'s)</div>';
		//Spell correction
		if ($search->getCorrectedQueryString()) {
			$content .='<div class="left"><strong class="alert">'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_DIDYOUMEAN, false, MOD_ASE_CODENAME).' </strong><a href="'.$_SERVER['SCRIPT_NAME'].'?q='.urlencode($search->getCorrectedQueryString()).'">'.$search->getCorrectedQueryString().'</a></div>';
		}
		$content .='<h2>'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_RESULTS, false, MOD_ASE_CODENAME).'</h2>';
		
		$queryTerms = $search->getQueryTerms();
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
				 '.$cms_language->getMessage(MESSAGE_ASE_RESULTS_INDEXED, false, MOD_ASE_CODENAME).' '.$search->getMatchValue($result, 'indexationDate', array('format' => 'm-d-Y')).'
				 - '.$cms_language->getMessage(MESSAGE_ASE_RESULTS_PUBLISHED, false, MOD_ASE_CODENAME).' '.$search->getMatchValue($result, 'pubDate', array('format' => 'm-d-Y'));
				if (!is_array($expandDocsIds) || (is_array($expandDocsIds) && !in_array($search->getMatchValue($result, 'docid'), $expandDocsIds))) {
					$content .= ' - <a href="'.$_SERVER['SCRIPT_NAME'].'?q='.urlencode($_REQUEST['q']).'&amp;expandDocs='.($_REQUEST['expandDocs'] ? urlencode($_REQUEST['expandDocs']).','.$search->getMatchValue($result, 'docid') : $search->getMatchValue($result, 'docid')).'" title="'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_RELOAD_USING_THIS_DOC, false, MOD_ASE_CODENAME).'">'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_MORERELEVANT, false, MOD_ASE_CODENAME).'</a>';
				} else {
				 	$content .= ' - <span class="alert">'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_MORERELEVANT, false, MOD_ASE_CODENAME).'</span>';
				}
			$content .= '
			</small><br />
			<br /><br />';
		}
		//Spell correction
		if ($search->getCorrectedQueryString()) {
			$content .='<div class="left"><strong class="alert">'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_DIDYOUMEAN, false, MOD_ASE_CODENAME).' </strong><a href="'.$_SERVER['SCRIPT_NAME'].'?q='.urlencode($search->getCorrectedQueryString()).'">'.$search->getCorrectedQueryString().'</a></div>';
		}
		//pages
		if ($resultsNumber < $search->getMatchesNumbers()) {
			$max = ($search->getMatchesNumbers()-(($pageNB - 1) * $resultsNumber + 1) >= $resultsNumber) ? ($pageNB * $resultsNumber) : $search->getMatchesNumbers();
			$toPage = 1;
			$content .= '<br /><div class="center">'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_PAGES, false, MOD_ASE_CODENAME).' ';
			while((($toPage-1)*$resultsNumber) <= $search->getMatchesNumbers()) {
				if ($toPage != $pageNB) {
					$content .= '<a href="'.$_SERVER['SCRIPT_NAME'].'?q='.urlencode($_REQUEST['q']).'&amp;page='.$toPage.'&amp;expandDocs='.urlencode($_REQUEST['expandDocs']).'">'.$toPage.'</a>&nbsp;&nbsp;&nbsp;';
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
				$content .='<a href="'.$_SERVER['SCRIPT_NAME'].'?q='.urlencode($_REQUEST['q']).'+'.urlencode($term).'&amp;expandDocs='.urlencode($_REQUEST['expandDocs']).'">'.$term.'</a>&nbsp;&nbsp;';
			}
			$content .='</div>';
		}
		$content .= '
		<hr />
		<div class="right">'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_RESULTCOUNT, array((($pageNB - 1) * $resultsNumber + 1), $max, $search->getMatchesNumbers('~')), MOD_ASE_CODENAME).' ('.round($time,3).'s)</div>
		<br />
		<form name="searchbottom" action="'.$_SERVER['SCRIPT_NAME'].'" method="get">
		<input type="text" style="width:60%;" name="q" value="'.htmlspecialchars($_REQUEST['q']).'" />&nbsp;<input type="submit" value="'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_SEARCH, false, MOD_ASE_CODENAME).'" />
		&nbsp;&nbsp;&nbsp;<a href="#help">'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_HELP, false, MOD_ASE_CODENAME).'</a>
		</form>';
		
		$content .='<br />';
	} else {
		if (!$error) {
			$content .= '<div class="center"><strong class="alert">'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_NORESULTS, false, MOD_ASE_CODENAME).'</strong></div>';
		} else {
			$content .= '<div class="center"><strong class="alert">'.$cms_language->getMessage(MESSAGE_ASE_RESULTS_SEARCHERROR, false, MOD_ASE_CODENAME).'</strong></div>';
		}
	}
}
$content .= '
<a name="help"></a>
<h3>-= '.$cms_language->getMessage(MESSAGE_ASE_RESULTS_HELP, false, MOD_ASE_CODENAME).' =-</h3>
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
<p>Les accents, majuscules ainsi que les termes vides de sens (le, les, de, du, etc.) ne sont pas pris en compte. Les recherches sont <a href="http://fr.wikipedia.org/wiki/Lemmatisation" target="_blank" alt="Voir la définition de Wikipedia" title="Voir la définition de Wikipedia">lemmatisées</a> (cheval équivaut à chevaux, documentation équivaut à documenter et inversement).</p>
<h4>Affiner votre recherche :</h4>
<p>Les termes proposés pour affiner votre recherche sont des termes importants dans les premiers documents renvoyés par votre recherche.</p>
<p>La case à cocher à droite d\'un résultat de recherche vous permet d\'identifier les documents qui vous semblent correspondre le plus à ce que vous recherchez pour relancer une recherche qui en tiendra compte.</p>
<p>Si vos termes de recherche contiennent des mots dans une langue étrangère (anglais), sélectionner cette langue pour la recherche permettra une meilleur analyse lexicale de votre recherche et donc de meilleurs résultats.</p>
<h4>Opérateurs :</h4>
<table>
	<tr>
		<th>AND : </th>
		<td>Les documents résultant répondront aux deux termes.</td>
	</tr>
	<tr>
		<th>OR : </th>
		<td>Les documents résultant répondront à l\'un des deux termes.</td>
	</tr>
	<tr>
		<th>NOT : </th>
		<td>Les documents résultant répondront uniquement au terme de gauche.</td>
	</tr>
	<tr>
		<th>XOR : </th>
		<td>Les documents résultant répondront à l\'un des deux termes mais pas au deux.</td>
	</tr>
	<tr>
		<th>( et ) : </th>
		<td>Vous permet de grouper les expressions.</td>
	</tr>
	<tr>
		<th>+ et - : </th>
		<td>Opérateurs unaires. Les documents résultant répondront à tous les termes préfixés d\'un signe plus et à aucun des termes préfixés d\'un signe moins. <br />Exemple : +Cimpa -Airbus</td>
	</tr>
	<tr>
		<th>NEAR : </th>
		<td>Les documents résultant contiendront les deux termes à 10 mots d\'intervalle maximum.<br />Exemple : Cimpa NEAR Airbus</td>
	</tr>
	<tr>
		<th>" " : </th>
		<td>Permet une recherche de phrase exacte.</td>
	</tr>
	<tr>
		<th>* : </th>
		<td>Signe joker. Attention l\'emploi de cet opérateur peut ralentir votre recherche.</td>
	</tr>
</table>
<h4>Préfixes :</h4>
<p>Les préfixes suivants vous permettent de restreindre vos recherches sur certaines caractéristiques de documents. Le terme doit suivre le préfixe directement (sans espaces). Vous pouvez combiner ces préfixes avec tout type de recherche par mots clés classique.</p>
<table>
	<tr>
		<th>"title:" : </th>
		<td>Le terme suivant ce préfixe sera dans le titre du document.<br />Exemple : title:Airbus</td>
	</tr>
	<tr>
		<th>"filetype:" : </th>
		<td>Les documents résultant seront des fichiers du format donné <br />Les formats disponibles sont : '.implode(', ',CMS_filter_catalog::getTypes()).'<br />Exemple : filetype:pdf</td>
	</tr>
	<tr>
		<th>"language:" : </th>
		<td>Les documents résultant seront dans la langue donnée <br />Les langues disponibles sont : fr, en <br />Exemple : language:fr</td>
	</tr>
	<tr>
		<th>"page:" : </th>
		<td>Les documents résultant seront dans la page donnée<br />Example : page:12</td>
	</tr>
	<tr>
		<th>"root:" : </th>
		<td>Les documents résultant seront sous la page donnée<br />Example : root:12</td>
	</tr>
</table>
<br /><br />';
if (defined('SYSTEM_DEBUG') && SYSTEM_DEBUG && is_object($search) && !$error) {
	$resultstime = getmicrotime() - $startresultstime;
	$content .='<hr />Displaying results in '.round($resultstime,3).'s.<br />';
	$content .='<strong>Query : </strong>'.$search->getQueryDesc();
	$content .='<hr />';
	$content .='<small><strong>Extended Query : </strong>'.$search->getQueryDesc(true).'</small>';
}
echo $content;
?>