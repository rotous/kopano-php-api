<?php

namespace Kopano\Api;

require_once(__DIR__ . '/Item.php');
require_once(__DIR__ . '/items/CommonViewMessage.php');
require_once(__DIR__ . '/items/MailMessage.php');
require_once(__DIR__ . '/items/Contact.php');
require_once(__DIR__ . '/items/Appointment.php');

class Message extends Item {
	static protected $_propertyKeys = array(
		PR_FLAG_STATUS,
		PR_FLAG_ICON
	);

	static public $classMap = array(
		'IPM.Microsoft.WunderBar.Link' => '\Kopano\Api\CommonViewMessage',
		'IPM.Note' => '\Kopano\Api\MailMessage',
		'IPM.Contact' => '\Kopano\Api\Contact',
		'IPM.Appointment' => '\Kopano\Api\Appointment',
	);

}
