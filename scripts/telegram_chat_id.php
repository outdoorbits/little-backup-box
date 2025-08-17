<?php

// # Author: Stefan Saam, github@saams.de
// # Original author: Dmitri Popov, dmpop@linux.com
//
// #######################################################################
// # This program is free software: you can redistribute it and/or modify
// # it under the terms of the GNU General Public License as published by
// # the Free Software Foundation, either version 3 of the License, or
// # (at your option) any later version.
//
// # This program is distributed in the hope that it will be useful,
// # but WITHOUT ANY WARRANTY; without even the implied warranty of
// # MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// # GNU General Public License for more details.
//
// # You should have received a copy of the GNU General Public License
// # along with this program.  If not, see <http://www.gnu.org/licenses/>.
// #######################################################################


declare(strict_types=1);

/**
* telegram_chat_id.php
* usage:
*   CLI:  php telegram_chat_id.php 123456789:ABC...     (or)  php telegram_chat_id.php token=123...
*   Web:  POST token=123456789:ABC...
*
* returns JSON
*/

function is_cli(): bool {
	return PHP_SAPI === 'cli';
}

function get_token(): ?string {
	if (!is_cli()) {
		return $_POST['token'] ?? null;
	}

	global $argv;
	if (!isset($argv) || count($argv) < 2) return null;

	// 1. variant: first parameter is the token
	if (strpos($argv[1], '=') === false) return $argv[1];

	// 2. variant: token=xyz
	foreach ($argv as $arg) {
		if (strpos($arg, '=') !== false) {
			[$key, $value] = explode('=', $arg, 2);
			if ($key === 'token') return $value;
		}
	}
	return null;
}

function http_get(string $url, int $timeout = 10): string|false {
	if (function_exists('curl_init')) {
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CONNECTTIMEOUT => $timeout,
			CURLOPT_TIMEOUT        => $timeout,
			CURLOPT_USERAGENT      => 'TelegramChatIdFetcher/1.0',
		]);
		$res = curl_exec($ch);
		$err = curl_error($ch);
		curl_close($ch);
		return $res === false ? false : $res;
	} else {
		$ctx = stream_context_create([
			'http' => ['method' => 'GET', 'timeout' => $timeout, 'header' => "User-Agent: TelegramChatIdFetcher/1.0\r\n"]
		]);
		return @file_get_contents($url, false, $ctx);
	}
}

function output_json(array $payload, int $status = 200): void {
	if (!is_cli()) {
		if (!headers_sent()) {
			// optionally allow CORS sharing if the script serves as an API:
			header('Content-Type: application/json; charset=utf-8');
			http_response_code($status);
		}
		echo json_encode($payload, JSON_UNESCAPED_UNICODE);
	} else {
		fwrite(STDOUT, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL);
	}
	exit;
}

// --- logic -------------------------------------------------------------------
$token = get_token();
if ($token === null || !preg_match('/^\d+:[A-Za-z0-9_-]+$/', $token)) {
	output_json(['ok' => false, 'error' => 'invalid_token', 'hint' => 'POST token=... or CLI: php script.php <token>'], 400);
}

$url  = "https://api.telegram.org/bot{$token}/getUpdates";
$resp = http_get($url, 15);
if ($resp === false) {
	output_json(['ok' => false, 'error' => 'telegram_unreachable'], 502);
}

$data = json_decode($resp, true);
if (!is_array($data) || ($data['ok'] ?? false) !== true) {
	output_json(['ok' => false, 'error' => 'bad_api_response', 'raw' => $data], 502);
}

// parse result  & deduplicate chats
$out = [];
$results = $data['result'] ?? [];
foreach ($results as $u) {
	$chat = $u['message']['chat']
		?? $u['edited_message']['chat']
		?? $u['channel_post']['chat']
		?? $u['edited_channel_post']['chat']
		?? null;

	if ($chat && isset($chat['id'])) {
		$id   = (string)$chat['id'];
		$type = $chat['type'] ?? null; // private, group, supergroup, channel
		$name = $chat['title'] ?? ($chat['first_name'] ?? null);
		$out[$id] = ['id' => $chat['id'], 'type' => $type, 'name' => $name];
	}
}

output_json(['ok' => true, 'chats' => array_values($out)]);
?>
