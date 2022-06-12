<?php declare(strict_types=1);
define("BASE_DIR", dirname(($p = realpath(__FILE__)) !== false ? $p : __FILE__));

// load any deployment-specific config
if (file_exists($config_path = BASE_DIR . '/config.inc.php'))
	require_once($config_path);

// load includes
if (($sdi = glob(BASE_DIR . '/includes/*.inc.php')) !== false)
	foreach ($sdi as $filename) require_once($filename);
