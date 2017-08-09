<?php

namespace Kopano\Api;

class CommonViewsFolder extends Folder {
	static protected $_itemPropertyKeys = array(
		PR_WLINK_ENTRYID,
		PR_WLINK_FLAGS,
		PR_WLINK_ORDINAL,
		PR_WLINK_STORE_ENTRYID,
		PR_WLINK_TYPE,
		PR_WLINK_SECTION,
		PR_WLINK_RECKEY,
		PR_NORMALIZED_SUBJECT,
	);
}
