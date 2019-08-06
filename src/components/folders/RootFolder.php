<?php

namespace Kopano\Api;

require_once(__DIR__ . '/../../mapi/mapitags.php');
require_once(__DIR__ . '/../Folder.php');

class RootFolder extends Folder{
	public function getReminderFolder() {
		$folders = $this->getSubFolders();
		if ( $folders ) {
			foreach ($folders as $folder) {
				if ( $folder->getProperty(PR_CONTAINER_CLASS) === 'Outlook.Reminder' ) {
					return $folder;
				}
			}
		}

		return false;
	}
}
