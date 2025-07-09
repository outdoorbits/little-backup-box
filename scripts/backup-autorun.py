#!/usr/bin/env python

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

# started by crontab @reboot

import os
import subprocess
import shutil
import threading

import backup
import lib_common
import lib_cron_ip
import lib_display
import lib_git
import lib_language
import lib_proftpd
import lib_setup
import lib_storage

# import lib_debug
# xx	= lib_debug.debug()

class backup_autorun(object):
	def __init__(self):
		self.WORKING_DIR	= os.path.dirname(__file__)

		self.__setup								= lib_setup.setup(rewrite_configfile=True)
		self.__display								= lib_display.display()
		self.__lan									= lib_language.language()

		self.__const_WEB_ROOT_LBB					= self.__setup.get_val('const_WEB_ROOT_LBB')
		self.__const_MEDIA_DIR						= self.__setup.get_val('const_MEDIA_DIR')
		self.__const_RCLONE_CONFIG_FILE				= self.__setup.get_val('const_RCLONE_CONFIG_FILE')
		self.__const_BUTTONS_PRIVATE_CONFIG_FILE	= self.__setup.get_val('const_BUTTONS_PRIVATE_CONFIG_FILE')
		self.__const_VPN_DIR_OpenVPN				= self.__setup.get_val('const_VPN_DIR_OpenVPN')
		self.__const_VPN_FILENAME_OpenVPN			= self.__setup.get_val('const_VPN_FILENAME_OpenVPN')
		self.__const_VPN_DIR_WireGuard				= self.__setup.get_val('const_VPN_DIR_WireGuard')
		self.__const_VPN_FILENAME_WireGuard			= self.__setup.get_val('const_VPN_FILENAME_WireGuard')

		self.conf_MAIL_TIMEOUT_SEC					= self.__setup.get_val('conf_MAIL_TIMEOUT_SEC')

		self.__mail_threads_started					= []

	def run(self):
		self.__cleanup_at_boot()
		self.__display_hello()

		# ip info
		ip_info	= lib_cron_ip.ip_info()

		ip_info.display_ip()

		# ip_info.mail_ip() starts as thread
		self.__mail_threads_started.append(ip_info.mail_ip())

		self.__default_backup()

		# check for updates
		gitObj	= lib_git.git()
		git_thread	= threading.Thread(target=gitObj.UpdateSetup, args=())
		git_thread.start()
		git_thread.join(timeout=15)

		# Wait for running threads (mails to send)
		lib_common.join_mail_threads(self.__display, self.__lan, self.__mail_threads_started, self.conf_MAIL_TIMEOUT_SEC)

	def __cleanup_at_boot(self):
		# remove IP_SENT_MARKERFILE
		const_IP_SENT_MARKERFILE	= self.__setup.get_val('const_IP_SENT_MARKERFILE')
		try:
			os.remove(const_IP_SENT_MARKERFILE)
		except:
			pass

		# remove display content files
		const_DISPLAY_CONTENT_PATH	= self.__setup.get_val('const_DISPLAY_CONTENT_PATH')
		try:
			subprocess.run(f'rm {const_DISPLAY_CONTENT_PATH}/*', shell=True)
		except:
			pass

		# remove display image files
		const_DISPLAY_IMAGE_EXPORT_PATH	= self.__setup.get_val('const_DISPLAY_IMAGE_EXPORT_PATH')
		try:
			subprocess.run(f'rm {const_DISPLAY_IMAGE_EXPORT_PATH}/*', shell=True)
		except:
			pass

		# remove task files
		const_TASKS_PATH	= self.__setup.get_val('const_TASKS_PATH')
		try:
			subprocess.run(f'rm {const_TASKS_PATH}/*', shell=True)
		except:
			pass

		const_DISPLAY_CONTENT_OLD_FILE	= self.__setup.get_val('const_DISPLAY_CONTENT_OLD_FILE')
		try:
			os.remove(const_DISPLAY_CONTENT_OLD_FILE)
		except:
			pass

		# init new logfile
		const_LOGFILE	= self.__setup.get_val('const_LOGFILE')
		try:
			with open(const_LOGFILE,'w') as f:
				f.write('Little Backup Box\n')
			shutil.chown(const_LOGFILE, user='www-data', group='www-data')
		except:
			pass

		# remove idletime lockfile
		const_IDLETIME_LOCKFILE	= self.__setup.get_val('const_IDLETIME_LOCKFILE')
		try:
			os.remove(const_IDLETIME_LOCKFILE)
		except:
			pass

		# dos2unix
		FilesDos2Unix	= [
			os.path.join(self.__const_WEB_ROOT_LBB, 'config.cfg'),
			os.path.join(self.__const_MEDIA_DIR, self.__const_RCLONE_CONFIG_FILE),
			os.path.join(self.__const_MEDIA_DIR, self.__const_BUTTONS_PRIVATE_CONFIG_FILE),
			os.path.join(self.__const_VPN_DIR_OpenVPN, self.__const_VPN_FILENAME_OpenVPN),
			os.path.join(self.__const_VPN_DIR_WireGuard, self.__const_VPN_FILENAME_WireGuard)
		]

		for FileDos2Unix in FilesDos2Unix:
			if os.path.isfile(FileDos2Unix):
				try:
					subprocess.run(['dos2unix', FileDos2Unix])
				except:
					pass

		# init new cmd logfile
		const_CMD_LOGFILE				= self.__setup.get_val('const_CMD_LOGFILE')
		try:
			with open(const_CMD_LOGFILE,'w') as f:
				f.write('Little Backup Box\n')
			shutil.chown(const_CMD_LOGFILE, user='www-data', group='www-data')
		except:
			pass

		# remove mountpoints
		lib_storage.remove_all_mountpoints(self.__setup)

		# reset proftpd
		lib_proftpd.proftpd().setDefaultRoot()

	def __display_hello(self):
		if self.__setup.get_val('conf_DISP_COLOR_MODEL') == '1':
			self.__display.message(['set:time=2', f':IMAGE={self.WORKING_DIR}/img/little-backup-box-mono.png'])
		else:
			self.__display.message(['set:time=2', f':IMAGE={self.WORKING_DIR}/img/little-backup-box-color.png'])

		lib_cron_ip.ip_info().display_ip()

	def __default_backup(self):

		conf_BACKUP_DEFAULT_SOURCE				= self.__setup.get_val('conf_BACKUP_DEFAULT_SOURCE')
		conf_BACKUP_DEFAULT_TARGET				= self.__setup.get_val('conf_BACKUP_DEFAULT_TARGET')
		conf_BACKUP_DEFAULT_SOURCE2				= self.__setup.get_val('conf_BACKUP_DEFAULT_SOURCE2')
		conf_BACKUP_DEFAULT_TARGET2				= self.__setup.get_val('conf_BACKUP_DEFAULT_TARGET2')

		conf_BACKUP_DEFAULT_MOVE_FILES			= self.__setup.get_val('conf_BACKUP_DEFAULT_MOVE_FILES')
		conf_BACKUP_DEFAULT_RENAME_FILES		= self.__setup.get_val('conf_BACKUP_DEFAULT_RENAME_FILES')
		conf_BACKUP_DEFAULT2_MOVE_FILES			= self.__setup.get_val('conf_BACKUP_DEFAULT2_MOVE_FILES')
		conf_BACKUP_DEFAULT_GENERATE_THUMBNAILS	= self.__setup.get_val('conf_BACKUP_DEFAULT_GENERATE_THUMBNAILS')
		conf_BACKUP_DEFAULT_UPDATE_EXIF			= self.__setup.get_val('conf_BACKUP_DEFAULT_UPDATE_EXIF')
		conf_POWER_OFF							= self.__setup.get_val('conf_POWER_OFF')

		# Default backup
		PrimaryBackupConfig		= []
		if conf_BACKUP_DEFAULT_SOURCE != 'none' and conf_BACKUP_DEFAULT_TARGET != 'none':
			PrimaryBackupConfig	= [
				f'--SourceName', f'{conf_BACKUP_DEFAULT_SOURCE}',
				f'--TargetName', f'{conf_BACKUP_DEFAULT_TARGET}',
				f'--move-files', f'{conf_BACKUP_DEFAULT_MOVE_FILES}',
				f'--rename-files', f'{conf_BACKUP_DEFAULT_RENAME_FILES}',
				f'--force-sync-database', 'False',
				f'--generate-thumbnails', f'{conf_BACKUP_DEFAULT_GENERATE_THUMBNAILS}',
				f'--update-exif', f'{conf_BACKUP_DEFAULT_UPDATE_EXIF}',
				f'--power-off', f'{conf_POWER_OFF}'
			]

		SecondaryBackupConfig	= []
		if conf_BACKUP_DEFAULT_SOURCE2 != 'none' and conf_BACKUP_DEFAULT_TARGET2 != 'none':
			SecondaryBackupConfig	= [
				f'--SecSourceName', f'{conf_BACKUP_DEFAULT_SOURCE2}',
				f'--SecTargetName', f'{conf_BACKUP_DEFAULT_TARGET2}',
				f'--move-files2', f'{conf_BACKUP_DEFAULT2_MOVE_FILES}',
			]

		if PrimaryBackupConfig:
			Command	= ['python3', f'{self.WORKING_DIR}/backup.py'] + PrimaryBackupConfig + SecondaryBackupConfig
			subprocess.run(Command)

if __name__ == '__main__':
	backup_autorun().run()

