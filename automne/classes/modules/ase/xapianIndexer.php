<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Automne (TM)                                                         |
// +----------------------------------------------------------------------+
// | Copyright (c) 2000-2007 WS Interactive                               |
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
// $Id: xapianIndexer.php,v 1.1.1.1 2007/09/04 15:01:29 sebastien Exp $

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

//Xapian Document values
define('XAPIAN_VALUENO_TIMESTAMP', 0);
define('XAPIAN_VALUENO_MODULE', 1);
define('XAPIAN_VALUENO_LANGUAGE', 2);
define('XAPIAN_VALUENO_TITLE', 3);
define('XAPIAN_VALUENO_UID', 4);
define('XAPIAN_VALUENO_XID', 5);
define('XAPIAN_VALUENO_TYPE', 6);

class CMS_XapianIndexer extends CMS_grandFather {
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
	function index() {
		if (!$this->_document->isFiltered()) {
			if (!$this->_document->filter()) {
				$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : can not filter document to plain-text format ...');
				return false;
			}
		}
		//create Xapian document
		$this->_xapianDocument = new XapianDocument();
		
		//XID
		if (!($xid = $this->_getXID())) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : can not get valid XID for document');
			return false;
		}
		
		//document datas (only first 500 caracters, more is useless)
		$this->_xapianDocument->set_data(substr($this->_document->getTextContent(),0,500));
		/* 
		 * document values
		 */
		
		//time
		$this->_xapianDocument->add_value(XAPIAN_VALUENO_TIMESTAMP, time());
		//module
		$this->_xapianDocument->add_value(XAPIAN_VALUENO_MODULE, $this->_document->getValue('module'));
		//UID
		$this->_xapianDocument->add_value(XAPIAN_VALUENO_UID, $this->_document->getValue('uid'));
		//language
		$this->_xapianDocument->add_value(XAPIAN_VALUENO_LANGUAGE, $this->_document->getValue('language'));
		//title
		$this->_xapianDocument->add_value(XAPIAN_VALUENO_TITLE, $this->_document->getValue('title'));
		//XID
		$this->_xapianDocument->add_value(XAPIAN_VALUENO_XID, $xid);
		//Type
		$this->_xapianDocument->add_value(XAPIAN_VALUENO_TYPE, $this->_document->getValue('type'));
		/* 
		 * document posting and attributes
		 */
		//add document XID as posting
		$this->_addPosting($xid);
		//add document module as posting
		$this->_addPosting('__MODULE__:'.$this->_document->getValue('module'));
		//add a common posting for all documents (needed for all 'AND NOT' search)
		$this->_addPosting('__ALL__');
		//add all modules attributes
		$this->_addAttributes($this->_document->getModuleAttributes());
		//get WDF (within-document frequency) value for title from module parameters
		$module = CMS_modulesCatalog::getByCodename(MOD_ASE_CODENAME);
		//add title postings (WDF for title is a parameter) and add it as attributes too
		$this->_addPosting($this->_document->getTitlePosting(), (int) $module->getParameters('DOCUMENT_TITLE_WDF'), 'title');
		//add document postings
		$this->_addPosting($this->_document->getDocumentPosting());
		//save document in Xapian DB
		$this->_writeToPersistence();
		//pr($this->_document->getDocumentPosting());
		return true;
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
					$this->_addPosting('__'.strtoupper($attributeName).'__:'.$attributeValue);
				}
			} elseif($attributeValues) {
				$this->_addPosting('__'.strtoupper($attributeName).'__:'.$attributeValues);
			} else {
				return false;
			}
		}
		return true;
	}
	
	/**
	  * Add posting to document
	  *
	  * @param mixed $posting : the posting words and stems to add to document array('words' => array(string), 'stems' => array(string))
	  *		or a string for an unique posting term to add
	  * @param integer $wdf : within-document frequency. Default = 1
	  * @param string $attribute : add posting as a document attribute too (default : false)
	  * @return boolean true on success, false on failure
	  * @access private
	  */
	function _addPosting($posting, $wdf = 1, $attribute=false) {
		if (is_array($posting)) {
			//add stems
			if (is_array($posting['stems'])) {
				$attributes = array();
				foreach ($posting['stems'] as $stem) {
					$this->_postings++;
					$this->_xapianDocument->add_posting($stem, $this->_postings, $wdf);
					if ($attribute !== false) {
						$attributes[$attribute][] = $stem;
					}
				}
				if (sizeof($attributes)) {
					//add all modules attributes
					$this->_addAttributes($attributes);
				}
			}
			//add words
			if (is_array($posting['words'])) {
				foreach ($posting['words'] as $word) {
					$this->_postings++;
					//all words must be prefixed by an uppercase W. This allow distinction between words and stems
					//for now, words are only used for expand sets
					$this->_xapianDocument->add_posting('W'.$word, $this->_postings, $wdf);
				}
			}
		} elseif (is_string($posting)) {
			$this->_postings++;
			$this->_xapianDocument->add_posting($posting, $this->_postings, $wdf);
			if ($attribute !== false) {
				$attributes = array();
				$attributes[$attribute][] = $posting;
				$this->_addAttributes($attributes);
			}
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
	  * Write document into persistence (Xapian database)
	  *
	  * @return boolean true on success, false on failure
	  * @access private
	  */
	function _writeToPersistence() {
		//load Xapian DB
		$db = new CMS_XapianDB($this->_document->getValue('module'), true);
		if (!$db->isWritable()) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : can not get database ... add task to queue list again');
			//add script to indexation
			CMS_scriptsManager::addScript(MOD_ASE_CODENAME, array('task' => 'reindex', 'uid' => $this->_document->getValue('uid'), 'module' => $this->_document->getValue('module')));
			return false;
		}
		$xid = $this->_getXID();
		if ($this->_document->getValue('xid')) {
			//replace document using XID posting (seems to be better than using DB docid directly)
			$db->replaceDocument($xid, $this->_xapianDocument);
		} else {
			//add document
			$db->addDocument($this->_xapianDocument);
			//add document XID
			$this->_document->setValue('xid', $xid);
		}
		//end DB transaction (remove lock and destroy object)
		$db->endTransaction();
		//write document into persistence
		$this->_document->writeToPersistence();
		return true;
	}
}
?>