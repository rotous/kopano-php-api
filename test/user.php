<?php

require_once(__DIR__ . '/../src/mapi/mapitags.php');

require_once(__DIR__ . '/../src/util/Logger.php');
use \Kopano\Api\Logger as Logger;

require_once(__DIR__ . '/../src/components/User.php');
require_once(__DIR__ . '/../src/components/Folder.php');
require_once(__DIR__ . '/../src/components/folders/CommonViewsFolder.php');

/*
$user = new \Kopano\Api\User('fabian', 'test');
$user->setServer('default:');
/*/
$user = new \Kopano\Api\User('rtoussaint', '*');
$user->setServer('https://email.kopano.com:237/kopano');
//*/
//$user = new \Kopano\Api\User('ronald', 'bigron');
//$user->setServer('http://localhost:236/kopano');

$user->logon();

$inbox = $user->getInbox();
$inbox->getProperty(PR_ENTRYID);
Logger::log('Inbox', $inbox);
Logger::log('Inbox items', $inbox->getItems(0,3));

$commonViews = $user->getCommonViewsFolder();
Logger::log('Common views', $commonViews);
$items = $commonViews->getAssociatedItems();
Logger::log('Common views items', $items);

/*
foreach ( $items as $i=>$item ){
	$item->getProperty(PR_MESSAGE_CLASS);
	Logger::log('Common views item '.$i, $item->getProperties());
}
*/
$draftsEntryid = $user->getStore()->getDraftsEntryId();
Logger::log('drafts', bin2hex($draftsEntryid));
Logger::log('outbox', bin2hex($user->getStore()->getOutboxEntryId()));
Logger::log('deleted items', bin2hex($user->getStore()->getDeletedItemsEntryId()));
//Logger::log('junk', bin2hex($user->getStore()->getJunkEntryId()));

//$store = $user->getStore();
$todoList = $user->getTodoList();
$restriction = $todoList->getSearchRestriction();
Logger::logRestrictionHtml('search restriction todo list', $restriction['restriction']);
//Logger::log('search restriction todo list', $restriction['restriction']);

$todoItems = $todoList->getItems();
Logger::logHtml('sender entryid of first todo item', bin2hex($todoItems[0]->getFolderEntryId()));

$folder = $todoItems[0]->getFolder();
$items = $folder->getItems(0,5, array(PR_SENDER_NAME, PR_SUBJECT, PR_MESSAGE_CLASS, PR_ENTRYID));
$records = array();
foreach ($items as $item){
	$records[] = array(
		'name' => $item->getSenderName(),
		'subject' => $item->getSubject(),
		'messageClass' => $item->getMessageClass(),
//		'body' => $item->getProperty(PR_BODY)
	);
}
//Logger::log('items', $items);
//Logger::log('folder items', $records);

$item = new \Kopano\Api\Folder(hex2bin('0000000045482bc46e924602b90e4791449a89d5010000000300000075fd409b0cb141f992cf044d8cb3289800000000'), $user->getStore());
Logger::log('item1', $item->getDisplayName());
$item = new \Kopano\Api\Folder(hex2bin('0000000045482bc46e924602b90e4791449a89d5010000000300000006770870af95453c8f7fd44ac90b308900000000'), $user->getStore());
Logger::log('item2', $item->getDisplayName());
$item = new \Kopano\Api\Folder(hex2bin('0000000045482bc46e924602b90e4791449a89d50100000003000000134bc0b9016d48df85c2564e4d0f54dc00000000'), $user->getStore());
Logger::log('item3', $item->getDisplayName());
$item = new \Kopano\Api\Folder(hex2bin('0000000045482bc46e924602b90e4791449a89d5010000000300000040e612b0432f4a7689b6b689e4e8955c00000000'), $user->getStore());
Logger::log('item4', $item->getDisplayName());
