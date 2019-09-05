<?php

namespace Kopano\Api;

require_once(__DIR__ . '/../../mapi/mapitags.php');
require_once(__DIR__ . '/../../mapi/mapiguid.php');

require_once(__DIR__ . '/../Folder.php');

class InboxFolder extends Folder {

    static protected $_instance = Array();

	protected function _construct($store) {
		// Since not all stores contain an inbox (e.g. the public store), we'll wrap this
		// in a try catch block
		try {
			$inbox = mapi_msgstore_getreceivefolder($store->getResource());
			//$inboxProps = mapi_getprops($inbox, array(PR_ENTRYID));
		} catch (Exception $e) {
			return NULL;
		}

		return $inbox;

	}

	/**
     * Factory function to get an instance of the InboxFolder for a store
     * @param  Kopano\Api\Store $store The message store for which the to-do list folder will be returned
     *
     * @return Kopano\Api\FinderFolder|Boolean The to-do list folder of the given store or false if not found.
     */
    public static function getInstance($store) {
        $storeEntryId = $store->getEntryId();

        // TODO: use entryid comparison!
        if ( !in_array($storeEntryId, InboxFolder::$_instance) ){
			// Since not all stores contain an inbox (e.g. the public store), we'll wrap this
			// in a try catch block
			try {
				$inbox = mapi_msgstore_getreceivefolder($store->getResource());
				$inboxProps = mapi_getprops($inbox, array(PR_ENTRYID));
				$inboxEntryId = $inboxProps[PR_ENTRYID];
			} catch (MAPIException $e) {
				Logger::log('Exception: ',$e);
				return false;
			}
            if ( !$inboxEntryId ){
                return false;
            }
			InboxFolder::$_instance[$storeEntryId] = new InboxFolder($inboxEntryId, $store);
			InboxFolder::$_instance[$storeEntryId]->setResource($inbox);
        }

           return InboxFolder::$_instance[$storeEntryId];
    }
}
