<?php declare(strict_types=1);
if (!defined("BASE_DIR")) die;

/* hCurlFetch is a (very slightly) modified version of CurlHelpers::fetchUrl,
 * from https://github.com/u1f408/phphelpers - licensed under the MIT license.
 *
 * Copyright (c) 2021 The Iris System, and contributors
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

function hCurlFetch(string $url, array $curlopts = [], bool $json_only = false)
{
	$ch = curl_init();
	curl_setopt_array($ch, $curlopts);
	curl_setopt_array($ch, [
		CURLOPT_URL => $url,
		CURLOPT_HEADER => true,
		CURLOPT_RETURNTRANSFER => true,
	]);

	/** @var string|false $response */
	$response = curl_exec($ch);
	if ($response === false)
		return false;

	$req_info = curl_getinfo($ch);
	$header_length = array_key_exists('header_size', $req_info) ? $req_info['header_size'] : 0;
	list($headers, $body) = [substr($response, 0, $header_length), substr($response, $header_length)];

	if (array_key_exists('content_type', $req_info))
	{
		$content_type = explode(';', $req_info['content_type'], 2)[0];
		if (trim($content_type) === 'application/json')
		{
			$body = json_decode($body, true);
		}
		else
		{
			if ($json_only === true)
				return false;
		}
	}

	return $body;
}
