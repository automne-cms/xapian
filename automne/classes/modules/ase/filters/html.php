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
// $Id: html.php,v 1.3 2009/11/13 17:31:13 sebastien Exp $

/**
  * Class CMS_filter_html
  *
  * Represent a filter for HTML, XHTML documents.
  *
  * @package CMS
  * @subpackage module
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

/**
  * ASE Messages
  */
define('MESSAGE_HTML_FILTER_LABEL', 11);

class CMS_filter_html extends CMS_filter_common
{
	/**
	  * Filter label
	  * 
	  * @var constant
	  * @access private
	  */
	var $_label = MESSAGE_HTML_FILTER_LABEL;
	
	/**
	  * Supported documents extension (must be in lowercase)
	  * 
	  * @var array
	  * @access private
	  */
	var $_supportedExtensions = array('htm','html','xhtml');
	
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
		//get tmp path
		$tmpPath = CMS_file::getTmpPath();
		if (!$tmpPath) {
			$this->_raiseError(get_class($this).' : '.__FUNCTION__.' : can\'t get temporary path to write in ...');
			return false;
		}
		//generate random filename
		$filename = sensitiveIO::sanitizeAsciiString('filter_'.APPLICATION_LABEL.'_'.microtime());
		while(is_file($tmpPath.'/'.$filename)) {
			$filename = sensitiveIO::sanitizeAsciiString('filter_'.APPLICATION_LABEL.'_'.microtime());
		}
		$this->_convertedDocument = $tmpPath.'/'.$filename;
		if (!touch($this->_convertedDocument)) {
			$this->_raiseError(get_class($this).' : '.__FUNCTION__.' : can\'t create temporary document : '.$this->_convertedDocument);
			return false;
		}
		//convert document
		if (!file_put_contents($this->_convertedDocument, html_entity_decode(strip_tags(file_get_contents($this->_sourceDocument)), ENT_COMPAT, (strtolower(APPLICATION_DEFAULT_ENCODING) != 'utf-8' ? 'ISO-8859-1' : 'UTF-8')))) {
			$this->_raiseError(get_class($this).' : '.__FUNCTION__.' : can\'t convert HTML document ... ');
			return false;
		}
		//run some cleaning task on converted document command
		$this->_cleanConverted();
		return true;
	}
}
?>