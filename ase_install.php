<?php
/**
  * Install or update Polymod
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  * @version $Id: ase_install.php,v 1.1.1.1 2007/09/04 15:01:29 sebastien Exp $
  */

require_once($_SERVER["DOCUMENT_ROOT"]."/cms_rc_admin.php");

//Check if Polymod was already installed (if so, it is an update) and which previous version was installed
$sql = "show tables";
$q = new CMS_query($sql);
$installed = false;
$v0_90_installed = false;
$v0_91_installed = false;
$v0_95_installed = false;
$v0_97_installed = false;
while ($table = $q->getValue(0)) {
	if ($table == 'mod_object_definition') {
		$installed = true;
	}
	if ($table == 'mod_object_plugin_definition') {
		$v0_90_installed = true;
	}
	if ($table == 'mod_object_rss_definition') {
		$v0_91_installed = true;
	}
}
if ($installed) {
	//check fields in mod_object_definition table
	$sql = "DESCRIBE mod_object_definition";
	$q = new CMS_query($sql);
	while ($field = $q->getValue('Field')) {
		if ($field == 'indexable_mod') {
			$v0_95_installed = true;
		}
	}
	//check fields in mod_object_field table
	$sql = "DESCRIBE mod_object_field";
	$q = new CMS_query($sql);
	while ($field = $q->getValue('Field')) {
		if ($field == 'desc_id_mof') {
			$v0_97_installed = true;
		}
	}
}
//do install
if (!$installed) {
	echo "Polymod installation : Not installed : Launch installation ...<br />";
	if (CMS_patch::executeSqlScript(PATH_REALROOT_FS.'/sql/mod_polymod.sql',true)) {
		CMS_patch::executeSqlScript(PATH_REALROOT_FS.'/sql/mod_polymod.sql',false);
		if (CMS_patch::executeSqlScript(PATH_REALROOT_FS.'/sql/mod_polymod_I18NM_messages.sql',true)) {
			CMS_patch::executeSqlScript(PATH_REALROOT_FS.'/sql/mod_polymod_I18NM_messages.sql',false);
			echo "Polymod installation : Installation done.<br />";
		} else {
			echo "Polymod installation : INSTALLATION ERROR ! Problem in SQL syntax (messages) ...<br />";
		}
	} else {
		echo "Polymod installation : INSTALLATION ERROR ! Problem in SQL syntax (tables) ...<br />";
	}
} 
//upgrade only
else {
	echo "Polymod installation : Already installed : Launch update ...<br />";
	if (CMS_patch::executeSqlScript(PATH_REALROOT_FS.'/sql/mod_polymod_I18NM_messages.sql',true)) {
		CMS_patch::executeSqlScript(PATH_REALROOT_FS.'/sql/mod_polymod_I18NM_messages.sql',false);
		//Upgrade DB to V0.90
		if (!$v0_90_installed) {
			if (CMS_patch::executeSqlScript(PATH_REALROOT_FS.'/sql/updates/mod_polymod_update_0.90.sql',true)) {
				CMS_patch::executeSqlScript(PATH_REALROOT_FS.'/sql/updates/mod_polymod_update_0.90.sql',false);
				echo "Polymod upgrade DB to V0.90 : Done.<br />";
			} else {
				echo "Polymod upgrade DB to V0.90 : FAILED !<br />";
			}
		}
		//Upgrade DB to V0.91
		if (!$v0_91_installed) {
			if (CMS_patch::executeSqlScript(PATH_REALROOT_FS.'/sql/updates/mod_polymod_update_0.91.sql',true)) {
				CMS_patch::executeSqlScript(PATH_REALROOT_FS.'/sql/updates/mod_polymod_update_0.91.sql',false);
				echo "Polymod upgrade DB to V0.91 : Done.<br />";
			} else {
				echo "Polymod upgrade DB to V0.91 : FAILED !<br />";
			}
		}
		//Upgrade DB to V0.95
		if (!$v0_95_installed) {
			if (CMS_patch::executeSqlScript(PATH_REALROOT_FS.'/sql/updates/mod_polymod_update_0.95.sql',true)) {
				CMS_patch::executeSqlScript(PATH_REALROOT_FS.'/sql/updates/mod_polymod_update_0.95.sql',false);
				echo "Polymod upgrade DB to V0.95 : Done.<br />";
			} else {
				echo "Polymod upgrade DB to V0.95 : FAILED !<br />";
			}
		}
		//Upgrade DB to V0.97
		if (!$v0_97_installed) {
			if (CMS_patch::executeSqlScript(PATH_REALROOT_FS.'/sql/updates/mod_polymod_update_0.97.sql',true)) {
				CMS_patch::executeSqlScript(PATH_REALROOT_FS.'/sql/updates/mod_polymod_update_0.97.sql',false);
				echo "Polymod upgrade DB to V0.97 : Done.<br />";
			} else {
				echo "Polymod upgrade DB to V0.97 : FAILED !<br />";
			}
		}
		//launch definitions updates
		if ($return = @file_get_contents(CMS_websitesCatalog::getMainURL().PATH_ADMIN_MODULES_WR.'/polymod/update-definitions.php')) {
			echo $return;
		} else {
			echo '<a href="'.CMS_websitesCatalog::getMainURL().PATH_ADMIN_MODULES_WR.'/polymod/update-definitions.php" class="admin" target="_blank">Please click this link to compile all modules definitions</a><br />';
		}
		echo "Polymod : Update done.<br />";
		echo "/!\ To ensure rows compilation compatibility, you should regenerate the website /!\<br />";
	} else {
		echo "Polymod installation : UPDATE ERROR ! Problem in SQL syntax (messages) ...<br />";
	}
}
//here copy some files only if Automne < 3.2.2
/*if (AUTOMNE_VERSION == '3.2.0' || AUTOMNE_VERSION == '3.2.1') {
	if (CMS_file::copyTo(PATH_TMP_FS.'/automne/admin/modules_admin.php',PATH_REALROOT_FS.'/automne/admin/modules_admin.php')
		&& CMS_file::copyTo(PATH_TMP_FS.'/automne/admin/fckeditor/editor/js/fckeditorcode_gecko.js',PATH_REALROOT_FS.'/automne/admin/fckeditor/editor/js/fckeditorcode_gecko.js')
		&& CMS_file::copyTo(PATH_TMP_FS.'/automne/admin/fckeditor/editor/js/fckeditorcode_ie.js',PATH_REALROOT_FS.'/automne/admin/fckeditor/editor/js/fckeditorcode_ie.js')
		&& CMS_file::copyTo(PATH_TMP_FS.'/automne/admin/fckeditor/editor/css/fck_editorarea.css',PATH_REALROOT_FS.'/automne/admin/fckeditor/editor/css/fck_editorarea.css')
		) {
		
		CMS_file::chmodFile(FILES_CHMOD, PATH_REALROOT_FS.'/automne/admin/modules_admin.php');
		CMS_file::chmodFile(FILES_CHMOD, PATH_REALROOT_FS.'/automne/admin/fckeditor/editor/js/fckeditorcode_gecko.js');
		CMS_file::chmodFile(FILES_CHMOD, PATH_REALROOT_FS.'/automne/admin/fckeditor/editor/js/fckeditorcode_ie.js');
		CMS_file::chmodFile(FILES_CHMOD, PATH_REALROOT_FS.'/automne/admin/fckeditor/editor/css/fck_editorarea.css');
		
		echo "Polymod installation : Automne update done.<br /><br />";
	} else {
		echo "Polymod installation : Automne update ERROR ! Can not copy update files ...<br />";
	}
}*/
?>