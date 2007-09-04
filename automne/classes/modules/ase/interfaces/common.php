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
// $Id: common.php,v 1.1.1.1 2007/09/04 15:01:29 sebastien Exp $

/**
  * Class CMS_ase_interface
  * 
  * Represent common interface methods between modules and ase module
  *
  * @package CMS
  * @subpackage module
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

/**
  * Perform a simple text replace
  * This should be used when the string does not contain HTML
  * (on by default)
  */
define('STR_HIGHLIGHT_SIMPLE', 1);

/**
  * Only match whole words in the string
  * (off by default)
  */
define('STR_HIGHLIGHT_WHOLEWD', 2);

/**
  * Case sensitive matching
  * (on by default)
  */
define('STR_HIGHLIGHT_CASESENS', 4);

/**
  * Overwrite links if matched
  * This should be used when the replacement string is a link
  * (off by default)
  */
define('STR_HIGHLIGHT_STRIPLINKS', 8);

class CMS_ase_interface extends CMS_grandFather {
	var $_filters = array();
	var $_codename;
	var $_results;
	
	function CMS_ase_interface($codename) {
		$this->_codename = $codename;
		return;
	}
	
	function isActive() {
		//assume all modules that has an interface are active, otherwise, module should properly set this method
		return true;
	}
	
	/*************************************************************
	*             SEARCH RESULTS DEFAULT METHODS                 *
	*************************************************************/
	
	function getMatchValue($matchInfo, $value, $parameters = array()) {
		return '';
	}
	
	function getAvailableMatchValues($matchInfo) {
		return array();
	}
	
	function setResultsUID($resultsUID) {
		return true;
	}
	
	/*************************************************************
	*                STRINGS MANAGEMENT METHODS                  *
	*************************************************************/
	
	/**
	  * Highlight a string in text without corrupting HTML tags
	  *
	  * @author      Aidan Lister <aidan@php.net>
	  * @version     3.1.1
	  * @link        http://aidanlister.com/repos/v/function.str_highlight.php
	  * @param       string          $text           Haystack - The text to search
	  * @param       array|string    $needle         Needle - The string to highlight
	  * @param       bool            $options        Bitwise set of options
	  * @param       array           $highlight      Replacement string
	  * @return      Text with needle highlighted
	  */
	function strHighlight($text, $needle, $options = null, $highlight = null)
	{
	    if ($options === null) {
			$options = STR_HIGHLIGHT_SIMPLE & STR_HIGHLIGHT_CASESENS;
		}
		// Default highlighting
	    if ($highlight === null) {
	        $highlight = '<strong>\1</strong>';
	    }
	    // Select pattern to use
	    if ($options & STR_HIGHLIGHT_SIMPLE) {
	        $pattern = '#(%s)#';
	        $sl_pattern = '#(%s)#';
	    } else {
	        $pattern = '#(?!<.*?)(%s)(?![^<>]*?>)#';
	        $sl_pattern = '#<a\s(?:.*?)>(%s)</a>#';
	    }
	    // Case sensitivity
	    if (!($options & STR_HIGHLIGHT_CASESENS)) {
	        $pattern .= 'i';
	        $sl_pattern .= 'i';
	    }
	    $needle = (array) $needle;
	    foreach ($needle as $needle_s) {
	        $needle_s = preg_quote($needle_s);
	        // Escape needle with optional whole word check
	        if ($options & STR_HIGHLIGHT_WHOLEWD) {
	            $needle_s = '\b' . $needle_s . '\b';
	        }
	        // Strip links
	        if ($options & STR_HIGHLIGHT_STRIPLINKS) {
	            $sl_regex = sprintf($sl_pattern, $needle_s);
	            $text = preg_replace($sl_regex, '\1', $text);
	        }
	        $regex = sprintf($pattern, $needle_s);
	        $text = preg_replace($regex, $highlight, $text);
	    }
	    return $text;
	}
	
	/**
	  * Chop a string into a smaller string.
	  *
	  * @author      Aidan Lister <aidan@php.net>
	  * @version     1.1.0
	  * @link        http://aidanlister.com/repos/v/function.strChop.php
	  * @param       mixed  $string   The string you want to shorten
	  * @param       int    $length   The length you want to shorten the string to
	  * @param       bool   $center   If true, chop in the middle of the string
	  * @param       mixed  $append   String appended if it is shortened
	  */
	function strChop($string, $length = 60, $center = false, $append = null)
	{
	    // Set the default append string
	    if ($append === null) {
	        $append = ($center === true) ? ' ... ' : ' ...';
	    }
	    // Get some measurements
	    $len_string = strlen($string);
	    $len_append = strlen($append);
	    // If the string is longer than the maximum length, we need to chop it
	    if ($len_string > $length) {
	        // Check if we want to chop it in half
	        if ($center === true) {
	            // Get the lengths of each segment
	            $len_start = $length / 2;
	            $len_end = $len_start - $len_append;
	            // Get each segment
	            $seg_start = substr($string, 0, $len_start);
	            $seg_end = substr($string, $len_string - $len_end, $len_end);
	            // Stick them together
	            $string = $seg_start . $append . $seg_end;
	        } else {
	            // Otherwise, just chop the end off
	            $string = substr($string, 0, $length - $len_append) . $append;
	        }
	    }
	    return $string;
	}
}
?>