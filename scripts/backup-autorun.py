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
import lib_cron_ip
import lib_display
import lib_language
import lib_setup
import lib_storage

class backup_autorun(object):
	def __init__(self):
		self.WORKING_DIR	= os.path.dirname(__file__)

		self.__setup	= lib_setup.setup(rewrite_configfile=True)
		self.__display	= lib_display.display()
		self.__lan		= lib_language.language()

	def run(self):
		self.__cleanup_at_boot()
		self.__display_hello()
		threading.Thread(target=lib_cron_ip.ip_info().mail_ip).start()
		self.__default_backup()

	def __cleanup_at_boot(self):
		# remove IP_SENT_MARKERFILE
		const_IP_SENT_MARKERFILE	= self.__setup.get_val('const_IP_SENT_MARKERFILE')
		try:
			os.remove(const_IP_SENT_MARKERFILE)
		except:
			pass

		# remove display content files
		const_DISPLAY_CONTENT_FOLDER	= self.__setup.get_val('const_DISPLAY_CONTENT_FOLDER')
		try:
			subprocess.run(f"rm {const_DISPLAY_CONTENT_FOLDER}/*",shell=True)
		except:
			pass

		const_DISPLAY_CONTENT_OLD_FILE	= self.__setup.get_val('const_DISPLAY_CONTENT_OLD_FILE')
		try:
			os.remove(const_DISPLAY_CONTENT_OLD_FILE)
		except:
			pass

		# init new logfile
		const_LOGFILE					= self.__setup.get_val('const_LOGFILE')
		try:
			with open(const_LOGFILE,'w') as f:
				f.write('Little Backup Box\n')
			shutil.chown(const_LOGFILE, user='www-data', group='www-data')
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

	def __display_hello(self):
		if self.__setup.get_val('conf_DISP_COLOR_MODEL') == '1':
			self.__display.message(["set:time=2", f":IMAGE={self.WORKING_DIR}/little-backup-box.bmp"])
		else:
			self.__display.message(["set:time=2", f":IMAGE={self.WORKING_DIR}/little-backup-box.jpg"])

		lib_cron_ip.ip_info().display_ip()

	def __default_backup(self):

		conf_BACKUP_DEFAULT_SOURCE				= self.__setup.get_val('conf_BACKUP_DEFAULT_SOURCE')
		conf_BACKUP_DEFAULT_TARGET				= self.__setup.get_val('conf_BACKUP_DEFAULT_TARGET')
		conf_BACKUP_DEFAULT_SOURCE2				= self.__setup.get_val('conf_BACKUP_DEFAULT_SOURCE2')
		conf_BACKUP_DEFAULT_TARGET2				= self.__setup.get_val('conf_BACKUP_DEFAULT_TARGET2')

		conf_BACKUP_MOVE_FILES					= self.__setup.get_val('conf_BACKUP_MOVE_FILES')
		conf_BACKUP_DEFAULT_GENERATE_THUMBNAILS	= self.__setup.get_val('conf_BACKUP_DEFAULT_GENERATE_THUMBNAILS')
		conf_BACKUP_DEFAULT_UPDATE_EXIF			= self.__setup.get_val('conf_BACKUP_DEFAULT_UPDATE_EXIF')
		conf_POWER_OFF							= self.__setup.get_val('conf_POWER_OFF')

		# Default backup
		PrimaryBackupConfig		= []
		if conf_BACKUP_DEFAULT_SOURCE != 'none' and conf_BACKUP_DEFAULT_TARGET != 'none':
			PrimaryBackupConfig	= [
				f"--SourceName", f"{conf_BACKUP_DEFAULT_SOURCE}",
				f"--TargetName", f"{conf_BACKUP_DEFAULT_TARGET}",
				f"--move-files", f"{conf_BACKUP_MOVE_FILES}",
				f"--sync-database", "False",
				f"--generate-thumbnails", f"{conf_BACKUP_DEFAULT_GENERATE_THUMBNAILS}",
				f"--update-exif", f"{conf_BACKUP_DEFAULT_UPDATE_EXIF}",
				f"--power-off", f"{conf_POWER_OFF}"
			]

		SecundaryBackupConfig	= []
		if conf_BACKUP_DEFAULT_SOURCE2 != 'none' and conf_BACKUP_DEFAULT_TARGET2 != 'none':
			SecundaryBackupConfig	= [
				f"--SecSourceName", f"{conf_BACKUP_DEFAULT_SOURCE2}",
				f"--SecTargetName", f"{conf_BACKUP_DEFAULT_TARGET2}"
			]

		if PrimaryBackupConfig:
			Command	= ['python3', f"{self.WORKING_DIR}/backup.py"] + PrimaryBackupConfig + SecundaryBackupConfig
			subprocess.run(Command)

if __name__ == "__main__":
	backup_autorun().run()

