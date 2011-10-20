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

/**
  * Class CMS_CJKTokenizer
  *
  * This class will tokenize CJK (Chinese, Japanese, Korean) texts so they can be indexed
  * The tokenization use ngram segmentation to do the job. Only CJK caracters will be tokenized using ngram. Latin characters are skipped.
  * 
  * @package CMS
  * @subpackage module
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

class CMS_CJKTokenizer extends CMS_grandFather {
	public static $ngramLength = 2;
	
	public static function tokenize($text) {
		$length = io::strlen($text);
		$textDatas = array();
		$textData = array();
		$last = false;
		for ($i = 0; $i < $length; $i++) {
			$c = io::substr($text , $i , 1);
			$type = self::_isCJK($c) ? 'cjk' : 'latin';
			if ($last != $type) {
				$textDatas[] = $textData;
				$textData = array();
			}
			$textData = array(
				'type' => $type,
				'text' => (isset($textData['text']) ? $textData['text'].$c : $c)
			);
			$last = $type;
		}
		
		foreach ($textDatas as $key => $textData) {
			if (!isset($textData['type']) || !isset($textData['text'])) {
				unset($textDatas[$key]);
			} else {
				if ($textData['type'] == 'cjk') {
					$text = self::_getNgrams($textDatas[$key]['text'], 2);
					if ($text) {
						$textDatas[$key]['text'] = implode(' ', $text);
					} else {
						unset($textDatas[$key]);
					}
				} else {
					//remove accents, underscore and quotes
					$text = CMS_XapianIndexer::removeAccents($textDatas[$key]['text']);
					//strip whitespaces
					$textDatas[$key]['text'] = trim(preg_replace('/\s\s+/', ' ', $text));
					if (!$textDatas[$key]['text']) {
						unset($textDatas[$key]);
					}
				}
			}
		}
		
		$text = '';
		foreach ($textDatas as $key => $textData) {
			$text .= $textData['text'].' ';
		}
		return $text;
	}
	
	protected static function _isCJK($c) {
	    $p = self::_hexChars($c);
		return (($p >= 0x2E80 && $p <= 0x2EFF)
	     || ($p >= 0x3000 && $p <= 0x303F)
	     || ($p >= 0x3040 && $p <= 0x309F)
	     || ($p >= 0x30A0 && $p <= 0x30FF)
	     || ($p >= 0x3100 && $p <= 0x312F)
	     || ($p >= 0x3130 && $p <= 0x318F)
	     || ($p >= 0x3190 && $p <= 0x319F)
	     || ($p >= 0x31A0 && $p <= 0x31BF)
	     || ($p >= 0x31C0 && $p <= 0x31EF)
	     || ($p >= 0x31F0 && $p <= 0x31FF)
	     || ($p >= 0x3200 && $p <= 0x32FF)
	     || ($p >= 0x3300 && $p <= 0x33FF)
	     || ($p >= 0x3400 && $p <= 0x4DBF)
	     || ($p >= 0x4DC0 && $p <= 0x4DFF)
	     || ($p >= 0x4E00 && $p <= 0x9FFF)
	     || ($p >= 0xA700 && $p <= 0xA71F)
	     || ($p >= 0xAC00 && $p <= 0xD7AF)
	     || ($p >= 0xF900 && $p <= 0xFAFF)
	     || ($p >= 0xFE30 && $p <= 0xFE4F)
	     || ($p >= 0xFF00 && $p <= 0xFFEF)
	     || ($p >= 0x20000 && $p <= 0x2A6DF)
	     || ($p >= 0x2F800 && $p <= 0x2FA1F)
	     || ($p >= 0x2F800 && $p <= 0x2FA1F));
	}
	
	protected static function _hexChars($data) {
	    $mb_hex = '';
	    for ($i=0; $i < io::strlen($data); $i++) {
	        $c = io::substr($data, $i, 1);
	        $o = unpack('N', mb_convert_encoding($c, 'UCS-4BE', 'UTF-8'));
	        $mb_hex .= self::_hexFormat($o[1]);
	    }
	    return '0x'.$mb_hex;
	}
	
	protected static function _hexFormat($o) {
	    $h = strtoupper(dechex($o));
	    $len = strlen($h);
	    if ($len % 2 == 1) {
	        $h = "0$h";
		}
	    return $h;
	}
	
	protected static function _getNgrams($word, $n = '') {
		if (!$n) {
			$n = self::$ngramLength;
		}
		$ngrams = array();
		$len = io::strlen($word);
		for($i = 0; $i < $len; $i++) {
				if($i > ($n - 2)) {
						$ng = '';
						for($j = $n-1; $j >= 0; $j--) {
								$ng .= io::substr($word , ($i-$j) , 1);
						}
						$ngrams[] = $ng;
				}
		}
		return $ngrams;
	}
}
?>