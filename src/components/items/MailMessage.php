<?php

namespace Kopano\Api;

require_once (__DIR__ . '/../Message.php');

class MailMessage extends Message {
    static protected $_propertyKeys = array(
        PR_DISPLAY_TO,
        PR_DISPLAY_CC,
        PR_DISPLAY_BCC,
        PR_SUBJECT,
        PR_NORMALIZED_SUBJECT,
        PR_IMPORTANCE,
	);

    protected function _init() {
        $this->_addPropertyKeys(Item::$_itemPropertyKeys);

        parent::_init();
    }
}
