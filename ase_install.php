<?php
/**
  * Install or update ASE module
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  * @version $Id: ase_install.php,v 1.7 2009/08/12 14:31:24 sebastien Exp $
  */

require_once($_SERVER["DOCUMENT_ROOT"]."/cms_rc_admin.php");

//check if ASE is already installed (if so, it is an update)
$sql = "show tables";
$q = new CMS_query($sql);
$installed = false;
while ($table = $q->getValue(0)) {
	if ($table == 'mod_ase_document') {
		$installed = true;
	}
}
if (!$installed) {
	echo "ASE installation : Not installed : Launch installation ...<br />";
	if (CMS_patch::executeSqlScript(PATH_REALROOT_FS.'/sql/mod_ase.sql',true)) {
		CMS_patch::executeSqlScript(PATH_REALROOT_FS.'/sql/mod_ase.sql',false);
		//copy module parameters file and module row
		if (CMS_file::copyTo(PATH_TMP_FS.PATH_PACKAGES_WR.'/modules/ase_rc.xml',PATH_PACKAGES_FS.'/modules/ase_rc.xml')
			&& CMS_file::copyTo(PATH_TMP_FS.PATH_TEMPLATES_ROWS_WR.'/mod_ase.xml',PATH_TEMPLATES_ROWS_FS.'/mod_ase.xml')
			&& CMS_file::copyTo(PATH_TMP_FS.'/css/modules/ase.css',PATH_REALROOT_FS.'/css/modules/ase.css')
			&& CMS_file::copyTo(PATH_TMP_FS.PATH_TEMPLATES_WR.'/mod_ase_search.php',PATH_TEMPLATES_FS.'/mod_ase_search.php')
			) {
			CMS_file::chmodFile(FILES_CHMOD, PATH_PACKAGES_FS.'/modules/ase_rc.xml');
			CMS_file::chmodFile(FILES_CHMOD, PATH_TEMPLATES_ROWS_FS.'/mod_ase.xml');
			CMS_file::chmodFile(FILES_CHMOD, PATH_REALROOT_FS.'/css/modules/ase.css');
			CMS_file::chmodFile(FILES_CHMOD, PATH_TEMPLATES_FS.'/mod_ase_search.php');
			echo "ASE installation : Installation done.<br /><br />";
		} else {
			echo "ASE installation : INSTALLATION ERROR ! Can not copy parameters file ...<br />";
		}
	} else {
		echo "ASE installation : INSTALLATION ERROR ! Problem in SQL syntax (SQL tables file) ...<br />";
	}
} else {
	echo "ASE installation : Already installed : Launch update ...<br />";
	//load destination module parameters
	$module = CMS_modulesCatalog::getByCodename('ase');
	$moduleParameters = $module->getParameters(false,true);
	if (!is_array($moduleParameters)) {
		$moduleParameters = array();
	}
	//load the XML data of the source the files
	$sourceXML = new CMS_file(PATH_TMP_FS.PATH_PACKAGES_WR.'/modules/ase_rc.xml');
	$domdocument = new CMS_DOMDocument();
	try {
		$domdocument->loadXML($sourceXML->readContent("string"));
	} catch (DOMException $e) {}
	$paramsTags = $domdocument->getElementsByTagName('param');
	$sourceParameters = array();
	foreach ($paramsTags as $aTag) {
		$name = ($aTag->hasAttribute('name')) ? $aTag->getAttribute('name') : '';
		$type = ($aTag->hasAttribute('type')) ? $aTag->getAttribute('type') : '';
		$sourceParameters[$name] = array(CMS_DOMDocument::DOMElementToString($aTag, true),$type);
	}
	//merge the two tables of parameters
	$resultParameters = array_merge($sourceParameters,$moduleParameters);
	//set new parameters to the module
	if ($module->setAndWriteParameters($resultParameters)) {
		echo 'Modules parameters successfully merged<br />';
		echo "ASE installation : Update done.<br />";
	} else {
		echo "ASE installation : UPDATE ERROR ! Problem for merging modules parameters ...";
	}
}
$instruction = new CMS_file(PATH_TMP_FS.'/HOW_TO_INSTALL');
echo nl2br($instruction->readContent());
?>