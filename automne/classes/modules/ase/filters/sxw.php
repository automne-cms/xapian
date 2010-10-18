<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Automne (TM)                                                         |
// +----------------------------------------------------------------------+
// | Copyright (c) 2000-2005 WS Interactive                               |
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
// $Id: sxw.php,v 1.3 2009/11/13 17:31:13 sebastien Exp $

/**
  * Class CMS_filter_sxw
  *
  * Represent a filter for Open Office and Open Document formats.
  *
  * @package CMS
  * @subpackage module
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

/**
  * ASE Messages
  */
define('MESSAGE_SXW_FILTER_LABEL', 9);

class CMS_filter_sxw extends CMS_filter_common
{
	/**
	  * Filter label
	  * 
	  * @var constant
	  * @access private
	  */
	var $_label = MESSAGE_SXW_FILTER_LABEL;
	
	/**
	  * Supported documents extension (must be in lowercase)
	  * 
	  * @var array
	  * @access private
	  */
	var $_supportedExtensions = array('sxw','odt');
	
	/**
	  * All binaries needed to the filter
	  * 
	  * @var array
	  * @access private
	  */
	var $_binaries = array('unzip','sed','iconv');
	
	/**
	  * Create conversion command line
	  *
	  * @return string : the conversion command
	  * @access private
	  */
	function _createConversionCommand() {
		//check for shell script executable status
		if (!CMS_file::fileIsExecutable(PATH_WINDOWS_BIN_FS.'/sxwtoplain.sh') && !CMS_file::makeExecutable(PATH_WINDOWS_BIN_FS.'/sxwtoplain.sh')) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : shell script sxwtoplain.sh is not executable ... ');
			return false;
		}
		return PATH_WINDOWS_BIN_FS.'/sxwtoplain.sh '.$this->_sourceDocument.' > '.$this->_convertedDocument;
	}
	
	/**
	  * Check if filter is active (all needed binaries exists on the system)
	  *
	  * @return boolean true if filter is active, false otherwise
	  * @access public
	  */
	function isActive() {
		if (!parent::isActive()) {
			return false;
		}
		//check for shell script executable status
		if (!CMS_file::fileIsExecutable(PATH_WINDOWS_BIN_FS.'/sxwtoplain.sh') && !CMS_file::makeExecutable(PATH_WINDOWS_BIN_FS.'/sxwtoplain.sh')) {
			return false;
		}
		return true;
	}
	
	function _cleanConverted() {
		//transcode document content
		if (!file_put_contents($this->_convertedDocument, iconv("UTF-8", (strtolower(APPLICATION_DEFAULT_ENCODING) != 'utf-8' ? 'ISO-8859-1' : 'UTF-8')."//IGNORE", file_get_contents($this->_convertedDocument)))) {
			$this->_raiseError(get_class($this).' : '.__FUNCTION__.' : can\'t convert DOCX document ... ');
			return false;
		}
	}
}
?>