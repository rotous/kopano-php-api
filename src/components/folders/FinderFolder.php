<?php

namespace Kopano\Api;

require_once(__DIR__ . '/../../mapi/mapitags.php');

require_once(__DIR__ . '/../Folder.php');

class FinderFolder extends Folder {
    /**
     * An array with Kopano\Api\FinderFolder instances as values and store entryids as keys.
     * @var Array
     */
    private static $_instance = array();

    private static $_defaultItemPropertyKeys = array(

	);

    protected function _init() {
        $this->_addItemPropertyKeys(FinderFolder::$_defaultItemPropertyKeys);

        parent::_init();
    }

    /**
     * Factory function to get an instance of the FinderFolder for a store
     * @param  Kopano\Api\Store $store The message store for which the finder folder will be returned
     *
     * @return Kopano\Api\FinderFolder|Boolean The finder folder of the given store or false if not found.
     */
    public static function getInstance($store) {
        $storeEntryId = $store->getEntryId();

        // TODO: use entryid comparison!
        if ( !isset(FinderFolder::$_instance[$storeEntryId]) ){
            $entryId = FinderFolder::_getFinderEntryId($store);
            if ( !$entryId ){
                return false;
            }
            FinderFolder::$_instance[$storeEntryId] = new FinderFolder($entryId, $store);
        }

        if ( isset(FinderFolder::$_instance[$storeEntryId]) ){
            return FinderFolder::$_instance[$storeEntryId];
        } else {
            return false;
        }
    }

    /**
     * Retrieves the entryid of the Finder folder of the given store.
     * @param Kopano\Api\Store $store The message store for which the finder folder is retrieved.
     *
     * @return String|Boolean The (binary) entryid of the finder folder of the given store, or false if not found
     */
    private static function _getFinderEntryId($store) {
        // check if we can create search folders
        $storeProperties = mapi_getprops($store->getResource(), array(PR_STORE_SUPPORT_MASK, PR_FINDER_ENTRYID));
        if ( ($storeProperties[PR_STORE_SUPPORT_MASK] & STORE_SEARCH_OK) !== STORE_SEARCH_OK ) {
            // store doesn't support search folders (public store doesn't have FINDER_ROOT folder)
            return false;
        }

        return $storeProperties[PR_FINDER_ENTRYID];
    }

}
