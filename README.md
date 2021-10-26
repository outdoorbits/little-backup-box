<h1>Keep in mind!</h1>
This fork is under heavy construction. Under the surface it is very diffrent to its parent.<br>
<b>Please test your Installation carefully before productive use!</b>
<br>
This are the main diffrences:<br>
<ul>
<li>Mounting devices is managed by UUIDs. If a device disconnects*1, it can be automatically reconnected.</li>
<li>Some tools are added (check device, repair device)</li>
<li>Installation of components (comitup, mejiro) is automated</li>
<li>This fork is enabled to sync to your rsync-server</li>
</ul>
*1 Maybe because of power-failures sometimes the connection to a device is disrupted. After reconnecting, it gets a new drive-identifier (e.g. sda becomes sdb) while the drives UUID is still the same.<br>
<br>
... a lot of stuff! Let me know about problems. Good point to start will be Dmitri Popovs manual, really worth to buy: <a href="https://dmpop.gumroad.com/l/little-backup-book">https://dmpop.gumroad.com/l/little-backup-book</a>

# Little Backup Box

This fork is done because I need a solution for some special problems about xqd-cards. Please have a look at <a href="https://github.com/dmpop/little-backup-box">https://github.com/dmpop/little-backup-box</a>.

## Installation of this fork

1. Create a bootable SD card with the latest version of Raspberry Pi OS Lite for use with Little Backup Box. KEEP standard-language settings en_GB.UTF-8.
2. Make sure that your Raspberry Pi is connected to the internet.
3. Run the following command on the Raspberry Pi: `curl -sSL https://raw.githubusercontent.com/stefans73/little-backup-box/main/install-little-backup-box.sh | bash`

## Contribute

If you've found a bug or have a suggestion for improvement, open an issue in the [Issues](https://github.com/stefans73/little-backup-box/issues) section.

To add a new feature or fix issues yourself, follow the following steps.

1. Fork the project's repository.
2. Create a feature branch using the `git checkout -b new-feature` command.
3. Add your new feature or fix bugs and run the `git commit -am 'Add a new feature'` command to commit changes.
4. Push changes using the `git push origin new-feature` command.
5. Submit a pull request.

## Author
Author of this fork is Stefan Saam
Founder is [Dmitri Popov](https://www.tokyoma.de/)

## Contributors
- Dmitri Popov
- Stefan Saam (major improvements)
- Kerry Staite (STL files)

## License

The [GNU General Public License version 3](http://www.gnu.org/licenses/gpl-3.0.en.html)
