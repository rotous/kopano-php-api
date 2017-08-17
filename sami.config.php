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

 use Sami\Parser\Filter\TrueFilter;

$sami = new Sami\Sami(__DIR__ . '/src', array(
    'template_dirs' => array(__DIR__.'/sami-themes/phpduck'),
    'theme'         => 'phpduck',
    'title'         => 'Kopano PHP API',
    'build_dir'     => __DIR__ . '/docs'
));

/*
 * Include this section if you want sami to document
 * private and protected functions/properties
 */
$sami['filter'] = function () {
    return new TrueFilter();
};

return $sami;
