<?php

namespace Kopano\Api;

require_once (__DIR__ . '/MapiObject.php');
require_once (__DIR__ . '/SearchFolder.php');
require_once (__DIR__ . '/folders/CommonViewsFolder.php');

class Store extends MapiObject{

	private $_mapiSession; // Needed to open the store
	private $_root;

	public function __construct ($mapiSession, $entryId) {
		$this->_mapiSession = $mapiSession;

		parent::__construct($entryId);
	}

	/**
	 * Opens this store.
	 *
	 * @return Kopano\Api\Store The store that was opened.
	 */
	public function open($force=false){
		if ( is_resource($this->_resource) && !$force ){
			return $this;
		}

		if ( !isset($this->_mapiSession) ){
			throw Exception('No mapi session started!');
		}

		try {
			$resource = mapi_openmsgstore($this->_mapiSession, $this->_entryId);
			$this->setResource($resource);
		} catch (MAPIException $e){
			//TODO: Error handling
			Logger:log('exception');
		}

		return $this;
	}

	public function getRoot() {
		if ( !isset($this->_root) ){
			try {
				$this->_root = mapi_msgstore_openentry($this->_resource, null);
			} catch (MapiException $e){
				//TODO: Error handling
			}
		}

		return $this->_root;
	}

	public function getRootProperties($props) {
		return mapi_getprops($this->getRoot(), $props);
	}

	public function getRootProperty($prop) {
		$props = $this->getRootProperties(array($prop));

		if ( !isset($props[$prop]) ){
			return null;
		}

		return $props[$prop];
	}

	public function getTodoListEntryId() {
		$additionalRenEntryidsEx = $this->getRootProperty(PR_ADDITIONAL_REN_ENTRYIDS_EX);

		$headerFormat = 'Sid/Ssize';
		while ( strlen($additionalRenEntryidsEx) > 0 ){
			$todolist = false;
			$header = unpack($headerFormat, substr($additionalRenEntryidsEx, 0, 4));
			$header['id'] = dechex($header['id']);
			$additionalRenEntryidsEx = substr($additionalRenEntryidsEx, 4);
			if ( $header['id'] === '8004' ){
				$todolist = true;
			}

			$dataHeader = unpack($headerFormat, substr($additionalRenEntryidsEx, 0, 4));
			if ( $todolist === true ){
				$dataHeader['id'] = dechex($dataHeader['id']);
			}
			$additionalRenEntryidsEx = substr($additionalRenEntryidsEx, 4);

			if ( $todolist === true ){
				$todoFolderEntryid = substr($additionalRenEntryidsEx, 0, $dataHeader['size']);
				break;
			}
			$additionalRenEntryidsEx = substr($additionalRenEntryidsEx, $dataHeader['size']);
		}

		return $todoFolderEntryid;
	}

	public function getFavoritesEntryId() {
		return $this->getProperty(PR_IPM_FAVORITES_ENTRYID);
	}

	public function getDraftsEntryId() {
		return $this->getRootProperty(PR_IPM_DRAFTS_ENTRYID);
	}

	public function getOutboxEntryId() {
		return $this->getProperty(PR_IPM_OUTBOX_ENTRYID);
	}

	public function getSentItemsEntryId() {
		return $this->getProperty(PR_IPM_SENTMAIL_ENTRYID);
	}

	public function getJunkEntryId() {
		$additionalRenEntryIds = $this->getRootProperty(PR_ADDITIONAL_REN_ENTRYIDS);
		return $additionalRenEntryIds;
	}

	public function getDeletedItemsEntryId() {
		return $this->getProperty(PR_IPM_WASTEBASKET_ENTRYID);
	}

	public function getPublicFolderEntryId() {
		return $this->getRootProperty(PR_IPM_PUBLIC_FOLDERS_ENTRYID);
	}

	public function getCommonViewsEntryId() {
		return $this->getProperty(PR_COMMON_VIEWS_ENTRYID);
	}

	public function getFolderFromEntryId($entryId, $folderClass=NULL) {
		$this->open();
		if ( $folderClass && class_exists($folderClass) ){
			$folder = new $folderClass($entryId, $this);
		} else {
			$folder = new Folder($entryId, $this);
		}

		// It's pretty safe to assume someone wants to do something with the folder
		// if they request it, so let's open it.
		$folder->open();

		$folderType = $folder->getType();
		if ( $folderType !== FOLDER_SEARCH ){
			return $folder;
		}

		// Change the return var to a search folder
		$searchFolder = new SearchFolder($entryId, $this);
		$searchFolder->setResource($folder->getResource());

		return $searchFolder;
	}


}
