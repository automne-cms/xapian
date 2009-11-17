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
// $Id: common.php,v 1.5 2009/11/17 12:33:26 sebastien Exp $

/**
  * Class CMS_filter_common
  *
  * Represent common stuff for documents filters class.
  *
  * @package CMS
  * @subpackage module
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

if (!defined("DATA_TYPE_FILE")) {
	define("DATA_TYPE_FILE",1);
}

if (!defined("DATA_TYPE_CDATA")) {
	 define("DATA_TYPE_CDATA",2);
}

/**
  * ASE Messages
  */
define('MESSAGE_UNKNOWN_FILTER_LABEL', 13);

class CMS_filter_common extends CMS_grandFather
{
	/**
	  * Filter label
	  * 
	  * @var constant
	  * @access private
	  */
	var $_label = MESSAGE_UNKNOWN_FILTER_LABEL;
	
	/**
	  * Source document path
	  * 
	  * @var string
	  * @access private
	  */
	var $_sourceDocument;
	
	/**
	  * Converted document path
	  * 
	  * @var string
	  * @access private
	  */
	var $_convertedDocument;
	
	/**
	  * Supported documents extension (must be in lowercase)
	  * 
	  * @var array
	  * @access private
	  */
	var $_supportedExtensions = array();
	
	/**
	  * All binaries needed to the filter
	  * 
	  * @var array
	  * @access private
	  */
	var $_binaries = array();
	
	/**
	  * sourceDocument is it a temporary file (destroyed with with object) ?
	  * 
	  * @var boolean
	  * @access private
	  */
	var $_isTmpFile;
	
	/**
	  * Constructor
	  * 
	  * @access public
	  * @param string $source : document path or content to filter
	  * @param constant $type : type of source : DATA_TYPE_FILE for a path to a file (default) or DATA_TYPE_CDATA for a string content
	  * @param constant $from : for file type : type of path : FILE_SYSTEM for a file system path (default) or WEBROOT for a webroot relative path
	  */
	function CMS_filter_common($source = false, $type = DATA_TYPE_FILE, $from = CMS_file::FILE_SYSTEM) {
		//if source is false, do not really load object, this is only for filter activity test purpose
		if ($source === false) {
			return;
		}
		//check filter requirements
		if (!$this->isActive()) {
			$this->_raiseError(get_class($this).' : '.__FUNCTION__.' : filter is not currently active, please check all binaries requirement : '.implode(', ',$this->_binaries));
			return;
		}
		//check if source exists
		if (!$source) {
			$this->_raiseError(get_class($this).' : '.__FUNCTION__.' : empty source ...');
			return;
		}
		if ($type == DATA_TYPE_FILE) {
			//check file
			$file = new CMS_file($source, $from, CMS_file::TYPE_FILE);
			if (!$file->exists()) {
				$this->_raiseError(get_class($this).' : '.__FUNCTION__.' : given file does not exists : '.$source);
				return;
			}
			//check extension
			$pathinfo = pathinfo($file->getName());
			if (!$pathinfo['extension'] || !in_array(strtolower($pathinfo['extension']), $this->_supportedExtensions)) {
				$this->_raiseError(get_class($this).' : '.__FUNCTION__.' : file "'.$source.'" extension does not exists or does not match supported ones : '.implode(', ',$this->_supportedExtensions));
				return;
			}
			$this->_sourceDocument = $file->getName();
		} else {
			//create tmp file
			$tmpFile = new CMS_file(PATH_TMP_FS.'/filter_'.md5(mt_rand().microtime()).'.tmp');
			$tmpFile->setContent($source);
			$tmpFile->writeTopersistence();
			if (!$tmpFile->exists()) {
				$this->raiseError('can\'t create temporary document : '.$tmpFile->getName());
				return;
			}
			$this->_sourceDocument = $tmpFile->getName();
			$this->_isTmpFile = true;
		}
	}
	
	/**
	  * Check if filter is active (all needed binaries exists on the system)
	  *
	  * @return boolean true if filter is active, false otherwise
	  * @access public
	  */
	function isActive() {
		static $active;
		$classname = strtolower(get_class($this));
		if (!isset($active[$classname])) {
			$supported = true;
			foreach ($this->_binaries as $binary) {
				$binary = escapeshellcmd(trim($binary));
				$error = '';
				$supported = (io::substr(CMS_patch::executeCommand('which '.$binary.' 2>&1',$error),0,1) == '/' && !$error) ? $supported : false;
			}
			$active[$classname] = $supported;
		}
		return $active[$classname];
	}
	
	/**
	  * Get missing binaries for inactive filter
	  *
	  * @return array : the missing binaries names for filter
	  * @access public
	  */
	function getMissingBinaries() {
		static $missing;
		$classname = strtolower(get_class($this));
		if (!isset($missing[$classname])) {
			$binaries = array();
			foreach ($this->_binaries as $binary) {
				$binary = escapeshellcmd(trim($binary));
				$error = '';
				if (io::substr(CMS_patch::executeCommand('which '.$binary.' 2>&1',$error),0,1) != '/' || $error) {
					$binaries[] = $binary;
				}
			}
			$missing[$classname] = $binaries;
		}
		return $missing[$classname];
	}
	
	/**
	  * Get current filter label
	  *
	  * @param mixed $language : the current CMS_language object or the current language code
	  * @return string : the filter name
	  * @access public
	  */
	function getLabel(&$language) {
		if (is_a($language, "CMS_language")) {
			return $language->getMessage($this->_label, false, MOD_ASE_CODENAME);
		} else {
			$tmplanguage = new CMS_language($language);
			return $tmplanguage->getMessage($this->_label, false, MOD_ASE_CODENAME);
		}
	}
	
	/**
	  * Get supported document extensions
	  *
	  * @return array : the supported extensions
	  * @access public
	  */
	function getSupportedExtensions() {
		return $this->_supportedExtensions;
	}
	
	/**
	  * get converted plain/text document path
	  *
	  * @return string the file system absolute converted document
	  * @access public
	  */
	function getPlainTextDocument() {
		//convert document to plain/text
		$this->_convert();
		if (!$this->_convertedDocument) {
			$this->_raiseError(get_class($this).' : '.__FUNCTION__.' : can\'t get converted document ... ');
			return false;
		}
		//then return 
		return $this->_convertedDocument;
	}
	
	/**
	  * Destroy all conversion temporary files
	  * This destructor absolutely needs to be run after filter useage to clean temp directory
	  *
	  * @return boolean true on success, false otherwise
	  * @access public
	  */
	function destroy() {
		if ($this->_isTmpFile && $this->_sourceDocument) {
			unlink($this->_sourceDocument);
		}
		if ($this->_convertedDocument) {
			unlink($this->_convertedDocument);
		}
		unset($this);
		return true;
	}
	
	/**
	  * Convert initial file into plain/text
	  *
	  * @return boolean true on success, false otherwise
	  * @access private
	  */
	function _convert() {
		if ($this->hasError()) {
			$this->_raiseError(get_class($this).' : '.__FUNCTION__.' : can\'t convert document, object has an error ...');
			return false;
		}
		$this->_convertedDocument = PATH_TMP_FS.'/filter_'.md5(mt_rand().microtime()).'.tmp';
		if (!touch($this->_convertedDocument)) {
			$this->raiseError('can\'t create temporary document : '.$this->_convertedDocument);
			return false;
		}
		//create conversion command
		if (!($conversionCommand = $this->_createConversionCommand())) {
			$this->_raiseError(get_class($this).' : '.__FUNCTION__.' : can\'t create conversion command ... ');
			return false;
		}
		//run conversion command line
		$error = '';
		$return = CMS_patch::executeCommand($conversionCommand,$error);
		if ($error) {
			$this->_raiseError(get_class($this).' : '.__FUNCTION__.' : conversion command "'.$conversionCommand.'" output with errors : '.print_r($error,true).'. Return is : '.print_r($return,true));
			return false;
		}
		//run some cleaning task on converted document command
		$this->_cleanConverted();
		return true;
	}
	
	/**
	  * Create conversion command line
	  *
	  * @return string : the conversion command
	  * @access private
	  */
	function _createConversionCommand() {
		//use binaries to create conversionCommand
		$conversionCommand = '';
		$count = 0;
		foreach ($this->_binaries as $binary) {
			$binary = escapeshellcmd(trim($binary));
			$conversionCommand .= (!$count) ? $binary.' '.$this->_sourceDocument.' ' : '| '.$binary.' ';
			$count++;
		}
		return  $conversionCommand . '> ' . $this->_convertedDocument;
	}
	
	/**
	  * Create conversion command line
	  *
	  * @return string : the conversion command
	  * @access private
	  */
	function _cleanConverted() {
		//by default do nothing
		return true;
	}
}
?>