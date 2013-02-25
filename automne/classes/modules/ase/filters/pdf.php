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
// $Id: pdf.php,v 1.3 2009/11/13 17:31:13 sebastien Exp $

/**
  * Class CMS_filter_pdf
  *
  * Represent a filter for PDF documents.
  *
  * @package CMS
  * @subpackage module
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

/**
  * ASE Messages
  */
define('MESSAGE_PDF_FILTER_LABEL', 8);

class CMS_filter_pdf extends CMS_filter_common {
	/**
	  * Filter label
	  * 
	  * @var constant
	  * @access private
	  */
	var $_label = MESSAGE_PDF_FILTER_LABEL;
	
	/**
	  * Supported documents extension (must be in lowercase)
	  * 
	  * @var array
	  * @access private
	  */
	var $_supportedExtensions = array('pdf');
	
	/**
	  * All binaries needed to the filter
	  * 
	  * @var array
	  * @access private
	  */
	var $_binaries = array('pdftotext');
	
	/**
	  * Create conversion command line
	  *
	  * @return string : the conversion command
	  * @access private
	  */
	function _createConversionCommand() {
		return  $this->_binaries[0].' '.$this->_sourceDocument.' '.$this->_convertedDocument;
	}
}
?>