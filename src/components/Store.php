<?php

namespace Kopano\Api;

require_once(__DIR__ . '/../mapi/mapidefs.php');
require_once(__DIR__ . '/../mapi/mapitags.php');

require_once (__DIR__ . '/MapiObject.php');
require_once (__DIR__ . '/SearchFolder.php');
require_once (__DIR__ . '/folders/RootFolder.php');
require_once (__DIR__ . '/folders/CommonViewsFolder.php');
require_once (__DIR__ . '/folders/InboxFolder.php');
require_once (__DIR__ . '/folders/TodoListFolder.php');
require_once (__DIR__ . '/folders/FinderFolder.php');

class Store extends MapiObject{

	private $_mapiSession; // Needed to open the store
	private $_root;

	static private $_persistBlockTypes = array(
		RSF_PID_RSS_SUBSCRIPTION,
		RSF_PID_SEND_AND_TRACK,
		RSF_PID_TODO_SEARCH,
		RSF_PID_CONV_ACTIONS,
		RSF_PID_COMBINED_ACTIONS,
		RSF_PID_SUGGESTED_CONTACTS,
		RSF_PID_CONTACT_SEARCH,
		RSF_PID_BUDDYLIST_PDLS,
		RSF_PID_BUDDYLIST_CONTACTS,
	);


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
				if ( is_resource($resource = mapi_msgstore_openentry($this->_resource, null)) ){
					$properties = mapi_getprops($resource, array(PR_ENTRYID));
					if ( isset($properties[PR_ENTRYID]) ){
						$this->_root = new RootFolder($properties[PR_ENTRYID], $this);
						$this->_root->setResource($resource);
						// Request a property to load the default properties
						$this->_root->getProperty(PR_ENTRYID);
					}
				}
			} catch (MapiException $e){
				//TODO: Error handling
			}
		}

		return $this->_root;
	}

	public function getRootProperties($props) {
		return mapi_getprops($this->getRoot()->getResource(), $props);
	}

	public function getRootProperty($prop) {
		$props = $this->getRootProperties(array($prop));

		if ( !isset($props[$prop]) ){
			return null;
		}

		return $props[$prop];
	}

	public function setRootProperties($properties) {
		return $this->getRoot()->setProperties($properties);
	}

	public function setRootProperty($key, $value) {
		return $this->getRoot()->setProperty($key, $value);
	}

	/**
	 * Checks if this store supports search folders.
	 *
	 * @return Boolean True is this store supports search folders, false otherwise.
	 */
	public function supportsSearchFolders() {
		$supportMask = $this->getProperty(PR_STORE_SUPPORT_MASK);
		return $supportMask & STORE_SEARCH_OK;
	}

	/**
	 * TODO: Let's move this to its own class. (make classes for properties like these that need some logic
	 * of their own)
	 *
	 * Parses the passed value of PR_ADDITIONAL_REN_ENTRYIDS_EX. Will return an array of entryids,
	 * where the key is a PersistBlockType (see https://msdn.microsoft.com/en-us/library/office/cc842311.aspx)
	 * and the value the entryid of the blocktype.
	 * @param  String $additionalRenEntryidsEx Binary formatted string containing the value of
	 * PR_ADDITIONAL_REN_ENTRYIDS_EX
	 *
	 * @return Array The entryids found in the given the PR_ADDITIONAL_REN_ENTRYIDS_EX property
	 */
	public function parseAdditionalEntryIdsEx($additionalRenEntryidsEx) {
		$blocks = array();
		$headerFormat = 'Sid/Ssize';
		while ( strlen($additionalRenEntryidsEx) > 0 ){
			$header = unpack($headerFormat, substr($additionalRenEntryidsEx, 0, 4));
			$additionalRenEntryidsEx = substr($additionalRenEntryidsEx, 4);

			if ( $header['id'] === PERSIST_SENTINEL ){
				// We found the end block
				break;
			}

			if ( array_search($header['id'], Store::$_persistBlockTypes)<0 ){
				// ignore other entries like the specs say
				$additionalRenEntryidsEx = substr($additionalRenEntryidsEx, $header['size']);
				continue;
			}

			// Now read the PersistElementBlock
			$dataHeader = unpack($headerFormat, substr($additionalRenEntryidsEx, 0, 4));
			// We're only interested in entryids
			if ( $dataHeader['id'] !== RSF_ELID_ENTRYID ){
				$additionalRenEntryidsEx = substr($additionalRenEntryidsEx, $header['size']);
				continue;
			}

			// We found an entryid
			$entryId = substr($additionalRenEntryidsEx, 4, $dataHeader['size']);
			$blocks[$header['id']] = $entryId;

			// Remove the data and move on to the next block
			$additionalRenEntryidsEx = substr($additionalRenEntryidsEx, $header['size']);
		}

		return $blocks;
	}

	public function removeEntryIdFromAdditionalEntryIdsEx($persistBlockType) {
		$additionalRenEntryidsEx = $this->getRootProperty(PR_ADDITIONAL_REN_ENTRYIDS_EX);
		$blocks = array();
		$headerFormat = 'Sid/Ssize';
		$filteredBlocks = '';

		while ( strlen($additionalRenEntryidsEx) > 0 ){
			$header = unpack($headerFormat, substr($additionalRenEntryidsEx, 0, 4));

			if ( $header['id'] === PERSIST_SENTINEL ){
				// We found the end block, so the given block was not found
				$filteredBlocks .= substr($additionalRenEntryidsEx, 0, 4 + $header['size']);
				$additionalRenEntryidsEx = substr($additionalRenEntryidsEx, 4 + $header['size']);
				continue;
			}

			if ( $header['id'] === $persistBlockType ){
				// This is the block we should remove
				$additionalRenEntryidsEx = substr($additionalRenEntryidsEx, 4 + $header['size']);
				// We should be able to return now because there should only be one block for the given
				// block type, but we'll continue to be sure. (devs might have fucked it up ;-)
				continue;
			}

			// Keep this block
			$filteredBlocks .= substr($additionalRenEntryidsEx, 0, 4 + $header['size']);

			// Remove the data and move on to the next block
			$additionalRenEntryidsEx = substr($additionalRenEntryidsEx, 4 + $header['size']);
		}

		$this->setRootProperty(PR_ADDITIONAL_REN_ENTRYIDS_EX, $filteredBlocks);
		// TODO: do we need to mapi_savechanges?

		return $filteredBlocks;
	}

	public function addEntryIdToAdditionalEntryIdsEx($persistBlockType, $entryId) {
		if ( array_search($entryId, Store::$_persistBlockTypes)<0 ){
			return false;
		}
		// Make sure we don't store duplicates
		$this->removeEntryIdFromAdditionalEntryIdsEx($persistBlockType);

		$additionalRenEntryidsEx = $this->getRootProperty(PR_ADDITIONAL_REN_ENTRYIDS_EX);
		$dataHeader = pack('SS', RSF_ELID_ENTRYID, strlen($entryId));
		$dataElement = $dataHeader . $entryId;
        $blockHeader = pack('SS', $persistBlockType, strlen($dataElement));
        $additionalRenEntryidsEx = $blockHeader . $dataElement . $additionalRenEntryidsEx;

		$this->setRootProperty(PR_ADDITIONAL_REN_ENTRYIDS_EX, $additionalRenEntryidsEx);

		return $this;
	}

	public function getInbox() {
		return InboxFolder::getInstance($this);
	}

	public function getIpmSubtreeEntryId() {
		return $this->getProperty(PR_IPM_SUBTREE_ENTRYID);
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
		return $additionalRenEntryIds[4];
	}

	public function getDeletedItemsEntryId() {
		return $this->getProperty(PR_IPM_WASTEBASKET_ENTRYID);
	}

	public function getDefaultContactsFolderEntryId() {
		return $this->getRootProperty(PR_IPM_CONTACT_ENTRYID);
	}

	public function getPublicFolderEntryId() {
		return $this->getRootProperty(PR_IPM_PUBLIC_FOLDERS_ENTRYID);
	}

	public function getCommonViewsEntryId() {
		return $this->getProperty(PR_COMMON_VIEWS_ENTRYID);
	}

	public function getTodoListEntryId() {
		$additionalRenEntryidsEx = $this->getRootProperty(PR_ADDITIONAL_REN_ENTRYIDS_EX);
		$entryIds = $this->parseAdditionalEntryIdsEx($additionalRenEntryidsEx);
		if ( isset($entryIds[RSF_PID_TODO_SEARCH]) ){
			return $entryIds[RSF_PID_TODO_SEARCH];
		}

		if ( $this->supportsSearchFolders() ){
			// Let's create a to do-list search folder
			$todoList = TodoListFolder::create($this);
			return $todoList->getEntryId();
		}

		return false;
	}

	public function getTodoList() {
		return TodoListFolder::getInstance($this);
	}

	public function getFinderFolder() {
		return FinderFolder::getInstance($this);
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

	public function getDefaultContactsFolder() {
		$folder = new Folder($this->getDefaultContactsFolderEntryId(), $this);
		// Get a property to open the folder and fetch the default properties
		$folder->getProperty(PR_ENTRYID);
		return $folder;
	}

	public function getReminderFolder() {
		return $this->getRoot()->getReminderFolder();
	}
}
