<?php
/**
  * Install or update ASE module
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  * @version $Id: ase_install.php,v 1.3 2007/09/04 15:58:36 sebastien Exp $
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
			) {
			CMS_file::chmodFile(FILES_CHMOD, PATH_PACKAGES_FS.'/modules/ase_rc.xml');
			CMS_file::chmodFile(FILES_CHMOD, PATH_TEMPLATES_ROWS_FS.'/mod_ase.xml');
			echo "ASE installation : Installation done.<br /><br />";
		} else {
			echo "ASE installation : INSTALLATION ERROR ! Can not copy parameters file ...<br />";
		}
	} else {
		echo "ASE installation : INSTALLATION ERROR ! Problem in SQL syntax (SQL tables file) ...<br />";
	}
} else {
	echo "ASE installation : Already installed : Launch update ...<br />";
	
	//merge module parameters file
	$module = CMS_modulesCatalog::getByCodename('ase');
	$moduleParameters = $module->getParameters(false,true);
	
	//load the XML data of the source the files
	$sourceXML = new CMS_file(PATH_TMP_FS.PATH_PACKAGES_WR.'/modules/ase_rc.xml');
	$parser = new CMS_XMLParser(XMLPARSER_DATA_TYPE_CDATA, $sourceXML->readContent("string"));
	$parser->addWantedTag("param", false);
	$parser->setDebug(false);
	$parser->setLog(false);
	$parser->parse();
	$sourceParameters = array();
	foreach ($parser->getTags() as $aTag) {
		$attributes = $aTag->getAttributes();
		$sourceParameters[$attributes["name"]] = array($aTag->getInnerContent(),$attributes["type"]);
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