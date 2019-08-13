<?php

namespace Kopano\Api;

require_once (__DIR__ . '/../Message.php');

class Appointment extends Message {
    static protected $_propertyKeys = array(
        PR_OBJECT_TYPE,
        PR_ICON_INDEX,
        PR_SUBJECT,
        PR_NORMALIZED_SUBJECT,
        PR_LAST_MODIFICATION_TIME,
        PR_CREATION_TIME,
        PR_LAST_VERB_EXECUTED,
        PR_LAST_VERB_EXECUTION_TIME,
        PR_HASATTACH,
        PR_MESSAGE_SIZE,
        PR_MESSAGE_FLAGS,
        PR_FLAG_COMPLETE_TIME,
        PR_BLOCK_STATUS,
        'reminder_time' => "PT_SYSTIME:PSETID_Common:0x8502", // reminder_time
        'reminder' => "PT_BOOLEAN:PSETID_Common:0x8503", // reminder
		'flagdueby' => "PT_SYSTIME:PSETID_Common:0x8560", // flagdueby
	);
/*
    $properties["received_by_entryid"] = PR_RECEIVED_BY_ENTRYID;
    $properties["received_by_search_key"] = PR_RECEIVED_BY_SEARCH_KEY;
    $properties["message_delivery_time"] = PR_MESSAGE_DELIVERY_TIME;
    $properties["last_modification_time"] = PR_LAST_MODIFICATION_TIME;
    $properties["creation_time"] = PR_CREATION_TIME;
    $properties["last_verb_executed"] = PR_LAST_VERB_EXECUTED;
    $properties["last_verb_execution_time"] = PR_LAST_VERB_EXECUTION_TIME;
    $properties["hasattach"] = PR_HASATTACH;
    $properties["message_size"] = PR_MESSAGE_SIZE;
    $properties["message_flags"] = PR_MESSAGE_FLAGS;
    $properties["flag_status"] = PR_FLAG_STATUS;
    $properties["flag_complete_time"] = PR_FLAG_COMPLETE_TIME;
    $properties["flag_icon"] = PR_FLAG_ICON;
    $properties["block_status"] = PR_BLOCK_STATUS;
    $properties["reminder_time"] = "PT_SYSTIME:PSETID_Common:0x8502";
    $properties["reminder_set"] = "PT_BOOLEAN:PSETID_Common:0x8503";
    $properties["flag_request"] = "PT_STRING8:PSETID_Common:0x8530";
    $properties["flag_due_by"] = "PT_SYSTIME:PSETID_Common:0x8560";
    $properties["reply_requested"] = PR_REPLY_REQUESTED;
    $properties["reply_time"] = PR_REPLY_TIME;
    $properties["response_requested"] = PR_RESPONSE_REQUESTED;
    $properties["client_submit_time"] = PR_CLIENT_SUBMIT_TIME;
    $properties["sensitivity"] = PR_SENSITIVITY;
    $properties["read_receipt_requested"] = PR_READ_RECEIPT_REQUESTED;
    $properties["categories"] = "PT_MV_STRING8:PS_PUBLIC_STRINGS:Keywords";
    $properties["transport_message_headers"] = PR_TRANSPORT_MESSAGE_HEADERS;
    $properties["x_original_to"] = "PT_STRING8:PS_INTERNET_HEADERS:x-original-to";
    $properties["source_message_info"] = "PT_BINARY:PSETID_Common:0x85CE";
    $properties["deferred_send_time"] = PR_DEFERRED_SEND_TIME;
*/
}
