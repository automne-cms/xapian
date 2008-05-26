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
// $Id: document.php,v 1.5 2008/05/26 09:40:48 sebastien Exp $

/**
  * Class CMS_ase_document
  * 
  * Represent a documents to index
  *
  * @package CMS
  * @subpackage module
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

class CMS_ase_document extends CMS_grandFather
{
	/**
	  * string DB id
	  * @var integer
	  * @access private
	  */
	var $_ID;
	
	/**
	  * Document datas
	  * 
	  * @var array
	  * @access private
	  */
	var $_document = array(
		'uid' 		=> '', //document module unique ID
		'xid' 		=> '', //document xapian unique ID
		'module' 	=> '', //document module codename
		'language' 	=> '', //document language code
		'type'		=> '', //document type
		'title'		=> '', //document title
	);
	
	var $_moduleAttributes = array();
	
	/**
	  * is document filtered
	  * 
	  * @var boolean
	  * @access private
	  */
	var $_isFiltered = false;
	
	/**
	  * is document filtered
	  * 
	  * @var boolean
	  * @access private
	  */
	var $_hasDocumentInfos = false;
	
	/**
	  * document original content (if it is not a file system document)
	  * 
	  * @var array
	  * @access private
	  */
	var $_content;
	
	/**
	  * document plain text content (once filtered)
	  * 
	  * @var string
	  * @access private
	  */
	var $_plainTextContent;
	
	/**
	  * document original path (file system relative)
	  * 
	  * @var array
	  * @access private
	  */
	var $_path;
	
	/**
	  * max indexed words for document
	  * 
	  * @var integer
	  * @access private
	  */
	var $_maxwords = 20000;
	
	/**
	  * max plain text length for document
	  * 
	  * @var integer
	  * @access private
	  */
	var $_maxplaintextlength = 300000;
	
	var $_minIndexableWordLength;
	
	/**
	  * Constructor.
	  * initialize object.
	  *
	  * @param array $parameters module and uid values array('module' => string codename, 'uid' => string uid, )
	  * @return void
	  * @access public
	  */
	function CMS_ase_document($parameters=array())
	{
		$datas = array();
		if ($parameters['uid'] && $parameters['module'] && !sizeof($dbValues)) {
			$sql = "
				select
					*
				from
					mod_ase_document
				where
					uid_mased='".SensitiveIO::sanitizeSQLString($parameters['uid'])."'
					and module_mased='".SensitiveIO::sanitizeSQLString($parameters['module'])."'
			";
			$q = new CMS_query($sql);
			if ($q->getNumRows()) {
				$datas = $q->getArray();
			} else {
				$datas['uid_mased'] 	= strtolower($parameters['uid']);
				$datas['module_mased'] 	= strtolower($parameters['module']);
			}
		}
		if (sizeof($datas)) {
			$this->_ID = (int) $datas['id'];
			
			$this->_document['uid'] 	= $datas['uid_mased'];
			$this->_document['xid'] 	= $datas['xid_mased'];
			$this->_document['module'] 	= $datas['module_mased'];
			$this->_document['language']= $datas['language_mased'];
			$this->_document['type'] 	= $datas['type_mased'];
		}
		//load document parameters
		//get module words limit for a document
		$module = CMS_modulesCatalog::getByCodename(MOD_ASE_CODENAME);
		$maxwords = (int) $module->getParameters('DOCUMENT_MAX_WORDS_TO_INDEX');
		if ($maxwords) {
			$this->_maxWords = $maxwords;
		}
		//get module max plaintext length for this document
		$maxPlaintextLength = (int) $module->getParameters('DOCUMENT_MAX_INDEXABLE_DOCUMENT_LENGTH');
		if ($maxPlaintextLength) {
			$this->_maxplaintextlength = $maxPlaintextLength;
		}
		$this->_minIndexableWordLength =  (int) $module->getParameters('DOCUMENT_MIN_INDEXABLE_WORD_LENGTH');
	}
	
	/**
	  * Set original document content
	  *
	  * @param string &$content the document content to set
	  * @param string $type the document type
	  * @return boolean true on success, false on failure
	  * @access public
	  */
	function addContent(&$content, $type) {
		$this->_content[] = array('content' => $content, 'type' => $type);
		return true;
	}
	
	/**
	  * Add a file as content
	  *
	  * @param string $filepath the document path
	  * @param string $type the document type (if none, type will be get from file extension)
	  * @param constant $from the path relative to reference (FILE_SYSTEM (default), WEBROOT)
	  * @return boolean true on success, false on failure
	  * @access private
	  */
	function addFile($filepath, $type='', $from = FILE_SYSTEM) {
		//check file
		$file = new CMS_file($filepath, $from, TYPE_FILE);
		if (!$file->exists()) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : given file does not exists : '.$filepath);
			return false;
		}
		if (!$type) {
			//check extension
			$pathinfo = pathinfo($file->getName());
			if (!$pathinfo['extension']) {
				$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : file "'.$filepath.'" extension does not exists');
				return false;
			}
			$type = $pathinfo['extension'];
		}
		//set document path
		$this->_path[] = array('file' => $file->getName(), 'type' => $type);
		return true;
	}
	
	/**
	  * Set a document value.
	  *
	  * @param string $valueName the name of the value to set
	  * @param mixed $value the value to set
	  * @return boolean true on success, false on failure
	  * @access public
	  */
	function setValue($valueName, $value)
	{
		if (!in_array($valueName,array_keys($this->_document))) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : unknown valueName to set :'.$valueName);
			return false;
		}
		$this->_document[$valueName] = strtolower($value);
		return true;
	}
	
	/**
	  * Get a document value.
	  *
	  * @param string $valueName the name of the value to get
	  * @return mixed, the value
	  * @access public
	  */
	function getValue($valueName)
	{
		if (!in_array($valueName,array_keys($this->_document))) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : unknown valueName to get :'.$valueName);
			return false;
		}
		return $this->_document[$valueName];
	}
	
	/**
	  * Set a module attribute.
	  *
	  * @param string $attributeName the name of the attribute to set
	  * @param mixed $value the value to set
	  * @return boolean true on success, false on failure
	  * @access public
	  */
	function setModuleAttribute($attributeName, $value)
	{
		$this->_moduleAttributes[$attributeName][] = $value;
		return true;
	}
	
	/**
	  * Get a module attribute.
	  *
	  * @param string $attributeName the name of the attribute to get
	  * @return array, the value
	  * @access public
	  */
	function getModuleAttribute($attributeName)
	{
		return $this->_moduleAttributes[$attributeName];
	}
	
	/**
	  * Get all module attributes.
	  *
	  * @return array, attributes values
	  * @access public
	  */
	function getModuleAttributes()
	{
		return $this->_moduleAttributes;
	}
	
	/**
	  * Get document infos (such as language, type, content, etc.) from his original module.
	  *
	  * @return boolean true on success, false on failure
	  * @access public
	  */
	function getDocumentInfosFromModule() {
		if (!$this->_document['uid'] || !$this->_document['module']) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : unknown module or uid for document');
			return false;
		}
		if (!($moduleInterface = CMS_ase_interface_catalog::getModuleInterface($this->_document['module']))) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : no interface for module '.$this->_document['module']);
			return false;
		}
		if ($moduleInterface->getDocumentInfos($this)) {
			$this->_hasDocumentInfos = true;
		} else {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : module does not return informations for document ...');
			return false;
		}
	}
	
	/**
	  * Filter document
	  * Instanciate correct filter according to document content type then run it and get plain/text content
	  *
	  * @return boolean true on success, false on failure
	  * @access public
	  */
	function filter() {
		if (!$this->_hasDocumentInfos) {
			$this->getDocumentInfosFromModule();
		}
		if (!$this->_hasDocumentInfos) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : can not filter document with no informations set ...');
			return false;
		}
		$filters = array();
		//create filters to convert content/files to plain/text
		if (sizeof($this->_content)) {
			foreach ($this->_content as $content) {
				if (!$filterName = CMS_filter_catalog::getFilterForType($content['type'])) {
					$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : can not get filter for content document type : '.$content['type']);
					return false;
				}
				$filters[] = new $filterName($content['content'], DATA_TYPE_CDATA);
			}
		}
		if (sizeof($this->_path)) {
			foreach ($this->_path as $filepath) {
				if (!$filterName = CMS_filter_catalog::getFilterForType($filepath['type'])) {
					$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : can not get filter for file document type : '.$filepath['type']);
					return false;
				}
				pr($filepath['file']);
				$filters[] = new $filterName($filepath['file'], DATA_TYPE_FILE);
			}
		}
		/*if (!sizeof($this->_path) && !sizeof($this->_content)) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : can not filter document with no path or content set');
			return false;
		}*/
		foreach ($filters as $filter) {
			if (!($plainTextPath = $filter->getPlainTextDocument())) {
				$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : can not get filtered document');
				return false;
			}
			//then get filtered Content
			$textfile = new CMS_file($plainTextPath);
			//try to detect utf-8 strings
			$content = $this->_detectUTF8($textfile->getContent()) ? utf8_decode($textfile->getContent()) : $textfile->getContent();
			//add content to plain/text content
			$this->addPlainTextContent($content);
			//destroy filter object (required to destroy tmp filter files)
			$filter->destroy();
		}
		//set document as filtered
		$this->_isFiltered = true;
		return true;
	}
	
	/**
	  * Try to detect UTF-8 content
	  *
	  * @author chris AT w3style.co DOT uk
	  * @return boolean true/false
	  * @access private
	  */
	function _detectUTF8($string) {
		return preg_match('%(?:
		[\xC2-\xDF][\x80-\xBF]        		# non-overlong 2-byte
		|\xE0[\xA0-\xBF][\x80-\xBF]			# excluding overlongs
		|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}	# straight 3-byte
		|\xED[\x80-\x9F][\x80-\xBF]			# excluding surrogates
		|\xF0[\x90-\xBF][\x80-\xBF]{2}		# planes 1-3
		|[\xF1-\xF3][\x80-\xBF]{3}			# planes 4-15
		|\xF4[\x80-\x8F][\x80-\xBF]{2}		# plane 16
		)+%xs', $string);
	}
	
	/**
	  * Add content to document plain text content
	  *
	  * @return boolean true/false
	  * @access private
	  */
	function addPlainTextContent($content) {
		if (strlen($this->_plainTextContent) >= $this->_maxplaintextlength) {
			return false;
		}
		$this->_plainTextContent .= $content.' ';
		if (strlen($this->_plainTextContent) > $this->_maxplaintextlength) {
			$this->_plainTextContent = substr($this->_plainTextContent, 0, $this->_maxplaintextlength);
		}
		return true;
	}
	
	/**
	  * Is document already filtered ?
	  *
	  * @return boolean true/false
	  * @access private
	  */
	function isFiltered() {
		return $this->_isFiltered;
	}
	
	/**
	  * Get document plain text content
	  *
	  * @return string : the document plain text content (filtered content)
	  * @access public
	  */
	function getTextContent() {
		if (!$this->_isFiltered) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : document must be filtered first ...');
			return false;
		}
		return $this->_plainTextContent;
	}
	
	function &getStopper() {
		//get stop words for document language
		$stoplist = new CMS_file(PATH_MODULES_FILES_FS.'/'.MOD_ASE_CODENAME.'/stopwords/'.strtolower($this->getValue('language')).'.txt');
		if (!$stoplist->exists()) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : no stopwords list founded for language : '.$this->getValue('language'));
			return false;
		}
		$stopwords = $stoplist->readContent('array');
		//instanciate stoppper and add stopwords list
		$stopper = new XapianSimpleStopper();
		foreach ($stopwords as $stopword) {
			$stopper->add(utf8_encode($stopword));
		}
		return $stopper;
	}
	
	function &getStemmer() {
		return new XapianStem(strtolower($this->getValue('language')));
	}
	
	/**
	  * Writes the document reference into persistence (MySQL for now), along with base data.
	  *
	  * @return boolean true on success, false on failure
	  * @access public
	  */
	function writeToPersistence()
	{
		if (!$this->_document['uid'] || !$this->_document['module']) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : can not write document without uid and module set');
			return false;
		}
		//save data
		$sql_fields = "
			uid_mased='".SensitiveIO::sanitizeSQLString($this->_document['uid'])."',
			xid_mased='".SensitiveIO::sanitizeSQLString($this->_document['xid'])."',
			module_mased='".SensitiveIO::sanitizeSQLString($this->_document['module'])."',
			language_mased='".SensitiveIO::sanitizeSQLString($this->_document['language'])."',
			type_mased='".SensitiveIO::sanitizeSQLString($this->_document['type'])."'
		";
		
		if ($this->_ID) {
			$sql = "
				update
					".$this->getTableName()."
				set
					mod_ase_document
				where
					id='".$this->_ID."'
			";
		} else {
			//HERE WE USE REPLACE INSTEAD OF INSERT TO AVOID UNIQUE DATAS TO BE A PROBLEM
			$sql = "
				replace into
					mod_ase_document
				set
					".$sql_fields;
		}
		$q = new CMS_query($sql);
		if ($q->hasError()) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : can\'t save object');
			return false;
		} elseif (!$this->_ID) {
			$this->_ID = $q->getLastInsertedID();
		}
		return true;
	}
}
?>