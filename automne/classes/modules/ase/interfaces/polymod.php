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
// $Id: polymod.php,v 1.13 2010/01/12 09:14:37 sebastien Exp $

/**
  * Class CMS_polymod_ase
  *
  * Represent an interface between polymod modules and ase module
  *
  * @package CMS
  * @subpackage module
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

class CMS_polymod_ase extends CMS_ase_interface {

	function isActive() {
		//Create a search on objects to restrict on viewable ones
		$sql = "
			select
				1
			from
				mod_object_definition
			where
				indexable_mod = '1'
				and module_mod='".$this->_codename."'
		";
		$q = new CMS_query($sql);
		return ($q->getNumRows()) ? true : false;
	}

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
		//this UID is an object
		if (!sensitiveIO::isPositiveInteger($uid)) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : uid must be a positive integer : '.$uid);
			return false;
		}
		//get item
		$item = CMS_poly_object_catalog::getObjectByID($uid, false, true);
		if (!$item || $item->hasError()) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : given object id does not exists : '.$uid);
			return false;
		}
		//get object definition
		$def = $item->getObjectDefinition();
		return $def->getObjectLabel($cms_language->getCode()).' : '.$item->getLabel().' ('.$uid.')';
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
		//this UID is an object
		if (!sensitiveIO::isPositiveInteger($document->getValue('uid'))) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : uid must be a positive integer : '.$document->getValue('uid'));
			return false;
		}
		//if no user founded, instanciate super administrator to allow full document content to be indexed without worry about rights
		global $cms_user, $cms_language;
		if (!is_object($cms_user)) {
			$cms_user = new CMS_profile_user(1);
		}
		//get item to index
		$item = CMS_poly_object_catalog::getObjectByID($document->getValue('uid'), false, true);
		if (!$item || $item->hasError()) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : given object id does not exists : '.$document->getValue('uid'));
			return false;
		}
		//set object language
		$language = $item->getLanguage();
		$document->setValue('language', $language);
		$document->setModuleAttribute('language', $language);
		//instanciate object language as general language
		$cms_language = new CMS_language($language);
		//get item label (in first, because a strange bug cause item _objectValues to be reseted next)
		$itemLabel = $item->getLabel();
		//add object type id as a module attribute
		$document->setModuleAttribute('objecttype', $item->getObjectID());
		//add item id as a module attribute
		$document->setModuleAttribute('itemid', $item->getID());
		//set all object attributes and plain text values then get files objets if any
		$content = '';
		$files = array();
		$this->_getFieldsContent($item, $content, $files);
		//remove all HTML from content
		$content = CMS_filter_common::stripTags($content);
		//then set it as plain text content for document
		$document->addPlainTextContent($content);
		//add files
		$type = '';
		if (sizeof($files)) {
			foreach($files as $file) {
				if (!$type) {
					$type = $file['extension'];
				} elseif ($file['extension'] != $type) {
					$type = 'mix';
				}
				//then add file
				$document->addFile($file['file'], $file['extension'], CMS_file::WEBROOT);
			}
		} else {
			$type = 'txt';
		}
		//set document type
		$document->setValue('type', $type);
		//add document type as a module attribute
		$document->setModuleAttribute('type', $type);
		//set document title
		$document->setValue('title', $itemLabel);
		return true;
	}

	function _getFieldsContent($item, &$content, &$files) {
		//get object fields definitions
		$objectFields = CMS_poly_object_catalog::getFieldsDefinition($item->getObjectID());
		$itemFieldsObjects =& $item->getFieldsObjects();
		$supportedFilesTypes = CMS_filter_catalog::getTypes();
		foreach ($itemFieldsObjects as $fieldID => $itemField) {
			//if field is indexable
			if ($objectFields[$fieldID]->getValue('indexable')) {
				//check field type
				$fieldType = $objectFields[$fieldID]->getValue('type');
				if (sensitiveIO::isPositiveInteger($fieldType)) {
					//this field is a poly_object so recurse on his values
					$this->_getFieldsContent($itemField, $content, $files);
				} elseif (io::strpos($fieldType,"multi|") !== false) {
					//this field is a multi_poly_object so recurse on all poly_objects it contain
					$params = $itemField->getParamsValues();
					if ($itemField->getValue('count')) {
						$items = $itemField->getValue('fields');
						if ($params['indexOnlyLastSubObjects']) {
							$this->_getFieldsContent(array_shift($items), $content, $files);
						} else {
							foreach ($items as $anItem) {
								$this->_getFieldsContent($anItem, $content, $files);
							}
						}
					}
				} else {
					//this field is only ... a field so get his value
					$content .= ' '.$itemField->getHTMLDescription();
					//if this field is a file, check for file
					if ($fieldType == 'CMS_object_file') {
						if ($itemField->getValue('filename') && in_array(io::strtolower($itemField->getValue('fileExtension')), $supportedFilesTypes)) {
							$files[] = array('file' => parse_url($itemField->getValue('filePath'), PHP_URL_PATH).'/'.$itemField->getValue('filename'), 'extension' => $itemField->getValue('fileExtension'));
						}
					}
				}
			}
		}
		return;
	}

	/**
	  * Get all objects to index from module
	  * This is a short list : ie. only the documents from which can gets all documents dependencies
	  *
	  * @return array(module uid)
	  * @access public
	  */
	function getShortUIDList() {
		//get all objects ID which are indexable
		$sql = "
			select
				id_mod
			from
				mod_object_definition
			where
				indexable_mod='1'
				and module_mod='".$this->_codename."'
		";
		$q = new CMS_query($sql);
		$indexableItems = array();
		if ($q->getNumRows()) {
			while ($objectID = $q->getValue("id_mod")) {
				$indexableItems = array_merge(CMS_poly_object_catalog::getAllObjects($objectID, true, array(), false), $indexableItems);
			}
		}
		return $indexableItems;
	}

	function getDeleteInfos($uid) {
		return array(array('uid' => $uid, 'module' => $this->_codename, 'deleteInfos' => array()));
	}

	function getIndexInfos($uid) {
		//check for indexable object
		$definition = CMS_poly_object_catalog::getObjectDefinitionByID($uid,true);
		if(!$definition->getValue('indexable')){
			return array();
		}
		return array(array('uid' => $uid, 'module' => $this->_codename));
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
		$this->_filters[$type] = $value;
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
		//Create a search on objects to restrict on viewable ones
		$sql = "
			select
				id_mod as objectID
			from
				mod_object_definition
			where
				indexable_mod = '1'
				and module_mod='".$this->_codename."'
		";
		$q = new CMS_query($sql);
		$objectsIDs = array();
		while ($objectID = $q->getValue('objectID')) {
			$objectsIDs[$objectID] = $objectID;
		}
		//for each type of objects, get results items
		if (sizeof($objectsIDs)) {
			$validItems = array();
			foreach ($objectsIDs as $objectID) {
				if (!is_array($this->_filters)) {
					$this->_filters = array();
				}
				$items = CMS_poly_object_catalog::getAllObjects($objectID, true, $this->_filters, false);
				$validItems = array_merge($items,$validItems);
			}
			$filteredItems = array();
			foreach ($validItems as $validItem) {
				if (sensitiveIO::isPositiveInteger($validItem)) {
					$filteredItems[$validItem] = $validItem;
				}
			}
			if (sizeof($filteredItems)) {
				//then get the opposite set of those items
				$sql = "
					select
						id_moo as id
					from
						mod_object_polyobjects
					where
						deleted_moo = '0'
						and object_type_id_moo in (".implode(',',$objectsIDs).")
						and id_moo not in (".implode(',',$filteredItems).")
				";
				$q = new CMS_query($sql);
				$invalidItems = array();
				while ($id = $q->getValue('id')) {
					$invalidItems[$id] = $id;
				}
				//compare valid and invalid items number and get the lower set
				if (sizeof($validItems) > sizeof($invalidItems)) {
					$filters['out']['itemid'] = $invalidItems;
				} else {
					$filters['in']['itemid'] = $validItems;
				}
			} else {
				$filters['in']['none'][] = 'none';
			}
		}
		return $filters;
	}

	/**
	  * This method help module interface to prepare results (pre-load)
	  * This method must be as fast as possible (search results performance is at this cost)
	  *
	  * @param array $resultsUID : the results uid to prepare
	  * @return boolean true on success, false on failure
	  * @access public
	  */
	function setResultsUID($resultsUID) {
		//get results objetIDs
		$sql = "
			select
				id_moo as id,
				object_type_id_moo as objectID
			from
				mod_object_polyobjects
			where
				id_moo in (".implode(',',$resultsUID).")
		";
		$q = new CMS_query($sql);
		$objectsIDs = array();
		while ($data = $q->getArray()) {
			$objectsIDs[$data['objectID']][] = $data['id'];
		}
		//then for each type of objects, get results items
		$this->_results = array();
		foreach ($objectsIDs as $objectID => $ids) {
			$this->_results = CMS_poly_object_catalog::getAllObjects($objectID, true, array('items' => $ids), true) + $this->_results;
		}
		return true;
	}

	/**
	  * Get all values name this module can return for a given match result
	  *
	  * @param array $matchInfos : the match infos to check
	  * @return array(matchinfo) : valid match infos
	  * @access public
	  */
	function getAvailableMatchValues($matchInfo) {
		return array('HTMLTitle', 'item', 'description', 'pubDate', 'url');
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
		global $cms_user, $cms_language;
		//this UID is an object
		if (!sensitiveIO::isPositiveInteger($matchInfo['uid'])) {
			$this->_raiseError(__CLASS__.' : '.__FUNCTION__.' : uid must be a positive integer : '.$matchInfo['uid']);
			return false;
		}
		if (!isset($matchInfo['uid']) || !isset($this->_results[$matchInfo['uid']]) || !is_object($this->_results[$matchInfo['uid']])) {
			return;
		}
		//page content
		switch ($value) {
			case 'HTMLTitle':
				if (!isset($parameters['icon']) || $parameters['icon'] == true) {
					$type = CMS_XapianQuery::getMatchValue($matchInfo, 'type');
					//set document path
					$iconPath = PATH_MODULES_FILES_FS.'/'.MOD_STANDARD_CODENAME.'/icons/'.$type.'.gif';
					//file content
					$icon = new CMS_file($iconPath, CMS_file::FILE_SYSTEM, CMS_file::TYPE_FILE);
					$iconHTML = ($icon->exists()) ? '<img src="'.PATH_MODULES_FILES_WR.'/'.MOD_STANDARD_CODENAME.'/icons/'.$type.'.gif" alt="'.$type.'" title="'.$type.'" /> ' : '';
				}
				//title
				$title = $this->_results[$matchInfo['uid']]->getLabel();
				$url = $this->getMatchValue($matchInfo, 'url', $parameters);
				return '<a href="'.$url.'" title="'.htmlspecialchars($title).'">'.$this->strChop($title, 120).'</a> '.$iconHTML;
			break;
			case 'itemID':
				return $this->_results[$matchInfo['uid']]->getID();
			break;
			case 'item':
				return $this->_results[$matchInfo['uid']];
			break;
			case 'description':
				//TODO
				return '';
			break;
			case 'pubDate' :
				if (method_exists($this->_results[$matchInfo['uid']], 'getPublicationDate')) {
					$pubDate = $this->_results[$matchInfo['uid']]->getPublicationDate();
					if ($pubDate && is_object($pubDate)) {
						if (!$parameters['format']) {
							return $pubDate->getTimestamp();
						} else {
							return date($parameters['format'], $pubDate->getTimestamp());
						}
					}
				} else {
					if ($this->_results[$matchInfo['uid']]->getObjectResourceStatus() == 1) {
						$pubDate = $this->_results[$matchInfo['uid']]->getPublicationDateStart();
						if (!$parameters['format']) {
							return $pubDate->getTimestamp();
						} else {
							return date($parameters['format'], $pubDate->getTimestamp());
						}
					}
				}
			break;
			case 'url':
				if (isset($parameters['url'])) {
					return $parameters['url'];
				} else {
					//if indexURL exists for object, use it
					$objectDefinition = $this->_results[$matchInfo['uid']]->getObjectDefinition();
					if ($objectDefinition->getValue('indexURL')) {
						//create object var used by compiledIndexURL
						$object[$this->_results[$matchInfo['uid']]->getObjectID()] = $this->_results[$matchInfo['uid']];
						//set public status to true
						$parameters['public'] = true;
						//then execute compiled link definition
						ob_start();
						eval(sensitiveIO::stripPHPTags($objectDefinition->getValue('compiledIndexURL')));
						$data = trim(ob_get_contents());
						ob_end_clean();
						//check if $data has a website url
						if (io::substr($data,0,7) != 'http://') {
							$data = CMS_websitesCatalog::getMainURL() . $data;
						}
						return $data;
					} else {
						//try to get URL from previz URL
						$itemURL = $this->_results[$matchInfo['uid']]->getPrevizPageURL(false);
						return $itemURL;
					}
				}
			break;
		}
	}
}
?>