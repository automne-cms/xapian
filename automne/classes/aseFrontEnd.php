<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Automne (TM)                                                         |
// +----------------------------------------------------------------------+
// | Copyright (c) 2000-2005 WS Interactive                               |
// | Copyright (c) 2000-2004 Cédric Soret                                 |
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
// $Id: aseFrontEnd.php,v 1.4 2009/06/08 14:22:13 sebastien Exp $

/**
  * Main Include File of the Frontend Package : ASE
  * Includes all of the package files.
  */
session_name('AutomneSession');
@session_start();
//Delete polymod session if already exists
if (isset($_SESSION['polyModule']) && sizeof($_SESSION['polyModule'])) {
	unset($_SESSION['polyModule']);
}
require_once($_SERVER["DOCUMENT_ROOT"]."/cms_rc_frontend.php");
?>