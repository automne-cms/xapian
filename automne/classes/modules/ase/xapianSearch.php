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
// $Id: xapianSearch.php,v 1.9 2009/11/13 17:31:14 sebastien Exp $

/**
  * Class CMS_XapianQuery
  *
  * represent a Xapian database query
  * Xapian is an Open Source Probabilistic Information Retrieval library, 
  * released under the GPL. It's written in C++, and bindings are under 
  * development to allow use from other languages. See http://www.xapian.org for more.
  * 
  * It requires the Xapian PHP module to be loaded. To do this simply
  * download the Xapian core and SWIG bindings and compile or use distributed packages for your system.
  * 
  * @package CMS
  * @subpackage module
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

define("XAPIAN_QUERY_FLAG_BOOLEAN", XapianQueryParser::FLAG_BOOLEAN); 				//allow use of boolean : AND, OR, etc. and bracketted operations
define("XAPIAN_QUERY_FLAG_PHRASE", XapianQueryParser::FLAG_PHRASE);					//allow use of quoted phrase
define("XAPIAN_QUERY_FLAG_LOVEHATE", XapianQueryParser::FLAG_LOVEHATE);				//allow use of +, - operators
define("XAPIAN_QUERY_FLAG_BOOLEAN_ANY_CASE", XapianQueryParser::FLAG_BOOLEAN_ANY_CASE); //allow use of boolean : AND, and, OR, or, etc. and bracketted operations
define("XAPIAN_QUERY_FLAG_WILDCARD", XapianQueryParser::FLAG_WILDCARD);				//allow use of * wildcard
define("XAPIAN_QUERY_FLAG_PURE_NOT", XapianQueryParser::FLAG_PURE_NOT); 				//Allow queries such as 'NOT apples'.
define("XAPIAN_QUERY_FLAG_PARTIAL", XapianQueryParser::FLAG_PARTIAL); 				//Enable partial matching. (auto add wilcard on last word)
define("XAPIAN_QUERY_FLAG_SPELLING_CORRECTION", XapianQueryParser::FLAG_SPELLING_CORRECTION); //Enable spelling correction.
define("XAPIAN_QUERY_FLAG_SYNONYM", XapianQueryParser::FLAG_SYNONYM); 				//Enable synonym operator '~'.
define("XAPIAN_QUERY_FLAG_AUTO_SYNONYMS", XapianQueryParser::FLAG_AUTO_SYNONYMS); 	//Enable automatic use of synonyms for single terms.
define("XAPIAN_QUERY_FLAG_AUTO_MULTIWORD_SYNONYMS", XapianQueryParser::FLAG_AUTO_MULTIWORD_SYNONYMS); //Enable automatic use of synonyms for single terms and groups of terms.

define("XAPIAN_STEM_NONE", XapianQueryParser::STEM_NONE);
define("XAPIAN_STEM_SOME", XapianQueryParser::STEM_SOME);
define("XAPIAN_STEM_ALL", XapianQueryParser::STEM_ALL);

define("XAPIAN_QUERY_OP_OR", XapianQuery::OP_OR);
define("XAPIAN_QUERY_OP_AND", XapianQuery::OP_AND);
define("XAPIAN_QUERY_OP_AND_NOT", XapianQuery::OP_AND_NOT);
define("XAPIAN_QUERY_OP_XOR", XapianQuery::OP_XOR);
define("XAPIAN_QUERY_OP_AND_MAYBE", XapianQuery::OP_AND_MAYBE);
define("XAPIAN_QUERY_OP_FILTER", XapianQuery::OP_FILTER);
define("XAPIAN_QUERY_OP_NEAR", XapianQuery::OP_NEAR);
define("XAPIAN_QUERY_OP_PHRASE", XapianQuery::OP_PHRASE);
define("XAPIAN_QUERY_OP_ELITE_SET", XapianQuery::OP_ELITE_SET);

class CMS_XapianQuery extends CMS_grandFather {
	
	var $_query;
	var $_modules = array();
	var $_language = APPLICATION_DEFAULT_LANGUAGE;
	var $_enumerators;
	var $_enquire;
	var $_matches;
	var $_matchesInfos = null;
	var $_rSet;
	var $_modulesInterfaces = array();
	var $_filters = array();
	var $_relevantIds = array();
	var $_querydesc;
	var $_querylongdesc;
	var $_queryTerms = array();
	var $_correctedQueryString = '';
	var $_maxResults;
	var $_resultsPerPage;
	var $_minMatchResultsCheck;
	var $_minIndexableWordLength;
	var $_expandSetNumber;
	var $_uidOnly;
	var $_availableMatchInfos = array('docid','xid','uid','module','title','language','indexationDate','percent','relevance','position', 'indexedDatas', 'type');
	
	function CMS_XapianQuery($query, $modules = array(), $language, $returnUIDOnly = false) {
		//sanitize query string
		if ($language == 'ja' || $language == 'jp') {
			if ($return = CMS_XapianIndexer::tokenizeJapanese($query)) {
				$query = $return;
			}
		}
		$query = strtr($query,"_’'", 
							  "   ");
		$this->_query = strtolower(APPLICATION_DEFAULT_ENCODING) != 'utf-8' ? utf8_decode($query) : $query;
		if (is_array($modules) && sizeof($modules)) {
			$this->_modules = $modules;
		} else {
			//search on all active modules
			$this->_modules = array_keys(CMS_ase_interface_catalog::getActiveModules());
		}
		$this->_language = io::strtolower($language);
		$this->_uidOnly = ($returnUIDOnly) ? true : false;
		//set enumerators
		$this->_enumerators = XAPIAN_QUERY_FLAG_BOOLEAN
							| XAPIAN_QUERY_FLAG_PHRASE
							| XAPIAN_QUERY_FLAG_LOVEHATE
							| XAPIAN_QUERY_FLAG_BOOLEAN_ANY_CASE
							| XAPIAN_QUERY_FLAG_WILDCARD
							| XAPIAN_QUERY_FLAG_SPELLING_CORRECTION;
		if (!$this->_uidOnly) {
			//load module parameters
			$module = CMS_modulesCatalog::getByCodename(MOD_ASE_CODENAME);
			$this->_maxResults = (int) $module->getParameters('XAPIAN_SEARCH_MAX_RESULTS_PER_PAGES');
			$this->_resultsPerPage =  (int) $module->getParameters('XAPIAN_SEARCH_DEFAULT_RESULTS_PER_PAGES');
			$this->_minMatchResultsCheck =  (int) $module->getParameters('XAPIAN_SEARCH_MIN_MATCH_RESULTS_CHECK');
			$this->_expandSetNumber =  (int) $module->getParameters('XAPIAN_SEARCH_EXPAND_SET_MAX_NUMBER');
			$this->_minIndexableWordLength =  (int) $module->getParameters('DOCUMENT_MIN_INDEXABLE_WORD_LENGTH');
		}
	}
	
	function setModuleInterface($module, &$interface) {
		if (!is_a($interface, 'CMS_ase_interface')) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : module interface must be a valid CMS_ase_interface object');
			return false;
		}
		if (in_array($module, $this->_modules)) {
			$this->_modulesInterfaces[io::strtolower($module)] = $interface;
		}
		return true;
	}
	
	function addRelevantDocument($docid) {
		if (!sensitiveIO::isPositiveInteger($docid)) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : $docid must be a positive integer : '.$docid);
			return false;
		}
		$this->_relevantIds[$docid] = (int) $docid;
		return true;
	}
	
	function addFilters($module, $filters) {
		if (!is_array($filters)) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : $filters must be an array ...');
			return false;
		}
		if (!isset($this->_filters[$module])) {
			$this->_filters[$module] = array();
		}
		$this->_filters[$module] = array_merge_recursive($this->_filters[$module], $filters);
		return true;
	}
	
	function query(&$page, &$resultsNumber) {
		//check for rejected user agent (ie : robots must not be able of doing search)
		if (isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT']) {
			$module = CMS_modulesCatalog::getByCodename(MOD_ASE_CODENAME);
			$userAgentRejected = $module->getParameters('USER_AGENT_REJECTED');
			if ($userAgentRejected) {
				$userAgents = explode(';', $userAgentRejected);
				if (is_array($userAgents)) {
					foreach($userAgents as $userAgent) {
						if (io::strpos(io::strtolower($_SERVER['HTTP_USER_AGENT']), io::strtolower($userAgent)) !== false) {
							return false;
						}
					}
				}
			}
		}
		if (((int) $page) < 1) {
			$page = 1;
		}
		if ($resultsNumber) {
			$this->_resultsPerPage =& $resultsNumber;
		} else {
			$resultsNumber = $this->_resultsPerPage;
		}
		if (!$this->_uidOnly) {
			if ($this->_resultsPerPage > $this->_maxResults) {
				$this->_resultsPerPage = $this->_maxResults;
			}
		} else {
			$this->_minMatchResultsCheck = $this->_resultsPerPage;
		}
		//create query parser
		$queryParser = new XapianQueryParser();
		//set stemmer
		$queryParser->set_stemmer($this->_getStemmer());
		//set stemming strategy
		$queryParser->set_stemming_strategy(XAPIAN_STEM_SOME);
		//set stopper
		$stopper = $this->_getStopper();
		$queryParser->set_stopper($stopper);
		//append all databases then load modules interfaces if not already exists
		$count = 0;
		foreach($this->_modules as $module) {
			if (!$count) {
				$db = new CMS_XapianDB($module);
			} else {
				$db->addDatabase(new CMS_XapianDB($module));
			}
			//interfaces
			if (!isset($this->_modulesInterfaces[io::strtolower($module)]) || !is_object($this->_modulesInterfaces[io::strtolower($module)])) {
				//load module interface
				if (!($moduleInterface = CMS_ase_interface_catalog::getModuleInterface($module))) {
					$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : no interface for module '.$module);
					return false;
				}
				$this->_modulesInterfaces[io::strtolower($module)] = $moduleInterface;
			}
			$count++;
		}
		if (!is_a($db, 'CMS_XapianDB')) {
			return false;
		}
		//set DB to query parser
		$queryParser->set_database($db->getDatabase());
		if (!$this->_uidOnly) {
			//register query prefixes
			$queryParser->add_boolean_prefix('page', 		'__PAGE__:');
			$queryParser->add_boolean_prefix('filetype', 	'__TYPE__:');
			$queryParser->add_boolean_prefix('root', 		'__ANCESTOR__:');
			$queryParser->add_boolean_prefix('language', 	'__LANGUAGE__:');
			$queryParser->add_boolean_prefix('xid', 		'__XID__:');
			$queryParser->add_boolean_prefix('website', 	'__WEBSITE__:');
			$queryParser->add_boolean_prefix('module', 		'__MODULE__:');
			//beware, title IS NOT a boolean prefix, 
			$queryParser->add_prefix('title', 				'__TITLE__:');
		}
		//pre-check query to stop words in phrase query (not properly done by parse_query method ... maybe it is a bug ?)
		/*if (io::strpos($this->_query, '"') !== false) {
			$this->_query = $this->_filterStopWords($this->_query);
		}*/
		//set user query and enumerators then parse query
		$query = @$queryParser->parse_query($this->_query,$this->_enumerators);
		//get corrected query string if any
		$this->_correctedQueryString = strtolower(APPLICATION_DEFAULT_ENCODING) != 'utf-8' ? utf8_decode($queryParser->get_corrected_query_string()) : $queryParser->get_corrected_query_string();
		if (!is_object($query) || !$query->get_length()) {
			return false;
		}
		$this->_querydesc = $query->get_description();
		if (!$this->_uidOnly) {
			//get query terms
			$mTermsI = $query->get_terms_begin();
			while (!$mTermsI->equals($query->get_terms_end())) {
				$this->_queryTerms[] = $mTermsI->get_term();
				$mTermsI->next();
			}
		}
		//get filters from modules interfaces
		foreach (array_keys($this->_modulesInterfaces) as $module) {
			$this->addFilters($module, $this->_modulesInterfaces[$module]->getContextFilters());
		}
		
		//create filters query
		$queryFilter = $this->_getFiltersQuery();
		if ($queryFilter) {
			//add filters to query
			$query = new XapianQuery(XAPIAN_QUERY_OP_FILTER, $query, $queryFilter);
		}
		$this->_querylongdesc = $query->get_description();
		
		//create enquire object for DB
		$this->_enquire = new XapianEnquire($db->getDatabase());
		//set it the query
		$this->_enquire->set_query($query);
		//create relevance set
		$this->_rSet = new XapianRset();
		if (!$this->_uidOnly) {
			//add relevant documents to the relevance set
			foreach ($this->_relevantIds as $docid) {
				$this->_rSet->add_document($docid);
			}
		}
		//then get first $this->_maxResults matches (and at least first $this->_minMatchResultsCheck will be checked)
		$this->_matches = @$this->_enquire->get_MSet(($page-1) * $this->_resultsPerPage,$this->_resultsPerPage, $this->_minMatchResultsCheck, $this->_rSet);
		
		//if pages is over match numbers, decrease page number until results are founded
		while ($this->getMatchesNumbers() > 0 && $this->_matches->size() == 0 && $page > 1) {
			$page = (int) ceil($this->getMatchesNumbers() / $this->_resultsPerPage);
			$this->_matches = $this->_enquire->get_MSet(($page-1) * $this->_resultsPerPage,$this->_resultsPerPage, $this->_minMatchResultsCheck, $this->_rSet);
		}
		return true;
	}
	
	function getMatches() {
		if ($this->_matchesInfos === null) {
			//extract all matches infos
			$this->_getMatchesInfos();
		}
		return $this->_matchesInfos;
	}
	
	function getMatchesNumbers($aproxSign = '') {
		if (!is_object($this->_matches)) {
			return 0;
		}
		$matchNumber = $this->_matches->get_matches_estimated();
		return ($matchNumber > $this->_minMatchResultsCheck ? $aproxSign:'').$matchNumber;
	}
	
	function getQueryDesc($long = false) {
		if ($long) {
			return $this->_querylongdesc;
		} else {
			return $this->_querydesc;
		}
	}
	
	function getMatchValue(&$match, $value, $parameters = array()) {
		if (!isset($this->_availableMatchInfos) || !is_array($this->_availableMatchInfos) || (is_array($this->_availableMatchInfos) && in_array($value, $this->_availableMatchInfos))) {
			switch ($value) {
				case 'docid':
				case 'xid':
				case 'uid':
				case 'module':
				case 'percent':
				case 'relevance':
				case 'position':
					return $match[$value];
				break;
				case 'title':
					return $match['doc']->get_value(CMS_XapianIndexer::XAPIAN_VALUENO_TITLE);
				break;
				case 'language':
					return $match['doc']->get_value(CMS_XapianIndexer::XAPIAN_VALUENO_LANGUAGE);
				break;
				case 'indexationDate':
					if (!$parameters['format']) {
						return $match['doc']->get_value(CMS_XapianIndexer::XAPIAN_VALUENO_TIMESTAMP);
					} else {
						return date($parameters['format'], $match['doc']->get_value(CMS_XapianIndexer::XAPIAN_VALUENO_TIMESTAMP));
					}
				break;
				case 'indexedDatas':
					return $match['doc']->get_data();
				break;
				case 'type':
					return $match['doc']->get_value(CMS_XapianIndexer::XAPIAN_VALUENO_TYPE);
				break;
				case 'dateStart':
					return $match['doc']->get_value(CMS_XapianIndexer::XAPIAN_VALUENO_TYPE);
				break;
			}
			return '';
		} else {
			return $this->_modulesInterfaces[$match['module']]->getMatchValue($match, $value, $parameters);
		}
	}
	
	function getAvailableMatchValues($match) {
		if (!$this->_matchesInfos[$match['xid']]) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : unknown match ID : '.$match['xid']);
			return false;
		}
		return array_merge($this->_availableMatchInfos, $this->_modulesInterfaces[$match['module']]->getAvailableMatchValues($match));
	}
	
	function getExpandSet() {
		static $expandTerms;
		if ($this->_matchesInfos === null) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : Expand set can\'t be get before results. Use getMatches method first.');
			return false;
		}
		if (!isset($expandTerms)) {
			//then get relevant expand set from rSet
			$expands = $this->_enquire->get_eset(($this->_expandSetNumber*3),$this->_rSet,0);
			$eSetI = $expands->begin();
			$expandTerms = array();
			//get stemmer
			$stemmer = $this->_getStemmer();
			//get stopper
			$stopper = $this->_getStopper();
			//pr($this->_queryTerms);
			while (!$eSetI->equals($expands->end()) && sizeof($expandTerms) < $this->_expandSetNumber) {
			    $term = $eSetI->get_termname();
				//only words (starting with W) should compose expand set and it should not already in query nor in stopwords
				if (io::substr($term,0,1) !== 'Z' && io::substr($term,0,2) !== '__' && io::strlen($term) > 3 && !in_array('Z'.$stemmer->apply($term), $this->_queryTerms) && !in_array($term, $this->_queryTerms) && !$stopper->apply($term)) {
					$expandTerms[$stemmer->apply($term)] = strtolower(APPLICATION_DEFAULT_ENCODING) != 'utf-8' ? utf8_decode($term) : $term;
				}
				//iterate eSet
				$eSetI->next();
			}
		}
		return $expandTerms;
	}
	
	function getCorrectedQueryString() {
		return $this->_correctedQueryString;
	}
	
	function getQueryTerms() {
		return $this->_queryTerms;
	}
	
	function _getMatchesInfos() {
		$this->_matchesInfos = array();
		if (!is_object($this->_matches)) {
			return true;
		}
		$mSetI = $this->_matches->begin();
		$count=0;
		$uidList = array();
		while (!$mSetI->equals($this->_matches->end()) && $count < $this->_resultsPerPage) {
			$doc = $mSetI->get_document();
			if (!$this->_uidOnly) {
				$matchInfos = array(
					'docid'		=> $mSetI->get_docid(),
					'xid'		=> $doc->get_value(CMS_XapianIndexer::XAPIAN_VALUENO_XID),
					'uid'		=> $doc->get_value(CMS_XapianIndexer::XAPIAN_VALUENO_UID),
					'module'	=> $doc->get_value(CMS_XapianIndexer::XAPIAN_VALUENO_MODULE),
					'position'	=> $count,
					'percent'	=> $mSetI->get_percent(),
					'relevance'	=> $mSetI->get_weight(),
					'doc'		=> $doc,
				);
				$uidList[$matchInfos['module']][] = $matchInfos['uid'];
				$this->_matchesInfos[$matchInfos['xid']] = $matchInfos;
				//put the 5 top relevant mSet in rSet
				if ($this->_rSet->size() < 5) {
					$this->_rSet->add_document($mSetI->get_docid());
				}
			} else {
				$this->_matchesInfos[$doc->get_value(CMS_XapianIndexer::XAPIAN_VALUENO_XID)] = $doc->get_value(CMS_XapianIndexer::XAPIAN_VALUENO_UID);
			}
			//iterate mSet
			$mSetI->next();
			$count++;
		}
		if (!$this->_uidOnly) {
			//call module interface to send him the results which depends on it, so if it needs, it can prepare them
			foreach (array_keys($this->_modulesInterfaces) as $module) {
				if (isset($uidList[$module]) && is_array($uidList[$module]) && sizeof($uidList[$module])) {
					$this->_modulesInterfaces[$module]->setResultsUID($uidList[$module]);
				}
			}
		}
		return true;
	}
	
	function _getFiltersQuery() {
		$filtersQuery = null;
		foreach ($this->_filters as $module => $moduleFilters) {
			$inquery = $outquery = $moduleQuery = null;
			foreach ($moduleFilters as $status => $filters) {
				$typequery = null;
				foreach ($filters as $type => $typeFilters) {
					$type = io::strtoupper($type);
					$query = null;
					foreach ($typeFilters as $value) {
						$query = (is_object($query)) ? new XapianQuery(XAPIAN_QUERY_OP_OR, $query, new XapianQuery('__'.$type.'__:'.$value)) : new XapianQuery('__'.$type.'__:'.$value);
					}
					$typequery = (is_object($typequery)) ? new XapianQuery(XAPIAN_QUERY_OP_OR, $query, $typequery) : $query;
				}
				if (is_object($typequery) && $status == 'in') {
					$inquery = $typequery;
				} elseif (is_object($typequery) && $status == 'out') {
					$outquery = $typequery;
				}
			}
			if (is_array($moduleFilters) && sizeof($moduleFilters)) {
				$moduleQuery = new XapianQuery('__MODULE__:'.io::strtolower($module));
				//if inquery, add it
				if ($inquery) {
					$moduleQuery = new XapianQuery(XAPIAN_QUERY_OP_AND, $moduleQuery, $inquery);
				}
				//if outquery, add it
				if ($outquery) {
					$moduleQuery = new XapianQuery(XAPIAN_QUERY_OP_AND_NOT, $moduleQuery, $outquery);
				}
				$filtersQuery = ($filtersQuery) ? new XapianQuery(XAPIAN_QUERY_OP_OR, $filtersQuery, $moduleQuery) : $moduleQuery;
			}
		}
		return $filtersQuery;
	}
	
	function _getStopper() {
		static $stopper;
		if (!isset($stopper)) {
			//instanciate stoppper and add stopwords list
			$stopper = new XapianSimpleStopper();
			//get stop words for document language
			$stoplist = new CMS_file(PATH_MODULES_FILES_FS.'/'.MOD_ASE_CODENAME.'/stopwords/'.$this->_language.'.txt');
			if (!$stoplist->exists()) {
				$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : no stopwords list founded for language : '.$this->_language);
				return $stopper;
			}
			$stopwords = $stoplist->readContent('array');
			foreach ($stopwords as $stopword) {
				$stopper->add($stopword);
			}
		}
		return $stopper;
	}
	
	function _getStemmer() {
		$languageCode = io::strtolower($this->_language);
		$languagesMap = CMS_ase_document::languagesMap();
		if (isset($languagesMap[$languageCode]) && in_array($languagesMap[$languageCode], explode(' ', XapianStem::get_available_languages()))) {
			return new XapianStem($languagesMap[$languageCode]);
		}
		return new XapianStem('none');
	}
	
	/**
	  * Filters words according to language stoplist and remove one letter's words in list
	  *
	  * @param array &$words the words array to filter (by reference)
	  * @return boolean true on success, false on failure
	  * @access private
	  */
	function _filterStopWords($query) {
		$query = preg_replace_callback("#\"(.*)\"#U",array($this, '_removeStopWords'), $query);
		return $query;
	}
	
	function _removeStopWords($words) {
		$words = preg_split('/[\s[:punct:]]+/S', $words[0], 0, PREG_SPLIT_NO_EMPTY);
		$stopper = $this->_getStopper();
		foreach ($words as $key => $word) {
			if (io::strlen($word) <= $this->_minIndexableWordLength || $stopper->apply($word)) {
				unset($words[$key]);
			}
		}
		return '"'.implode(' ',$words).'"';
	}
}
?>
