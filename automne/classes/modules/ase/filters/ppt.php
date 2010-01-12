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
// $Id: ppt.php,v 1.4 2010/01/12 09:14:37 sebastien Exp $

/**
  * Class CMS_filter_ppt
  *
  * Represent a filter for Microsoft Powerpoint documents.
  *
  * @package CMS
  * @subpackage module
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

/**
  * ASE Messages
  */
define('MESSAGE_PPT_FILTER_LABEL', 10);

class CMS_filter_ppt extends CMS_filter_common
{
	/**
	  * Filter label
	  * 
	  * @var constant
	  * @access private
	  */
	var $_label = MESSAGE_PPT_FILTER_LABEL;
	
	/**
	  * Supported documents extension (must be in lowercase)
	  * 
	  * @var array
	  * @access private
	  */
	var $_supportedExtensions = array('ppt','pps');
	
	/**
	  * All binaries needed to the filter
	  * 
	  * @var array
	  * @access private
	  */
	var $_binaries = array('ppthtml');
	
	/**
	  * Create conversion command line
	  *
	  * @return string : the conversion command
	  * @access private
	  */
	function _createConversionCommand() {
		return  'cd '.dirname($this->_sourceDocument).';'.$this->_binaries[0].' '.basename($this->_sourceDocument).' > '.$this->_convertedDocument;
	}
	
	function _cleanConverted() {
		//transcode document content
		if (!file_put_contents($this->_convertedDocument, iconv("UTF-8", (strtolower(APPLICATION_DEFAULT_ENCODING) != 'utf-8' ? 'ISO-8859-1' : 'UTF-8')."//IGNORE", file_get_contents($this->_convertedDocument)))) {
			$this->_raiseError(get_class($this).' : '.__FUNCTION__.' : can\'t convert DOCX document ... ');
			return false;
		}
		//convert HTML to plain text
		if (!file_put_contents($this->_convertedDocument, html_entity_decode($this->stripTags(file_get_contents($this->_convertedDocument)), ENT_COMPAT, (strtolower(APPLICATION_DEFAULT_ENCODING) != 'utf-8' ? 'ISO-8859-1' : 'UTF-8')))) {
			$this->_raiseError(get_class($this).' : '.__FUNCTION__.' : can\'t convert HTML document ... ');
			return false;
		}
	}
}
?>