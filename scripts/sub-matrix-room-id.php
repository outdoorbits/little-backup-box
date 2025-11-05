<!--
# Author: Stefan Saam, github@saams.de

#######################################################################
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
#######################################################################
-->

<script>
	async function openMatrixRoomPicker() {
		const homeserverEl							= document.getElementById('conf_SOCIAL_MATRIX_HOMESERVER');
		const tokenEl								= document.getElementById('conf_SOCIAL_MATRIX_TOKEN');
		const conf_SOCIAL_MATRIX_ROOM_ID			= document.getElementById('conf_SOCIAL_MATRIX_ROOM_ID');
		const conf_SOCIAL_MATRIX_ROOM_IDENTIFIER	= document.getElementById('conf_SOCIAL_MATRIX_ROOM_IDENTIFIER');
		const MATRIX_ROOM_ID_PRESENTER				= document.getElementById('MATRIX_ROOM_ID_PRESENTER');
		const Button_Target_Matrix					= document.getElementById('Target_social:matrix');

		const homeserver							= (homeserverEl?.value || '').trim();
		const token									= (tokenEl?.value || '').trim();

		if (!homeserver) { alert('<?php echo L::config_social_matrix_error_not_set_homeserver; ?>'); return; }
		if (!token)      { alert('<?php echo L::config_social_matrix_error_not_set_token; ?>'); return; }

		// Fetch joined rooms via server-side script (to avoid CORS issues with the Matrix homeserver)
		const fd = new FormData();
		fd.append('homeserver', homeserver);
		fd.append('token', token);

		const res  = await fetch('/matrix_rooms.php', { method: 'POST', body: fd });
		const data = await res.json();

		if (!data.ok) {
			const errors = {
				'invalid_token'      : <?php echo json_encode(L::config_social_matrix_error_invalid_token); ?>,
				'matrix_unreachable' : <?php echo json_encode(L::config_social_matrix_error_unreachable); ?>,
				'bad_api_response'   : <?php echo json_encode(L::config_social_matrix_error_bad_api_response); ?>
			};
			alert(errors[data.error] || ('Error: ' + (data.error || 'unknown')));
			return;
		}

		const rooms = Array.isArray(data.rooms) ? data.rooms : [];
		if (rooms.length === 0) {
			alert('<?php echo L::config_social_matrix_no_rooms_found; ?>');
			return;
		}

		// build dialog if not yet present
		let dlg = document.getElementById('mx-room-picker');
		if (!dlg) {
			dlg = document.createElement('dialog');
			dlg.id = 'mx-room-picker';
			dlg.innerHTML = `
			<form method="dialog" style="min-width:520px; font-family: system-ui, sans-serif;">
				<h3 style="margin:0 0 .5rem 0;"><?php echo L::config_social_matrix_select_room; ?></h3>
				<p style="margin:.25rem 0 .5rem 0; color:#555;"><?php echo L::config_social_matrix_table_headers; ?></p>
				<select id="mx-room-select" size="10" style="width:100%;"></select>
				<div style="display:flex; gap:.5rem; margin-top:.75rem; justify-content:flex-end; flex-wrap:wrap;">
					<button id="mx-room-cancel" value="cancel" type="submit"><?php echo L::config_social_matrix_close; ?></button>
					<button id="mx-room-choose" value="default"><?php echo L::config_social_matrix_accept; ?></button>
					<button id="mx-room-test" type="button"><?php echo L::config_social_matrix_test_message_send; ?></button>
				</div>
				<p id="mx-room-picked" style="margin:.5rem 0 0 0; color:#555;"></p>
			</form>`;
			document.body.appendChild(dlg);
		}

		// fill options
		const sel = dlg.querySelector('#mx-room-select');
		sel.innerHTML = '';
		for (const r of rooms) {
			const opt = document.createElement('option');
			const id  = String(r.room_id);
			const name = r.name || r.canonical_alias || '(<?php echo L::config_social_matrix_without_name; ?>)';
			const alias = r.canonical_alias || '';

			opt.dataset.alias	= alias;
			opt.dataset.name	= name;
			opt.value			= id;
			opt.textContent		= alias ? `${name} — ${alias} — ${id}` : `${name} — ${id}`;

			sel.appendChild(opt);
		}

		const chosenP	= dlg.querySelector('#mx-room-picked');

		// choose room
		dlg.querySelector('#mx-room-choose').onclick	= (e) => {
			e.preventDefault();
			const opt = sel.selectedOptions[0];
			if (!opt) return;

			const name			= opt.dataset.name  || '';
			const alias			= opt.dataset.alias || '';
			const identifier	= alias ? `${name} (${alias})` : name;
			const id			= opt.value;

			if (conf_SOCIAL_MATRIX_ROOM_ID) {conf_SOCIAL_MATRIX_ROOM_ID.value = id;}
			if (conf_SOCIAL_MATRIX_ROOM_IDENTIFIER) {conf_SOCIAL_MATRIX_ROOM_IDENTIFIER.value = identifier;}

			if (Button_Target_Matrix) {
				if (identifier) {
					Button_Target_Matrix.innerHTML	= '<?php echo L::box_backup_mode_social_matrix; ?>' + '<br /><small style="font-weight: normal;">' + identifier + '</small>';
				} else {
					Button_Target_Matrix.innerHTML	= '<?php echo L::box_backup_mode_social_matrix; ?>';
				}
			}
			if (MATRIX_ROOM_ID_PRESENTER) {
				MATRIX_ROOM_ID_PRESENTER.value = id + ": " + identifier;
			}

			chosenP.textContent	= `<?php echo L::config_social_matrix_selected; ?>: ${id}`;
			dlg.close('ok');
		};

		// test message button
		dlg.querySelector('#mx-room-test').onclick = async () => {
		const opt = sel.selectedOptions[0];
		if (!opt) {
			alert('<?php echo L::config_social_matrix_select_room_first; ?>');
			return;
		}

		const roomId = opt.value;
		const name   = opt.dataset.name  || '';
		const alias  = opt.dataset.alias || '';

		// Build a nicer label for the alert
		let label = roomId;
		if (name || alias) {
			label = name || alias;
			if (alias && alias !== name) {
				label += ' (' + alias + ')';
			}
		}

		try {
			const fd2 = new FormData();
			fd2.append('homeserver', homeserver);
			fd2.append('token', token);
			fd2.append('room_id', roomId);
			fd2.append('text', '<?php echo L::config_social_matrix_test_message; ?>');

			const r    = await fetch('/matrix_send_test.php', {
				method: 'POST',
				body: fd2
			});
			const res2 = await r.json();
			if (res2.ok) {
				alert('<?php echo L::config_social_matrix_test_message_sent_to; ?> ' + label);
			} else {
				alert('<?php echo L::config_social_matrix_test_message_error; ?>: ' + JSON.stringify(res2));
			}
		} catch (e) {
			alert('<?php echo L::config_social_matrix_test_message_error; ?>: ' + e);
		}
	};


		if (typeof dlg.showModal === 'function') dlg.showModal();
		else dlg.style.display = 'block';
	}
	</script>

	<label for="MATRIX_ROOM_ID_PRESENTER"><?php echo L::config_social_matrix_room_id_label; ?></label><br />
	<input type="hidden" id="conf_SOCIAL_MATRIX_ROOM_ID" name="conf_SOCIAL_MATRIX_ROOM_ID" value="<?php echo htmlspecialchars($config['conf_SOCIAL_MATRIX_ROOM_ID'] ?? '', ENT_QUOTES); ?>">
	<input type="hidden" id="conf_SOCIAL_MATRIX_ROOM_IDENTIFIER" name="conf_SOCIAL_MATRIX_ROOM_IDENTIFIER" value="<?php echo !empty($_POST['conf_SOCIAL_MATRIX_ROOM_IDENTIFIER']) ? htmlspecialchars($_POST['conf_SOCIAL_MATRIX_ROOM_IDENTIFIER'], ENT_QUOTES) : htmlspecialchars($config['conf_SOCIAL_MATRIX_ROOM_IDENTIFIER'] ?? '', ENT_QUOTES); ?>">
	<input type="text" id="MATRIX_ROOM_ID_PRESENTER" name="MATRIX_ROOM_ID_PRESENTER" size="40" value="<?php
		if (!empty($config['conf_SOCIAL_MATRIX_ROOM_ID'])) {
			echo htmlspecialchars($config['conf_SOCIAL_MATRIX_ROOM_ID'] . ': ' . ($config['conf_SOCIAL_MATRIX_ROOM_IDENTIFIER'] ?? ''), ENT_QUOTES);
		}
		?>" disabled>
	<button type="button" onclick="openMatrixRoomPicker()" id="get_matrix_room_id">
		<?php echo L::config_social_matrix_button_select_room; ?>
	</button><br />

