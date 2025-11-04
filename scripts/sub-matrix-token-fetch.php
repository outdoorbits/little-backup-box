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
async function openMatrixTokenFetcher() {
	const homeserverEl	= document.getElementById('conf_SOCIAL_MATRIX_HOMESERVER');
	const tokenEl		= document.getElementById('conf_SOCIAL_MATRIX_TOKEN');

	const homeserverDefault = (homeserverEl?.value || '').trim();

	// Build dialog if not yet present
	let dlg = document.getElementById('mx-token-fetcher');
	if (!dlg) {
		dlg		= document.createElement('dialog');
		dlg.id	= 'mx-token-fetcher';
		dlg.innerHTML = `
		<form method="dialog" style="min-width:420px; font-family: system-ui, sans-serif;">
			<h3 style="margin:0 0 .5rem 0;"><?php echo L::config_social_matrix_gettoken_header; ?></h3>
			<p style="margin:.25rem 0 .75rem 0; color:#555;"><?php echo L::config_social_matrix_gettoken_desc; ?></p>

			<label style="display:block; margin-bottom:.25rem;">
				<?php echo L::config_social_matrix_homeserver_label; ?><br>
				<input type="text" id="mx-gettoken-homeserver" style="width:100%;">
			</label>

			<label style="display:block; margin-bottom:.25rem;">
				<?php echo L::config_social_matrix_gettoken_username_label; ?><br>
				<input type="text" id="mx-gettoken-username" style="width:100%;">
			</label>

			<label style="display:block; margin-bottom:.25rem;">
				<?php echo L::config_social_matrix_gettoken_password_label; ?><br>
				<input type="password" id="mx-gettoken-password" style="width:100%;">
			</label>

			<div id="mx-gettoken-status" style="margin:.5rem 0; color:#555; min-height:1.2em;"></div>

			<div style="display:flex; gap:.5rem; margin-top:.75rem; justify-content:flex-end; flex-wrap:wrap;">
				<button id="mx-gettoken-cancel" value="cancel" type="submit">
					<?php echo L::config_social_matrix_close; ?>
				</button>
				<button id="mx-gettoken-fetch" value="default">
					<?php echo L::config_social_matrix_gettoken_button_label; ?>
				</button>
			</div>
		</form>`;
		document.body.appendChild(dlg);

		const hsInput	= dlg.querySelector('#mx-gettoken-homeserver');
		const userInput	= dlg.querySelector('#mx-gettoken-username');
		const pwInput	= dlg.querySelector('#mx-gettoken-password');
		const statusEl	= dlg.querySelector('#mx-gettoken-status');

		// Handler für "Fetch token"
		dlg.querySelector('#mx-gettoken-fetch').onclick = async (e) => {
			e.preventDefault();

			const homeserver	= (hsInput.value || '').trim();
			const username		= (userInput.value || '').trim();
			const password		= pwInput.value || '';

			if (!homeserver) {
				alert('<?php echo L::config_social_matrix_error_not_set_homeserver; ?>');
				return;
			}
			if (!username) {
				alert('<?php echo L::config_social_matrix_gettoken_enter_username_first; ?>');
				return;
			}
			if (!password) {
				alert('<?php echo L::config_social_matrix_gettoken_enter_password_first; ?>');
				return;
			}

			statusEl.textContent	= '<?php echo L::config_social_matrix_gettoken_fetching; ?>';

			try {
				const fd = new FormData();
				fd.append('homeserver', homeserver);
				fd.append('username', username);
				fd.append('password', password);

				const res = await fetch('/matrix_get_token.php', {
					method: 'POST',
					body: fd
				});
				const data = await res.json();

				if (!data.ok) {
					const errors = {
						'invalid_credentials': <?php echo json_encode(L::config_social_matrix_gettoken_error_invalid_credentials); ?>,
						'bad_api_response'   : <?php echo json_encode(L::config_social_matrix_error_bad_api_response); ?>,
						'matrix_unreachable' : <?php echo json_encode(L::config_social_matrix_error_unreachable); ?>,
						'missing_parameters' : <?php echo json_encode(L::config_social_matrix_gettoken_error_missing_parameters); ?>,
					};
					const msg = errors[data.error] || ('Error: ' + (data.error || 'unknown'));
					statusEl.textContent	= msg;
					return;
				}

				const token		= data.token || '';
				const userId	= data.user_id || '';

				if (!token) {
					statusEl.textContent	= '<?php echo L::config_social_matrix_gettoken_error_no_token; ?>';
					return;
				}

				// write token to main input
				if (tokenEl) {
					tokenEl.value	= token;
				}

				// also fill homeserver field if empty
				if (homeserverEl && !homeserverEl.value) {
					homeserverEl.value	= homeserver;
				}

				statusEl.textContent	= '<?php echo L::config_social_matrix_gettoken_success; ?>' + (userId ? ' ' + userId : '');
				// dialog offen lassen, damit man die Meldung sieht
			} catch (e) {
				console.error(e);
				statusEl.textContent	= '<?php echo L::config_social_matrix_gettoken_error_exception; ?>';
			}
		};
	}

	// Default-Wert für Homeserver übergeben
	const hsInput = dlg.querySelector('#mx-gettoken-homeserver');
	if (hsInput && homeserverDefault && !hsInput.value) {
		hsInput.value	= homeserverDefault;
	}

	if (typeof dlg.showModal === 'function') dlg.showModal();
	else dlg.style.display	= 'block';
}
</script>

<button type="button" onclick="openMatrixTokenFetcher()" id="get_matrix_token">
	<?php echo L::config_social_matrix_gettoken_button_label; ?>
</button>
