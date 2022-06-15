<?php declare(strict_types=1);

/**
 * pkavi/cache.php - Statistics for pkavi cache
 * Part of irys-tools - https://tools.irys.cc
 * License: Public domain / CC0
 *
 * Requirements:
 * - PHP >= 7.4
 *
 * Configuration options (config.inc.php):
 * - `PKAVI_CACHE_ENABLED` - bool, whether to cache avatars (defaults to `false`)
 * - `PKAVI_CACHE_PATH` - string, where to cache avatars
 *    defaults to `BASE_DIR . '/cache/pkavi'`)
 */

require_once(dirname(dirname(__DIR__)) . '/bootstrap.php');

if (!defined("PKAVI_CACHE_ENABLED"))
	define("PKAVI_CACHE_ENABLED", false);
if (!defined("PKAVI_CACHE_PATH"))
	define("PKAVI_CACHE_PATH", BASE_DIR . '/cache/pkavi');

header("content-type: application/json");

if (!PKAVI_CACHE_ENABLED)
{
	print json_encode([
		'cache' => null,
	]);

	exit;
}

$total_size = $image_count = $response_count = 0;
$dir_iter = new \RecursiveDirectoryIterator(PKAVI_CACHE_PATH);
$iter = new \RecursiveIteratorIterator($dir_iter, \RecursiveIteratorIterator::CHILD_FIRST);
foreach ($iter as $file)
{
	if ($file->isFile())
	{
		$name = $file->getPathName();
		$total_size += $file->getSize();

		if (preg_match("/\/[0-9a-f]{64}$/", $name))
			$image_count += 1;
		else if (preg_match("/\/[0-9]{8}[msg]-[a-z]{5,}\.json$/", $name))
			$response_count += 1;
	}
}

print json_encode([
	'cache' => [
		'response_count' => $response_count,
		'image_count' => $image_count,
		'total_size' => $total_size,
	],
]);
