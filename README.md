<h1>Little Backup Box</h1>
Dmitri Popov has entrusted me with the further maintenance of Little Backup Box today. Thank you Dima for this great project. I very much hope you stay connected to the project!<br>
26.12.2021<br>
<h1>NEWS</h1>
<ul>
	<li>2021/12/21: Add language support for French, Spanisch and German: <b>Caution: The translations were carried out by machine and could cause unpleasant feelings in native speakers. Help is particularly welcome here. Technically, working on the language files is extremely easy. Would you help?</b></li>
	<li>2021/12/13: There is a [one-click-update-function](https://github.com/outdoorbits/little-backup-box/wiki/03.-Installation#update)<br></li>
	<li>2021/12/18: Select color-themes and background-images. Design is more colorful now to make orientation easier.</li>
</ul>
<br>
<img src="https://github.com/outdoorbits/little-backup-box/wiki/images/webui.png">
<h1>Keep in mind!</h1>
This fork is still under construction. Under the surface it is very different to its parent.<br>
<b>Please test your Installation carefully before productive use!</b><br>
Final tests offered minor bugs (fixed), it looks good for the moment! Tell me, if you find problems!<br>
<br>
These are the main differences:<br>
<ul>
<li>Mounting devices is managed by UUIDs. If a device disconnects*1, it can be automatically reconnected. The process becomes much more robust.</li>
<li>Some tools are added (check device, repair device)</li>
<li>Installation of components (comitup, mejiro) is automated</li>
<li>It has a full powered webserver now, ssl as standard</li>
<li>Setup is formular-based</li>
<li>Web interface and Samba-Server can be configured for password-protection</li>
<li>This fork is enabled to sync to your rsync-server</li>
</ul>
*1 Maybe because of power-failures sometimes the connection to a device is disrupted. After reconnecting, it gets a new drive-identifier (e.g. sda becomes sdb) while the drives UUID is still the same.<br>
<br>
... a lot of stuff! Please let me know about problems.<br>
<br>
As there is no manual available anymore, I started to edit the github-wiki <a href="https://github.com/outdoorbits/little-backup-box/wiki">https://github.com/outdoorbits/little-backup-box/wiki</a>. It's not really extensive yet, but maybe you can contribute?

# Little Backup Box

This fork is done because I need a solution for some special problems about xqd-cards. Please have a look at <a href="https://github.com/dmpop/little-backup-box">https://github.com/dmpop/little-backup-box</a>.

## Installation of this fork

1. Create a bootable SD card with the latest version of Raspberry Pi OS Lite for use with Little Backup Box.
2. Add an empty (text-)file "ssh" to the new boot-partition to enable ssh.
3. Do not change any more settings.
4. Make sure that your Raspberry Pi is connected to the internet.
5. Run the following command on the Raspberry Pi:<br>
`curl -sSL https://raw.githubusercontent.com/outdoorbits/little-backup-box/main/install-little-backup-box.sh | bash` <br>
or to save the error-messages during installation to disk: <br>
`curl -sSL https://raw.githubusercontent.com/outdoorbits/little-backup-box/main/install-little-backup-box.sh | bash  2> install-error.log` <br>

## Update
To update you can perform a webUI-based one-click update. Just follow the instructions at the [wiki](https://github.com/outdoorbits/little-backup-box/wiki/03.-Installation#update).

## Contribute

If you've found a bug or have a suggestion for improvement, open an issue in the [Issues](https://github.com/outdoorbits/little-backup-box/issues) section.

To add a new feature or fix issues yourself, follow the following steps.

1. Fork the project's repository.
2. Create a feature branch using the `git checkout -b new-feature` command.
3. Add your new feature or fix bugs and run the `git commit -am 'Add a new feature'` command to commit changes.
4. Push changes using the `git push origin new-feature` command.
5. Submit a pull request.

## Author
Author of this fork is Stefan Saam, founder is [Dmitri Popov](https://www.tokyoma.de/)

## Contributors
- Dmitri Popov
- Stefan Saam (major improvements)
- Kerry Staite (STL files)

## License

The [GNU General Public License version 3](http://www.gnu.org/licenses/gpl-3.0.en.html)
