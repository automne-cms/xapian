#!/bin/sh
## 
## +----------------------------------------------------------------------+
## | Automne (TM)                                                         |
## +----------------------------------------------------------------------+
## | Copyright (c) 2000-2005 WS Interactive                               |
## +----------------------------------------------------------------------+
## | This source file is subject to version 2.0 of the GPL license,       |
## | or (at your discretion) to version 3.0 of the PHP license.           |
## | The first is bundled with this package in the file LICENSE-GPL, and  |
## | is available at through the world-wide-web at                        |
## | http://www.gnu.org/copyleft/gpl.html.                                |
## | The later is bundled with this package in the file LICENSE-PHP, and  |
## | is available at through the world-wide-web at                        |
## | http://www.php.net/license/3_0.txt.                                  |
## +----------------------------------------------------------------------+
## | Author: Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>      |
## +----------------------------------------------------------------------+

##
## Convert Microsoft Excel 2007 documents (xlsx format) into plain text
##

## +----------------------------------------------------------------------+
## | Vars and Parameters                                                  |
## +----------------------------------------------------------------------+

## Temporary path
temppath=/tmp/xlsxtoplain

## ------------------------------------------------------------------------

if [ ! -d $temppath ] ; then
	mkdir $temppath
fi
if [ -f $temppath/xl/sharedStrings.xml ] ; then
	rm $temppath/xl/sharedStrings.xml
fi
if [ -f $1 ] ; then
	unzip $1 xl/sharedStrings.xml -d $temppath/ > /dev/null 2>&1
	if [ -f $temppath/xl/sharedStrings.xml ] ; then
		cat $temppath/xl/sharedStrings.xml | sed -e "s/<[^>]*>/ /g"
	fi
fi