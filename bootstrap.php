<?php declare(strict_types=1);
define("BASE_DIR", dirname(($p = realpath(__FILE__)) !== false ? $p : __FILE__));

// load includes
if (($sdi = glob(BASE_DIR . '/includes/*.inc.php')) !== false)
	foreach ($sdi as $filename) require_once($filename);
