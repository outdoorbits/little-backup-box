<h1>Little Backup Box</h1>
<h2>About</h2>
Depending on your needs and options, little-backup-box can be operated in a simple housing, with a display (possibly with buttons for the display menu) or even with a touchscreen. <br />
Thanks to the web interface, functionality is fully guaranteed in any case.<br />
<br />
<img src="https://raw.githubusercontent.com/outdoorbits/media/main/little-backup-box/lbb-orange.jpg"  align="left" title="Little Backup Box in its 3D printed case"><BR CLEAR="all">
Little Backup Box in its <a href="https://github.com/outdoorbits/case-for-little-backup-box">3D printed case</a><br />
<br />
<a href="https://youtu.be/qAEebY8UlRc">
<img src="https://raw.githubusercontent.com/outdoorbits/media/main/little-backup-box/youtube-lbb-desktop-variant.png"  align="left" title="Little Backup Box in a touchscreen housing"><BR CLEAR="all">
Little Backup Box in a touchscreen housing - watch Dave's video on YouTube</a><br />
<br />
<a href="https://github.com/outdoorbits/case-for-little-backup-box/tree/main/Raspberry_Pi_zero_2W">
<img src="https://github.com/outdoorbits/case-for-little-backup-box/blob/main/Raspberry_Pi_zero_2W/images/zero2w_icon.jpg"  align="left" title="Housing for Raspberry Pi zero 2W"><BR CLEAR="all">
Little Backup Box in a tiny protected housing for Raspberry Pi zero 2W</a><br />
<br />
This software turns a single-board computer into a versatile, pocket-sized backup solution.<br />
Especially for digital photography, this is the solution for backing up images and media files on mass storage devices when traveling or at events.<br />
Backups can be made directly from many cameras and smartphones. Of course, USB storages (card reader) can also serve as a source.<br />
Your valuable data can be backed up on cloud storage from numerous providers - even with VPN protection if necessary.<br />
Media content can be viewed and rated for the subsequent process.<br />
<br />
<br />
<table>
<tr>
<td>
<img src="https://raw.githubusercontent.com/outdoorbits/media/main/little-backup-box/flag_us.png" width="50px" align="center">
</td>
<td>
<img src="https://raw.githubusercontent.com/outdoorbits/media/main/little-backup-box/flag_france.png" width="50px" align="center">
</td>
<td>
<img src="https://raw.githubusercontent.com/outdoorbits/media/main/little-backup-box/flag_spain.png" width="50px" align="center">
</td>
<td>
<img src="https://raw.githubusercontent.com/outdoorbits/media/main/little-backup-box/flag_germany.png" width="50px" align="center">
</td>
</tr>
</table>
These languages are already available. Do you want to maintain or create a translation?<br />
<br />
<a href="#installation"><b>Jump to installation instructions</b></a><br />
<br />
<a href="changelog.md">Changelog</a><br />
<a href="gallery.md">Have a look at the gallery</a><br />
<a href="resources.md">Web resources</a><br />
<br />
<h2>Features</h2>
Some of the features of the web user interface are
<ul>
	<li><b>automate backup from</b>
		<ul>
			<li>usb storage</li>
			<li>camera</li>
			<li>internal storage</li>
			<li>NVMe SSD storage</li>
			<li>cloud storage</li>
		</ul>
		<b>to</b>
		<ul>
			<li>usb storage</li>
			<li>internal storage</li>
			<li>NVMe SSD storage</li>
			<li>rsync server</li>
			<li>cloud storage</li>
		</ul>
	</li>
	<li><b>Tools for memory management</b>
		<ul>
			<li>manual mount local and cloud stoarges</li>
			<li>check disk integrity</li>
			<li>format disks</li>
			<li>check USB device for authenticity</li>
			<li>cloud storage</li>
		</ul>
	</li>
	<li><b>Email information about the backup success</b></li>
	<li><b>Preview and rate images</b></li>
	<li><b>Manage files by file manager</b></li>
	<li><b>Convenient configuration via web interface</b></li>
</ul>
It's your swiss army knife for reliably performing, checking and managing backups on the go.

<h2>Development status</h2>
Little Backup Box is still under construction.<br />
<b>Please test your Installation carefully before productive use!</b><br />
Little Backup Box has undergone massive development over the past few months. At the moment, no further major innovations are planned. Instead, the focus should now be on finding and correcting any errors and making the system even more reliable.<br />
Hence the request: Test, test, test!<br />
Please give feedback on problems or translation errors but also on the successful use, if possible with details of your equipment.<br />
<br />
<h2>Please mind the wiki!</h2>
As there is no manual available anymore, the github-wiki should replace it one day: <a href="https://github.com/outdoorbits/little-backup-box/wiki">https://github.com/outdoorbits/little-backup-box/wiki</a>. It's not particularly detailed yet, but it can certainly help with the most common questions.

<h2 id="installation">Installation</h2>
<ol>
<li>Create a bootable SD card with <b>Raspberry Pi OS (bookworm or bullseye)</b> for use with Little Backup Box. If you don't plan to use a graphic environment on a regular monitor, you are recommended to use the lite version.'<br />
An easy way is to use <a href="https://www.raspberrypi.com/software/">Raspberry Pi Imager</a>. Please select "Raspberry Pi OS (other)" > "Raspberry Pi OS Lite (32-bit)" or "Raspberry Pi OS Lite (64-bit)".<br />
In Raspberry Pi Imager you can define options for your image. Shift+Ctrl+x opens the options-dialog. Here you can ...
<ul>
<li>enable ssh</li>
<li>change the password for user pi (standard password is "raspberry")</li>
<li>define wifi settings</li>
</ul>
</li>
<li>If you couldn't activate ssh in the previous step, just add an empty (text-)file "ssh" to the new boot-partition to enable ssh.</li>
<li>Do not change any more settings (language!).</li>
<li>Make sure that your Raspberry Pi is connected to the internet.</li>
<li>login to your Raspberry Pi. If you are using ssh, there is a report about problems using WINSCP. While WINSCP itself uses putty, it's reported to work with native putty.</li>
<li>
Run the following command on the Raspberry Pi:<br />
<code>branch='main'; curl -sSL https://raw.githubusercontent.com/outdoorbits/little-backup-box/${branch}/install-little-backup-box.sh | bash -s -- ${branch} 2> install-error.log</code><br />
<br />
Errors are reported into ./install-error.log.
</li>
<li>
You will be asked for the default backup mode.<br />
This is the action started automatically after boot.
</li>
<li>
You will be asked if you want to install <a href="https://davesteele.github.io/comitup/">comitup</a>.<br />
The comitup service establishes wifi connectivity for a headless Linux system, using wifi as the only access mechanism to the system. Access the box by its own wifi hotspot or connect it to a local wifi access point.
</li>
<li>
<b>Don't worry:</b> Please note this in case you installed via wifi/ssh and decided to install comitup. If your installation-screen scrolls down and seems to freeze at something like <i>Setting up python3-networkmanager (2.2-1) ...</i>, this doesn't mean anything went wrong. Comitup is installed at the end of the process and when it's coming up, it changes your wifi-network. Your ssh-session is broken and you will not get any more information. In the background the installation finishes and a few seconds later the pi reboots. What you can't see anymore is a short information, how to access Little Backup Box. In your setting, this would be the information you need:<br />
<br />
******************************************************************************************<br />
*** How to proceed:<br />
***<br />
*** Your raspberrys wifi-configuration has been removed by comitup now. Because comitup has no<br />
*** access-data for your wifi yet, it can't connect to it and will start as a wifi-hotspot:<br />
*** ssid=little-backup-box-nnnn (nnnn is a random number).<br />
***<br />
*** Please connect your mobile or notebook to this wifi.<br />
***<br />
*** There are two options now:<br />
***<br />
*** 1. If you want to configure comitup now, navigate to <b>http</b>://10.41.0.1 (http, not https!)<br />
*** and connect your lbb to your wifi.<br />
*** Connect your mobile or notbook to the same wifi. If you use a display on your lbb, it will<br />
*** tell you its new IP. Connect to lbb:<br />
*** <b>https</b>://IP.IN.YOUR.WIFI (secure, certificate can't be verified automatically, please confirm it)<br />
*** <b>http</b>://IP.IN.YOUR.WIFI:8000 (insecure)<br />
***<br />
*** 2. Alternatively can reach the web UI of Little Backup Box when you are connected to its<br />
*** hotspot as follows:<br />
***<br />
*** <b>https</b>://10.41.0.1 (secure, certificate can't be verified automatically, please confirm it)<br />
*** <b>http</b>://10.41.0.1:8000 (insecure)<br />
***<br />
*** Please use the settings of the web UI to optimally adapt the Little Backup Box for you.<br />
***<br />
*** If you have further questions, please check the wiki first:<br />
*** https://github.com/outdoorbits/little-backup-box/wiki <br />
***<br />
*** We are always happy to receive your feedback!<br />
******************************************************************************************<br />
<br />
</li>
</ol>

<h2>Start</h2>
If everything went well, it's done! Open a browser and enter https://IP.OF.YOUR.BOX or http://IP.OF.YOUR.BOX:8000<br />Have fun!

<h2 id="update">Update</h2>
<ul>
<li>Updates can be carried out quickly and easily via the WebUI. Just follow the instructions in the <a href="https://github.com/outdoorbits/little-backup-box/wiki/03.-Installation#update">wiki</a>.</li>
<li>Alternatively just login via ssh and execute the installation-command from above. It will run in update-mode.</li>
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
Stefan Saam, founder is <a href="https://www.tokyoma.de/">Dmitri Popov</a>. He has entrusted me with the further maintenance of Little Backup Box. Thank you Dima for this great project.

<h2>Contributors</h2>
<ul>
	<li>Dmitri Popov</li>
	<li>Kerry Staite (STL files)</li>
	<li><a href="https://github.com/munecito">munecito</a> (corrections of the Spanish translation)</li>
</ul>

<h2>License</h2>

The <a href="http://www.gnu.org/licenses/gpl-3.0.en.html">GNU General Public License version 3</a>
