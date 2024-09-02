<?php
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\AnnotationReader;
use Composer\Autoload\ClassLoader;

define('TESTS_PATH', __DIR__);
define('TESTS_TEMP_DIR', __DIR__.'/temp');
define('VENDOR_PATH', dirname(__DIR__) . '/vendor');

/** @var $loader ClassLoader */
$loader = require __DIR__.'/../vendor/autoload.php';


//AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
//$reader = new AnnotationReader();
//$_ENV['annotation_reader'] = $reader;
