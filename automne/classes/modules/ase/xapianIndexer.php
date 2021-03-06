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
// $Id: xapianIndexer.php,v 1.11 2009/12/17 14:52:20 sebastien Exp $

/**
  * Class CMS_XapianIndexer
  *
  * Index a document in Xapian Database
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

class CMS_XapianIndexer extends CMS_grandFather {
	//Xapian Document values
	const XAPIAN_VALUENO_TIMESTAMP = 0;
	const XAPIAN_VALUENO_MODULE = 1;
	const XAPIAN_VALUENO_LANGUAGE = 2;
	const XAPIAN_VALUENO_TITLE = 3;
	const XAPIAN_VALUENO_UID = 4;
	const XAPIAN_VALUENO_XID = 5;
	const XAPIAN_VALUENO_TYPE = 6;
	
	/**
	 * Postings in currently indexed document
	 * @access	protected
	 */
	var $_postings = 0;
	
	/**
	 * Currently indexed document
	 * @var		object CMS_ase_document
	 * @access	protected
	 */
	var $_document;
	
	/**
	 * Xapian internal document
	 * @var		object XapianDocument
	 * @access	protected
	 */
	var $_xapianDocument;
	
	/**
	 * Currently database used to store indexed document
	 * @var		object CMS_XapianDB
	 * @access	protected
	 */
	var $_db;
	
	/**
	  * Constructor.
	  * initialize object.
	  *
	  * @param CMS_ase_document $document the document object to index
	  * @return void
	  * @access public
	  */
	function CMS_XapianIndexer(&$document) {
		if (!is_a($document, 'CMS_ase_document')) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : $document need to be a valid CMS_ase_document object');
			return false;
		}
		$this->_document =& $document;
	}
	
	/**
	  * Index document in Xapian DB.
	  *
	  * @return boolean true on success/false on failure
	  * @access public
	  */
	function index($returnIndexableContent = false) {
		if (!$this->_document->isFiltered()) {
			if (!$this->_document->filter()) {
				$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : can not filter document to plain-text format ...');
				return false;
			}
		}
		//create Xapian document
		$this->_xapianDocument = new XapianDocument();
		//load database
		if (!$this->_loadWritableDatabase()) {
			return false;
		}
		//XID
		if (!($xid = $this->_getXID())) {
			//end DB transaction (remove lock and destroy object)
			$this->_db->endTransaction();
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : can not get valid XID for document');
			return false;
		}
		//document displayed datas (only first 500 caracters, more is useless)
		$this->_xapianDocument->set_data($this->prepareDisplayedText($this->_document->getTextContent()));
		
		/* 
		 * document values
		 */
		
		//time
		$this->_xapianDocument->add_value(self::XAPIAN_VALUENO_TIMESTAMP, time());
		//module
		$this->_xapianDocument->add_value(self::XAPIAN_VALUENO_MODULE, $this->_document->getValue('module'));
		//UID
		$this->_xapianDocument->add_value(self::XAPIAN_VALUENO_UID, $this->_document->getValue('uid'));
		//language
		$this->_xapianDocument->add_value(self::XAPIAN_VALUENO_LANGUAGE, $this->_document->getValue('language'));
		//title
		$this->_xapianDocument->add_value(self::XAPIAN_VALUENO_TITLE, $this->_document->getValue('title'));
		//XID
		$this->_xapianDocument->add_value(self::XAPIAN_VALUENO_XID, $xid);
		//Type
		$this->_xapianDocument->add_value(self::XAPIAN_VALUENO_TYPE, $this->_document->getValue('type'));
		/* 
		 * document posting and attributes
		 */
		//add document XID as posting
		$this->_addPosting($xid);
		//add document module as posting
		$this->_addPosting('__MODULE__:'.$this->_document->getValue('module'));
		
		//add all modules attributes
		$this->_addAttributes($this->_document->getModuleAttributes());
		
		//INDEX DOCUMENT
		//get document stemmer
		$stemmer = $this->_document->getStemmer();
		//get document stopper
		$stopper = $this->_document->getStopper();
		//create Terms generator for document
		$indexer = new XapianTermGenerator();
		//set terms generator infos
		$indexer->set_flags(XapianTermGenerator::FLAG_SPELLING);
		$indexer->set_stemmer($stemmer);
		$indexer->set_stopper($stopper);
		$indexer->set_database($this->_db->getDatabase());
		$indexer->set_document($this->_xapianDocument);
		//index document content
		$indexer->index_text($this->prepareText($this->_document->getTextContent(), $this->_document->getValue('language')));
		//index document title
		//get WDF (within-document frequency) value for title from module parameters
		$module = CMS_modulesCatalog::getByCodename(MOD_ASE_CODENAME);
		$indexer->index_text($this->prepareText($this->_document->getValue('title'), $this->_document->getValue('language')), (int) $module->getParameters('DOCUMENT_TITLE_WDF'));
		
		//save document in Xapian DB
		$this->_writeToPersistence();
		//end DB transaction (remove lock and destroy object)
		$this->_db->endTransaction();
		if (!$returnIndexableContent) {
			return true;
		} else {
			return $this->prepareText($this->_document->getTextContent(), $this->_document->getValue('language'));
		}
	}
	
	/**
	  * This function prepare a string to be indexed or searched by Xapian
	  * - Tokenize Japanese if needed and possible
	  * - Strip underscore and quotes
	  * - Encode in uf8 if needed
	  * - Remove accents
	  *
	  * @param string $text : the text to prepare
	  * @return string $text : the prepared text
	  * @access public
	  * @static
	  */
	function prepareText($text, $language) {
		//eventually tokenize japanese text
		if (in_array($language, array('zh', 'ja', 'jp', 'ko'))) { //Chinese, Japanese, Korean
			if ($return = CMS_CJKTokenizer::tokenize($text)) {
				$text = $return;
			}
		}
		//convert text to utf-8 if needed
		if (!io::isUTF8($text)) {
			$text = utf8_encode($text);
		}
		//remove accents, underscore and quotes
		$text = CMS_XapianIndexer::removeAccents($text);
		//strip whitespaces
		$text = trim(preg_replace('/\s\s+/', ' ', $text));
		return $text;
	}
	
	/**
	  * This function prepare a string to be stored in index as displayed text.
	  * - Remove * and # and replace _ by spaces
	  * - Strip whitespaces
	  * - Cut to first 500 caracters
	  *
	  * @param string $text : the text to prepare
	  * @return string $text : the prepared text
	  * @access public
	  * @static
	  */
	function prepareDisplayedText($text) {
		//remove * and # and replace _ by spaces
		$text = strtr(trim(str_replace(array('*','#'), '',$text)),"_", " ");
		//strip whitespaces
		$text = preg_replace('/\s\s+/', ' ', $text);
		//cut to first 500 caracters
		$text = io::substr($text,0,500);
		return $text;
	}
	
	/**
	  * Remove accents, underscore and quotes from an utf8 string
	  *
	  * @param string $text : the text to clean
	  * @return string $text : the cleaned text
	  * @access public
	  * @static
	  */
	function removeAccents($string) {
		//get caracters map
		$map = io::sanitizeAsciiMap();
		//convert map to utf8
		$utfMap = array();
		foreach ($map as $char => $ascii) {
			$utfMap[utf8_encode($char)] = $ascii;
		}
		//remove underscore and quotes
		$replace = array(
			"_"				=> " ",
			"'"				=> " ",
			"\xE2\x80\x99"	=> " ",
		);
		$utfMap = array_merge($utfMap, $replace);
		$string = strtr($string, $utfMap);
		return $string;
	}
	
	/**
	  * Add attributes to document
	  *
	  * @param array $attributes : attributes to add array(attributeName => array(values))
	  * @return boolean true on success, false on failure
	  * @access private
	  */
	function _addAttributes($attributes) {
		if (!is_array($attributes)) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : attributes need to be an array : '.$attributes);
			return false;
		}
		foreach ($attributes as $attributeName => $attributeValues) {
			if (is_array($attributeValues)) {
				foreach ($attributeValues as $attributeValue) {
					$this->_addPosting('__'.io::strtoupper($attributeName).'__:'.$attributeValue);
				}
			} elseif($attributeValues) {
				$this->_addPosting('__'.io::strtoupper($attributeName).'__:'.$attributeValues);
			} else {
				return false;
			}
		}
		return true;
	}
	
	/**
	  * Add posting to document
	  *
	  * @param string $posting : the posting word to add to document
	  * @param integer $wdf : within-document frequency. Default = 1
	  * @param string $attribute : add posting as a document attribute too (default : false)
	  * @return boolean true on success, false on failure
	  * @access private
	  */
	function _addPosting($posting, $wdf = 1, $attribute=false) {
		$this->_postings++;
		$this->_xapianDocument->add_posting($posting, $this->_postings, $wdf);
		if ($attribute !== false) {
			$attributes = array();
			$attributes[$attribute][] = $posting;
			$this->_addAttributes($attributes);
		}
		return true;
	}
	
	/**
	  * Get document XID from his module and uid
	  *
	  * @return string : the document XID
	  * @access private
	  */
	function _getXID() {
		if (!$this->_document->getValue('module') || !$this->_document->getValue('uid')) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : need module codename and uid to generate XID ...');
			return false;
		}
		return '__XID__:'.$this->_document->getValue('module').$this->_document->getValue('uid');
	}
	
	/**
	  * Load database to store indexed document
	  *
	  * @return string : the document XID
	  * @access private
	  */
	function _loadWritableDatabase() {
		//load writable Xapian DB
		$this->_db = new CMS_XapianDB($this->_document->getValue('module'), true);
		if (!$this->_db->isWritable()) {
			//end DB transaction
			$this->_db->endTransaction();
			return false;
		}
		return true;
	}
	
	
	/**
	  * Write document into persistence (Xapian database)
	  *
	  * @return boolean true on success, false on failure
	  * @access private
	  */
	function _writeToPersistence() {
		$xid = $this->_getXID();
		if ($this->_document->getValue('xid')) {
			//replace document using XID posting (seems to be better than using DB docid directly)
			$this->_db->replaceDocument($xid, $this->_xapianDocument);
		} else {
			//add document
			$this->_db->addDocument($this->_xapianDocument);
			//add document XID
			$this->_document->setValue('xid', $xid);
		}
		//write document into persistence
		$this->_document->writeToPersistence();
		return true;
	}
}
?>