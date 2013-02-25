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
//
// $Id: standard.php,v 1.7 2010/03/19 09:39:18 sebastien Exp $

/**
  * Class CMS_standard_ase
  * 
  * Represent an interface between standard module and ase module
  *
  * @package CMS
  * @subpackage module
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
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
			if ($page && !$page->hasError()) {
				return $cms_language->getMessage(MESSAGE_STANDARD_UID_PAGE_TITLE).' : '.$page->getTitle(true).' ('.$uid.')';
			} else {
				return $cms_language->getMessage(MESSAGE_STANDARD_UID_PAGE_TITLE).' : Page error ('.$uid.')';
			}
		} elseif (io::substr($uid,0,4) == 'file') {
			$fileID = (int) array_pop(explode('_',$uid));
			if (!$fileID) {
				$this->raiseError('no file ID found for uid : '.$uid);
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
				$this->raiseError('no file found in DB for uid : '.$uid);
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
				$this->raiseError('given page id does not exists ... ');
				return false;
			}
			$tpl = $page->getTemplate();
			if (!$tpl || $tpl->hasError() || !$tpl->getPrintingClientSpaces()) {
				$this->raiseError('can not get infos from page : template error or no print clientspaces set... ');
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
		if (io::substr($document->getValue('uid'),0,4) == 'file') {
			$fileID = (int) array_pop(explode('_',$document->getValue('uid')));
			if (!$fileID) {
				$this->raiseError('no file ID found for uid : '.$document->getValue('uid'));
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
				$this->raiseError('no file found in DB for uid : '.$document->getValue('uid'));
				return false;
			}
			$fileDatas = $q->getArray();
			//set document path
			$filePath = PATH_MODULES_FILES_FS.'/'.MOD_STANDARD_CODENAME.'/public/'.$fileDatas['file'];
			//file content
			$file = new CMS_file($filePath, CMS_file::FILE_SYSTEM, CMS_file::TYPE_FILE);
			//check file
			if (!$file->exists()) {
				$this->raiseError('given file does not exists in file system : '.$filePath);
				return false;
			}
			//add document filename
			$filename = explode('_',$fileDatas["file"]);
			unset($filename[0]);
			$originalFilename = io::substr(implode('_',$filename),32);
			//set original filename as content
			$document->addPlainTextContent($originalFilename);
			//set document type
			$fileInfos = pathinfo($filePath);
			$document->setValue('type', $fileInfos['extension']);
			//add document type as a module attribute
			$document->setModuleAttribute('type', $fileInfos['extension']);
			//then add file
			$document->addFile($filePath, $fileInfos['extension'], CMS_file::FILE_SYSTEM);
			//get page id
			$pageId = $fileDatas['page'];
			//set document language from page
			$page = CMS_tree::getPageByID($pageId);
			if ($page->hasError()) {
				$this->raiseError('document page does not exists : '.$pageId);
				return false;
			}
			//set all page attributes and values
			$this->_addDocumentInfosForPage($document, $page);
			//set document title
			$document->setValue('title', $fileDatas['label']);
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
			$infos = array();
			//set document language
			$page = CMS_tree::getPageByID($uid);
			//check page for error, print status and publication status
			if ($page->hasError() || $page->getPublication() != RESOURCE_PUBLICATION_PUBLIC) {
				return false;
			}
			$tpl = $page->getTemplate();
			if ($tpl && !$tpl->hasError() && $tpl->getPrintingClientSpaces()) {
				//TODO : here we need to add pages without any print content. In this case, only title, description and keywords should be indexed
				$infos = array(
					//array('task' => 'delete', 'uid' => $uid, 'module' => MOD_STANDARD_CODENAME, 'deleteInfos' => array('page' => $uid)),
					array('uid' => $uid, 'module' => MOD_STANDARD_CODENAME),
				);
			}
			//then get file documents for the given page.
			$sql = "
			SELECT  
				DISTINCT (blocksFiles_public.id), 
				blocksFiles_public . *
			FROM 
				blocksFiles_public, 
				mod_standard_clientSpaces_public,
				pages
			WHERE
				id_pag = '".sensitiveIO::sanitizeSQLString($uid)."'
				and template_pag = template_cs
				AND rowsDefinition_cs = rowID
				and PAGE = '".sensitiveIO::sanitizeSQLString($uid)."'
			";
			$q = new CMS_query($sql);
			if ($q->getNumrows()) {
				while ($data = $q->getArray()) {
					//set document path
					$filePath = PATH_MODULES_FILES_FS.'/'.MOD_STANDARD_CODENAME.'/public/'.$data['file'];
					//file content
					$file = new CMS_file($filePath, CMS_file::FILE_SYSTEM, CMS_file::TYPE_FILE);
					//check file
					if ($file->exists()) {
						//check if file type match installed filters
						$fileinfos = pathinfo($filePath);
						if (isset($fileinfos['extension'])) {
							if (CMS_filter_catalog::getFilterForType($fileinfos['extension'])) {
								$infos[] = array('uid' => 'file_'.$data['page'].'_'.$data['id'],'module' => MOD_STANDARD_CODENAME);
							}
						} else {
							$this->raiseError('Cannot get extension for file : '.$filePath);
							return false;
						}
					}
				}
			}
			return $infos;
		}
		return false;
	}
	
	function getDeleteInfos($uid) {
		//delete all indexed documents for page
		return array(array('uid' => $uid, 'module' => MOD_STANDARD_CODENAME, 'deleteInfos' => array('page' => $uid)));
	}
	
	function reindexModuleDocument($parameters) {
		//if it is a page indexation, first delete all old documents for page
		if (sensitiveIO::isPositiveInteger($parameters['uid'])) {
			$db = new CMS_XapianDB($parameters['module'], true);
			if (!$db->isWritable()) {
				return false;
			}
			$db->deleteDocuments('__PAGE__:'.$parameters['uid']);
			$db->endTransaction();
		}
		//then reindex page or document
		$document = new CMS_ase_document(array('uid' => $parameters['uid'], 'module' => $parameters['module']));
		$indexer = new CMS_XapianIndexer($document);
		return $indexer->index();
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
					$this->raiseError('root filter value must be a valid page ID : '.$value);
					return false;
				}
				$this->_filters['root'][] = $value;
			break;
			case 'excludedroot':
				if (!sensitiveIO::isPositiveInteger($value)) {
					$this->raiseError('root filter value must be a valid page ID : '.$value);
					return false;
				}
				$this->_filters['excludedroot'][] = $value;
			break;
			case 'publication date after':
				if (!is_a($value,'CMS_date')) {
					$this->raiseError('publication date after filter value must be a valid CMS_date : '.$value);
					return false;
				}
				$this->_filters['publication date after'] = $value;
			break;
			case 'publication date before':
				if (!is_a($value,'CMS_date')) {
					$this->raiseError('publication date after filter value must be a valid CMS_date : '.$value);
					return false;
				}
				$this->_filters['publication date before'] = $value;
			break;
			default:
				$this->raiseError('unknown filter type : '.$type);
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
		if (isset($this->_filters['publication date after'])) {
			$sql .= " and publicationDateStart_rs >= '".$this->_filters['publication date after']->getDBValue(true)."'";
		}
		if (isset($this->_filters['publication date before'])) {
			$sql .= " and publicationDateStart_rs <= '".$this->_filters['publication date before']->getDBValue(true)."'";
		}
		if (isset($this->_filters['publication date after']) || isset($this->_filters['publication date before'])) {
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
		if (isset($this->_filters['publication date after']) || isset($this->_filters['publication date before'])) {
			$sql .= " and (";
			if (isset($this->_filters['publication date after'])) {
				$sql .= " publicationDateStart_rs < '".$this->_filters['publication date after']->getDBValue(true)."'";
			}
			if (isset($this->_filters['publication date after']) && isset($this->_filters['publication date before'])) {
				$sql .= " or ";
			}
			if (isset($this->_filters['publication date before'])) {
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
		if (isset($validPages) && sizeof($validPages)) {
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
		if (isset($excludedRoots) && is_array($excludedRoots) && sizeof($excludedRoots)) {
			foreach ($excludedRoots as $excludedRoot) {
				if ($excludedRoot) {
					$filters['out']['ancestor'][$excludedRoot] = $excludedRoot;
				}
			}
		}
		//merge filters with already set ones if any
		if (isset($this->_filters['root']) && is_array($this->_filters['root']) && sizeof($this->_filters['root'])) {
			$filters['in']['ancestor'] = $this->_filters['root'];
		}
		//merge filters with already set ones if any
		if (isset($this->_filters['excludedroot']) && is_array($this->_filters['excludedroot']) && sizeof($this->_filters['excludedroot'])) {
			if (isset($filters['out']['ancestor']) &&  is_array($filters['out']['ancestor'])) {
				$filters['out']['ancestor'] = array_merge($this->_filters['excludedroot'],$filters['out']['ancestor']);
			} else {
				$filters['out']['ancestor'] = $this->_filters['excludedroot'];
			}
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
					$this->raiseError('page does not exists : '.$matchInfo['uid']);
					return false;
				}
				$pages[$matchInfo['uid']] = $page;
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
		if (io::substr($matchInfo['uid'],0,4) == 'file') {
			static $filesDatas;
			//get file ID
			$fileID = (int) array_pop(explode('_',$matchInfo['uid']));
			if (!$fileID) {
				$this->raiseError('no file ID found for uid : '.$matchInfo['uid']);
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
					$this->raiseError('no file found in DB for uid : '.$matchInfo['uid']);
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
						$icon = new CMS_file($iconPath, CMS_file::FILE_SYSTEM, CMS_file::TYPE_FILE);
						$iconHTML = ($icon->exists()) ? '<img src="'.PATH_MODULES_FILES_WR.'/'.MOD_STANDARD_CODENAME.'/icons/'.$type.'.gif" alt="'.$type.'" title="'.$type.'" /> ' : '';
					}
					//title
					if (trim($filesDatas[$matchInfo['uid']]['label'])) {
						$title = $filesDatas[$matchInfo['uid']]['label'];
					} else {
						//add document filename
						$filename = explode('_',$filesDatas[$matchInfo['uid']]["file"]);
						unset($filename[0]);
						$title = io::substr(implode('_',$filename),32);
					}
					return '<a href="'.$this->getMatchValue($matchInfo, 'url').'" title="'.htmlspecialchars($title).'">'.$this->strChop($title, 120).'</a> '.$iconHTML;
				break;
				case 'fileType':
					//check if file type match installed filters
					$fileinfos = pathinfo(PATH_MODULES_FILES_FS.'/'.MOD_STANDARD_CODENAME.'/public/'.$filesDatas[$matchInfo['uid']]['file']);
					return $fileinfos['extension'];
				break;
				case 'page':
					//get page
					if (!isset($pages[$filesDatas[$matchInfo['uid']]['page']])) {
						$page = CMS_tree::getPageByID($filesDatas[$matchInfo['uid']]['page']);
						if ($page->hasError()) {
							$this->raiseError('page does not exists : '.$filesDatas[$matchInfo['uid']]['page']);
							return false;
						}
						$pages[$filesDatas[$matchInfo['uid']]['page']] = $page;
					}
					return $pages[$filesDatas[$matchInfo['uid']]['page']];
				break;
				case 'pageTitle':
					$page = $this->getMatchValue($matchInfo, 'page');
					return $page->getTitle(true);
				break;
				case 'pageID':
					return $filesDatas[$matchInfo['uid']]['page'];
				break;
				case 'fileTitle':
					//title
					if (trim($filesDatas[$matchInfo['uid']]['label'])) {
						$title = $filesDatas[$matchInfo['uid']]['label'];
					} else {
						//add document filename
						$filename = explode('_',$filesDatas[$matchInfo['uid']]["file"]);
						unset($filename[0]);
						$title = io::substr(implode('_',$filename),32);
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
					$file = new CMS_file($filePath, CMS_file::FILE_SYSTEM, CMS_file::TYPE_FILE);
					//check file
					return $file->exists();
				break;
				case 'url':
					$page = $this->getMatchValue($matchInfo, 'page');
					$website = $page->getWebsite();
					return $website->getURL().PATH_MODULES_FILES_WR.'/'.MOD_STANDARD_CODENAME.'/public/'.$filesDatas[$matchInfo['uid']]['file'];
				break;
				case 'absolutePath':
					return PATH_MODULES_FILES_FS.'/'.MOD_STANDARD_CODENAME.'/public/'.$filesDatas[$matchInfo['uid']]['file'];
				break;
				case 'pubDate' :
					$page = $this->getMatchValue($matchInfo, 'page');
					$pubDate = $page->getPublicationDateStart();
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
		if (io::substr($matchInfo['uid'],0,4) == 'file') {
			$fileID = (int) array_pop(explode('_',$matchInfo['uid']));
			if (!$fileID) {
				$this->raiseError('no file ID found for uid : '.$matchInfo['uid']);
				return false;
			}
			return array('fileType', 'pageTitle', 'documentTitle', 'fileSize', 'exists', 'path', 'absolutePath', 'pubDate');
		}
		return array();
	}
}
?>