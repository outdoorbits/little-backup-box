<?php class L {
const help = 'Hilfe';
const right_arrow = ' &rarr; ';
const log_logmonitor = 'Protokollmonitor';
const log_refresh_button = 'aktualisieren';
const log_delete_button = 'Logdatei löschen';
const mainmenue_main = 'Backup';
const mainmenue_sysinfo = 'System';
const mainmenue_config = 'Einstellungen';
const mainmenue_tools = 'Tools';
const mainmenue_filebrowser = 'Dateien';
const mainmenue_minidlna = 'MiniDLNA';
const mainmenue_mejiro = 'Mejiro';
const main_source_button = 'Quelle';
const main_external_button = 'ext. Speicher';
const main_internal_button = 'int. Speicher';
const main_camera_button = 'Kamera';
const main_ios_button = 'iOS';
const main_rsync_button = 'rsync-Server';
const main_reboot_button = 'Neustart';
const main_shutdown_button = 'Ausschalten';
const main_backup_backup = 'Backup von';
const main_backup_to = 'nach';
const main_backup_initiated = 'eingeleitet';
const main_cloudservice = 'Cloud-Dienst';
const main_reboot_m = 'Little Backup Box wird neu gestartet. Aktualisieren Sie diese Seite in wenigen Augenblicken.';
const main_shutdown_m = 'Little Backup Box wird ausgeschaltet. Sie können diese Seite schließen.';
const main_help_txt = '<p class = \'text-center\'> Bitte lies das <a href=\'https://github.com/outdoorbits/little-backup-box/wiki/05.-Operation\'> Wiki </a> </ p >';
const main_stopbackup_button = 'STOPP Backup';
const main_stopbackup_m = 'Backup wird angehalten';
const sysinfo_sysinfo = 'Systeminformationen';
const sysinfo_temp = 'Temperatur';
const sysinfo_cpuload = 'CPU-Last';
const sysinfo_devices = 'Geräte';
const sysinfo_diskspace = 'Festplattenplatz';
const sysinfo_camera = 'Kamera / Smartphone';
const sysinfo_conditions = 'Bedingungen';
const sysinfo_memory_ram = 'RAM belegt';
const sysinfo_memory_swap = 'SWAP belegt';
const config_config = 'Einstellungen';
const config_lang_section = 'Sprache / Language';
const config_lang_header = 'Sprache auswählen/Select language';
const config_lang_label = 'Language-selector will always be labeled in English too.';
const config_lang_browser_detect = 'Browser-Erkennung/Browser detect';
const config_backup_section = 'Backup';
const config_backup_header = 'Standard-Backup-Modus';
const config_backup_label = 'Wählen Sie den Standard-Backup-Modus:';
const config_backup_none = 'kein automatisches Backup';
const config_backup_storage_external = 'Quellspeicher zu externem Speicher';
const config_backup_storage_internal = 'Quellspeicher zum internen Speicher';
const config_backup_camera_external = 'Kamera auf externen Speicher';
const config_backup_camera_internal = 'Kamera zum internen Speicher';
const config_backup_ios_external = 'iOS zu externem Speicher';
const config_backup_ios_internal = 'iOS zum internen Speicher';
const config_backup_power_off_header = 'Ausschalten';
const config_backup_power_off_label = 'Ausschalten nach Backup';
const config_behavior_section = 'Verhalten';
const config_behavior_notify_header = 'E-Mails senden';
const config_behavior_notify_label = 'Versuchen Sie, E-Mails über aktuelle Links zu dieser Little Backup Box und über abgeschlossene Backups zu senden?';
const config_behavior_mail_html_header = 'Mails im HTML-Format versenden';
const config_behavior_mail_html_label = 'Wenn Mails aktiviert sind, sollen diese in HTML formatiert werden?';
const config_behavior_display_header = 'Anzeige';
const config_behavior_display_label = 'Ausgabe auf dem Display.';
const config_behavior_disp_ip_header = 'IP auf dem Display wiederholen';
const config_behavior_disp_ip_label = 'Wenn die Anzeige aktiviert ist, IP jede Minute ausdrucken.';
const config_behavior_loglevel_header = 'Log-Level';
const config_behavior_loglevel_text = 'Systemereignisse protokollieren (in Log Monitor)?<br>(Logfile:';
const config_behavior_loglevel_label = 'Wählen Sie das Log-Level:';
const config_view_section = 'Ansicht';
const config_view_theme_header = 'Farbschema';
const config_view_theme_label = 'Wählen Sie ein Farbthema aus:';
const config_view_theme_light = 'hell';
const config_view_theme_dark = 'dunkel';
const config_view_theme_sepia = 'Sepia';
const config_view_bg_image_header = 'Hintergrundbild';
const config_view_bg_image_label = 'Sie können ein Bild für Ihren Hintergrund auswählen. Bitte eigene Bilder speichern in';
const config_view_popup_header = 'Popup-Nachrichten';
const config_view_popup_label = 'Popup-Meldungen zulassen.';
const config_mail_section = 'Mail-Server-Konfiguration';
const config_mail_smtp_header = 'SMTP-Server';
const config_mail_smtp_label = 'Adresse des SMTP-Mailservers';
const config_mail_port_header = 'SMTP-Port';
const config_mail_port_label = 'Port des SMTP-Mailservers (Standard:';
const config_mail_user_header = 'Benutzername';
const config_mail_user_label = 'Benutzername für den Mailserver';
const config_mail_password_header = 'Passwort';
const config_mail_password_label = 'Passwort für den Mailserver';
const config_mail_recipient_header = 'Empfänger';
const config_mail_recipient_label = 'Mailadresse des Empfängers';
const config_rsync_section = 'rsync-Server-Konfiguration';
const config_rsync_server_header = 'rsync-Server';
const config_rsync_server_label = 'Adresse des rsync-Servers';
const config_rsync_port_header = 'rsync-Port';
const config_rsync_port_label = 'Port des rsync-Servers (Standard:';
const config_rsync_user_header = 'Benutzername';
const config_rsync_user_label = 'Benutzername für den rsync-Server';
const config_rsync_password_header = 'Passwort';
const config_rsync_password_label = 'Passwort für den rsync-Server';
const config_password_section = 'Globales Passwort';
const config_password_header = 'Neues globales Passwort';
const config_password_label1 = 'Das Passwort zum Schutz Ihrer Little-Backup-Box. Wird für das Webinterface, Filebrowser, ssh und Samba-Windows-Shares verwendet. Der Benutzername ist in allen Fällen lbb. Für Linux-Benutzer &quot;pi&quot; das Passwort ist auch gesetzt.';
const config_password_label2 = 'wiederholen:';
const config_password_remove_header = 'Passwort entfernen';
const config_password_remove_label = 'Aktivieren Sie dieses Kontrollkästchen, um den Passwortschutz zu entfernen. Passwörter für Linux-Benutzer (lbb, pi) werden nicht entfernt.';
const config_save_button = 'Speichern';
const config_cloud_section = 'Cloud-Dienste konfigurieren';
const config_cloud_header = 'rcloud GUI wird in einem neuen Fenster geöffnet.';
const config_username = 'Benutzername';
const config_password = 'Passwort';
const config_password_as_set_in = 'wie eingestellt in';
const config_cloud_rclone_gui = 'rclone GUI';
const config_update_section = 'Update';
const config_update_text = '<h3>Update Little Backup Box</h3><p>Achtung:<ul><li>Das Update startet direkt mit folgendem Link!</li><li>Das Update kann das System unbrauchbar machen.</li> <li>Das Update kann ein erhebliches Downloadvolumen und damit ggf. Kosten verursachen.</li><li>Das Update kann je nach Internetgeschwindigkeit mehrere Minuten oder länger dauern.</li></ul><br>Nur Führen Sie das Update durch, wenn Sie ...<ul><li>könnten ggf. auch eine Neuinstallation durchführen</li><li>eine stabile Internetverbindung haben</li><li>eine stabile Stromversorgung haben</li> </ul></p><a href=\'/update.php\'>Jetzt aktualisieren - keine weiteren Schritte vorher!</a>';
const config_save_settings_download_link_text = 'Einstellungen herunterladen';
const config_save_settings_download_text = 'Zip-Archiv mit Einstellungen herunterladen';
const config_save_settings_section = 'Download / Upload der Einstellungen';
const config_save_settings_upload_button = 'Einstellungen hochladen';
const config_save_settings_download_header = 'Herunterladen';
const config_save_settings_upload_header = 'Hochladen';
const config_save_settings_upload_label = 'Wählen Sie die Einstellungsdatei';
const config_alert_password_change_after_reboot = 'Globales Passwort
Änderungen werden nach dem Neustart wirksam';
const config_alert_password_characters_not_allowed = 'Das Passwort darf keine Anführungszeichen (& quot;) und Backslash (& bsol;) enthalten.';
const config_alert_password_not_identical = 'Wiederholtes Passwort ist nicht identisch.';
const config_alert_password_too_short = 'Das Passwort muss mindestens 5 Zeichen lang sein.';
const config_alert_settings_upload_not_zip = 'Die Datei, die Sie hochladen möchten, ist keine ZIP-Datei. Bitte versuche es erneut.';
const config_alert_settings_upload_problem = 'Beim Hochladen ist ein Problem aufgetreten. Bitte versuche es erneut.';
const config_alert_settings_upload_success = 'Ihre Einstellungsdatei wurde hochgeladen und diese Dateien wurden installiert:';
const config_message_settings_saved = 'config.cfg wurde erfolgreich geschrieben.';
const config_alert_password_global = 'Globales Passwort';
const config_alert_password_mail_header = 'Mail-Passwort';
const config_alert_password_rsync_header = 'rsync-';
const config_behavior_power_off_idle_time_header = 'Herunterfahren bei Inaktivität?';
const config_behavior_power_off_idle_time_label = 'Wählen Sie die Leerlaufzeit aus, nach der das System gestoppt werden soll.';
const config_behavior_power_off_idle_time_none = 'niemals';
const config_backup_target_basedir_cloud_header = 'Basisordner auf dem Cloud-Server (Ziel)';
const config_backup_target_basedir_cloud_label = 'Die Sicherung auf dem Cloud-Server wird unterhalb dieses Verzeichnisses durchgeführt.';
const config_rsync_module_header = 'rsync-Modul';
const config_rsync_module_label1 = 'Modulname auf dem rsync-Server, z.B. \'little-backup-box\' (Standard:';
const config_rsync_module_label2 = ').<br>Der Modulname ist serverseitig in rsyncd.conf definiert und in [eckige Klammern] gesetzt. Beachten Sie auch <a href=\'https://github.com/outdoorbits/little-backup-box/blob/main/readme_rsync.md\'>readme_rsync.md</a>.';
const config_backup_camera_folder_mask_header = 'Zu sichernde Verzeichnisse auf Smartphones und Kameras';
const config_backup_camera_folder_mask_label = 'Hier kann vorgegeben werden, welche Verzeichnisse von welchen Kameras oder Smartphones gesichert werden.<br>
Einträge hier haben das Formular: <br>
&quot;KAMERA:PFAD&quot; <br>
Den Namen einer angeschlossenen Kamera finden Sie auf der Seite &quot;System&quot; unter &quot;Kamera/Smartphone&quot;.<br>
Wenn eine Anweisung für alle Kameras gelten soll, wird &quot;*&quot; (ohne weitere Zusätze) als Joker für alle Geräte nutzbar.<br>
Der Pfad muss nicht vollständig angegeben werden. Es reicht aus, einen eindeutigen Teil davon zu verwenden. <br>
Beispiele: <br>
&quot;*:DCIM&quot; (alle Kameras: alle Ordner, die &quot;DCIM&quot; enthalten) <br>
&quot;*:DCIM;MyCamera:IMAGES&quot; (zusätzlich von der Kamera &quot;MyCamera&quot;: alle Ordner, die &quot;IMAGES&quot; enthalten)<br>
&quot;&quot; (Feld bleibt leer: Alle Ordner aller Kameras sichern.)<br>
<br>
Statt nach einem Muster suchen zu lassen, können auch absolute Pfade angegeben werden. Diese müssen immer mit &quot;!/&quot; beginnen:
&quot;MyCamera:!/store_00020002/DCIM&quot;<br>
Sobald ein absoluter Ordner für eine Kamera zutrifft, erfolgt für diese keine Suche nach einem Muster mehr. Dies spart Zeit. 
Weitere Informationen im <a href="https://github.com/outdoorbits/little-backup-box/wiki/04.-Setup">wiki</a>';
const config_behavior_log_sync_protokoll_header = 'Log-Sync-Protokoll';
const config_behavior_log_sync_protokoll_label = 'Sync-Details protokollieren?';
const config_behavior_loglevel_minimum = 'minimal';
const config_behavior_loglevel_medium = 'mittel';
const config_behavior_loglevel_maximum = 'maximal';
const tools_tools = 'Werkzeug';
const tools_mount_header = 'Gerät einbinden';
const tools_mount_b = 'Mount';
const tools_umount_b = 'Entfernen';
const tools_repair = 'Reparatur';
const tools_select_partition = 'Partition auswählen: ';
const tools_fsck_check_b = 'Überprüfen';
const tools_fsck_autorepair_b = 'Reparatur';
const tools_help = 'Hilfe';
const tools_help_text = '<h2> Reparatur </h2>
  <p class = \'text-center\'> <mark> Verwenden Sie es nicht, wenn Sie nicht wissen, was Sie tun. Es könnte Ihren Speicher beschädigen! </mark> </p>
  <h3> Verfahren: </h3>
  <ol>
  <li> Überprüfen Sie Ihre Stromversorgung. Eine Unterbrechung der Stromversorgung kann den Speicher irreparabel beschädigen. </li>
  <li> Stellen Sie sicher, dass kein Backup-Auftrag ausgeführt wird. </li>
  <li> Klicken Sie auf keine Schaltflächen, bis alle Prüf- und Reparaturvorgänge abgeschlossen sind. Starten Sie keine Prozesse! </li>
  <li> Führen Sie zuerst die Dateisystemprüfung durch. </li>
  <li> Wenn Sie Fehler im Protokoll sehen, führen Sie die Dateisystemreparatur durch. </li>
  <li> Wenn der Reparaturvorgang abgeschlossen ist, schalten Sie den Computer aus und trennen Sie die Speichergeräte vor dem Booten. </li>
  </ol>';
const tools_fsck_check_m = 'Prüfung eingeleitet';
const tools_fsck_autorepair_m = 'Reparatur eingeleitet';
const update_header = 'Little Backup Box Updater';
const update_warning = 'NICHT STOPPEN oder nachladen während des Updates!';
const update_return_link = 'Zurück zur Little Backup Box - NICHT KLICKEN, BEVOR DAS UPDATE ABGESCHLOSSEN IST!';
const minutes_long = 'Minuten';
const box_backup_ext_storage_ok = 'Ext. Speicher o.k.';
const box_backup_insert_source_1 = 'Bereit';
const box_backup_insert_storage_1 = 'Bereit';
const box_backup_insert_source_2 = 'Quelle verbinden';
const box_backup_insert_storage_2 = 'Speicher verbinden';
const box_backup_int_storage_ok = 'Int. Speicher o.k.';
const box_backup_invalid_mode_combination_1 = 'Ungültige';
const box_backup_invalid_mode_combination_2 = 'Modus-';
const box_backup_invalid_mode_combination_3 = 'Kombination';
const box_backup_no_valid_destination_mode_1 = 'Kein gültiger';
const box_backup_no_valid_destination_mode_2 = 'Ziel-';
const box_backup_no_valid_destination_mode_3 = 'Modus definiert';
const box_backup_no_valid_source_mode_1 = 'Kein gültiger';
const box_backup_no_valid_source_mode_2 = 'Quell-';
const box_backup_no_valid_source_mode_3 = 'Modus definiert';
const box_backup_storage_free = 'frei';
const box_backup_storage_size = 'Größe';
const box_backup_storage_used = 'genutzt';
const box_backup_waiting_for_cloud_1 = 'Bereit';
const box_backup_waiting_for_cloud_2 = 'Warten auf Cloud';
const box_backup_camera_ok = 'Kamera o.k.';
const box_backup_complete = 'Sicherung komplett';
const box_backup_connect_camera_1 = 'Bereit';
const box_backup_connect_camera_2 = 'Kamera verbinden';
const box_backup_connect_ios_1 = 'Bereit';
const box_backup_connect_ios_2 = 'iOS-Gerät';
const box_backup_connect_ios_3 = 'verbinden';
const box_backup_error_cooling_1 = 'Fehler!';
const box_backup_error_cooling_2 = 'Kühlung';
const box_backup_error_cooling_3 = 'Verdacht auf';
const box_backup_error_cooling_4 = 'Überhitzung';
const box_backup_files_missing = 'Dateien fehlen!';
const box_backup_lost_device = 'Fehler. Gerät verloren!';
const box_backup_no_ios_waiting_1 = 'Kein iOS-Gerät';
const box_backup_no_ios_waiting_2 = 'warte';
const box_backup_of = 'von';
const box_backup_source_ok = 'Quelle o.k.';
const box_backup_try_backup = 'Backup Versuch';
const box_backup_working = 'arbeite';
const box_backup_status = 'Sicherungsstatus';
const box_backup_mail_backup = 'Backup';
const box_backup_mail_backup_type = 'Typ';
const box_backup_mail_log = 'Backup-Log';
const box_backup_mail_to = 'nach';
const box_backup_mail_tries_needed = 'Versuch(e) erforderlich';
const box_backup_try = 'Versuch';
const box_backup_checking_old_files = 'abgleichen';
const box_backup_camera_scanning_folders = 'Ordner scannen ...';
const box_cronip_offline = 'offline';
const box_cronip_online = 'online';
const box_cronip_mail_desription_http = 'http (unsicher)';
const box_cronip_mail_desription_https = 'https (sicher, Zertifikat kann nicht automatisch verifiziert werden, bitte bestätigen)';
const box_cronip_mail_info = 'Little Backup Box Info';
const box_cronip_mail_main = 'web UI';
const box_cronip_mail_open_samba = 'Im Dateimanager öffnen (Samba / Windows)';
const box_poweroff_do_not_unplug = 'Nicht ausstecken';
const box_poweroff_power_down_via_gui_1 = 'Ausschalten über';
const box_poweroff_power_down_via_gui_2 = 'web UI';
const box_poweroff_poweroff = 'Herunterfahren';
const box_poweroff_rebooting = 'Neustart';
const box_poweroff_while_act_led_on_1 = 'so lange die grüne';
const box_poweroff_while_act_led_on_2 = 'LED leuchtet. Bye!';
const box_poweroff_idle_time_reached = 'Leerlaufzeit erreicht';
const seconds_short = 's';
const sysconditions_arm_frq_capped_cur = 'Die ARM-Frequenz ist derzeit begrenzt.';
const sysconditions_arm_frq_capped_prev = 'Die ARM-Frequenz wurde zuvor begrenzt.';
const sysconditions_cpu_throttled_cur = 'CPU ist derzeit gedrosselt.';
const sysconditions_cpu_throttled_prev = 'CPU wurde zuvor gedrosselt.';
const sysconditions_normal = 'Es wurden keine anormalen Bedingungen festgestellt.';
const sysconditions_temperature_limit_cur = 'Aktuell am weichen Temperaturlimit.';
const sysconditions_temperature_limit_prev = 'Zuvor an der weichen Temperaturgrenze.';
const sysconditions_under_voltage_cur = 'Aktuell ist die Spannung zu niedrig.';
const sysconditions_under_voltage_prev = 'Zuvor war die Spannung zu niedrig.';
public static function __callStatic($string, $args) {
    return vsprintf(constant("self::" . $string), $args);
}
}
function L($string, $args=NULL) {
    $return = constant("L::".$string);
    return $args ? vsprintf($return,$args) : $return;
}