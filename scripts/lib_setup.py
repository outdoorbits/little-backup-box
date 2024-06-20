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

# Provides the standard-setup,the types of constants and routines to handle the setup

import os
import subprocess
import sys

from configobj import ConfigObj

class setup(object):

	def __init__(self,rewrite_configfile=False):

		WORKING_DIR	= os.path.dirname(__file__)

		config_file_path 			= "{}/config.cfg".format(WORKING_DIR)
		standard_config_file_path	= "{}/config-standards.cfg".format(WORKING_DIR)
		constants_file_path = "{}/constants.sh".format(WORKING_DIR)

		self.__mount_user		= "www-data"
		self.__mount_group		= "www-data"
		self.__uid		= int(subprocess.check_output(['id','-u',f"{self.__mount_user}"]).decode().strip())
		self.__gid		= int(subprocess.check_output(['id','-g',f"{self.__mount_group}"]).decode().strip())

		# constants
		constants=self.__get_constants_types()
		self.constants	= self.__get_constants(constants_file_path,constants)

		# config
		self.config	= self.__get_config_standard()

		if rewrite_configfile:
			self.rewrite_configfile(standard_config_file_path)
			self.rewrite_buttons_private_config_file()

		self.__get_config_configured(config_file_path)

		self.setup	= self.config | self.constants

		if rewrite_configfile:
			self.rewrite_configfile(config_file_path)


	def get_val(self,setup_var):
		if setup_var in self.setup:
			return (self.setup[setup_var]['value'])
		else:
			return('Error: Unknown VARIABLE {}'.format(setup_var))

	def __norm_value(self,value,val_type):
		if val_type == 'int':
			return(int(value) if value else 0)

		elif val_type == 'int16':
				return(int(value,16) if value else 0)

		elif val_type == 'float':
			return(int(value))

		elif val_type == 'bool':
			return(
				(value == True) or
				(value == 1) or
				(value == '1') or
				(value.lower() == 'true')
			)

		else:
			return(str(value))

	def rewrite_configfile(self,ConfigFilePath):
		with open(ConfigFilePath,'w') as f:
			for ConfigVar in self.config:

				if self.config[ConfigVar]['type'] == 'str':
					Separator = "'"
				else:
					Separator = ''

				if self.config[ConfigVar]['type'] == 'int16':
					Separator = "'"
					if not isinstance(self.config[ConfigVar]['value'], str):
						self.config[ConfigVar]['value']	= hex(self.config[ConfigVar]['value'])

				f.write(f"{ConfigVar}={Separator}{self.config[ConfigVar]['value']}{Separator}\n")

		os.chown(ConfigFilePath, self.__uid, self.__gid)

	def rewrite_buttons_private_config_file(self):
		FilePath	= os.path.join(self.constants['const_MEDIA_DIR']['value'],self.constants['const_BUTTONS_PRIVATE_CONFIG_FILE']['value'])

		if not os.path.isfile(FilePath):
			print('create')
			with open(FilePath,'w') as f:
				f.writelines([
						'# Individual button configuration.\n',
						'# You can define as many buttons as you have.\n',
						'#\n',
						'# Pattern:\n',
						'# GPIO-PIN=function,GPIO-PIN=function,GPIO-PIN=function:Your comment\n',
						'# Please mind to use the GPIO.BCM notation, not the PIN-numbers!!!\n',
						'#\n',
						'# Possible functions are:\n',
						'# - up\n',
						'# - down\n',
						'# - left\n',
						'# - right\n',
						'#\n',
						'# Example:\n',
						'# 5=up,6=down,17=left,27=right,16=right:My individual buttons\n',
					])

		os.chown(FilePath, self.__uid, self.__gid)


	def __get_config_configured(self,ConfigFilePath):
		if os.path.isfile(ConfigFilePath):

			config_file = ConfigObj(ConfigFilePath)

			for conf_var in config_file:

				conf_val	= config_file[conf_var]

				#migrate variables to new definitions
				if conf_var == 'conf_MAIL_conf_PASSWORD':
					conf_var	= 'conf_MAIL_PASSWORD'

				if conf_var == 'conf_conf_DISP_IP_REPEAT':
					conf_var	= 'conf_DISP_IP_REPEAT'

				if conf_var == 'conf_NOTIFY':
					conf_var	= 'conf_MAIL_NOTIFICATIONS'

				#migrate values to new definitions
				if conf_var == 'conf_BACKUP_DEFAULT_SOURCE':
					conf_val = 'usb' if conf_val == 'storage' else conf_val

				if conf_var == 'conf_BACKUP_DEFAULT_TARGET':
					conf_val = 'usb' if conf_val == 'external' else conf_val

				if conf_var == 'conf_BACKUP_DEFAULT_SOURCE2':
					conf_val = 'usb' if conf_val == 'storage' else conf_val

				if conf_var == 'conf_BACKUP_DEFAULT_TARGET2':
					conf_val = 'usb' if conf_val == 'external' else conf_val
					conf_val = 'cloud_rsync' if conf_val == 'rsyncserver' else conf_val

				if conf_var == 'conf_MAIL_FROM':
					conf_val = config_file['conf_MAIL_USER'] if conf_val == '' else conf_val

				# set type
				if conf_var in self.config:
					conf_type	= self.config[conf_var]['type']
				else:
					conf_type	= 'str'

				# set value
				self.config[conf_var]	= {'value': self.__norm_value(conf_val, conf_type), 'type': conf_type}

			return

	def __get_config_standard(self):
		return(
				{
					'conf_LANGUAGE':								{'value': '', 'type': 'str'},
					'conf_TIME_ZONE':								{'value': 'Europe/London', 'type': 'str'},
					'conf_BACKUP_DEFAULT_SOURCE':					{'value': 'none', 'type': 'str'},
					'conf_BACKUP_DEFAULT_TARGET':					{'value': 'none', 'type': 'str'},
					'conf_BACKUP_DEFAULT_GENERATE_THUMBNAILS':		{'value': True, 'type': 'bool'},
					'conf_BACKUP_DEFAULT_UPDATE_EXIF':				{'value': False, 'type': 'bool'},
					'conf_BACKUP_DEFAULT_SOURCE2':					{'value': 'none', 'type': 'str'},
					'conf_BACKUP_DEFAULT_TARGET2':					{'value': 'none', 'type': 'str'},
					'conf_BACKUP_CAMERA_FOLDER_MASK':				{'value': '*:DCIM', 'type': 'str'},
					'conf_BACKUP_TARGET_BASEDIR_CLOUDS':			{'value': '', 'type': 'str'},
					'conf_BACKUP_SYNC_METHOD_CLOUDS':				{'value': 'rsync', 'type': 'str'},
					'conf_BACKUP_GENERATE_THUMBNAILS':				{'value': True, 'type': 'bool'},
					'conf_BACKUP_UPDATE_EXIF':						{'value': False, 'type': 'bool'},
					'conf_BACKUP_TARGET_SIZE_MIN':					{'value': 0, 'type': 'int'},
					'conf_BACKUP_MOVE_FILES':						{'value': False, 'type': 'bool'},
					'conf_POWER_OFF':								{'value': False, 'type': 'bool'},
					'conf_VIEW_CONVERT_HEIC':						{'value': True, 'type': 'bool'},
					'conf_VIEW_WRITE_RATING_EXIF':					{'value': False, 'type': 'bool'},
					'conf_DISP':									{'value': False, 'type': 'bool'},
					'conf_DISP_CONNECTION':							{'value': 'I2C', 'type': 'str'},
					'conf_DISP_DRIVER':								{'value': 'SSD1306', 'type': 'str'},
					'conf_DISP_I2C_ADDRESS':						{'value': '0x3c', 'type': 'int16'},
					'conf_DISP_SPI_PORT':							{'value': '0', 'type': 'int'},
					'conf_DISP_RESOLUTION_X':						{'value': 128, 'type': 'int'},
					'conf_DISP_RESOLUTION_Y':						{'value': 64, 'type': 'int'},
					'conf_DISP_ROTATE':								{'value': 0, 'type': 'int'},
					'conf_DISP_CONTRAST':							{'value': 255, 'type': 'int'},
					'conf_DISP_COLOR_MODEL':						{'value': '1', 'type': 'str'},
					'conf_DISP_COLOR_TEXT':							{'value': 'grey', 'type': 'str'},
					'conf_DISP_COLOR_HIGH':							{'value': 'white', 'type': 'str'},
					'conf_DISP_COLOR_ALERT':						{'value': 'orange', 'type': 'str'},
					'conf_DISP_FONT_SIZE':							{'value': 12, 'type': 'int'},
					'conf_DISP_FRAME_TIME':							{'value': 2, 'type': 'int'},
					'conf_DISP_BLACK_ON_POWER_OFF':					{'value': True, 'type': 'bool'},
					'conf_DISP_IP_REPEAT':							{'value': True, 'type': 'bool'},
					'conf_MENU_ENABLED':							{'value': True, 'type': 'bool'},
					'conf_MENU_BUTTON_COMBINATION':					{'value': '1', 'type': 'str'},
					'conf_MENU_BUTTON_ROTATE':						{'value': 2, 'type': 'int'},
					'conf_MENU_BUTTON_BOUNCETIME':					{'value': 15, 'type': 'int'},
					'conf_MENU_BUTTON_EDGE_DETECTION':				{'value': 'RISING', 'type': 'str'},
					'conf_MENU_BUTTON_RESISTOR_PULL':				{'value': 'DOWN', 'type': 'str'},
					'conf_VIRTUAL_KEYBOARD_ENABLED':				{'value': True, 'type': 'bool'},
					'conf_FAN_PWM_TEMP_C':							{'value': 50, 'type': 'int'},
					'conf_FAN_PWM_GPIO':							{'value': 18, 'type': 'int'},
					'conf_POPUP_MESSAGES':							{'value': True, 'type': 'bool'},
					'conf_THEME':									{'value': 'dark', 'type': 'str'},
					'conf_BACKGROUND_IMAGE':						{'value': '', 'type': 'str'},
					'conf_LOGLEVEL':								{'value': 1, 'type': 'int'},
					'conf_LOG_SYNC':								{'value': True, 'type': 'bool'},
					'conf_POWER_OFF_IDLE_TIME':						{'value': 0, 'type': 'int'},
					'conf_MAIL_NOTIFICATIONS':						{'value': False, 'type': 'bool'},
					'conf_MAIL_TIMEOUT_SEC':						{'value': 60, 'type': 'int'},
					'conf_MAIL_HTML':								{'value': True, 'type': 'bool'},
					'conf_SMTP_SERVER':								{'value': '', 'type': 'str'},
					'conf_SMTP_PORT':								{'value': '465', 'type': 'str'},
					'conf_MAIL_SECURITY':							{'value': 'STARTTLS', 'type': 'str'},
					'conf_MAIL_USER':								{'value': '', 'type': 'str'},
					'conf_MAIL_PASSWORD':							{'value': '', 'type': 'str'},
					'conf_MAIL_FROM':								{'value': '', 'type': 'str'},
					'conf_MAIL_TO':									{'value': '', 'type': 'str'},
					'conf_RSYNC_SERVER':							{'value': '', 'type': 'str'},
					'conf_RSYNC_PORT':								{'value': '873', 'type': 'str'},
					'conf_RSYNC_USER':								{'value': '', 'type': 'str'},
					'conf_RSYNC_PASSWORD':							{'value': '', 'type': 'str'},
					'conf_RSYNC_SERVER_MODULE':						{'value': 'little-backup-box', 'type': 'str'},
					'conf_WIFI_COUNTRY':							{'value': 'GB', 'type': 'str'},
					'conf_VPN_TYPE_RSYNC':							{'value': 'none', 'type': 'str'},
					'conf_VPN_TYPE_CLOUD':							{'value': 'none', 'type': 'str'},
					'conf_VPN_TIMEOUT':								{'value': 20, 'type': 'int'},
					'conf_PASSWORD':								{'value': '', 'type': 'str'}
				}
		)


	def __get_constants(self,constants_file_path,constants):
		if os.path.isfile(constants_file_path):
			constants_file = ConfigObj(constants_file_path)
			for const_var in constants_file:
				if const_var in constants:
					const_type	= constants[const_var]['type']
				else:
					const_type	= 'str'

				constants[const_var]	= {'value': self.__norm_value(constants_file[const_var], const_type), 'type': const_type}

			return(constants)

	def __get_constants_types(self):
		return(
				{
					'const_MEDIA_DIR':								{'type': 'str'},
					'const_MOUNTPOINT_USB_TARGET':	{'type': 'str'},
					'const_MOUNTPOINT_USB_SOURCE':	{'type': 'str'},
					'const_MOUNTPOINT_TECH_USB_TARGET':				{'type': 'str'},
					'const_MOUNTPOINT_TECH_USB_SOURCE':				{'type': 'str'},
					'const_MOUNTPOINT_NVME_TARGET':	{'type': 'str'},
					'const_MOUNTPOINT_NVME_SOURCE':	{'type': 'str'},
					'const_MOUNTPOINT_TECH_NVME_TARGET':				{'type': 'str'},
					'const_MOUNTPOINT_TECH_NVME_SOURCE':				{'type': 'str'},
					'const_MOUNTPOINT_CLOUD_TARGET':		{'type': 'str'},
					'const_MOUNTPOINT_CLOUD_SOURCE':		{'type': 'str'},
					'const_MOUNT_LOCAL_TIMEOUT':					{'type': 'int'},
					'const_MOUNT_CLOUD_TIMEOUT':					{'type': 'int'},
					'const_INTERNAL_BACKUP_DIR':					{'type': 'str'},
					'const_BACKGROUND_IMAGES_DIR':					{'type': 'str'},
					'const_WEB_ROOT_LBB':							{'type': 'str'},
					'const_LOGFILE':								{'type': 'str'},
					'const_DISPLAY_LINES_LIMIT':					{'type': 'int'},
					'const_DISPLAY_CONTENT_FOLDER':					{'type': 'str'},
					'const_DISPLAY_CONTENT_OLD_FILE':				{'type': 'str'},
					'const_IP_SENT_MARKERFILE':						{'type': 'str'},
					'const_MENU_TIMEOUT_SEC':						{'type': 'int'},
					'const_RCLONE_CONFIG_FILE':						{'type': 'str'},
					'const_BUTTONS_CONFIG_FILE':					{'type': 'str'},
					'const_BUTTONS_PRIVATE_CONFIG_FILE':			{'type': 'str'},
					'const_STORAGE_EXT_MASK':						{'type': 'str'},
					'const_STORAGE_INT_MASK':						{'type': 'str'},
					'const_CMD_RUNNER_LOCKFILE':					{'type': 'str'},
					'const_IMAGE_DATABASE_FILENAME':				{'type': 'str'},
					'const_FILE_EXTENSIONS_LIST_WEB_IMAGES':		{'type': 'str'},
					'const_FILE_EXTENSIONS_LIST_HEIC':				{'type': 'str'},
					'const_FILE_EXTENSIONS_LIST_RAW':				{'type': 'str'},
					'const_FILE_EXTENSIONS_LIST_TIF':				{'type': 'str'},
					'const_FILE_EXTENSIONS_LIST_VIDEO':				{'type': 'str'},
					'const_FILE_EXTENSIONS_LIST_AUDIO':				{'type': 'str'},
					'const_VIEW_GRID_COLUMNS':						{'type': 'int'},
					'const_VIEW_MAGNIFYING_GLASS_ZOOM':				{'type': 'int'},
					'const_VIEW_BANNED_PATHS':						{'type': 'str'},
					'const_VIEW_RATING_STANDARD_VALUE':				{'type': 'str'},
					'const_MAIL_TIMEOUT':							{'type': 'int'},
					'const_VPN_DIR_OpenVPN':						{'type': 'str'},
					'const_VPN_DIR_WireGuard':						{'type': 'str'},
					'const_VPN_FILENAME_OpenVPN':					{'type': 'str'},
					'const_VPN_FILENAME_WireGuard':					{'type': 'str'},
					'const_SYNC_TIME_OVERHEATING_THRESHOLD_SEC':	{'type': 'int'},
					'const_SYNC_TIME_OVERHEATING_WAIT_SEC':			{'type': 'int'},
					'const_BACKUP_MAX_TRIES':						{'type': 'int'},
					'const_SOFTWARE_VERSION':						{'type': 'str'},
					'const_FONT_PATH':								{'type': 'str'}
				}
		)


if __name__ == "__main__":
	# write config files
	__setup	= setup(True)




