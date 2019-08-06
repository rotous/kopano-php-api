<?php

namespace Kopano\Api;

require_once (__DIR__ . '/../Message.php');

class Contact extends Message {
    static protected $_propertyKeys = array(
        PR_FLAG_STATUS,
        PR_FLAG_COMPLETE_TIME,
        PR_FLAG_ICON,
        "PT_SYSTIME:PSETID_Common:0x8502", // reminder_time
        "PT_BOOLEAN:PSETID_Common:0x8503", // reminder_set
	);
}
