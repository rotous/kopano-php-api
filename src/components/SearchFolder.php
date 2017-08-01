<?php

namespace Kopano\Api;

require_once (__DIR__ . '/Folder.php');

class SearchFolder extends Folder {
	public function getSearchRestriction() {
		$this->open();

		try {
			return mapi_folder_getsearchcriteria($this->_resource);
		} catch (MAPIException $e){
			//TODO: Error handling
		}
	}

	/**
	 * Sets a search restriction on the search folder.
	 * @param Array $restriction The search restriction that will be set on the search folder
	 * @param Array $folderEntryIds An array of (binary) entryids of the folders to which the search will apply.
	 * @param long $flags The flags that will be set for the searching
	 */
	public function setRestriction($restriction, $folderEntryIds, $flags=SHALLOW_SEARCH) {
		$this->open();

		try{
			mapi_folder_setsearchcriteria($this->_resource, $restriction, $folderEntryIds, $flags);
		} catch (MAPIException $e){
			// TODO: Error handling
		}
	}
}
