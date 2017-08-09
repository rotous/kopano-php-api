<?php

/**
 * This file includes all the necessary files to use the Kopano PHP API. This is the only
 * file that should be included when writing scripts that use the Kopano PHP API.
 */

// The mapi tags and defs
require_once(__DIR__ . '/mapi/mapitags.php');

// The Server is the starting point of the API. All other files will be included
// recursively.
require_once(__DIR__ . '/components/Server.php');

// Utilities
require_once(__DIR__ . '/util/Logger.php');
