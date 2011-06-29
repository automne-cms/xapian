<?php
/**
  * Install or update ASE module
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  * @version $Id: ase_install.php,v 1.7 2009/08/12 14:31:24 sebastien Exp $
  */

require_once(dirname(__FILE__).'/../../cms_rc_admin.php');

//check if ASE is already installed (if so, it is an update)
$sql = "show tables";
$q = new CMS_query($sql);
$installed = false;
while ($table = $q->getValue(0)) {
	if ($table == 'mod_ase_document') {
		$q = new CMS_query("select * from modules where codename_mod='ase'");
		if ($q->getNumRows()) {
			$installed = true;
		}
	}
}
if (!$installed) {
	echo "ASE installation : Not installed : Launch installation ...<br />";
	if (CMS_patch::executeSqlScript(PATH_MAIN_FS.'/sql/mod_ase.sql',true)) {
		CMS_patch::executeSqlScript(PATH_MAIN_FS.'/sql/mod_ase.sql',false);
		//copy module parameters file and module row
		if (CMS_file::copyTo(PATH_TMP_FS.'/automne/tmp/modules/ase_rc.xml',PATH_PACKAGES_FS.'/modules/ase_rc.xml')
			&& CMS_file::copyTo(PATH_TMP_FS.'/automne/templates/rows/mod_ase.xml',PATH_TEMPLATES_ROWS_FS.'/mod_ase.xml')
			&& CMS_file::copyTo(PATH_TMP_FS.'/css/modules/ase.css',PATH_REALROOT_FS.'/css/modules/ase.css')
			&& CMS_file::copyTo(PATH_TMP_FS.'/automne/templates/mod_ase_search.php',PATH_TEMPLATES_FS.'/mod_ase_search.php')
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
	#change field language_mased of mod_ase_document to use a longer field size for 5 characters language code storage
	$sql = "show columns from mod_ase_document";
	$q = new CMS_query($sql);
	$installed = false;
	while($r = $q->getArray()) {
		if ($r["Field"] == "language_mased" && $r["Type"] == 'char(5)') {
			$installed = true;
		}
	}
	if (!$installed) {
		$q = new CMS_query('ALTER TABLE  mod_ase_document CHANGE  language_mased  language_mased CHAR( 5 ) NOT NULL');
		echo 'Database successfuly updated (handle language codes of 5 characters)<br/>';
	}
	//check modules parameters
	if (!file_exists(PATH_PACKAGES_FS.'/modules/ase_rc.xml')) {
		CMS_file::copyTo(PATH_TMP_FS.'/automne/tmp/modules/ase_rc.xml',PATH_PACKAGES_FS.'/modules/ase_rc.xml');
		CMS_file::chmodFile(FILES_CHMOD, PATH_PACKAGES_FS.'/modules/ase_rc.xml');
		echo "ASE installation : Update done.<br /><br />";
	} else {
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
			//move databases if needed
			if (is_dir(PATH_MODULES_FILES_FS.'/'.MOD_ASE_CODENAME.'/databases/')) {
				//create new directory
				$errorUpdate = false;
				if (!is_dir(PATH_MAIN_FS."/".MOD_ASE_CODENAME.'/databases/')) {
					if (!CMS_file::makeDir(PATH_MAIN_FS."/".MOD_ASE_CODENAME.'/databases/')) {
						$errorUpdate = true;
						echo 'Error : Cannot create directory '.PATH_MAIN_WR.'/'.MOD_ASE_CODENAME.'/databases/<br />';
					}
				}
				//copy all files from old directory to new one if they do not already exists
				if (!$errorUpdate) {
					try{
						foreach ( new RecursiveIteratorIterator(new RecursiveDirectoryIterator(PATH_MODULES_FILES_FS."/".MOD_ASE_CODENAME.'/databases'), RecursiveIteratorIterator::SELF_FIRST) as $file) {
							if ($file->isFile() && $file->getFilename() != '.htaccess') {
								$to = str_replace(PATH_MODULES_FILES_FS."/".MOD_ASE_CODENAME, PATH_MAIN_FS."/".MOD_ASE_CODENAME, $file->getPathname());
								if (!file_exists($to) && !CMS_file::copyTo($file->getPathname(), $to)) {
									//echo "Error copy on ".$file->getPathname().' -> '.$to.'<br />';
									$errorUpdate = true;
								}
							}
						}
					} catch(Exception $e) {}
				}
				//remove old dir
				if (!$errorUpdate) {
					if (!CMS_file::deltree(PATH_MODULES_FILES_FS.'/'.MOD_ASE_CODENAME, true)) {
						echo '/!\ To complete module update, delete directory '.PATH_MODULES_FILES_WR.'/'.MOD_ASE_CODENAME.' <br/> <br/>';
					} else {
						echo "ASE installation : Update done.<br /><br />";
					}
				} else {
					echo '/!\ To complete module update, copy all files from '.PATH_MODULES_FILES_WR.'/'.MOD_ASE_CODENAME.'/databases to '.PATH_MAIN_WR."/".MOD_ASE_CODENAME.'/databases then delete directory '.PATH_MODULES_FILES_WR.'/'.MOD_ASE_CODENAME.' <br/><br/>';
				}
			} else {
				echo "ASE installation : Update done.<br /><br />";
			}
		} else {
			echo "ASE installation : UPDATE ERROR ! Problem for merging modules parameters ...<br /><br />";
		}
	}
}
$instruction = new CMS_file(PATH_TMP_FS.'/HOW_TO_INSTALL');
echo nl2br($instruction->readContent());
?>