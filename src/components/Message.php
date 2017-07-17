<?php

namespace Kopano\Api;

require_once(__DIR__ . '/Item.php');
require_once(__DIR__ . '/items/CommonViewMessage.php');
require_once(__DIR__ . '/items/MailMessage.php');

class Message extends Item {
	static public $classMap = array(
		'IPM.Microsoft.WunderBar.Link' => '\Kopano\Api\CommonViewMessage',
		'IPM.Note' => '\Kopano\Api\MailMessage',
	);

}
