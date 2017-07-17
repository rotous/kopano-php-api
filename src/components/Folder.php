<?php

namespace Kopano\Api;

require_once(__DIR__ . '/../mapi/mapitags.php');

require_once(__DIR__ . '/Item.php');
require_once(__DIR__ . '/Message.php');

class Folder extends Item {
	/**
	 * This array contains all the keys of the properties that will be fetched
	 * whenever a list of items is fetched from this folder. Any call to
	 * getProperties() or getProperty() will also fetch these properties.
	 * Subclasses of the Folder class can call _addItemPropertyKeys() to add
	 * properties to this array. (preferably in their constructor)
	 */
	private $_defaultItemPropertyKeys = array(
		PR_ENTRYID,
		PR_PARENT_ENTRYID,
		PR_STORE_ENTRYID,
		PR_MESSAGE_CLASS,
		PR_DISPLAY_NAME,
		PR_SUBJECT,
	);

	protected function _addItemPropertyKeys($propertyKeys){
		$this->_defaultItemPropertyKeys = array_merge($this->_defaultItemPropertyKeys, $propertyKeys);
	}

	public function getType() {
		$this->open();
		return $this->getProperty(PR_FOLDER_TYPE);
	}

	public function getSubFolders() {

	}

	protected function _getItems($start=NULL, $limit=NULL, $properties=NULL, $assoc=false) {
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
}
