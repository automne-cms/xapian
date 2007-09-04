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
## | Author: Cédric Soret <cedric.soret@ws-interactive.fr>                |
## +----------------------------------------------------------------------+
##
## $Id: sxwtoplain.sh,v 1.1.1.1 2007/09/04 15:01:29 sebastien Exp $

##
## Convert Open Office documents (open document format) into plain text
##

## +----------------------------------------------------------------------+
## | Vars and Parameters                                                  |
## +----------------------------------------------------------------------+

## Temporary path
temppath=/tmp/sxwtoplain

## ------------------------------------------------------------------------

if [ ! -d $temppath ] ; then
	mkdir $temppath
fi
if [ -f $temppath/content.xml ] ; then
	rm $temppath/content.xml
fi
if [ -f $1 ] ; then
	unzip $1 content.xml -d $temppath/ > /dev/null 2>&1
	if [ -f $temppath/content.xml ] ; then
		cat $temppath/content.xml | iconv -c -f UTF-8 -t ISO-8859-1 | sed -e "s/<[^>]*>/ /g"
	fi
fi