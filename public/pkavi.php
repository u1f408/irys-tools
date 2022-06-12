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
 */

require_once(dirname(__DIR__) . '/bootstrap.php');
define("API_BASE", "https://api.pluralkit.me/v2");

if (empty($type = trim($_GET['ty'] ?? ''))) die("missing param: ty");
if (!in_array($type, ['m', 's' ,'g'])) die("invalid param: ty");
if (empty($pk_id = trim($_GET['id'] ?? ''))) die("missing param: id");
if (empty($fallback = trim($_GET['fb'] ?? ''))) $fallback = "https://cdn.discordapp.com/embed/avatars/0.png";
if (($resolution = intval(trim($_GET['rs'] ?? '0'))) === 0) $resolution = 256;

$avatar_data = false;
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
	$api_url = API_BASE . '/' . $api_component . '/' . $pk_id;
	$pk_json = hCurlFetch($api_url, [], true);

	// grab the avatar image
	if ($pk_json !== false)
	{
		$match_id = '/(?:\/|(?:\?|&)id=)(?:' . preg_quote($pk_json['id']) . '|' . preg_quote($pk_json['uuid']) . ')(?:\.|&|$)/';
		$avatar_url = $pk_json[$api_avatar] ?? '';

		// fallback on empty avatar url
		if (empty($avatar_url)) $avatar_data = false;

		// fallback if avatar url has our hostname in it
		else if (str_contains($avatar_url, $_SERVER['SERVER_NAME'])) $avatar_data = false;

		// fallback if avatar url contains the member ID or UUID
		// (in a specific format, that matches invocations of this script)
		else if (preg_match($match_id, $avatar_url) === 1) $avatar_data = false;

		// otherwise, we're good
		else
		{
			$avatar_data = hCurlFetch($pk_json[$api_avatar]);
		}
	}
}
catch (\Exception $e)
{
	// force avatar fallback on exception
	$avatar_data = false;
}

// grab fallback image if we have no avatar data
if ($avatar_data === false)
	$avatar_data = hCurlFetch($fallback);

// create Imagick image
$image = new Imagick();
$image->readImageBlob($avatar_data);

// get source resolution, prevent upscaling if it's smaller than `$resolution`
if (($source_res = min($image->getImageGeometry())) < $resolution) $resolution = $source_res;

// thumbnail the image
$image->cropThumbnailImage($resolution, $resolution);

// send image to client as a JPEG
$image->setImageFormat('jpeg');
header('Content-Type: image/jpeg');
print $image->getImageBlob();
