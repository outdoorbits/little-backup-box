<h1>Little Backup Box</h1>
Dmitri Popov has entrusted me with the further maintenance of Little Backup Box today. Thank you Dima for this great project. I very much hope you stay connected to the project!<br>
26.12.2021<br>

<h1>NEWS</h1>
<ul>
	<li>2021/12-27: Add power-off after idle time. Configuration in &quot;Settings&quot;.</li>
	<li>2021/12/21: Add language support for French, Spanisch and German: <b>Caution: The translations were carried out by machine and could cause unpleasant feelings in native speakers. Help is particularly welcome here. Technically, working on the language files is extremely easy. Would you help?</b></li>
	<li>2021/12/13: There is a <a href="https://github.com/outdoorbits/little-backup-box/wiki/03.-Installation#update">one-click-update-function</a></li>
	<li>2021/12/18: Select color-themes and background-images. Design is more colorful now to make orientation easier.</li>
</ul>
<br>
<img src="https://github.com/outdoorbits/little-backup-box/wiki/images/webui.png" align="center">
<h1>Keep in mind!</h1>
Little Backup Box is still under construction.<br>
<b>Please test your Installation carefully before productive use!</b><br>
Final tests offered minor bugs (fixed), it looks good for the moment! Tell me, if you find problems!<br>
<br>
<h2>Some major developement-steps:</h2>
<ul>
	<li>Multiple cloud-services can be used as storage. Check for <b>rclone</b> at the settings.</li>
	<li>Power-off after idle time. Configuration in &quot;Settings&quot;.</li>
	<li>Mounting devices is managed by UUIDs. If a device disconnects*1, it can be automatically reconnected. The process becomes much more robust.</li>
	<li>Some tools are added (check device, repair device)</li>
	<li>Installation of components (comitup, mejiro) is automated</li>
	<li>It has a full powered webserver now, ssl as standard</li>
	<li>Setup is form-based</li>
	<li>Setup can be ex- and imported as zip-file.</li>
	<li>Web interface and Samba-Server can be configured for password-protection</li>
	<li>sync to your rsync-server is possible</li>
</ul>
*1 Maybe because of power-failures sometimes the connection to a device is disrupted. After reconnecting, it gets a new drive-identifier (e.g. sda becomes sdb) while the drives UUID is still the same.<br>
<br>
... a lot of stuff! Please let me know about problems.<br>
<br>
As there is no manual available anymore, I started to edit the github-wiki <a href="https://github.com/outdoorbits/little-backup-box/wiki">https://github.com/outdoorbits/little-backup-box/wiki</a>. It's not really extensive yet, but maybe you can contribute?

<h2>Installation</h2>
<ol>
	<li>Create a bootable SD card with the latest version of Raspberry Pi OS Lite (32 or 64 bit) for use with Little Backup Box.</li>
	<li>Add an empty (text-)file "ssh" to the new boot-partition to enable ssh.</li>
	<li>Do not change any more settings (language!).</li>
	<li>Make sure that your Raspberry Pi is connected to the internet.</li>
	<li>
		Run the following command on the Raspberry Pi:<br>
		<code>curl -sSL https://raw.githubusercontent.com/outdoorbits/little-backup-box/main/install-little-backup-box.sh | bash</code><br>
		or to save the error-messages during installation to disk: <br>
		<code>curl -sSL https://raw.githubusercontent.com/outdoorbits/little-backup-box/main/install-little-backup-box.sh | bash  2> install-error.log</code>
	</li>
</ol>
<h2>Update</h2>
To update you can perform a webUI-based one-click update. Just follow the instructions at the <a href="https://github.com/outdoorbits/little-backup-box/wiki/03.-Installation#update">wiki</a>.

<h2>Contribute</h2>
<ul>
	<li>If you've found a bug or have a suggestion for improvement, open an issue in the <a href="https://github.com/outdoorbits/little-backup-box/issues">Issues section</a>.</li>
	<li>If you could spend a bit of time and add a new language - great, just tell me, it's easy!</li>
	<li>To add a new feature or fix issues yourself, follow the following steps.</li>
</ul>

<ol>
	<li>Fork the project's repository.</li>
	<li>Create a feature branch using the <code>git checkout -b new-feature</code> command.</li>
	<li>Add your new feature or fix bugs and run the <code>git commit -am 'Add a new feature'</code> command to commit changes.</li>
	<li>Push changes using the <code>git push origin new-feature</code> command.</li>
	<li>Submit a pull request (in your fork at github.com).</li>
</ol>

<h2>Authors</h2>
Stefan Saam, founder is <a href="https://www.tokyoma.de/">Dmitri Popov</a>

<h2>Contributors</h2>
<ul>
	<li>Dmitri Popov</li>
	<li>Kerry Staite (STL files)</li>
</ul>

<h2>License</h2>

The <a href="http://www.gnu.org/licenses/gpl-3.0.en.html">GNU General Public License version 3</a>
