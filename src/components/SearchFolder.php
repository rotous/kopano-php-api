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
}
