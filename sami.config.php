<?php
/**
 * This file is the configuration file used by Sami (https://github.com/FriendsOfPHP/Sami)
 *
 * Download the sami phar and build the docs with the following command:
 *
 *      php sami.phar update /path/to/sami.config.php
 *
 * The documentation will now be build in the docs folder.
 */

return new Sami\Sami(__DIR__ . '/src', array(
    'template_dirs' => array(__DIR__.'/sami-themes/phpduck'),
    'theme'         => 'phpduck',
    'title'         => 'Kopano PHP API',
    'build_dir'     => __DIR__ . '/docs'
));
