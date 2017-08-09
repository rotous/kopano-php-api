<?php

require_once(__DIR__ . '/../src/kopano-api.php');

use \Kopano\Api\Logger as Logger;

$server = new \Kopano\Api\Server('default:');
//$server = new \Kopano\Api\Server('https://email.kopano.com:237/kopano');

$user = $server->getUser('user3', 'user3');
$user->logon();

//$todolist = $user->getStore()->getTodoList();
//$todolist->getProperty(PR_ENTRYID);
//Logger::log('todolist', $todolist);

$finderFolder = $user->getStore()->getFinderFolder();
$finderFolder->getProperty(PR_ENTRYID);
Logger::log($finderFolder);

$searchFolders = $finderFolder->getSubFolders();
Logger::log('SearchFolders in FINDER', $searchFolders);

if ( count($searchFolders) > 0 ){
    foreach ($searchFolders as $i => $searchFolder) {
        Logger::log('Items in search folder ' . $i . ': ' . $searchFolder->getProperty(PR_DISPLAY_NAME), $searchFolder->getItems());
    }
}
