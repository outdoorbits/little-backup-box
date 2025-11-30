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
* Usage:
*  CLI: php telegram_chat_id.php <token> [offset=<n>]
*  Web: POST token=<token> [&offset=<n>]
* Returns JSON: { ok: bool, chats: [{id,type,name}], error?, hint? }
*/

function is_cli(): bool { return PHP_SAPI === 'cli'; }

function get_token(): ?string {
	if (!is_cli()) {
		$t = $_POST['token'] ?? null;
		return (is_string($t) && $t !== '') ? $t : null;
	}
	global $argv;
	if (!isset($argv) || count($argv) < 2) return null;
	if (strpos($argv[1], '=') === false) return $argv[1]; // direct token
	foreach ($argv as $arg) {
		if (strpos($arg, '=') !== false) {
			[$k, $v] = explode('=', $arg, 2);
			if ($k === 'token') return $v;
		}
	}
	return null;
}

function get_offset(): ?int {
	if (!is_cli()) {
		$o = $_POST['offset'] ?? null;
		return is_numeric($o) ? (int)$o : null;
	}
	global $argv;
	foreach (($argv ?? []) as $arg) {
		if (str_starts_with($arg, 'offset=')) {
			$v = substr($arg, 7);
			return is_numeric($v) ? (int)$v : null;
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
			CURLOPT_USERAGENT      => 'TelegramChatIdFetcher/1.1',
		]);
		$res = curl_exec($ch);
		curl_close($ch);
		return $res === false ? false : $res;
	} else {
		$ctx = stream_context_create([
			'http' => ['method' => 'GET', 'timeout' => $timeout, 'header' => "User-Agent: TelegramChatIdFetcher/1.1\r\n"]
		]);
		return @file_get_contents($url, false, $ctx);
	}
}

function output_json(array $payload, int $status = 200): void {
	if (!is_cli()) {
		if (!headers_sent()) {
			header('Content-Type: application/json; charset=utf-8');
			http_response_code($status);
		}
		echo json_encode($payload, JSON_UNESCAPED_UNICODE);
	} else {
		fwrite(STDOUT, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL);
	}
	exit;
}

$token = get_token();
if ($token === null || !preg_match('/^\d+:[A-Za-z0-9_-]+$/', $token)) {
	output_json(['ok' => false, 'error' => 'invalid_token', 'hint' => 'POST token=... or CLI: php script.php <token>'], 400);
}

$offset = get_offset();
$q = $offset !== null ? ('?offset=' . urlencode((string)$offset)) : '';
$url  = "https://api.telegram.org/bot{$token}/getUpdates{$q}";
$resp = http_get($url, 15);
if ($resp === false) {
	output_json(['ok' => false, 'error' => 'telegram_unreachable'], 502);
}

$data = json_decode($resp, true);
if (!is_array($data) || ($data['ok'] ?? false) !== true) {
	output_json(['ok' => false, 'error' => 'bad_api_response', 'raw' => $data], 502);
}

// --- parse & dedupe ----------------------------------------------------------
$results = $data['result'] ?? [];
$chatsById = [];

/** Build display name */
$mkName = function(array $chat): string {
	if (!empty($chat['title'])) return (string)$chat['title']; // group/channel
	if (!empty($chat['username'])) return (string)$chat['username'];
	$first = $chat['first_name'] ?? '';
	$last  = $chat['last_name']  ?? '';
	$name  = trim($first . ' ' . $last);
	return $name !== '' ? $name : '(ohne Namen)';
};

$collect = function(array $chat) use (&$chatsById, $mkName) {
	if (!isset($chat['id'])) return;
	$id   = (string)$chat['id'];          // keep as string to preserve sign/width
	$type = $chat['type'] ?? 'unknown';   // private|group|supergroup|channel
	$name = $mkName($chat);
	$chatsById[$id] = ['id' => $chat['id'], 'type' => $type, 'name' => $name];
};

foreach ($results as $u) {
	// message-like updates
	foreach (['message','edited_message','channel_post','edited_channel_post'] as $k) {
		if (!empty($u[$k]['chat'])) $collect($u[$k]['chat']);
	}
	// membership updates
	if (!empty($u['my_chat_member']['chat'])) $collect($u['my_chat_member']['chat']);
	if (!empty($u['chat_member']['chat']))    $collect($u['chat_member']['chat']);
}

// sort: private, group, supergroup, channel, unknown; then by name
$order = ['private'=>0,'group'=>1,'supergroup'=>2,'channel'=>3,'unknown'=>9];
$chats = array_values($chatsById);
usort($chats, function($a,$b) use ($order) {
	$da = $order[$a['type']] ?? 9;
	$db = $order[$b['type']] ?? 9;
	if ($da !== $db) return $da <=> $db;
	return strcmp((string)$a['name'], (string)$b['name']);
});

output_json(['ok' => true, 'chats' => $chats]);
