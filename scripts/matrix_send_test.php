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

	$homeserver	= isset($_POST['homeserver'])	? trim($_POST['homeserver'])	: '';
	$token		= isset($_POST['token'])		? trim($_POST['token'])			: '';
	$room_id	= isset($_POST['room_id'])		? trim($_POST['room_id'])		: '';
	$text		= isset($_POST['text'])			? trim($_POST['text'])			: '';

	if ($homeserver === '' || $token === '' || $room_id === '' || $text === '') {
		echo json_encode(['ok' => false, 'error' => 'missing_parameters']);
		exit;
	}

	$homeserver	= rtrim($homeserver, '/');

	// small POST helper
	function matrix_post_json($url, $token, array $payload) {
		$ch	= curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_CUSTOMREQUEST  => 'PUT',
			CURLOPT_HTTPHEADER		=> [
				'Authorization: Bearer ' . $token,
				'Content-Type: application/json',
			],
			CURLOPT_POSTFIELDS	=> json_encode($payload),
		]);
		$body	= curl_exec($ch);
		$status	= curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return [$status, $body];
	}

	// Build transaction ID (should be unique per request)
	$txnId		= 'test_' . bin2hex(random_bytes(8));
	$roomIdEnc	= rawurlencode($room_id);

	$url = $homeserver . '/_matrix/client/v3/rooms/' . $roomIdEnc
		. '/send/m.room.message/' . $txnId;

	$payload	= [
		'msgtype' => 'm.text',
		'body'    => $text,
	];

	list($status, $body)	= matrix_post_json($url, $token, $payload);

	if ($status >= 200 && $status < 300) {
		echo json_encode(['ok' => true]);
	} else {
		echo json_encode([
			'ok'    => false,
			'error' => 'bad_api_response',
			'status'=> $status,
			'body'  => $body,
		]);
	}


