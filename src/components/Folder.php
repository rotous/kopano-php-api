<?php

namespace Kopano\Api;

require_once(__DIR__ . '/../mapi/mapitags.php');

require_once(__DIR__ . '/Item.php');
require_once(__DIR__ . '/Message.php');

class Folder extends Item {
	/**
	 * The properties that this class adds to the ones that will be fetched by default
	 * when one or more roperties are fetched using getProperty() or getProperties()
	 * @var Array
	 */
	static protected $_propertyKeys = array(
		PR_FOLDER_TYPE,
		PR_DISPLAY_NAME,
		PR_CONTAINER_CLASS,
		PR_CONTENT_COUNT,
		PR_CONTENT_UNREAD,
		PR_SUBFOLDERS,
	);

	/**
	 * This array contains all the keys of the properties that will be fetched
	 * whenever a list of items is fetched from this folder. Any call to
	 * getProperties() or getProperty() will also fetch these properties.
	 * Subclasses of the Folder class can call _addItemPropertyKeys() to add
	 * properties to this array. (preferably in their constructor)
	 * @var Array
	 */
	private $_defaultItemPropertyKeys = array(
		PR_ENTRYID,
		PR_PARENT_ENTRYID,
		PR_STORE_ENTRYID,
		PR_MESSAGE_CLASS,
		PR_DISPLAY_NAME,
		PR_SUBJECT,
	);

	/**
	 * Initializes the object by populating the $_itemPropertyKeys array which contains the properties
	 * that will be retrieved with the getItems method of the folder
	 */
	protected function _init() {
		// Loop over the class of the object and all its parents up to the Kopano\Api\Folder class
		// to find the defined item properties
		$class = get_class($this);
		while ( $class !== 'Kopano\Api\Folder' ){
			if ( isset($class::$_itemPropertyKeys) && is_array($class::$_itemPropertyKeys) ) {
				$this->_defaultItemPropertyKeys =
					array_values(array_unique(array_merge($this->_defaultItemPropertyKeys, $class::$_itemPropertyKeys)));
			};

			$class = get_parent_class($class);
		}

		parent::_init();
	}

	/**
	 * Returns the type of the folder (PR_FOLDER_TYPE)
	 *
	 * @return Integer The value of the PR_FOLDER_TYPE property
	 */
	public function getType() {
		$this->open();
		return $this->getProperty(PR_FOLDER_TYPE);
	}

	/**
	 * Returns the subfolders of this folder.
	 *
	 * @return Array An array of Kopano\Api\Folder objects. Search folders will be represented by
	 * Kopano\Api\SearchFolder objects.
	 */
	public function getSubFolders() {
		$this->open();

		try{
			$table = mapi_folder_gethierarchytable($this->_resource);
			$rows = mapi_table_queryallrows($table, $this->getDefaultPropertyKeys());
		} catch (MAPIException $e){
			// TODO: Error handling
		}

		$folders = array();
		foreach ( $rows as $i=>$row ){
			$entryId = isset($row[PR_ENTRYID]) ? $row[PR_ENTRYID] : NULL;
			if ( isset($row[PR_FOLDER_TYPE]) && $row[PR_FOLDER_TYPE]===FOLDER_SEARCH ){
				$className = 'Kopano\Api\SearchFolder';
			} else {
				$className = 'Kopano\Api\Folder';
			}
			$folder = new $className($entryId, $this->_store);
			$folder->addProperties($row);
			$folders[] = $folder;
		}

		return $folders;
	}

	public function getRowCount() {
		$this->open();
		$table = mapi_folder_getcontentstable($this->_resource);
		return mapi_table_getrowcount($table);
	}

	protected function _getItems($start=NULL, $limit=NULL, $properties=NULL, $assoc=false, $sort=false) {
		$this->open();

		if ( $properties!==NULL && !is_array($properties) ){
			$properties = array($properties);
		}

		if ( is_array($properties) ){
			$properties = array_merge($properties, $this->_defaultItemPropertyKeys);
		} else {
			$properties = $this->_defaultItemPropertyKeys;
		}

		if ( count($properties) === 0 ){
			$properties = NULL;
		}

		$flags = $assoc===true ? MAPI_ASSOCIATED : 0;

		$contentTable = mapi_folder_getcontentstable($this->_resource, $flags);

		if (!is_array($sort)) {
			$sort = Array(PR_MESSAGE_DELIVERY_TIME => TABLE_SORT_DESCEND);
		}
		mapi_table_sort($contentTable, $sort);
		if ( $start === NULL ){
			if ( is_array($properties) ){
				$rows = mapi_table_queryallrows($contentTable, $properties);
			} else {
				$rows = mapi_table_queryallrows($contentTable);
			}
		} else if ( $limit === NULL ){
			$rows = mapi_table_queryrows($contentTable, $properties, 0, $start);
		} else {
			$rows = mapi_table_queryrows($contentTable, $properties, $start, $limit);
		}

		$items = array();
		foreach ( $rows as $i=>$row ){
			$entryId = isset($row[PR_ENTRYID]) ? $row[PR_ENTRYID] : NULL;
			$className = array_key_exists($row[PR_MESSAGE_CLASS], Message::$classMap) ? Message::$classMap[$row[PR_MESSAGE_CLASS]] : 'Kopano\Api\Message';
			$item = new $className($entryId, $this->_store);
			$item->addProperties($row);
			$items[] = $item;
		}

		return $items;
	}

	public function getItems($start=NULL, $limit=NULL, $properties=NULL) {
		return $this->_getItems($start, $limit, $properties);
	}

	public function getAssociatedItems($start=NULL, $limit=NULL, $properties=NULL) {
		return $this->_getItems($start, $limit, $properties, true);
	}

	public function delete($flags = 0) {
		$parentEntryId = $this->getProperty(PR_PARENT_ENTRYID);
		$parentFolder = new Folder($parentEntryId, $this->_store);
		$entryId = $this->getEntryId();
		try {
			$parentFolder->open();
			$result = mapi_folder_deletefolder($parentFolder->getResource(), $entryId);
			$this->_properties = array();
			$this->_entryId = '';
			$this->_resource = null;
			$this->_store = null;
			$this->_folder = null;
			return $result;
		} catch (MAPIException $e){
			// TODO: Error handling
		}

		return false;
	}

	public function empty($flags = null) {
		$this->open();
		try {
			if ( $flags === null ) {
				mapi_folder_emptyfolder($this->getResource());
			} else {
				mapi_folder_emptyfolder($this->getResource(), $flags);
			}
		} catch (MAPIException $e) {
			// TODO: Error handling
			error_log("Cannot empty folder: " . $e->getMessage());
		}
	}
}
