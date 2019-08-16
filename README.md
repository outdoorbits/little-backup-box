# Little Backup Box

A collection of scripts that transform a Raspberry Pi (or any single-board computer running a Debian-based Linux distribution) into an inexpensive, fully-automatic, pocketable photo backup device.

<img src="https://i.imgur.com/oHljRK8.jpg" alt="" width="800"/>

**IMPORTANT**
Little Backup Box is designed to work with USB sticks as backup media. Theoretically, it's possible to make Little Backup Box work with an external hard disk, but this setup is not officially supported.

## Little Backup Box features

- Back up the contents of a storage card to an external USB storage device. Little Backup Box supports practically any USB card reader, and, by extension, any card format.
- Transfer photos and RAW files directly from a camera to an external USB storage device. If your camera is supported by gPhoto2 software, it will work with Little Backup Box.
- Use multiple cards. Little Backup Box assigns a unique ID to each card and create a separate folder for each card on the backup storage device.
- Perform card backup fully automatically with no user interaction.
- Start specific backup operations manually using Little Backup Box's web UI.
- Stream the backed up photos to any DLNA-compatible client.
- Access the backed up content from other machines on the network.
- Synchronize the backed up content with multiple machines.

## Installation

1. Create a bootable SD card with the latest version of Raspbian Lite for use with Little Backup Box.
2. Make sure that your Raspberry Pi is connected to the internet.
3. Run the following command on the Raspberry Pi: `curl -sSL https://is.gd/littlebackupbox | bash`

Little Backup Box supports four backup modes:

- **Remote control** Provides a simple web interface to manually choose one of the two modes described above.
- **Card backup** Automatically backs up the contents of a storage card to an external storage device.
- **Camera backup** Transfers photos, raw files, and videos from the camera connected directly to the Raspberry Pi. The transferred files are saved in the */home/pi/BACKUP/[CAMERA MODEL]* directory on the system storage card. **Important** Make sure that the camera is set to the MTP USB connection mode.
- **Internal backup** Automatically backs up the contents of a storage card to the internal storage.

During the installation, choose the desired mode from the selection dialog.

<img src="img/installer.png" alt="" width="800"/>

When prompted, reboot the Raspberry Pi.

## Usage

The exact steps depend on the backup mode.

### Remote control mode

1. Point the browser to *http://127.0.0.1:8000* (replace *127.0.0.1* with the actual IP address of the Raspberry Pi).
2. Start the desired action by pressing the appropriate button.

<img src="img/rc.png" alt="Remote control interface" />

The **System info** link gives you access to basic system information, such as a list of storage devices and memory usage.

### Card backup mode

1. Boot the Raspberry Pi
2. Plug in a backup storage device
3. Insert a storage card into a card reader and plug it into the Raspberry Pi
4. Wait till the Raspberry Pi shuts down

**Note:** To differentiate between different storage cards, the backup script creates a datetime-based *.id* file in the root of each storage card. The name of the *.id* file is also used as the destination backup folder on the storage device.

### Camera backup mode

1. Boot the Raspberry Pi
2. Connect the camera to the Raspberry Pi
3. Turn the camera on
4. Wait till the Raspberry Pi shuts down

### Internal backup mode

1. Boot the Raspberry Pi
2. Insert a storage card into a card reader and plug it into the Raspberry Pi
3. Wait till the Raspberry Pi shuts down

## Problems, comments, ideas, updates?

To discuss Little Backup Box-related topics and get the latest updates, visit the [official forum at PIXLS.US](https://discuss.pixls.us/c/hardware/little-backup-box).

Please report bugs and issues in the [Issues](https://github.com/dmpop/little-backup-box/issues) section.

## Contribute

If you've found a bug or have a suggestion for improvement, open an issue in the [Issues](https://github.com/dmpop/little-backup-box/issues) section.

To add a new feature or fix issues yourself, follow the following steps.

1. Fork the project's repository repository
2. Create a feature branch using the `git checkout -b new-feature` command
3. Add your new feature or fix bugs and run the `git commit -am 'Add a new feature'` command to commit changes
4. Push changes using the `git push origin new-feature` command
5. Submit a pull request

## Author

[Dmitri Popov](https://www.tokyomade.photography/)

## License

The [GNU General Public License version 3](http://www.gnu.org/licenses/gpl-3.0.en.html)

## Linux Photography

The [Linux Photography](https://gumroad.com/l/linux-photography) book provides step-by-step instructions on building a Raspberry Pi-based photo backup device running Little Backup Box. Get your copy at [Google Play Store](https://play.google.com/store/books/details/Dmitri_Popov_Linux_Photography?id=cO70CwAAQBAJ) or [Gumroad](https://gumroad.com/l/linux-photography).

<img src="https://scribblesandsnaps.files.wordpress.com/2016/07/linux-photography-6.jpg" width="200"/>

By buying the book, you are supporting the Linux Backup Box project.
