<?php

require_once(__DIR__ . '/../src/mapi/mapitags.php');

require_once(__DIR__ . '/../src/util/Logger.php');
use \Kopano\Api\Logger as Logger;

require_once(__DIR__ . '/../src/components/User.php');
require_once(__DIR__ . '/../src/components/Folder.php');
require_once(__DIR__ . '/../src/components/folders/CommonViewsFolder.php');
require_once(__DIR__ . '/../src/components/folders/FinderFolder.php');
require_once(__DIR__ . '/../src/components/folders/TodoListFolder.php');

//*
$user = new \Kopano\Api\User('user3', 'user3');
$user->setServer('default:');
/*/
$user = new \Kopano\Api\User('rtoussaint', '*');
$user->setServer('https://email.kopano.com:237/kopano');
//*/
//$user = new \Kopano\Api\User('ronald', 'bigron');
//$user->setServer('http://localhost:236/kopano');

$user->logon();

$todolist = $user->getStore()->getTodoList();
$todolist->getProperty(PR_ENTRYID);
Logger::log('todolist', $todolist);

Logger::log('todo items', $todolist->getItems());
Logger::logRestrictionHtml('todo restriction', $todolist->getSearchRestriction()['restriction']);

//$root = $user->getStore()->getRoot();
//$todosearchFolder = $root->getSubFolders()[0];
//$todosearchFolder->delete();
