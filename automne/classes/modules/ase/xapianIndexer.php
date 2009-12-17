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
		//document datas (only first 500 caracters, more is useless), remove *, #, tags and trailing spaces also
		$this->_xapianDocument->set_data(strtr(trim(str_replace(array('*','#'), '',io::substr($this->_document->getTextContent(),0,500))),"_’", " '"));
		
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
		//add a common posting for all documents (needed for all 'AND NOT' search)
		//$this->_addPosting('__ALL__');
		
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
		$indexer->index_text($this->_prepareTextToIndex($this->_document->getTextContent()));
		//index document title
		//get WDF (within-document frequency) value for title from module parameters
		$module = CMS_modulesCatalog::getByCodename(MOD_ASE_CODENAME);
		$indexer->index_text($this->_prepareTextToIndex($this->_document->getValue('title')), (int) $module->getParameters('DOCUMENT_TITLE_WDF'));
		
		//save document in Xapian DB
		$this->_writeToPersistence();
		//end DB transaction (remove lock and destroy object)
		$this->_db->endTransaction();
		if (!$returnIndexableContent) {
			return true;
		} else {
			return $this->_document->getTextContent();
		}
	}
	
	function _prepareTextToIndex($text) {
		//eventually tokenize japanese text
		if ($this->_document->getValue('language') == 'ja' || $this->_document->getValue('language') == 'jp') {
			if ($return = $this->tokenizeJapanese($text)) {
				$text = $return;
			}
		}
		$text = strtr($text,"_’'", 
							"   ");
		//convert text to utf-8 if needed
		if (!io::isUTF8($text)) {
			return utf8_encode($text);
		} else {
			return $text;
		}
		
	}
	
	/**
	  * Tokenize Japanese text to be used by Xapian
	  * This method use ChaSen binary (which must be available on the server)
	  * This does not handle mixed languages pretty well so avoid mixing japanese with other than pure ascii characters
	  *
	  * @param string $text : the japanese text to tokenize
	  * @return string $text : the japanese text tokenized
	  * @access public
	  */
	function tokenizeJapanese($text) {
		$error = '';
		if (io::substr(CMS_patch::executeCommand('which chasen 2>&1',$error),0,1) == '/' && !$error) {
			$text = preg_replace('/[\w\d\b .&,;:_()"*\'-]{2,}/s', "[$0]", $text);
			//get tmp path
			$tmpFile = new CMS_file(PATH_TMP_FS.'/chasen_'.md5(mt_rand().microtime()).'.tmp');
			$tmpFile->setContent($text);
			$tmpFile->writeTopersistence();
			$conversionCommand = 'chasen -F "%m " -r '.PATH_MODULES_FILES_FS.'/'.MOD_ASE_CODENAME.'/chasenrc -i w '.$tmpFile->getName();
			$return = CMS_patch::executeCommand($conversionCommand, $error);
			$tmpFile->delete();
			if ($error) {
				CMS_gtandFather::raiseError('Conversion command "'.$conversionCommand.'" output with errors : '.print_r($error,true).'. Return is : '.print_r($return,true));
				return false;
			} else {
				$text = $return;
			}
			$text = strtr($text, '[]', '  ');
		} else {
			CMS_gtandFather::raiseError('Cannot find chasen to properly tokenize japanese text ...');
			return false;
		}
		return $text;
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