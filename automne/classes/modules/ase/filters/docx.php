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
// $Id: docx.php,v 1.1 2008/05/13 16:14:29 jeremie Exp $

/**
  * Class CMS_filter_docx
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
define('MESSAGE_DOCX_FILTER_LABEL', 43);

class CMS_filter_docx extends CMS_filter_common
{
	/**
	  * Filter label
	  * 
	  * @var constant
	  * @access private
	  */
	var $_label = MESSAGE_DOCX_FILTER_LABEL;
	
	/**
	  * Supported documents extension (must be in lowercase)
	  * 
	  * @var array
	  * @access private
	  */
	var $_supportedExtensions = array('docx');
	
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
		if (!CMS_file::fileIsExecutable(PATH_REALROOT_FS.'/automne_bin/docxtoplain.sh') && !CMS_file::makeExecutable(PATH_REALROOT_FS.'/automne_bin/docxtoplain.sh')) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : shell script docxtoplain.sh is not executable ... ');
			return false;
		}
		return  PATH_REALROOT_FS.'/automne_bin/docxtoplain.sh '.$this->_sourceDocument.' > '.$this->_convertedDocument;
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
		if (!CMS_file::fileIsExecutable(PATH_REALROOT_FS.'/automne_bin/docxtoplain.sh') && !CMS_file::makeExecutable(PATH_REALROOT_FS.'/automne_bin/docxtoplain.sh')) {
			return false;
		}
		return true;
	}
}
?>