<?php
// # Author: Stefan Saam, github@saams.de
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

	header('Content-Type: application/json');

	// Basic input validation
	$homeserver	= isset($_POST['homeserver']) ? trim($_POST['homeserver']) : '';
	$token		= isset($_POST['token'])      ? trim($_POST['token'])      : '';

	if ($homeserver === '' || $token === '') {
		echo json_encode(['ok' => false, 'error' => 'missing_parameters']);
		exit;
	}

	$homeserver = rtrim($homeserver, '/');

	// Get the user_id of the bot for this access token
	list($stWho, $bodyWho, $errWho) = matrix_get(
		$homeserver . '/_matrix/client/v3/account/whoami',
		$token
	);
	$bot_user_id	= null;
	if (!$errWho && $stWho === 200) {
		$who	= json_decode($bodyWho, true);
		if (is_array($who) && isset($who['user_id'])) {
			$bot_user_id	= $who['user_id'];
		}
	}

	// Small helper for HTTP GET with Bearer token
	function matrix_get($url, $token) {
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
			CURLOPT_TIMEOUT        => 15,
		]);
		$body   = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$err    = curl_error($ch);
		curl_close($ch);
		return [$status, $body, $err];
	}

	// 1) Get joined rooms
	list($status, $body) = matrix_get($homeserver . '/_matrix/client/v3/joined_rooms', $token);
	if ($status !== 200) {
		echo json_encode([
			'ok'		=> false,
			'error'		=> ($status === 401 ? 'invalid_token' : 'bad_api_response'),
			'status'	=> $status,
		]);
		exit;
	}

	$data = json_decode($body, true);
	if (!is_array($data) || !isset($data['joined_rooms']) || !is_array($data['joined_rooms'])) {
		echo json_encode([
			'ok'		=> false,
			'error'		=> 'bad_api_response',
		]);
		exit;
	}

	$rooms_out	= [];
	$user_id	= null;

	foreach ($data['joined_rooms'] as $room_id) {
		$room = [
			'room_id'			=> $room_id,
			'name'				=> null,
			'canonical_alias'	=> null,
		];

		// Try to fetch m.room.name (optional)
		$roomEnc = rawurlencode($room_id);

		// Fetch power levels for this room
		list($st2, $body2, $err2) = matrix_get(
			$homeserver . '/_matrix/client/v3/rooms/' . $roomEnc . '/state/m.room.power_levels',
			$token
		);
		if ($err2 || $st2 !== 200) {
			// if we can't read power levels, skip room
			continue;
		}

		$pl = json_decode($body2, true);
		if (!is_array($pl)) {
			continue;
		}

		// Get relevant power level info
		$users          = $pl['users'] ?? [];
		$users_default  = isset($pl['users_default'])  ? (int)$pl['users_default']  : 0;
		$events         = $pl['events'] ?? [];
		$events_default = isset($pl['events_default']) ? (int)$pl['events_default'] : 0;

		// Required level to send m.room.message
		$sendLevel = isset($events['m.room.message'])
			? (int)$events['m.room.message']
			: $events_default;

		// Actual level of our bot user
		if ($bot_user_id === null) {
			// we don't know who we are → be safe and skip
			continue;
		}
		$bot_level = isset($users[$bot_user_id])
			? (int)$users[$bot_user_id]
			: $users_default;

		// If the bot's level is lower than required send level, skip this room
		if ($bot_level < $sendLevel) {
			continue;
		}

		// Skip spaces (rooms that are used only as containers)
		list($stCreate, $bodyCreate, $errCreate) = matrix_get(
			$homeserver . '/_matrix/client/v3/rooms/' . $roomEnc . '/state/m.room.create',
			$token
		);
		if (!$errCreate && $stCreate === 200) {
			$create = json_decode($bodyCreate, true);
			if (is_array($create) && isset($create['type']) && $create['type'] === 'm.space') {
				// This is a Space, not a normal chat room → skip
				continue;
			}
		}

		// fetch Power Levels
		list($st2, $body2, $err2)	= matrix_get("$homeserver/_matrix/client/v3/rooms/$roomEnc/state/m.room.power_levels", $token);
		if ($err2 || $st2 !== 200) continue;

		$pl = json_decode($body2, true);
		if (!is_array($pl)) continue;

		// check Power Levels
		$users				= $pl['users'] ?? [];
		$defaultUserLevel	= intval($pl['users_default'] ?? 0);
		$sendLevel			= intval($pl['events_default'] ?? 0);

		list($st_name, $body_name) = matrix_get(
			$homeserver . '/_matrix/client/v3/rooms/' . $roomEnc . '/state/m.room.name',
			$token
		);
		if ($st_name === 200) {
			$name_data = json_decode($body_name, true);
			if (is_array($name_data) && isset($name_data['name'])) {
				$room['name']	= $name_data['name'];
			}
		}

		// Try to fetch m.room.canonical_alias (optional)
		list($st_alias, $body_alias) = matrix_get(
			$homeserver . '/_matrix/client/v3/rooms/' . $roomEnc . '/state/m.room.canonical_alias',
			$token
		);
		if ($st_alias === 200) {
			$alias_data = json_decode($body_alias, true);
			if (is_array($alias_data) && isset($alias_data['alias'])) {
				$room['canonical_alias'] = $alias_data['alias'];
			}
		}

		$rooms_out[]	= $room;
	}

	if (empty($rooms_out)) {
		echo json_encode([
			'ok'	=> false,
			'error'	=> 'no_writable_rooms'
		]);
		exit;
	}

	echo json_encode([
		'ok'    => true,
		'rooms' => $rooms_out,
	]);

