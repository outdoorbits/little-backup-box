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

	$homeserver	= isset($_POST['homeserver']) ? trim($_POST['homeserver']) : '';
	$username	= isset($_POST['username'])   ? trim($_POST['username'])   : '';
	$password	= isset($_POST['password'])   ? $_POST['password']         : '';

	if ($homeserver === '' || $username === '' || $password === '') {
		echo json_encode([
			'ok'	=> false,
			'error'	=> 'missing_parameters',
		]);
		exit;
	}

	$homeserver = rtrim($homeserver, '/');

	// Simple helper for POST JSON with curl
	function matrix_post_json($url, array $payload) {
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_POST		=> true,
			CURLOPT_HTTPHEADER	=> ['Content-Type: application/json'],
			CURLOPT_POSTFIELDS	=> json_encode($payload),
			CURLOPT_TIMEOUT		=> 15,
		]);
		$body	= curl_exec($ch);
		$status	= curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$err	= curl_error($ch);
		curl_close($ch);
		return [$status, $body, $err];
	}

	$url	= $homeserver . '/_matrix/client/v3/login';

	// Matrix login payload (password login)
	$payload = [
		'type'			=> 'm.login.password',
		'identifier'	=> [
			'type'	=> 'm.id.user',
			'user'	=> $username,
		],
		'password'	=> $password,
	];

	list($status, $body, $err) = matrix_post_json($url, $payload);

	if ($body === false || $err) {
		echo json_encode([
			'ok'	=> false,
			'error'	=> 'matrix_unreachable',
			'debug'	=> $err,
		]);
		exit;
	}

	if ($status === 403 || $status === 401) {
		echo json_encode([
			'ok'		=> false,
			'error'		=> 'invalid_credentials',
			'status'	=> $status,
		]);
		exit;
	}

	if ($status !== 200) {
		echo json_encode([
			'ok' 		=> false,
			'error' 	=> 'bad_api_response',
			'status'	=> $status,
			'body' 		=> $body,
		]);
		exit;
	}

	$data = json_decode($body, true);
	if (!is_array($data)) {
		echo json_encode([
			'ok' 		=> false,
			'error' 	=> 'bad_api_response',
			'body' 		=> $body,
		]);
		exit;
	}

	$token  = isset($data['access_token']) ? $data['access_token'] : null;
	$userId = isset($data['user_id'])      ? $data['user_id']      : null;

	if (!$token) {
		echo json_encode([
			'ok' 		=> false,
			'error' 	=> 'bad_api_response',
			'body' 		=> $body,
		]);
		exit;
	}

	echo json_encode([
		'ok' 		=> true,
		'token' 	=> $token,
		'user_id' 	=> $userId,
	]);
