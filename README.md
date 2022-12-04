<img src="https://raw.github.com/outdoorbits/little-backup-box/main/img/lbb_display.jpg" align="right" width="320" height="213">

<h1>Little Backup Box</h1>

<h2>About</h2>
This software turns a single-board computer into a versatile, pocket-sized backup solution.<br>
Especially for digital photography, this is the solution for backing up images and media files on mass storage devices when traveling or at events.<br>
Media content can be viewed and rated for the subsequent process.

<h2>NEWS</h2>
<ul>
	<li>2022/12/03: Rating function supports writing of EXIF-data into media-files. This allows to use the ratings in futher processing (e.g. in <a href="https://www.darktable.org/">darktable</a>).</li>
	<li>2022/11/19: Image-viewer &quot;View&quot; now can handle videos and audios! <b>IMPORTANT: ALL THUMBNAILS HAVE TO BE CREATED AGAIN AFTER THIS UPDATE! PLEASE DELETE ALL TIMS-FOLDERS ON YOUR STORAGE.</b></li>
	<li>2022/11/14: Image-viewer &quot;View&quot; got a rating function. Images having one star only can be deleted.</li>
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
	<li>2021/12-27: Add power-off after idle time. Configuration in &quot;Settings&quot;.</li>
	<li>2021/12/21: Add language support to the web-UI for French, Spanisch and German*1</li>
	<li>2021/12/13: There is a <a href="https://github.com/outdoorbits/little-backup-box/wiki/03.-Installation#update">menue based update-function</a>.</li>
	<li>2021/12/18: Select color-themes and background-images. Design is more colorful now to make orientation easier.</li>
</ul>

<figure>
	<table align="right"><tr><td>
		<img src="https://raw.github.com/outdoorbits/little-backup-box/main/img/camera_backup.gif" align="center">
	</td></tr><tr><td>
		<figcaption>The display while a backup<br>from camera to usb and<br>from usb to rsync-server.</figcaption>
	</td></tr></table>
</figure>

<h3>Some major developement-steps:</h3>
<ul>
	<li>Image-viewer mejiro is replaced by a integrated solution offering much faster options to find images.</li>
	<li>Multiple cloud-services can be used as storage. Check for <b>rclone</b> at the settings.</li>
	<li>Mounting devices is managed by UUIDs. If a device disconnects*2, it can be automatically reconnected. The backup-process becomes much more robust.</li>
	<li>Some tools are added (check device, repair device)</li>
	<li>Installation of components (comitup, mejiro) is automated</li>
	<li>It has a full powered webserver now, ssl as standard</li>
	<li>Setup is form-based</li>
	<li>Setup can be ex- and imported as zip-file.</li>
	<li>Web interface and Samba-Server can be configured for password-protection</li>
	<li>sync to your rsync-server is possible</li>
</ul>
<br>
... a lot of stuff! Please let me know about problems.<br>
<br>
*1) Caution: The translations were carried out by machine and could cause unpleasant feelings in native speakers. Help is particularly welcome here. Technically, working on the language files is extremely easy. Would you help?<br>
*2) Maybe because of power-failures sometimes the connection to a device is disrupted. After reconnecting, it gets a new drive-identifier (e.g. sda becomes sdb) while the drives UUID is still the same.<br>
<br>
<figure align="center">
	<img src="https://raw.github.com/outdoorbits/little-backup-box/main/img/webui.png" align="center" width="90%">
	<figcaption>The web UI</figcaption>
</figure>
<br>
<figure>
	<img src="https://raw.github.com/outdoorbits/little-backup-box/main/img/webui-smartphone.png" align="center">
	<figcaption>The web UI on a smartphone. Full function in pocket-size.</figcaption>
</figure>

<h2>Developement status</h2>
Little Backup Box is still under construction.<br>
<b>Please test your Installation carefully before productive use!</b><br>
Final tests offered minor bugs (fixed), it looks good for the moment!<br>
Little Backup Box has undergone massive development over the past few months. At the moment, no further major innovations are planned. Instead, the focus should now be on finding and correcting any errors and making the system even more reliable.<br>
Hence the request: Test, test, test!<br>
Please give feedback on problems or translation errors but also on the successful use, if possible with details of your equipment.<br>
<br>
<h2>Please mind the wiki!</h2>
As there is no manual available anymore, the github-wiki should replace it one day: <a href="https://github.com/outdoorbits/little-backup-box/wiki">https://github.com/outdoorbits/little-backup-box/wiki</a>. It's not particularly detailed yet, but it can certainly help with the most common questions.

<h2>Installation</h2>
<ol>
	<li>Create a bootable SD card with the latest version of Raspberry Pi OS Lite (32 or 64 bit) for use with Little Backup Box.<br>
		An easy way is to use <a href="https://www.raspberrypi.com/software/">Raspberry Pi Imager</a>. Please select &quot;Raspberry Pi OS (other)&quot; &gt; &quot;Raspberry Pi OS Lite (32-bit)&quot; or &quot;Raspberry Pi OS Lite (64-bit)&quot;. If you need, you can enable ssh by Raspberry Pi Imager: Shift+Ctrl+x opens an options-dialog, where you can activate ssh. Write ...</li>
	<li>If you couldn't activate ssh in the previous step, just add an empty (text-)file "ssh" to the new boot-partition to enable ssh.</li>
	<li>Do not change any more settings (language!).</li>
	<li>Make sure that your Raspberry Pi is connected to the internet.</li>
	<li>
		Run the following command on the Raspberry Pi:<br>
		<code>curl -sSL https://raw.githubusercontent.com/outdoorbits/little-backup-box/main/install-little-backup-box.sh | bash</code><br>
		or to save the error-messages during installation to disk: <br>
		<code>curl -sSL https://raw.githubusercontent.com/outdoorbits/little-backup-box/main/install-little-backup-box.sh | bash  2> install-error.log</code>
	</li>
	<li>
		You will be asked if you want to install <a href="https://davesteele.github.io/comitup/">comitup</a>.<br>
		The comitup service establishes wifi connectivity for a headless Linux system, using wifi as the only access mechanism to the system. Access the box by its own wifi hotspot or connect it to a local wifi access point.
	</li>
	<li>
		<b>Don't worry:</b> Please note this in case you installed via wlan/ssh and decided to install comitup. If your installation-screen scrolls down and seems to freeze at something like <i>Setting up python3-networkmanager (2.2-1) ...</i>, this doesn't mean anything went wrong. Comitup is installed at the end of the process and when it's comming up, it changes your wlan-network. Your ssh-session is broken and you will not get any more information. In the background the installation finishes and a few seconds later the pi reboots. What you can't see anymore is a short information, how to access Little Backup Box. In your setting, this would be the information you need:<br>
		<br>	&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;<br>
		&ast;&ast;&ast; How to proceed:<br>
		&ast;&ast;&ast;<br>
		&ast;&ast;&ast; Your raspberrys wlan-configuration has been removed by comitup now. Because comitup has no<br>
		&ast;&ast;&ast; access-data for your wlan yet, it can't connect to it and will start as a wlan-hotspot:<br>
		&ast;&ast;&ast; ssid=little-backup-box-nnnn (nnnn is a random number).<br>
		&ast;&ast;&ast;<br>
		&ast;&ast;&ast; Please connect your mobile or your notebook to this wlan.<br>
		&ast;&ast;&ast; If you want to configure comitup now, navigate to <b>http</b>://10.41.0.1 (http, not https!)<br>
		&ast;&ast;&ast;<br>
		&ast;&ast;&ast; Alternatively can reach the web UI of Little Backup Box when you are connected to its<br>
		&ast;&ast;&ast; hotspot as follows:<br>
		&ast;&ast;&ast;<br>
		&ast;&ast;&ast; <b>https</b>://10.41.0.1 (secure, certificate can't be verified automatically, please confirm it)<br>
		&ast;&ast;&ast; <b>http</b>://10.41.0.1:8000 (insecure)<br>
		&ast;&ast;&ast;<br>
		&ast;&ast;&ast; Please use the settings of the web UI to optimally adapt the Little Backup Box for you.<br>
		&ast;&ast;&ast;<br>
		&ast;&ast;&ast; If you have further questions, please check the wiki first:<br>
		&ast;&ast;&ast; https://github.com/outdoorbits/little-backup-box/wiki <br>
		&ast;&ast;&ast;<br>
		&ast;&ast;&ast; We are always happy to receive your feedback!<br>
		&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;&ast;<br>
		... using comitup, you now have to connect your computer to the wlan <i>little-backup-box-nnnn</i> to access the new IP 10.41.0.1.
		<br>
	</li>
</ol>
<figure align="center">
	<img src="https://raw.github.com/outdoorbits/little-backup-box/main/img/webui-view.png" align="center" width="90%">
	<figcaption>Image-viewer View</figcaption>
</figure>
<h2>Start</h2>
If everything went well, it's done! Open a browser and enter https://IP.OF.YOUR.BOX or http://IP.OF.YOUR.BOX:8000<br>Have fun!
<h2>Update</h2>
<ul>
	<li>Updates can be carried out quickly and easily via the WebUI. Just follow the instructions in the <a href="https://github.com/outdoorbits/little-backup-box/wiki/03.-Installation#update">wiki</a>.</li>
	<li>Alternatively just login via ssh an execute the installation-command from above. It will run in update-mode.</li>
</ul>


<h2>Contribute</h2>
<ul>
	<li>If you've found a bug or have suggestions for improvements, open an issue in the <a href="https://github.com/outdoorbits/little-backup-box/issues">Issues section</a>.</li>
	<li>If you could spend a bit of time and add a new language - great, just tell me, it's easy!</li>
	<li>To add new features or fix issues yourself, follow the following steps.</li>
</ul>

<ol>
	<li>Open an issue to discuss your idea.</li>
	<li>Fork the project's repository.</li>
	<li>Create a feature branch using the <code>git checkout -b new-feature</code> command.</li>
	<li>Add your new feature or fix bugs and run the <code>git commit -am 'Add a new feature'</code> command to commit changes.</li>
	<li>Push changes using the <code>git push origin new-feature</code> command.</li>
	<li>Submit a pull request (in your fork at github.com).</li>
</ol>

<h2>Authors</h2>
Stefan Saam, founder is <a href="https://www.tokyoma.de/">Dmitri Popov</a>. He has entrusted me with the further maintenance of Little Backup Box. Thank you Dima for this great project.<br>

<h2>Contributors</h2>
<ul>
	<li>Dmitri Popov</li>
	<li>Kerry Staite (STL files)</li>
	<li><a href="https://github.com/munecito">munecito</a> (corrections of the Spanish translation)</li>
</ul>

<h2>License</h2>

The <a href="http://www.gnu.org/licenses/gpl-3.0.en.html">GNU General Public License version 3</a>
