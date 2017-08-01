<?php

namespace Kopano\Api;

require_once(__DIR__ . '/../../mapi/mapiguid.php');

require_once(__DIR__ . '/../SearchFolder.php');

class TodoListFolder extends SearchFolder {
    static protected $_instance = array();
    /**
     * Factory function to get an instance of the TodoListFolder for a store
     * @param  Kopano\Api\Store $store The message store for which the to-do list folder will be returned
     *
     * @return Kopano\Api\FinderFolder|Boolean The to-do list folder of the given store or false if not found.
     */
    public static function getInstance($store) {
        $storeEntryId = $store->getEntryId();

        // TODO: use entryid comparison!
        if ( !isset(TodoListFolder::$_instance[$storeEntryId]) ){
            $entryId = $store->getTodoListEntryId();
            if ( !$entryId ){
                return false;
            }
            TodoListFolder::$_instance[$storeEntryId] = new TodoListFolder($entryId, $store);
        }

        if ( isset(TodoListFolder::$_instance[$storeEntryId]) ){
            return TodoListFolder::$_instance[$storeEntryId];
        } else {
            return false;
        }
    }

    /**
     * Creates a search folder under the store root, and set the search restriction for the to-do list on it.
     */
    static public function create($store) {
        $root = $store->getRoot();

        try {
            $resource = mapi_folder_createfolder(
                $root->getResource(), // create in the root folder
                'To-do list search folder (WebApp)', // the name of the folder
                null, // no description necessary
                OPEN_IF_EXISTS, // if this folder already exists, we will open it
                FOLDER_SEARCH // create a search folder
            );
            mapi_savechanges($resource); // TODO: is this necessary???
            $todoList = new TodoListFolder('', $store);
            $todoList->setResource($resource);
            $todoList->setEntryId($todoList->getProperty(PR_ENTRYID));

            $todoList->setRestriction(
                TodoListFolder::createRestriction($store),
                array($store->getIpmSubtreeEntryId()),
                RECURSIVE_SEARCH
            );
            mapi_savechanges($resource); // TODO: is this necessary???

            // Set the container class
            $todoList->setProperty(PR_CONTAINER_CLASS, 'IPM.Task');

            // Add the entryid to the store
            $store->addEntryIdToAdditionalEntryIdsEx(RSF_PID_TODO_SEARCH, $todoList->getEntryId());

            return $todoList;
        } catch (MAPIException $e){
            // TODO: Error handling
        }

        return false;
    }

    static public function createRestriction($store) {
        // First get some entryids that we need for the restriction
        $entryIds = array(
            'deletedItems'  => $store->getDeletedItemsEntryId(),
            'junk'          => $store->getJunkEntryId(),
            'drafts'        => $store->getDraftsEntryId(),
            'outbox'        => $store->getOutboxEntryId(),
        );

        // Now get some task request properties that we need in the restriction
        $properties = array(
            'taskstate'     => "PT_LONG:PSETID_Task:0x8113",
            'taskaccepted'  => "PT_BOOLEAN:PSETID_Task:0x8108",
            'taskstatus'    => "PT_LONG:PSETID_Task:0x8101"
        );
        $propertyIds = getPropIdsFromStrings($store->getResource(), $properties);

        return array(
            RES_AND,
            array(
                array(
                    RES_AND,
                    array(
                        array(
                            RES_NOT,
                            array(
                                array(
                                    RES_CONTENT,
                                    array(
                                        FUZZYLEVEL    => FL_PREFIX | FL_IGNORECASE,
                                        ULPROPTAG     => PR_MESSAGE_CLASS,
                                        VALUE         => "IPM.Appointment"
                                    )
                                )
                            )
                        ),
                        array(
                            RES_NOT,
                            array(
                                array(
                                    RES_CONTENT,
                                    array(
                                        FUZZYLEVEL  => FL_PREFIX | FL_IGNORECASE,
                                        ULPROPTAG   => PR_MESSAGE_CLASS,
                                        VALUE       => "IPM.Activity"
                                    )
                                )
                            )
                        ),
                        array(
                            RES_NOT,
                            array(
                                array(
                                    RES_CONTENT,
                                    array(
                                        FUZZYLEVEL  => FL_PREFIX | FL_IGNORECASE,
                                        ULPROPTAG   => PR_MESSAGE_CLASS,
                                        VALUE       => "IPM.StickyNote"
                                    )
                                )
                            )
                        )
                    )
                ),
                array(
                    RES_AND,
                    array(
                        array(
                            RES_AND,
                            array(
                                array(
                                    RES_PROPERTY,
                                    array(
                                        RELOP       => RELOP_NE,
                                        ULPROPTAG   => PR_PARENT_ENTRYID,
                                        VALUE       => $entryIds['deletedItems']
                                    )
                                ),
                                array(
                                    RES_PROPERTY,
                                    array(
                                        RELOP       => RELOP_NE,
                                        ULPROPTAG   => PR_PARENT_ENTRYID,
                                        VALUE       => $entryIds['junk']
                                    )
                                ),
                                array(
                                    RES_PROPERTY,
                                    array(
                                        RELOP       => RELOP_NE,
                                        ULPROPTAG   => PR_PARENT_ENTRYID,
                                        VALUE       => $entryIds['drafts']
                                    )
                                ),
                                array(
                                    RES_PROPERTY,
                                    array(
                                        RELOP       => RELOP_NE,
                                        ULPROPTAG   => PR_PARENT_ENTRYID,
                                        VALUE       => $entryIds['outbox']
                                    )
                                ),
                            )
                        ),
                        array(
                            RES_OR,
                            array(
                                array(
                                    RES_OR,
                                    array(
                                        array(
                                            RES_AND,
                                            array(
                                                array(
                                                    RES_OR,
                                                    array(
                                                        array(
                                                            RES_NOT,
                                                            array(
                                                                array(
                                                                    RES_EXIST,
                                                                    array(
                                                                        ULPROPTAG => PR_FLAG_ICON
                                                                    )
                                                                )
                                                            )
                                                        ),
                                                        array(
                                                          RES_PROPERTY,
                                                          array(
                                                            RELOP       => RELOP_EQ,
                                                            ULPROPTAG   => PR_FLAG_ICON,
                                                            VALUE       => 0
                                                            )
                                                        )
                                                    )
                                                ),
                                                array(
                                                    RES_EXIST,
                                                    array(
                                                        ULPROPTAG => PR_FLAG_STATUS
                                                    )
                                                ),
                                                array(
                                                    RES_PROPERTY,
                                                    array(
                                                        RELOP       => RELOP_EQ,
                                                        ULPROPTAG   => PR_FLAG_STATUS,
                                                        VALUE       => 1
                                                    )
                                                )
                                            )
                                        ),
                                        array(
                                            RES_AND,
                                            array(
                                                array(
                                                    RES_PROPERTY,
                                                    array(
                                                        RELOP       => RELOP_EQ,
                                                        ULPROPTAG   => $propertyIds['taskstatus'],
                                                        VALUE       => 2
                                                    )
                                                ),
                                                array(
                                                    RES_EXIST,
                                                    array(
                                                        ULPROPTAG => $propertyIds['taskstatus']
                                                    )
                                                )
                                            )
                                        )
                                    )
                                ),
                                array(
                                    RES_AND,
                                    array(
                                        array(
                                            RES_EXIST,
                                            array(
                                                ULPROPTAG  => PR_TODO_ITEM_FLAGS
                                            )
                                        ),
                                        array(
                                            RES_BITMASK,
                                            array(
                                                ULPROPTAG   => PR_TODO_ITEM_FLAGS,
                                                ULTYPE      => BMR_NEZ,
                                                ULMASK      => 1
                                            )
                                        )
                                    )
                                ),
                                array(
                                    RES_AND,
                                    array(
                                        array(
                                            RES_EXIST,
                                            array(
                                                ULPROPTAG => PR_FLAG_ICON
                                            )
                                        ),
                                        array(
                                            RES_PROPERTY,
                                            array(
                                                RELOP       => RELOP_GT,
                                                ULPROPTAG   => PR_FLAG_ICON,
                                                VALUE       => 0
                                            )
                                        )
                                    )
                                ),
                                array(
                                    RES_AND,
                                    array(
                                        array(
                                            RES_NOT,
                                            array(
                                                array(
                                                    RES_AND,
                                                    array(
                                                        array(
                                                            RES_PROPERTY,
                                                            array(
                                                                RELOP => RELOP_NE,
                                                                ULPROPTAG => $propertyIds['taskaccepted'],
                                                                VALUE => true
                                                            )
                                                        ),
                                                        array(
                                                            RES_PROPERTY,
                                                            array(
                                                                RELOP => RELOP_EQ,
                                                                ULPROPTAG => $propertyIds['taskstate'],
                                                                VALUE => 2
                                                            )
                                                        )
                                                    )
                                                )
                                            )
                                        ),
                                        array(
                                            RES_OR,
                                            array(
                                                array(
                                                    RES_CONTENT,
                                                    array(
                                                        FUZZYLEVEL => FL_PREFIX | FL_IGNORECASE,
                                                        ULPROPTAG => PR_MESSAGE_CLASS,
                                                        VALUE => "IPM.Task."
                                                    )
                                                ),
                                                array(
                                                    RES_CONTENT,
                                                    array(
                                                        FUZZYLEVEL => FL_FULLSTRING | FL_IGNORECASE,
                                                        ULPROPTAG => PR_MESSAGE_CLASS,
                                                        VALUE => "IPM.Task"
                                                    )
                                                )
                                            )
                                        )
                                    )
                                )
                            )
                        )
                    )
                )
            )
        );

    }
}
