<?php

error_reporting(E_ALL | E_STRICT);

define('VENDOR_PATH', realpath(__DIR__ . '/../vendor'));
define('FIXTURES_PATH', realpath(__DIR__.'/FSi/Component/DataSource/Tests/Fixtures'));

if (!class_exists('PHPUnit_Framework_TestCase') ||
    version_compare(PHPUnit_Runner_Version::id(), '3.5') < 0
) {
    die('PHPUnit framework is required, at least 3.5 version'."\n");
}

if (!class_exists('PHPUnit_Framework_MockObject_MockBuilder')) {
    die('PHPUnit MockObject plugin is required, at least 1.0.8 version'."\n");
}
if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
    die('Install vendors using command: composer.phar install --dev'."\n");
}

$loader = require_once __DIR__.'/../vendor/autoload.php';
$loader->add('FSi\\Component\\DataSource\\Tests', __DIR__);
$loader->add('FSi\\FSiExtension\\DataGrid\\Extension\\Tests\\Fixtures', __DIR__);
if (class_exists($annotationRegistry = 'Doctrine\Common\Annotations\AnnotationRegistry')) {
    $annotationRegistry::registerLoader(function($class) {
        if (0 === strpos(ltrim($class, '/'), 'FSi\Component\DataSource')) {
            if (file_exists($file = __DIR__.'/../lib/'. str_replace('\\', '/', $class) .'.php')) {
                require_once $file;
            }
        }
        return class_exists($class, false);
    });
}