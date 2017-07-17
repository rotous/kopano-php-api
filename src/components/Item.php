<?php

namespace Kopano\Api;

require_once (__DIR__ . '/MapiObject.php');

class Item extends MapiObject {

	protected $_store;
	protected $_folder;

	static protected $_itemPropertyKeys = array(
		PR_ENTRYID,
		PR_PARENT_ENTRYID,
		PR_STORE_ENTRYID,
		PR_ACCESS,
		PR_ACCESS_LEVEL,
		PR_MESSAGE_CLASS,
	);

	public function __construct($entryId=NULL, $store=NULL){
		if ( $store ){
			$this->setStore($store);
		}

		parent::__construct($entryId);
	}

	protected function _init() {
		$this->_addPropertyKeys(Item::$_itemPropertyKeys);

		parent::_init();
	}

	public function setStore($store){
		if ( !$store instanceof Store ){
			throw new Exception('No store passed as parameter');
		}

		$this->_store = $store;

		return $this;
	}

	public function getStore(){
		return $this->_store;
	}

	public function getFolder() {
		if ( isset($this->_folder) ){
			return $this->_folder;
		}

		if ( !isset($this->_store) ){
			return NULL;
		}

		$this->_folder = new Folder($this->getFolderEntryId(), $this->_store);
		return $this->_folder;
	}

	public function open($force=false)
	{
		if ( is_resource($this->_resource) && !$force ){
			return $this->_resource;
		}

		try {
			$resource = mapi_msgstore_openentry($this->_store->getResource(), $this->_entryId);
			$this->setResource($resource);
		} catch (MAPIException $e){
			//TODO: Error handling
		}

		return $this;
	}

	/********************************************************************
	 * Some getters for popular properties
	 * *****************************************************************/

	 public function getEntryId() {
	 	return $this->getProperty(PR_ENTRYID);
	 }
	 public function getStoreEntryId() {
	 	return $this->getProperty(PR_STORE_ENTRYID);
	 }
	 public function getFolderEntryId() {
	 	return $this->getProperty(PR_PARENT_ENTRYID);
	 }
	 public function getMessageClass() {
	 	return $this->getProperty(PR_MESSAGE_CLASS);
	 }
	 public function getDisplayName() {
	 	return $this->getProperty(PR_DISPLAY_NAME);
	 }
	 public function getSenderName() {
	 	return $this->getProperty(PR_SENDER_NAME);
	 }
	 public function getSubject() {
	 	return $this->getProperty(PR_SUBJECT);
	 }
	 public function getSenderEntryId() {
	 	return $this->getProperty(PR_SENDER_ENTRYID);
	 }
}
