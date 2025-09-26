<script>
	async function openChatPicker() {
	const tokenEl						= document.getElementById('conf_SOCIAL_TELEGRAM_TOKEN');
	const conf_SOCIAL_TELEGRAM_CHAT_ID			= document.getElementById('conf_SOCIAL_TELEGRAM_CHAT_ID');
	const conf_SOCIAL_TELEGRAM_CHAT_IDENTIFIER	= document.getElementById('conf_SOCIAL_TELEGRAM_CHAT_IDENTIFIER');
	const TELEGRAM_CHAT_ID_PRESENTER	= document.getElementById('TELEGRAM_CHAT_ID_PRESENTER');
	const token   = (tokenEl?.value || '').trim();
	if (!token) { alert('Bitte zuerst den Bot-Token eintragen.'); return; }

	// fetch chats
	const fd = new FormData(); fd.append('token', token);
	const res = await fetch('/telegram_chat_id.php', { method: 'POST', body: fd });
	const data = await res.json();

	if (!data.ok) {
		const errors = {
		'invalid_token': <?php echo json_encode(L::config_social_telegram_error_invalid_token); ?>,
		'telegram_unreachable': <?php echo json_encode(L::config_social_telegram_error_unreachable); ?>,
		'bad_api_response': <?php echo json_encode(L::config_social_telegram_error_bad_api_response); ?>
		};
		alert(errors[data.error] || ('Error: ' + (data.error || 'unknown')));
		return;
	}

	const chats = Array.isArray(data.chats) ? data.chats : [];
	if (chats.length === 0) {
		alert('<?php echo L::config_social_telegram_no_chats_found; ?>');
		return;
	}

	// build dialog if not yet
	let dlg = document.getElementById('tg-chat-picker');
	if (!dlg) {
		dlg = document.createElement('dialog');
		dlg.id = 'tg-chat-picker';
		dlg.innerHTML = `
		<form method="dialog" style="min-width:520px; font-family: system-ui, sans-serif;">
			<h3 style="margin:0 0 .5rem 0;"><?php echo L::config_social_telegram_select_chat; ?></h3>
			<p style="margin:.25rem 0 .5rem 0; color:#555;"><?php echo L::config_social_telegram_table_headers; ?></code></p>
			<select id="tg-chat-select" size="10" style="width:100%;"></select>
			<div style="display:flex; gap:.5rem; margin-top:.75rem; justify-content:flex-end; flex-wrap:wrap;">
			<button id="tg-chat-cancel" value="cancel" type="submit"><?php echo L::config_social_telegram_close; ?></button>
			<button id="tg-chat-choose" value="default"><?php echo L::config_social_telegram_accept; ?></button>
			<button id="tg-chat-test" type="button"><?php echo L::config_social_telegram_test_message_send; ?></button>
			</div>
			<p id="tg-chat-picked" style="margin:.5rem 0 0 0; color:#555;"></p>
		</form>`;
		document.body.appendChild(dlg);
	}

	// fill options
	const sel = dlg.querySelector('#tg-chat-select');
	sel.innerHTML = '';
	for (const c of chats) {
		const opt = document.createElement('option');
		const id  = String(c.id);
		const t   = c.type || '<?php echo L::config_social_telegram_unknown; ?>';
		const n   = c.name || '(<?php echo L::config_social_telegram_without_name; ?>)';
		opt.type	= t;
		opt.name	= n;
		opt.value	= id;
		opt.textContent = `${n} (${t}) — ${id}`;
		sel.appendChild(opt);
	}

	const chosenP = dlg.querySelector('#tg-chat-picked');
	dlg.querySelector('#tg-chat-choose').onclick = (e) => {
		e.preventDefault();
		const opt = sel.selectedOptions[0];
		if (!opt) return;
		const name			= opt.name;
		const type			= opt.type;
		const identifier	= name + " (" + type + ")";
		const id			= opt.value;
		if (conf_SOCIAL_TELEGRAM_CHAT_ID) {conf_SOCIAL_TELEGRAM_CHAT_ID.value = id;}
		if (conf_SOCIAL_TELEGRAM_CHAT_IDENTIFIER) {conf_SOCIAL_TELEGRAM_CHAT_IDENTIFIER.value = identifier;}
		if (TELEGRAM_CHAT_ID_PRESENTER) {TELEGRAM_CHAT_ID_PRESENTER.value = id + ": " + identifier;}
		chosenP.textContent = `<?php echo L::config_social_telegram_selected; ?>: ${id}`;
		dlg.close('ok');
	};

	// test messenger button
	dlg.querySelector('#tg-chat-test').onclick = async () => {
		const opt = sel.selectedOptions[0];
		if (!opt) { alert('<?php echo L::config_social_telegram_select_chat_first; ?>'); return; }
		const chatId = opt.value;
		try {
		const fd2 = new FormData();
		fd2.append('chat_id', chatId);
		fd2.append('text', '<?php echo L::config_social_telegram_test_message; ?>');
		const r = await fetch(`https://api.telegram.org/bot${encodeURIComponent(token)}/sendMessage`, {
			method: 'POST',
			body: fd2
		});
		const res2 = await r.json();
		if (res2.ok) {
			alert('<?php echo L::config_social_telegram_test_message_sent_to; ?> ' + chatId);
		} else {
			alert('<?php echo L::config_social_telegram_test_message_error; ?>: ' + JSON.stringify(res2));
		}
		} catch (e) {
		alert('<?php echo L::config_social_telegram_test_message_error; ?>: ' + e);
		}
	};

	if (typeof dlg.showModal === 'function') dlg.showModal();
	else dlg.style.display = 'block';
	}
</script>

<button type="button" onclick="openChatPicker()" id="get_telegram_chat_id">
	<?php echo L::config_social_telegram_button_select_chat; ?>
</button><br />
<label for="TELEGRAM_CHAT_ID_PRESENTER"><?php echo L::config_social_telegram_chat_id_label; ?></label><br />
<input type="hidden" id="conf_SOCIAL_TELEGRAM_CHAT_ID" name="conf_SOCIAL_TELEGRAM_CHAT_ID" value="<?php echo $config['conf_SOCIAL_TELEGRAM_CHAT_ID']; ?>">
<input type="hidden" id="conf_SOCIAL_TELEGRAM_CHAT_IDENTIFIER" name="conf_SOCIAL_TELEGRAM_CHAT_IDENTIFIER" value="<?php echo $config['conf_SOCIAL_TELEGRAM_CHAT_IDENTIFIER']; ?>">
<input type="text" id="TELEGRAM_CHAT_ID_PRESENTER" name="TELEGRAM_CHAT_ID_PRESENTER" size="20" value="<?php if (intval($config['conf_SOCIAL_TELEGRAM_CHAT_ID']) <> 0) {echo $config['conf_SOCIAL_TELEGRAM_CHAT_ID'].": ".$config['conf_SOCIAL_TELEGRAM_CHAT_IDENTIFIER'];} ?>" disabled>
