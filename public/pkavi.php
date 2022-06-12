<?php declare(strict_types=1);

/**
 * pkavi.php - PluralKit member/system/group avatar proxy
 * Part of irys-tools - https://tools.irys.cc
 * License: Public domain / CC0
 *
 * Requirements:
 * - PHP >= 7.4
 * - `imagick` extension
 *
 * Query parameters:
 * - `ty` (required) - one of "m" (member), "s" (system), "g" (group)
 * - `id` (required) - PluralKit 5-character ID (or UUID) of the chosen object type
 * - `fb` (optional) - URL to fallback image (defaults to Discord default avatar)
 * - `rs` (optional) - Resolution of image (defaults to `256`, for 256x256px output)
 *
 * Configuration options (config.inc.php):
 * - `PLURALKIT_API_BASE` - string, defaults to https://api.pluralkit.me
 * - `PKAVI_CACHE_ENABLED` - bool, whether to cache avatars (defaults to `false`)
 * - `PKAVI_CACHE_PATH` - string, where to cache avatars
 *    defaults to `BASE_DIR . '/cache/pkavi'`)
 */

require_once(dirname(__DIR__) . '/bootstrap.php');

if (!defined("PLURALKIT_API_BASE"))
	define("PLURALKIT_API_BASE", "https://api.pluralkit.me");
if (!defined("PKAVI_CACHE_ENABLED"))
	define("PKAVI_CACHE_ENABLED", false);
if (!defined("PKAVI_CACHE_PATH"))
	define("PKAVI_CACHE_PATH", BASE_DIR . '/cache/pkavi');

if (empty($type = trim($_GET['ty'] ?? '')))
	die("missing param: ty");
if (!in_array($type, ['m', 's' ,'g']))
	die("invalid param: ty");
if (empty($pk_id = trim($_GET['id'] ?? '')))
	die("missing param: id");
if (empty($fallback = trim($_GET['fb'] ?? '')))
	$fallback = "https://cdn.discordapp.com/embed/avatars/0.png";
if (($resolution = intval(trim($_GET['rs'] ?? '0'))) === 0)
	$resolution = 256;

$avatar_url_hash = $avatar_data = false;
$api_avatar = 'avatar_url';
$api_component = 'undefined';

if ($type === 'm')
{
	$api_component = 'members';
}
else if ($type === 's')
{
	$api_component = 'systems';
}
else if ($type === 'g')
{
	$api_component = 'groups';
	$api_avatar = 'icon';
}

try
{
	// grab data from PluralKit API
	$api_url = PLURALKIT_API_BASE . '/v2/' . $api_component . '/' . $pk_id;
	$pk_json = hCurlFetch($api_url, [], true);

	// grab the avatar image
	if ($pk_json !== false)
	{
		$match_id = '/(?:\/|(?:\?|&)id=)(?:'
			. preg_quote($pk_json['id'] ?? '')
			. '|'
			. preg_quote($pk_json['uuid'] ?? '')
			. ')(?:\.|&|$)/';

		$avatar_url = $pk_json[$api_avatar] ?? '';

		// fallback on empty avatar url
		if (empty($avatar_url))
			$avatar_data = false;

		// fallback if avatar url has our hostname in it
		else if (str_contains($avatar_url, $_SERVER['SERVER_NAME']))
			$avatar_data = false;

		// fallback if avatar url contains the member ID or UUID
		// (in a specific format, that matches invocations of this script)
		else if (preg_match($match_id, $avatar_url) === 1)
			$avatar_data = false;

		// otherwise, we're good
		else
		{
			$avatar_url_hash = hash("sha256", $pk_json[$api_avatar]);
			if (PKAVI_CACHE_ENABLED && $avatar_url_hash !== false)
			{
				if (file_exists($cache_path = (PKAVI_CACHE_PATH . '/' . $avatar_url_hash)))
					$avatar_data = $cache_path;
				else
					$avatar_data = hCurlFetch($pk_json[$api_avatar]);
			}
			else
			{
				$avatar_data = hCurlFetch($pk_json[$api_avatar]);
			}
		}
	}
}
catch (\Exception $e)
{
	// force avatar fallback on exception
	$avatar_data = false;
}

// if we have a cached image, return it immediately
if (PKAVI_CACHE_ENABLED && str_starts_with($avatar_data, PKAVI_CACHE_PATH))
{
	header('Content-Type: image/jpeg');
	print file_get_contents($avatar_data);
	exit;
}

// grab fallback image if we have no avatar data
$cache_disable = false;
if ($avatar_data === false)
{
	$cache_disable = true;
	$avatar_data = hCurlFetch($fallback);
}

// create Imagick image
$image = new Imagick();
$image->readImageBlob($avatar_data);

// get source resolution, prevent upscaling if it's smaller than `$resolution`
if (($source_res = min($image->getImageGeometry())) < $resolution)
	$resolution = $source_res;

// thumbnail the image
$image->cropThumbnailImage($resolution, $resolution);

// get image blob
$image->setImageFormat('jpeg');
$image_blob = $image->getImageBlob();

// send image to client as a JPEG
header('Content-Type: image/jpeg');
print $image_blob;

// write to cache
if (PKAVI_CACHE_ENABLED && !$cache_disable && $avatar_url_hash !== false)
{
	if (!file_exists(PKAVI_CACHE_PATH))
		mkdir(PKAVI_CACHE_PATH, 0777, true);

	file_put_contents(PKAVI_CACHE_PATH . '/' . $avatar_url_hash, $image_blob);
}
