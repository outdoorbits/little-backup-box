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

# Provides a menu for the display.
# It can be used by hardware-buttons. Please read the Wiki at https://github.com/outdoorbits/little-backup-box/wiki/02a.-Displaymenu.

from gpiozero import Button
import sys
import time
import os
import subprocess

import lib_cron_ip
import lib_display
import lib_language
import lib_network
import lib_setup
import lib_storage
import lib_system

# import lib_debug
# xx	= lib_debug.debug()

class MENU_CONTROLLER(object):
	def __init__(self):
		self.proceed	= True

	def terminate(self):
		self.proceed	= False

class menu(object):

	def __init__(self, DISPLAY_LINES, setup, menu_controller):

		self.DISPLAY_LINES	= DISPLAY_LINES;

		self.__display			= lib_display.display()
		self.__setup			= setup
		self.__lan				= lib_language.language()

		self.WORKING_DIR = os.path.dirname(__file__)

		self.board_model_number							= lib_system.get_pi_model(number_only=True)

		self.const_MEDIA_DIR							= self.__setup.get_val('const_MEDIA_DIR')
		self.conf_DISP_FRAME_TIME						= self.__setup.get_val('conf_DISP_FRAME_TIME')
		self.conf_DISP_FRAME_TIME_IP					= self.__setup.get_val('conf_DISP_FRAME_TIME_IP')
		self.conf_RSYNC_SERVER							= self.__setup.get_val('conf_RSYNC_SERVER')
		self.conf_RSYNC_PORT							= self.__setup.get_val('conf_RSYNC_PORT')
		self.conf_RSYNC_USER							= self.__setup.get_val('conf_RSYNC_USER')
		self.conf_RSYNC_PASSWORD						= self.__setup.get_val('conf_RSYNC_PASSWORD')
		self.conf_RSYNC_SERVER_MODULE					= self.__setup.get_val('conf_RSYNC_SERVER_MODULE')
		self.const_BUTTONS_CONFIG_FILE					= self.__setup.get_val('const_BUTTONS_CONFIG_FILE')
		self.const_BUTTONS_PRIVATE_CONFIG_FILE			= self.__setup.get_val('const_BUTTONS_PRIVATE_CONFIG_FILE')
		self.conf_MENU_BUTTON_COMBINATION				= self.__setup.get_val('conf_MENU_BUTTON_COMBINATION')
		self.conf_MENU_BUTTON_ROTATE					= self.__setup.get_val('conf_MENU_BUTTON_ROTATE')
		self.conf_MENU_BUTTON_BOUNCETIME 				= self.__setup.get_val('conf_MENU_BUTTON_BOUNCETIME')
		self.GPIO_MENU_BUTTON_EDGE_DETECTION_RISING 	= self.__setup.get_val('conf_MENU_BUTTON_EDGE_DETECTION') == 'RISING'
		self.GPIO_MENU_BUTTON_RESISTOR_PULL_UP 			= self.__setup.get_val('conf_MENU_BUTTON_RESISTOR_PULL') == 'UP'
		self.const_MENU_FRAME_TIME						= self.__setup.get_val('const_MENU_FRAME_TIME')

		self.RCLONE_CONFIG_FILE							= f"{self.const_MEDIA_DIR}/{self.__setup.get_val('const_RCLONE_CONFIG_FILE')}"
		self.const_MENU_TIMEOUT_SEC						= self.__setup.get_val('const_MENU_TIMEOUT_SEC')

		self.conf_TELEGRAM_TOKEN						= self.__setup.get_val('conf_TELEGRAM_TOKEN')
		self.conf_TELEGRAM_CHAT_ID						= self.__setup.get_val('conf_TELEGRAM_CHAT_ID')

		self.conf_MASTODON_BASE_URL						= self.__setup.get_val('conf_MASTODON_BASE_URL')
		self.conf_MASTODON_TOKEN						= self.__setup.get_val('conf_MASTODON_TOKEN')

		self.conf_BLUESKY_API_BASE_URL					= self.__setup.get_val('conf_BLUESKY_API_BASE_URL')
		self.conf_BLUESKY_IDENTIFIER					= self.__setup.get_val('conf_BLUESKY_IDENTIFIER')
		self.conf_BLUESKY_APP_PASSWORD					= self.__setup.get_val('conf_BLUESKY_APP_PASSWORD')

		self.buttons	= {}

		## menu-types:
		#
		# menu
		# item: action is a confirmitem or as shell
		# confirmitem: Asks for confirmation, defines the action
		# shell: action contains the shell-command-array, title is ignored
		# info: displays an information

		kill_backup_process	= ['sudo',f'{self.WORKING_DIR}/stop_backup.sh']
		start_backup_trunk	= ['sudo','python3',f'{self.WORKING_DIR}/backup.py']

		# local backups
		local_services	= ['anyusb', 'usb', 'internal','camera']

		## NVMe available?
		if lib_storage.get_available_partitions(StorageType='nvme'):
			local_services.append('nvme')

		# backups
		cloudservices	= []

		## rsyncserver
		if not (self.conf_RSYNC_SERVER =='' or self.conf_RSYNC_PORT =='' or self.conf_RSYNC_USER =='' or self.conf_RSYNC_PASSWORD =='' or self.conf_RSYNC_SERVER_MODULE ==''):
			cloudservices.append('cloud_rsync')

		## rclone services
		rclone_cloudservices	= subprocess.check_output('sudo rclone config show --config "{}" | grep "^\[.*\]$" | sed "s/^\[//" | sed "s/\]$//"'.format(self.RCLONE_CONFIG_FILE),shell=True).decode('UTF-8').strip().split('\n')
		for i in range(len(rclone_cloudservices)):
			cloudservices.append(f'cloud:{rclone_cloudservices[i]}')

		## ftp
		cloudservices.append('ftp')

		## social media
		socialservices	= []

		### telegram
		if self.conf_TELEGRAM_TOKEN and int(self.conf_TELEGRAM_CHAT_ID) != 0:
			socialservices.append('social:telegram')

		### mastodon
		if self.conf_MASTODON_BASE_URL and self.conf_MASTODON_TOKEN:
			socialservices.append('social:mastodon')

		### bluesky
		if self.conf_BLUESKY_API_BASE_URL and self.conf_BLUESKY_IDENTIFIER and self.conf_BLUESKY_APP_PASSWORD:
			socialservices.append('social:bluesky')

		# generate menues
		BACKUP_SOURCES_MENU		= []

		for source in (local_services + cloudservices):
			BACKUP_SOURCE_MENU	= []

			# format service parameters
			SourceType, SourceServiceName		= lib_storage.extractService(source)

			if SourceType == 'cloud':
				sourceName	= SourceServiceName
			else:
				sourceName	= self.__lan.l(f'box_menu_backup_mode_{SourceType}')

			for target in (local_services + cloudservices + socialservices):

				# check for invalid combinations of Source and Target
				if (source == target) and (source != 'usb'):
					continue

				# exclude combinations
				if target in ['anyusb', 'camera', 'ftp']:
					continue
				if target == 'cloud_rsync' and source in ['anyusb', 'camera', 'ftp']:
					continue
				if target.startswith('social:') and (source in ['anyusb', 'camera', 'cloud_rsync', 'ftp'] or source.startswith('cloud:')):
					continue

				# format service parameters
				targetType, targetServiceName		= lib_storage.extractService(target)

				if targetType == 'cloud':
					targetName	= targetServiceName
				elif targetType == 'social':
					targetName	= self.__lan.l(f'box_menu_backup_mode_{targetServiceName}')
				else:
					targetName	= self.__lan.l(f'box_menu_backup_mode_{targetType}')

				# add to submenu
				BACKUP_SOURCE_MENU.append(
					{
						'type':		'item',
						'title':	self.__lan.l('box_menu_to') + '|' + targetName,
						'action':	self.create_confirmed_shell_action(
										title	= sourceName + '|' + self.__lan.l('box_menu_to') + '|' + targetName,
										command	= [ kill_backup_process, start_backup_trunk + ['--SourceName', source, '--TargetName', target] ]
									),
					}
				)

			# combine BACKUP_SOURCE_MENU to BACKUP_SOURCES_MENU
			menutitle	= self.__lan.l(f'box_menu_backup_mode_{SourceType}') if SourceType != 'cloud' else sourceName
			BACKUP_SOURCES_MENU.append(
				{
					'type':		'menu',
					'title':	menutitle + ' ' + self.__lan.l('box_menu_to'),
					'action':	BACKUP_SOURCE_MENU
				}
			)

		# create menu
		MENU_BACKUP	= [
			{
				'type':		'menu',
				'title':	self.__lan.l('box_menu_backup_backup'),
				'action':	BACKUP_SOURCES_MENU
			},

			{
				'type':		'menu',
				'title':	self.__lan.l('box_menu_backup_stop'),
				'action':	self.create_confirmed_shell_action(
								title	= self.__lan.l('box_menu_backup_stop'),
								command	= [kill_backup_process]
							)
			},
		]

		self.MENU_NETWORK	= [
			{
				'type':		'item',
				'title':	self.__lan.l('box_menu_ip'),
				'action':	[
								{
									'type':		'info',
									'title':	self.__lan.l('box_menu_ip'),
									'action':	'ip',
								}
							],
			},

			{
				'type':		'item',
				'title':	self.__lan.l('box_menu_comitup_reset'),
				'action':	self.create_confirmed_shell_action(
								title	= self.__lan.l('box_menu_comitup_reset'),
								command	= [['sudo',f'{self.WORKING_DIR}/comitup-reset.sh']]
							),
			},

			{
				'type':		'item',
				'title':	self.__lan.l('box_menu_vpn_stop'),
				'action':	self.create_confirmed_shell_action(
								title	= self.__lan.l('box_menu_vpn_stop'),
								command	= [['sudo','python3', f"{self.WORKING_DIR}/lib_vpn.py", 'stop']]
							),
			},
		]

		self.MENU_POWER	= [
			{
				'type':		'item',
				'title':	self.__lan.l('box_menu_power_reboot'),
				'action':	self.create_confirmed_shell_action(
								title	= self.__lan.l('box_menu_power_reboot'),
								command	= [['sudo','python3',f'{self.WORKING_DIR}/lib_poweroff.py','reboot' ]]
							),
			},

			{
				'type':		'item',
				'title':	self.__lan.l('box_menu_power_shutdown'),
				'action':	self.create_confirmed_shell_action(
								title	= self.__lan.l('box_menu_power_shutdown'),
								command	= [['sudo','python3',f'{self.WORKING_DIR}/lib_poweroff.py','poweroff' ]]
							),
			},
		]

		self.MENU_MAIN	= [
			{
				'type':		'menu',
				'title':	self.__lan.l('box_menu_backup_backup'),
				'action':	MENU_BACKUP,
			},

			{
				'type':		'menu',
				'title':	self.__lan.l('box_menu_network'),
				'action':	self.MENU_NETWORK,
			},

			{
				'type':		'menu',
				'title':	self.__lan.l('box_menu_system'),
				'action':	self.MENU_POWER,
			},

		]

		# define menu variables an (re-)set them
		self.reset(ShowMenu=False)

		self.GPIO_init()

		# debug commands
		self.debug()

		# iternal loop
		while menu_controller.proceed:
			time.sleep(1)

	def GPIO_init(self):
		if self.conf_MENU_BUTTON_COMBINATION:
			if self.conf_MENU_BUTTON_COMBINATION.isnumeric():
				ButtonsConfigFile		= f"{self.WORKING_DIR}/{self.const_BUTTONS_CONFIG_FILE}"
				ButtonCombinationNumber	= int(self.conf_MENU_BUTTON_COMBINATION)
			elif self.conf_MENU_BUTTON_COMBINATION[0:1] == 'c':
				ButtonsConfigFile		= f"{self.const_MEDIA_DIR}/{self.const_BUTTONS_PRIVATE_CONFIG_FILE}"
				ButtonCombinationNumber	=  int(self.conf_MENU_BUTTON_COMBINATION[1:])

			if os.path.isfile(ButtonsConfigFile):
				ConfigLines	= []
				with open(ButtonsConfigFile,'r') as f:
					ConfigLines	= f.readlines()

				ConfigLineNumber	= 0
				for ConfigLine in ConfigLines:
					ConfigLine	= ConfigLine.strip()
					if ConfigLine:
						if ConfigLine[0:1] != '#':
							ConfigLineNumber	+= 1
							if ConfigLineNumber	== ButtonCombinationNumber:
								ConfigLine	= ConfigLine.split(':',1)[0]
								ButtonDefs	= ConfigLine.split(',')
								for ButtonDef in ButtonDefs:
									GPIO_PIN, ButtonFunction	= ButtonDef.split('=')
									GPIO_PIN	= int(GPIO_PIN)

									self.GPIO_config_button(GPIO_PIN,ButtonFunction)


	def GPIO_config_button(self,GPIO_PIN,ButtonFunction):
		# rotate buttons
		if self.conf_MENU_BUTTON_ROTATE == 2:
			if ButtonFunction == 'up':
				ButtonFunction	= 'down'
			elif ButtonFunction == 'down':
				ButtonFunction	= 'up'
			elif ButtonFunction == 'left':
				ButtonFunction	= 'right'
			elif ButtonFunction == 'right':
				ButtonFunction	= 'left'

		# define button
		try:
			self.buttons[GPIO_PIN]				= Button(pin=GPIO_PIN, pull_up=self.GPIO_MENU_BUTTON_RESISTOR_PULL_UP, bounce_time=self.conf_MENU_BUTTON_BOUNCETIME / 1000)
		except:
			return()

		# add events to buttons
		if ButtonFunction == 'up':
			if self.GPIO_MENU_BUTTON_EDGE_DETECTION_RISING:
				self.buttons[GPIO_PIN].when_pressed	= self.move_up
			else:
				self.buttons[GPIO_PIN].when_released	= self.move_up

		if ButtonFunction == 'down':
			if self.GPIO_MENU_BUTTON_EDGE_DETECTION_RISING:
				self.buttons[GPIO_PIN].when_pressed		= self.move_down
			else:
				self.buttons[GPIO_PIN].when_released	= self.move_down

		if ButtonFunction == 'left':
			if self.GPIO_MENU_BUTTON_EDGE_DETECTION_RISING:
				self.buttons[GPIO_PIN].when_pressed	= self.move_left
			else:
				self.buttons[GPIO_PIN].when_released	= self.move_left

		if ButtonFunction == 'right':
			if self.GPIO_MENU_BUTTON_EDGE_DETECTION_RISING:
				self.buttons[GPIO_PIN].when_pressed	= self.move_right
			else:
				self.buttons[GPIO_PIN].when_released	= self.move_right

	def reset(self, ShowMenu=False):
		self.MENU_LEVEL		= 0 # integer
		self.MENU			= []
		self.MENU_POS		= []
		self.MENU_SHIFT		= []
		self.HEAD_LINES	= 0

		self.LAST_INPUT_TIME	= time.time()

		# init basic menu
		self.MENU.append(self.MENU_MAIN) # points to the actually active menu level
		self.MENU_POS.append(0)
		self.MENU_SHIFT.append(0)

		if ShowMenu:
			self.display()

	def check_timeout(self):
		if abs(time.time() - self.LAST_INPUT_TIME) >= self.const_MENU_TIMEOUT_SEC:
			self.reset()
		else:
			self.LAST_INPUT_TIME	= time.time()

	def create_confirmed_shell_action(self, title, command):
		return([
			{
				'type':		'confirmitem',
				'title':	title,
				'action':	self.create_shell_action(title, command),
			},
		])

	def create_shell_action(self, title, command):
		return([
			{
				'type':		'shell',
				'title':	title,
				'action':	command,
			},
		]
		)

	def set_shift(self):
		display_active_line	= self.MENU_POS[self.MENU_LEVEL] + 1 + self.HEAD_LINES - self.MENU_SHIFT[self.MENU_LEVEL]
		if display_active_line <= self.HEAD_LINES:
			# shift up
			self.MENU_SHIFT[self.MENU_LEVEL] = self.MENU_POS[self.MENU_LEVEL]
		elif display_active_line > self.DISPLAY_LINES - self.HEAD_LINES:
			# shift down
			self.MENU_SHIFT[self.MENU_LEVEL] = self.HEAD_LINES + self.MENU_POS[self.MENU_LEVEL] + 1 - self.DISPLAY_LINES

	def get_INFO(self, action):
		if action == 'ip':
			FrameTime	= self.conf_DISP_FRAME_TIME_IP * 2
			lib_cron_ip.ip_info().display_ip(FrameTime=FrameTime, force=True)
			return([], FrameTime)

		return([], self.conf_DISP_FRAME_TIME)

	def display(self):
		self.check_timeout()

		self.set_shift()

		FrameTime			= self.const_MENU_FRAME_TIME
		LINES 				= []
		n					= 0

		# define title
		self.HEAD_LINES = 0
		if self.MENU_LEVEL > 0:
			if self.MENU[self.MENU_LEVEL - 1][self.MENU_POS[self.MENU_LEVEL - 1]]['type'] == 'menu':
				HEAD_LINE = self.MENU[self.MENU_LEVEL - 1][self.MENU_POS[self.MENU_LEVEL - 1]]['title'].strip()
				if HEAD_LINE != '':
					self.HEAD_LINES = 1
					LINES	+= [f"s=h,u:{HEAD_LINE}"]

		# generate content
		for item in self.MENU[self.MENU_LEVEL]:

			if (n >= self.MENU_SHIFT[self.MENU_LEVEL]) and (n < self.DISPLAY_LINES + self.MENU_SHIFT[self.MENU_LEVEL] - self.HEAD_LINES):

				# Title can be combined by more than one parts translatet separately.
				# Parts are separated by "|"
				TITLE = ''
				titleparts = self.MENU[self.MENU_LEVEL][n]['title'].split('|')
				for titlepart in titleparts:
					TITLE = "{}{} ".format(TITLE,titlepart)

				# confirm item
				if self.MENU[self.MENU_LEVEL][n]['type'] == 'confirmitem':
					LINES	+= [f"s=b:{self.__lan.l('box_menu_confirm')}", f"s=b:{TITLE}", "s=h:{}".format(self.__lan.l('box_menu_yes'))]
					break

				elif self.MENU[self.MENU_LEVEL][n]['type'] == 'shell':
					i	= 0
					for command in self.MENU[self.MENU_LEVEL][n]['action']:
						i	+= 1

						# wait for all commands to finish except for the last
						if i < len(self.MENU[self.MENU_LEVEL][n]['action']):
							subprocess.run(command, shell=False)
						else:
							subprocess.Popen(command, shell=False)

					self.reset(ShowMenu=False)
					LINES	= []
					break

				elif self.MENU[self.MENU_LEVEL][n]['type'] == 'info':
					INFO, FrameTime	= self.get_INFO(self.MENU[self.MENU_LEVEL][n]['action'])
					if INFO:
						LINES	+= [f"s=h:{TITLE}"] + INFO

					self.reset(ShowMenu=True)
					LINES	= []
					break

				# menu or item
				elif self.MENU[self.MENU_LEVEL][n]['type'] in ['menu','item']:
					line	= TITLE

					if self.MENU[self.MENU_LEVEL][n]['type'] == 'menu':
						line = f"+ {line}"

					if n == self.MENU_POS[self.MENU_LEVEL]:
						style	= 's=h'
					else:
						style	= 's=b'

					LINES+= [f"{style}:{line}"]
				else:
					print('No known menu type: {}'.format(self.MENU[self.MENU_LEVEL][n]['type']),file=sys.stderr)

			n += 1

		if LINES:
			self.__display.message([f'set:clear,time={FrameTime}'] + LINES)

	def move_down(self):
		if len(self.MENU[self.MENU_LEVEL]) > (self.MENU_POS[self.MENU_LEVEL] + 1):
			self.MENU_POS[self.MENU_LEVEL] += 1
		else:
			self.MENU_POS[self.MENU_LEVEL] = 0

		self.display()

	def move_up(self):
		if self.MENU_POS[self.MENU_LEVEL] > 0:
			self.MENU_POS[self.MENU_LEVEL] += -1
		elif (len(self.MENU[self.MENU_LEVEL]) > 0):
			self.MENU_POS[self.MENU_LEVEL] = len(self.MENU[self.MENU_LEVEL]) - 1
		self.display()

	def move_right(self):
		menu_new	= self.MENU[self.MENU_LEVEL][self.MENU_POS[self.MENU_LEVEL]]['action']
		self.MENU_LEVEL += 1

		# replace or append next menu level
		if len(self.MENU) >= self.MENU_LEVEL + 1:
			self.MENU[self.MENU_LEVEL] = menu_new
			self.MENU_POS[self.MENU_LEVEL] = 0
			self.MENU_SHIFT[self.MENU_LEVEL] = 0
		else:
			self.MENU.append(menu_new)
			self.MENU_POS.append(0)
			self.MENU_SHIFT.append(0)

		self.display()

	def move_left(self):
		if self.MENU_LEVEL > 0:
			self.MENU_LEVEL += -1
		self.display()

##debug
	def debug(self):
		return()

		self.move_right()#debug
		self.move_right()#debug
		self.move_up()#debug

if __name__ == "__main__":
	# setup=lib_setup.setup()
 #
	# menu_controller	= MENU_CONTROLLER()
 #
	# menuobj	= menu(DISPLAY_LINES=10, setup=setup, menu_controller=menu_controller)
 #
	# menuobj.move_right()#debug
	# menuobj.move_right()#debug
	# menuobj.move_right()#debug
	# menuobj.move_up()#debug
	# menuobj.move_right()#debug
	# menuobj.move_right()#debug
 #
	# time.sleep (0.5)
	# menuobj.move_right()#debug
	# time.sleep (0.5)
	# menuobj.move_right()#debug
	# time.sleep (0.5)
	# menuobj.move_right()#debug
 #
	# menuobj.move_down()#debug
	# menuobj.move_down()#debug
	# menuobj.move_right()#debug
	# menuobj.move_down()#debug
	#menuobj.move_right()#debug
	#menuobj.move_right()#debug
	# time.sleep(20)

	pass
