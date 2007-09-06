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
// | Author: S�bastien Pauchet <sebastien.pauchet@ws-interactive.fr>      |
// +----------------------------------------------------------------------+
//
// $Id: standard.php,v 1.1.1.1 2007/09/04 15:01:29 sebastien Exp $

/**
  * Class CMS_standard_ase
  * 
  * Represent an interface between standard module and ase module
  *
  * @package CMS
  * @subpackage module
  * @author S�bastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

Define("MESSAGE_STANDARD_UID_PAGE_TITLE", 1328);
Define("MESSAGE_STANDARD_UID_FILE_TITLE", 191);

class CMS_standard_ase extends CMS_ase_interface {
	/*************************************************************
	*                   INDEXATION METHODS                       *
	*************************************************************/
	
	/**
	  * Get the title for a given UID
	  *
	  * @param string $uid : the uid to get title for
	  * @return string : the title
	  * @access public
	  */
	function getTitle($uid) {
		global $cms_language;
		if (sensitiveIO::isPositiveInteger($uid)) {
			$page = CMS_tree::getPageByID($uid);
			return $cms_language->getMessage(MESSAGE_STANDARD_UID_PAGE_TITLE).' : '.$page->getTitle(true).' ('.$uid.')';
		} elseif (substr($uid,0,4) == 'file') {
			$fileID = (int) array_pop(explode('_',$uid));
			if (!$fileID) {
				$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : no file ID found for uid : '.$uid);
				return false;
			}
			$sql = "
				select
					*
				from
					 blocksFiles_public
				where
					id='".sensitiveIO::sanitizeSQLString($fileID)."'
			";
			$q = new CMS_query($sql);
			if (!$q->getNumrows()) {
				$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : no file found in DB for uid : '.$uid);
				return false;
			}
			$fileDatas = $q->getArray();
			return $cms_language->getMessage(MESSAGE_STANDARD_UID_FILE_TITLE).' : '.$fileDatas['label'].' ('.$cms_language->getMessage(MESSAGE_STANDARD_UID_PAGE_TITLE).' : '.$fileDatas['page'].')';
		}
		return false;
	}
	
	/**
	  * Module document info : get infos for a given document :
	  * - Indexable content/files
	  * - Document values and attributes
	  *
	  * @param CMS_ase_document &$document the document to get infos
	  * @return Boolean true/false
	  * @access public
	  */
	function getDocumentInfos(&$document) {
		//this UID is a page
		if (sensitiveIO::isPositiveInteger($document->getValue('uid'))) {
			//page content
			$page = CMS_tree::getPageByID($document->getValue('uid'));
			if ($page->hasError()) {
				$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : given page id does not exists ... ');
				return false;
			}
			if (!$page->getPrintStatus()) {
				$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : can not get infos from page which hasn\'t anything to print ... ');
				return false;
			}
			//set all page attributes and values
			$this->_addDocumentInfosForPage($document, $page);
			$language = new CMS_language($page->getLanguage(true));
			//set document type
			$document->setValue('type', 'html');
			//add document type as a module attribute
			$document->setModuleAttribute('type', 'html');
			
			//if no user founded, instanciate super administrator to allow full document content to be indexed without worry about rights
			global $cms_user, $cms_language;
			if (!is_object($cms_user)) {
				$cms_user = new CMS_profile_user(1);
			}
			//get page content
			$pageContent = $page->getContent($language, PAGE_VISUALMODE_HTML_PUBLIC_INDEXABLE);
			//append page description and keywords if any
			if ($description = $page->getDescription(true, false)) {
				$pageContent .= '<br /><br />'.$description;
			}
			if ($keywords = $page->getKeywords(true, false)) {
				$pageContent .= '<br /><br />'.$keywords;
			}
			//set document content
			$document->addContent($pageContent, 'html');
			//set document title
			$document->setValue('title', $page->getTitle(true));
			return true;
		} else
		//this UID is a file in a page
		if (substr($document->getValue('uid'),0,4) == 'file') {
			$fileID = (int) array_pop(explode('_',$document->getValue('uid')));
			if (!$fileID) {
				$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : no file ID found for uid : '.$document->getValue('uid'));
				return false;
			}
			$sql = "
				select
					*
				from
					 blocksFiles_public
				where
					id='".sensitiveIO::sanitizeSQLString($fileID)."'
			";
			$q = new CMS_query($sql);
			if (!$q->getNumrows()) {
				$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : no file found in DB for uid : '.$document->getValue('uid'));
				return false;
			}
			$fileDatas = $q->getArray();
			//set document path
			$filePath = PATH_MODULES_FILES_FS.'/'.MOD_STANDARD_CODENAME.'/public/'.$fileDatas['file'];
			//file content
			$file = new CMS_file($filePath, FILE_SYSTEM, TYPE_FILE);
			//check file
			if (!$file->exists()) {
				$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : given file does not exists in file system : '.$filePath);
				return false;
			}
			//add document filename
			$filename = explode('_',$fileDatas["file"]);
			unset($filename[0]);
			$originalFilename = substr(implode('_',$filename),32);
			//set original filename as content
			$document->addPlainTextContent($originalFilename);
			//set document type
			$fileInfos = pathinfo($filePath);
			$document->setValue('type', $fileInfos['extension']);
			//add document type as a module attribute
			$document->setModuleAttribute('type', $fileInfos['extension']);
			//then add file
			$document->addFile($filePath, $fileInfos['extension'],FILE_SYSTEM);
			//get page id
			$pageId = $fileDatas['page'];
			//set document language from page
			$page = CMS_tree::getPageByID($pageId);
			if ($page->hasError()) {
				$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : document page does not exists : '.$pageId);
				return false;
			}
			//set all page attributes and values
			$this->_addDocumentInfosForPage($document, $page);
			//set document title
			$document->setValue('title', $fileDatas['label']);
			//TODO : here we need to delete all documents with attribute page:pageID from database to take care of documents deletion in pages
			return true;
		}
		return true;
	}
	
	/**
	  * Private method to get some pages infos
	  *
	  * @param CMS_ase_document &$document the document to set infos
	  * @param CMS_page &$page the page from which we needs infos
	  * @return Boolean true/false
	  * @access private
	  */
	function _addDocumentInfosForPage(&$document, &$page) {
		//add page id as a module attribute
		$document->setModuleAttribute('page', $page->getID());
		//set document language
		$language = $page->getLanguage(true);
		$document->setValue('language', $language);
		$document->setModuleAttribute('language', $language);
		//website
		$website = $page->getWebsite();
		$document->setModuleAttribute('website', $website->getID());
		$websiteRoot = $website->getRoot();
		//set pages lineage
		$lineage = CMS_tree::getLineage($websiteRoot->getID(), $page->getID(), false);
		if (is_array($lineage)) {
			foreach ($lineage as $ancestor) {
				$document->setModuleAttribute('ancestor', $ancestor);
			}
		}
		return true;
	}
	
	/**
	  * Get all objects to index from module
	  * This is a short list : ie. only the documents from which we can gets all documents dependencies
	  *
	  * @return array(module uid)
	  * @access public
	  */
	function getShortUIDList() {
		//get all pages ID which are indexable (public and in userspace at least)
		$sql = "
			select
				id_pag
			from
				pages,
				resources,
				resourceStatuses
			where
				resource_pag=id_res
				and status_res=id_rs
				and location_rs='".RESOURCE_LOCATION_USERSPACE."'
				and publication_rs='".RESOURCE_PUBLICATION_PUBLIC."'
		";
		$q = new CMS_query($sql);
		$indexablePages = array();
		while ($id = $q->getValue("id_pag")) {
			$indexablePages[$id] = $id;
		}
		return $indexablePages;
	}
	
	/**
	  * Get all documents dependencies to index from given module uid
	  * If this method exists in interface, then the module use a two times indexation.
	  * - First call to getShortUIDList to get a short UIDList of objects to index
	  * - Second call to this method to get the complete indexation content
	  * This method is called during indexation for all uid given by getShortUIDList method
	  *
	  * @param string $uid : the uid to get dependencies for
	  * @return array(module uid)
	  * @access public
	  */
	function getIndexInfos($uid) {
		if (sensitiveIO::isPositiveInteger($uid)) {
			//set document language
			$page = CMS_tree::getPageByID($uid);
			//check page for error, print status and publication status
			if ($page->hasError() || !$page->getPrintStatus() || $page->getPublication() != RESOURCE_PUBLICATION_PUBLIC) {
				return false;
			}
			//TODO : here we need to add pages without any print content. In this case, only title, description and keywords should be indexed
			$infos = array(
				array('task' => 'delete', 'uid' => $uid, 'module' => MOD_STANDARD_CODENAME, 'deleteInfos' => array('page' => $uid)),
				array('uid' => $uid, 'module' => MOD_STANDARD_CODENAME),
			);
			//then get file documents for the given page.
			//this is not a clean manner for doing that stuff but for now, no time to do better
			$sql = "
				select
					distinct(id), blocksFiles_public.*
				from
					blocksFiles_public,
					mod_standard_clientSpaces_public
				where
					page='".sensitiveIO::sanitizeSQLString($uid)."'
					and rowsDefinition_cs = rowID
			";
			$q = new CMS_query($sql);
			if ($q->getNumrows()) {
				while ($data = $q->getArray()) {
					//set document path
					$filePath = PATH_MODULES_FILES_FS.'/'.MOD_STANDARD_CODENAME.'/public/'.$data['file'];
					//file content
					$file = new CMS_file($filePath, FILE_SYSTEM, TYPE_FILE);
					//check file
					if ($file->exists()) {
						//check if file type match installed filters
						$fileinfos = pathinfo($filePath);
						if (CMS_filter_catalog::getFilterForType($fileinfos['extension'])) {
							$infos[] = array('uid' => 'file_'.$data['page'].'_'.$data['id'],'module' => MOD_STANDARD_CODENAME);
						}
					}
				}
			}
			return $infos;
		}
		return false;
	}
	
	function getDeleteInfos($uid) {
		return array(array('uid' => $uid, 'module' => MOD_STANDARD_CODENAME, 'deleteInfos' => array('page' => $uid)));
	}
	
	/*************************************************************
	*                 SEARCH RESULTS METHODS                     *
	*************************************************************/
	
	/**
	  * Add a search context filter
	  *
	  * @param string $type : the filter type to add
	  * @return mixed value : the filter value
	  * @access public
	  */
	function addFilter($type, $value) {
		switch ($type) {
			case 'root':
				if (!sensitiveIO::isPositiveInteger($value)) {
					$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : root filter value must be a valid page ID : '.$value);
					return false;
				}
				$this->_filters['root'][] = $value;
			break;
			case 'publication date after':
				if (!is_a($value,'CMS_date')) {
					$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : publication date after filter value must be a valid CMS_date : '.$value);
					return false;
				}
				$this->_filters['publication date after'] = $value;
			break;
			case 'publication date before':
				if (!is_a($value,'CMS_date')) {
					$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : publication date after filter value must be a valid CMS_date : '.$value);
					return false;
				}
				$this->_filters['publication date before'] = $value;
			break;
			default:
				$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : unknown filter type : '.$type);
				return false;
			break;
		}
		return true;
	}
	
	/**
	  * Return context filter
	  * This method must be as fast as possible (search results performance is at this cost)
	  *
	  * @param array $matchInfos : the match infos to check
	  * @return array(matchinfo) : valid match infos
	  * @access public
	  */
	function getContextFilters() {
		$filters = array();
		//check each used page for publication validity
		$validPages = array();
		$now = date("Y-m-d");
		$sql = "
			select
				id_pag
			from
				pages,
				resources,
				resourceStatuses
			where
				resource_pag=id_res
				and status_res=id_rs
				and location_rs='".RESOURCE_LOCATION_USERSPACE."'
				and publication_rs='".RESOURCE_PUBLICATION_PUBLIC."'
				and publicationDateStart_rs <= '".$now."'
				and (publicationDateEnd_rs = '0000-00-00' or publicationDateEnd_rs >= '".$now."')
		";
		//if filters exists on date start, add them to query
		if ($this->_filters['publication date after']) {
			$sql .= " and publicationDateStart_rs >= '".$this->_filters['publication date after']->getDBValue(true)."'";
		}
		if ($this->_filters['publication date before']) {
			$sql .= " and publicationDateStart_rs <= '".$this->_filters['publication date before']->getDBValue(true)."'";
		}
		if ($this->_filters['publication date after'] || $this->_filters['publication date before']) {
			$sql .= " and publicationDateStart_rs != '0000-00-00'";
		}
		$q = new CMS_query($sql);
		$validPages = array();
		while ($id = $q->getValue("id_pag")) {
			$validPages[$id] = $id;
		}
		//no valid pages found, so no need to go further, user has no rights on pages
		if (!sizeof($validPages)) {
			$filters['in']['none'][] = 'none';
			return $filters;
		}
		$sql = "
			select
				id_pag
			from
				pages,
				resources,
				resourceStatuses
			where
				resource_pag=id_res
				and status_res=id_rs
				and location_rs = '".RESOURCE_LOCATION_USERSPACE."'
				and ((publication_rs!='".RESOURCE_PUBLICATION_PUBLIC."' and publication_rs!='".RESOURCE_PUBLICATION_NEVERVALIDATED."')
				or publicationDateStart_rs > '".$now."'
				or (publicationDateEnd_rs != '0000-00-00' and publicationDateEnd_rs < '".$now."'))
		";
		//if filters exists on date start, add them to query
		if ($this->_filters['publication date after'] || $this->_filters['publication date before']) {
			$sql .= " and (";
			if ($this->_filters['publication date after']) {
				$sql .= " publicationDateStart_rs < '".$this->_filters['publication date after']->getDBValue(true)."'";
			}
			if ($this->_filters['publication date after'] && $this->_filters['publication date before']) {
				$sql .= " or ";
			}
			if ($this->_filters['publication date before']) {
				$sql .= " publicationDateStart_rs > '".$this->_filters['publication date before']->getDBValue(true)."'";
			}
			$sql .= " or publicationDateStart_rs = '0000-00-00')";
		}
		
		$q = new CMS_query($sql);
		$invalidPages = array();
		while ($id = $q->getValue("id_pag")) {
			$invalidPages[$id] = $id;
		}
		//add pages rights on filters if needed
		if (APPLICATION_ENFORCES_ACCESS_CONTROL) {
			global $cms_user;
			if (!$cms_user->hasAdminClearance(CLEARANCE_ADMINISTRATION_EDITVALIDATEALL)) {
				foreach ($validPages as $key => $pageID) {
					if (!$cms_user->hasPageClearance($pageID, CLEARANCE_PAGE_VIEW)) {
						//unset from valid pages
						unset($validPages[$key]);
						//set to invalid pages
						$invalidPages[$pageID] = $pageID;
					}
				}
			}
		}
		//compare valid and invalid pages number and get the lower set
		if (sizeof($validPages)) {
			if (sizeof($validPages) > sizeof($invalidPages)) {
				$filters['out']['page'] = $invalidPages;
			} else {
				$filters['in']['page'] = $validPages;
			}
		} else {
			//no valid pages found, so no need to go further, user has no rights on pages
			$filters['in']['none'][] = 'none';
			return $filters;
		}
		//check for excluded roots pages
		$module = CMS_modulesCatalog::getByCodename(MOD_ASE_CODENAME);
		$excludedRoots = preg_split('#[,;]#',$module->getParameters('XAPIAN_RESULTS_EXCLUDED_ROOTS'));
		if (is_array($excludedRoots) && sizeof($excludedRoots)) {
			foreach ($excludedRoots as $excludedRoot) {
				if ($excludedRoot) {
					$filters['out']['ancestor'][$excludedRoot] = $excludedRoot;
				}
			}
		}
		//merge filters with already set ones if any
		if (is_array($this->_filters['root']) && sizeof($this->_filters['root'])) {
			$filters['in']['ancestor'] = $this->_filters['root'];
		}
		return $filters;
	}
	
	/**
	  * Check all search results for this module then return only valid ones
	  * This method must be as fast as possible (search results performance is at this cost)
	  *
	  * @param array $matchInfos : the match infos to check
	  * @return array(matchinfo) : valid match infos
	  * @access public
	  */
	function getMatchValue(&$matchInfo, $value, $parameters = array()) {
		static $pages;
		//this UID is a page
		if (sensitiveIO::isPositiveInteger($matchInfo['uid'])) {
			if (!isset($pages[$matchInfo['uid']])) {
				//get page
				$page = CMS_tree::getPageByID($matchInfo['uid']);
				if (!$page || $page->hasError()) {
					$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : page does not exists : '.$matchInfo['uid']);
					return false;
				}
				$pages[$matchInfo['uid']] =& $page;
			}
			
			//page content
			switch ($value) {
				case 'HTMLTitle':
					return '<a href="'.$pages[$matchInfo['uid']]->getURL().'" title="'.htmlspecialchars($pages[$matchInfo['uid']]->getTitle(true)).'">'.$pages[$matchInfo['uid']]->getTitle(true).'</a>';
				break;
				case 'pageID':
					return $matchInfo['uid'];
				break;
				case 'page':
					return $pages[$matchInfo['uid']];
				break;
				case 'pageTitle':
					return $pages[$matchInfo['uid']]->getTitle(true);
				break;
				case 'linkTitle':
					return $pages[$matchInfo['uid']]->getLinkTitle(true);
				break;
				case 'url':
					return $pages[$matchInfo['uid']]->getURL();
				break;
				case 'description':
					return $pages[$matchInfo['uid']]->getDescription(true, false);
				break;
				case 'keywords':
					return $pages[$matchInfo['uid']]->getKeywords(true, false);
				break;
				case 'pubDate' :
					$pubDate = $pages[$matchInfo['uid']]->getPublicationDateStart();
					if (!$parameters['format']) {
						return $pubDate->getTimestamp();
					} else {
						return date($parameters['format'], $pubDate->getTimestamp());
					}
				break;
			}
		} else
		//this UID is a file in a page
		if (substr($matchInfo['uid'],0,4) == 'file') {
			static $filesDatas;
			//get file ID
			$fileID = (int) array_pop(explode('_',$matchInfo['uid']));
			if (!$fileID) {
				$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : no file ID found for uid : '.$matchInfo['uid']);
				return false;
			}
			if (!isset($filesDatas[$matchInfo['uid']])) {
				$sql = "
					select
						*
					from
						 blocksFiles_public
					where
						id='".sensitiveIO::sanitizeSQLString($fileID)."'
				";
				$q = new CMS_query($sql);
				if (!$q->getNumrows()) {
					$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : no file found in DB for uid : '.$matchInfo['uid']);
					return false;
				}
				$filesDatas[$matchInfo['uid']] = $q->getArray();
			}
			switch ($value) {
				case 'HTMLTitle':
					if (!isset($parameters['icon']) || $parameters['icon'] == true) {
						$type = $this->getMatchValue($matchInfo, 'fileType');
						//set document path
						$iconPath = PATH_MODULES_FILES_FS.'/'.MOD_STANDARD_CODENAME.'/icons/'.$type.'.gif';
						//file content
						$icon = new CMS_file($iconPath, FILE_SYSTEM, TYPE_FILE);
						$iconHTML = ($icon->exists()) ? '<img src="'.PATH_MODULES_FILES_WR.'/'.MOD_STANDARD_CODENAME.'/icons/'.$type.'.gif" alt="'.$type.'" title="'.$type.'" /> ' : '';
					}
					//title
					if (trim($filesDatas[$matchInfo['uid']]['label'])) {
						$title = $filesDatas[$matchInfo['uid']]['label'];
					} else {
						//add document filename
						$filename = explode('_',$filesDatas[$matchInfo['uid']]["file"]);
						unset($filename[0]);
						$title = substr(implode('_',$filename),32);
					}
					return '<a href="'.PATH_MODULES_FILES_WR.'/'.MOD_STANDARD_CODENAME.'/public/'.$filesDatas[$matchInfo['uid']]['file'].'" title="'.htmlspecialchars($title).'">'.$this->strChop($title, 120).'</a> '.$iconHTML;
				break;
				case 'fileType':
					//check if file type match installed filters
					$fileinfos = pathinfo(PATH_MODULES_FILES_FS.'/'.MOD_STANDARD_CODENAME.'/public/'.$filesDatas[$matchInfo['uid']]['file']);
					return $fileinfos['extension'];
				break;
				case 'pageTitle':
					if (!isset($pages[$matchInfo['uid']])) {
						//get page
						$page = CMS_tree::getPageByID($filesDatas[$matchInfo['uid']]['page']);
						if ($page->hasError()) {
							$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : page does not exists : '.$filesDatas[$matchInfo['uid']]['page']);
							return false;
						}
						$pages[$matchInfo['uid']] =& $page;
					}
					return $pages[$matchInfo['uid']]->getTitle(true);
				break;
				case 'pageID':
					return $filesDatas[$matchInfo['uid']]['page'];
				break;
				case 'page':
					//get page
					$page = CMS_tree::getPageByID($filesDatas[$matchInfo['uid']]['page']);
					if ($page->hasError()) {
						$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : page does not exists : '.$filesDatas[$matchInfo['uid']]['page']);
						return false;
					}
					return $page;
				break;
				case 'fileTitle':
					//title
					if (trim($filesDatas[$matchInfo['uid']]['label'])) {
						$title = $filesDatas[$matchInfo['uid']]['label'];
					} else {
						//add document filename
						$filename = explode('_',$filesDatas[$matchInfo['uid']]["file"]);
						unset($filename[0]);
						$title = substr(implode('_',$filename),32);
					}
					return $title;
				break;
				case 'fileSize':
					$fileSize = @filesize(PATH_MODULES_FILES_FS.'/'.MOD_STANDARD_CODENAME.'/public/'.$filesDatas[$matchInfo['uid']]['file']);
					//convert in KB or MB
					if ($fileSize > 1048576) {
						$fileSize = round(($fileSize/1048576),2).' M';
					} else {
						$fileSize = round(($fileSize/1024),2).' K';
					}
					return $fileSize;
				break;
				case 'exists':
					//set document path
					$filePath = PATH_MODULES_FILES_FS.'/'.MOD_STANDARD_CODENAME.'/public/'.$filesDatas[$matchInfo['uid']]['file'];
					//file content
					$file = new CMS_file($filePath, FILE_SYSTEM, TYPE_FILE);
					//check file
					return $file->exists();
				break;
				case 'url':
					return CMS_websitesCatalog::getMainURL().PATH_MODULES_FILES_WR.'/'.MOD_STANDARD_CODENAME.'/public/'.$filesDatas[$matchInfo['uid']]['file'];
				break;
				case 'absolutePath':
					return PATH_MODULES_FILES_FS.'/'.MOD_STANDARD_CODENAME.'/public/'.$filesDatas[$matchInfo['uid']]['file'];
				break;
				case 'pubDate' :
					if (!isset($pages[$matchInfo['uid']])) {
						//get page
						$page = CMS_tree::getPageByID($filesDatas[$matchInfo['uid']]['page']);
						if ($page->hasError()) {
							$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : page does not exists : '.$filesDatas[$matchInfo['uid']]['page']);
							return false;
						}
						$pages[$matchInfo['uid']] =& $page;
					}
					$pubDate = $pages[$matchInfo['uid']]->getPublicationDateStart();
					if (!$parameters['format']) {
						return $pubDate->getTimestamp();
					} else {
						return date($parameters['format'], $pubDate->getTimestamp());
					}
				break;
			}
		}
	}
	
	/**
	  * Get all values name this module can return for a given match result
	  *
	  * @param array $matchInfos : the match infos to check
	  * @return array(matchinfo) : valid match infos
	  * @access public
	  */
	function getAvailableMatchValues($matchInfo) {
		//this UID is a page
		if (sensitiveIO::isPositiveInteger($matchInfo['uid'])) {
			//page content
			return array('pageID', 'pageTitle', 'linkTitle', 'url', 'description', 'keywords', 'pubDate');
		} else
		//this UID is a file in a page
		if (substr($matchInfo['uid'],0,4) == 'file') {
			$fileID = (int) array_pop(explode('_',$matchInfo['uid']));
			if (!$fileID) {
				$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : no file ID found for uid : '.$matchInfo['uid']);
				return false;
			}
			return array('fileType', 'pageTitle', 'documentTitle', 'fileSize', 'exists', 'path', 'absolutePath', 'pubDate');
		}
		return array();
	}
}
?>