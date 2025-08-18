<h2>Changelog</h2>
<ul>
	<li>2025-08-18: Uploading selected media to Telegram is now possible. In addition, images can now be not only rated but also commented on in View. These comments are also published in Telegram. This allows images to be shared quickly and conveniently in Telegram groups and channels.</li>
	<li>2025-02-18: The integrated FTP server can now be used as a backup source. This makes it possible to use the LBB as a bridge from a camera to (almost) any backup destination. Note: The user 'lbb' must be used for this.</li>
	<li>2024-12-14: Add status bar to display</li>
	<li>2021-11-16: To optimize automatic backups, thumbnails are now created during the secondary backup (to cloud storage) instead of between the primary (to local disk) and secondary backups. This approach maximizes computing and network efficiency, significantly reducing the overall backup time.</li>
	<li>2021-10-19: Add option to rename media files after backup based on the files creation date and time. Example: &quot;Image.jpg&quot; from 2024/10/19 12:42:14 will be renamed to &quot;2024-10-19_12-42-14_-_Image.jpg&quot;</li>
	<li>2024-09-09: In the settings for both default backups, specify separately whether the files should be copied or moved; Allow moving files from former target devices; bugfixes</li>
	<li>2024-09-09: Integrate comitup into web UI</li>
	<li>2024-09-09: Web UI: Change port for http from 8000 to 8080</li>
	<li>2024-07-01: View: Add slideshow function to single view mode</li>
	<li>2024-07-01: Speed up transfer to cloud by using rclone (use setup to switch back to rsync)</li>
	<li>2024-04-08: Add support for NVMe SSD storage on Raspberry Pi 5</li>
	<li>2024-03-04: Add USB auto source option. All connected devices (cameras and storages) will be backed up.</li>
	<li>2024-02-26: A minimum size of the partition to be used on the target device can be set in the configuration. This allows smaller partitions to be skipped as a destination.</li>
	<li>2024-01-25: Enable using of graphic environment</li>
	<li>2024-01-19: Workaround to enable display with Raspberry Pi 5</li>
	<li>2023-12-29: Workaround to enable comitup with bookworm</li>
	<li>2023/09/17: When backing up from cameras, all connected cameras are backed up. Please check you logs or your mail reports after backup!</li>
	<li>2023/09/16: View image viewer: Support for tif images added</li>
	<li>2023/09/16: When backing up from USB, all connected USB partitions (except those of the target drive) are backed up.</li>
	<li>2023/08/24: Little Backup Box core files are nearly totally rewritten to python. This is a major step and the new version might be not stable yet but final tests have been very successfully. In <a href="#installation"><b>installation instructions</b></a> you find how to get this version. Please test and report. Thank you!</li>
	<li>2023/05/15: There is a printable case now! Look at <a href="https://github.com/outdoorbits/case-for-little-backup-box">outdoorbits/case-for-little-backup-box</a></li>
	<li>2023/04/14: Introducing support for hardware buttons including a display-menu to control a wide range of lbb's functions. Read more in the <a href="https://github.com/outdoorbits/little-backup-box/wiki/02a.-Displaymenu">Wiki</a>.</li>
	<li>2023/04/01: Add support for Apple file systems HFS and HFS Plus. Supported file systems now are Ext2, Ext3, Ext4, FAT, vFAT, exFAT, NTFS, HFS and HFS Plus.</li>
	<li>2023/02/28: Add support for multiple displays, I2C and SPI</li>
	<li>2023/02/24: Add VPN support for WireGuard and OpenVPN</li>
	<li>2023/02/15: Remove miniDLNA due to security concerns</li>
	<li>2023/02/15: Settings: Use global password as WiFi-password in comitup-hotspot-mode</li>
	<li>2022/12/12: View got magnifying glass-function. This allows quick ratings with improved evidence.</li>
	<li>2022/12/03: Rating function supports writing of EXIF-data into media-files. This allows to use the ratings in further processing (e.g. in <a href="https://www.darktable.org/">darktable</a>).</li>
	<li>2022/11/19: Image-viewer "View" now can handle videos and audios!</li>
	<li>2022/11/14: Image-viewer "View" got a rating function. Images having one star only can be deleted.</li>
	<li>2022/11/08: Image-viewer mejiro is replaced by a integrated solution offering much faster options to find images.</li>
	<li>2022/10/14: Add f3probe to tools to test external storages integrity before use.</li>
	<li>2022/10/09: Rework of the progress-monitoring to save traffic and increase speed.</li>
	<li>2022/10/03: If checked in settings, thumbnails will be generated after backup.</li>
	<li>2022/10/03: If checked in settings, the LCD display will be turned off before shutdown.</li>
	<li>2022/10/01: The Wifi country code can now be set in the settings.</li>
	<li>2022/09/24: A disk formatting function has been added to the tools.</li>
	<li>2022/04/03: A secondary default backup mode has been added. This allows you to configure a automatically starting network backup after a local backup.</li>
	<li>2022/01/16: The <a href="https://github.com/outdoorbits/little-backup-box/wiki/02.-Hardware">Wiki</a> explains now, how to connect a LCD-display.</li>
	<li>2022/01/12: Camera-backup: Paths to backup can be configured for each device.</li>
	<li>2022/01/08: Add option to backup only specific directories from cameras or smartphones.</li>
	<li>2022/01/08: Uploads to the box can be done by the filemanager now.</li>
	<li>2021/12/31: Add language support for display-messages*1</li>
	<li>2021/12-27: Add power-off after idle time. Configuration in "Settings".</li>
	<li>2021/12/21: Add language support to the web-UI for French, Spanish and German*1</li>
	<li>2021/12/13: There is a <a href="https://github.com/outdoorbits/little-backup-box/wiki/03.-Installation#update">menu based update-function</a>.</li>
	<li>2021/12/18: Select color-themes and background-images. Design is more colorful now to make orientation easier.</li>
</ul>
