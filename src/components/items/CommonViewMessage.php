<?php

namespace Kopano\Api;

class CommonViewMessage extends Message {

	static protected $_propertyKeys = array(
		PR_WLINK_ENTRYID,
		PR_WLINK_FLAGS,
		PR_WLINK_ORDINAL,
		PR_WLINK_STORE_ENTRYID,
		PR_WLINK_TYPE,
		PR_WLINK_SECTION,
		PR_WLINK_RECKEY,
	);
}
