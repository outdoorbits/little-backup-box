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

import time
import sys
import os
import subprocess
import RPi.GPIO as GPIO

from configobj import ConfigObj

class menu(object):

	def __init__(self,DISPLAY_LINES):

		self.DISPLAY_LINES	= DISPLAY_LINES;

		self.WORKING_DIR = os.path.dirname(__file__)

		config = ConfigObj("{}/config.cfg".format(self.WORKING_DIR))
		self.conf_DISP_FRAME_TIME		= float(config['conf_DISP_FRAME_TIME'])
		self.conf_RSYNC_SERVER			= config['conf_RSYNC_SERVER']
		self.conf_RSYNC_PORT			= config['conf_RSYNC_PORT']
		self.conf_RSYNC_USER			= config['conf_RSYNC_USER']
		self.conf_RSYNC_PASSWORD		= config['conf_RSYNC_PASSWORD']
		self.conf_RSYNC_SERVER_MODULE	= config['conf_RSYNC_SERVER_MODULE']


		constants = ConfigObj("{}/constants.sh".format(self.WORKING_DIR))
		self.const_RCLONE_CONFIG_FILE	= constants['const_RCLONE_CONFIG_FILE']
		self.const_MENU_TIMEOUT_SEC		= float(constants['const_MENU_TIMEOUT_SEC'])

		## menu-types:
		#
		# menu
		# item: action is a confirmitem or as shell
		# confirmitem: Asks for confirmation, defines the action
		# shell: action contains the shell-command-array, title is ignored
		# info: displays an information

		self.MENU_BACKUP_LOCAL	= [
			#usb
			{
				'type':		'item',
				'title':	'box_menu_backup_mode_usb|box_menu_to|box_menu_backup_mode_usb',
				'action':	self.create_shell_action('sudo pkill -f "{}/backup*";sudo {}/backup.sh usb usb'.format(self.WORKING_DIR,self.WORKING_DIR)),
			},

			{
				'type':		'item',
				'title':	'box_menu_backup_mode_usb|box_menu_to|box_menu_backup_mode_internal',
				'action':	self.create_shell_action('sudo pkill -f "{}/backup*";sudo {}/backup.sh usb internal'.format(self.WORKING_DIR,self.WORKING_DIR)),
			},

						{
				'type':		'item',
				'title':	'box_menu_backup_mode_internal|box_menu_to|box_menu_backup_mode_usb',
				'action':	self.create_shell_action('sudo pkill -f "{}/backup*";sudo {}/backup.sh internal usb'.format(self.WORKING_DIR,self.WORKING_DIR)),
			},

			#camera
			{
				'type':		'item',
				'title':	'box_menu_backup_mode_camera|box_menu_to|box_menu_backup_mode_usb',
				'action':		self.create_shell_action('sudo pkill -f "{}/backup*";sudo {}/backup.sh camera usb'.format(self.WORKING_DIR,self.WORKING_DIR)),
			},

			{
				'type':		'item',
				'title':	'box_menu_backup_mode_camera|box_menu_to|box_menu_backup_mode_internal',
				'action':		self.create_shell_action('sudo pkill -f "{}/backup*";sudo {}/backup.sh camera internal'.format(self.WORKING_DIR,self.WORKING_DIR)),
			},

			{
				'type':		'item',
				'title':	'box_menu_backup_mode_ios|box_menu_to|box_menu_backup_mode_usb',
				'action':		self.create_shell_action('sudo pkill -f "{}/backup*";sudo {}/backup.sh ios usb'.format(self.WORKING_DIR,self.WORKING_DIR)),
			},

			{
				'type':		'item',
				'title':	'box_menu_backup_mode_ios|box_menu_to|box_menu_backup_mode_internal',
				'action':		self.create_shell_action('sudo pkill -f "{}/backup*";sudo {}/backup.sh ios internal'.format(self.WORKING_DIR,self.WORKING_DIR)),
			},
		]

		self.MENU_BACKUP_CLOUD	= []
		devices	= ['usb', 'internal']
		rsync_unconfigurated	= (self.conf_RSYNC_SERVER =='' or self.conf_RSYNC_PORT =='' or self.conf_RSYNC_USER =='' or self.conf_RSYNC_PASSWORD =='' or self.conf_RSYNC_SERVER_MODULE =='')
		if not rsync_unconfigurated:
			for device in devices:
				self.MENU_BACKUP_CLOUD.append(
					{
						'type':		'item',
						'title':	'box_menu_backup_mode_{}|box_menu_to|box_menu_backup_mode_rsyncserver'.format(device),
						'action':	self.create_shell_action('sudo pkill -f "{}/backup*";sudo {}/backup.sh {} rsyncserver'.format(self.WORKING_DIR,self.WORKING_DIR,device)),
					}
				)

		cloudservices	= subprocess.check_output('sudo rclone config show --config "{}" | grep "^\[.*\]$" | sed "s/^\[//" | sed "s/\]$//"'.format(self.const_RCLONE_CONFIG_FILE),shell=True).decode('UTF-8').strip().split('\n')
		for cloudservice in cloudservices:
			if cloudservice != '':
				for device in devices:
					self.MENU_BACKUP_CLOUD.append(
						{
							'type':		'item',
							'title':	'box_menu_backup_mode_{}|box_menu_to|{}'.format(device,cloudservice),
							'action':	self.create_shell_action('sudo pkill -f "{}/backup*";sudo {}/backup.sh {} cloud_{}'.format(self.WORKING_DIR,self.WORKING_DIR,device,cloudservice)),
						}
					)

		self.MENU_BACKUP	= [
			{
				'type':		'menu',
				'title':	'box_menu_backup_local',
				'action':	self.MENU_BACKUP_LOCAL,
			},

			{
				'type':		'menu',
				'title':	'box_menu_backup_cloud',
				'action':	self.MENU_BACKUP_CLOUD,
			},

			{
				'type':		'menu',
				'title':	'box_menu_backup_stop',
				'action':	self.create_confirmed_shell_action('box_menu_backup_stop', 'sudo pkill -f "{}/backup*"'.format(self.WORKING_DIR)),
			},
		]

		self.MENU_NETWORK	= [
			{
				'type':		'item',
				'title':	'box_menu_ip',
				'action':	[
								{
									'type':		'info',
									'title':	'box_menu_ip',
									'action':	'ip',
								}
							],
			},

			{
				'type':		'item',
				'title':	'box_menu_comitup_reset',
				'action':	self.create_confirmed_shell_action('box_menu_comitup_reset','sudo comitup-cli d'),
			},
		]

		self.MENU_POWER	= [
			{
				'type':		'item',
				'title':	'box_menu_power_reboot',
				'action':	self.create_confirmed_shell_action('box_menu_power_reboot','sudo {}/poweroff.sh reboot force'.format(self.WORKING_DIR)),
			},

			{
				'type':		'item',
				'title':	'box_menu_power_shutdown',
				'action':	self.create_confirmed_shell_action('box_menu_power_shutdown','sudo {}/poweroff.sh poweroff force'.format(self.WORKING_DIR)),
			},
		]

		self.MENU_MAIN	= [
			{
				'type':		'menu',
				'title':	'box_menu_backup',
				'action':	self.MENU_BACKUP,
			},

			{
				'type':		'menu',
				'title':	'box_menu_network',
				'action':	self.MENU_NETWORK,
			},

			{
				'type':		'menu',
				'title':	'box_menu_system',
				'action':	self.MENU_POWER,
			},

		]

		# define menu variables an (re-)set them
		self.reset()

		self.Bouncetime = 100

		self.GPIO_init()

	def GPIO_init(self):
		GPIO.setmode(GPIO.BCM)
		GPIO.setwarnings(False)

		buttons	= {
				5:'up',
				6:'down',
				17:'left',
				27:'right'
				}

		self.buttonevent_timestamp = {}

		for pin in buttons.keys():
			self.GPIO_start_button(pin,buttons[pin])
			self.buttonevent_timestamp[pin] = 0

	def bouncing(self,channel):
		# GPIO bouncetime not always works as expected. This function filters bouncing.
		if channel == 0:
			return(False)

		if abs(time.time() - self.buttonevent_timestamp[channel]) >= self.Bouncetime/1000:
			self.buttonevent_timestamp[channel]	= time.time()
			return(False)
		else:
			return(True)


	def GPIO_start_button(self,pin,button):
		GPIO.setup(pin, GPIO.IN, pull_up_down = GPIO.PUD_DOWN)

		GPIO.remove_event_detect(pin)

		if button == 'up':
			GPIO.add_event_detect(pin, GPIO.RISING, callback = self.move_up, bouncetime=self.Bouncetime)
		elif button == 'down':
			GPIO.add_event_detect(pin, GPIO.RISING, callback = self.move_down, bouncetime=self.Bouncetime)
		elif button == 'left':
			GPIO.add_event_detect(pin, GPIO.RISING, callback = self.move_left, bouncetime=self.Bouncetime)
		elif button == 'right':
			GPIO.add_event_detect(pin, GPIO.RISING, callback = self.move_right, bouncetime=self.Bouncetime)


	def __del__(self):
		GPIO.cleanup()

	def reset(self):
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

	def check_timeout(self):
		if abs(time.time() - self.LAST_INPUT_TIME) >= self.const_MENU_TIMEOUT_SEC:
			self.reset()
		else:
			self.LAST_INPUT_TIME	= time.time()

	def create_confirmed_shell_action(self,title,command):
		return([
			{
				'type':		'confirmitem',
				'title':	title,
				'action':	self.create_shell_action(command,title),
			},
		])

	def create_shell_action(self,command,title=''):
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

	def translate(self,text):
		return (subprocess.check_output(['bash','-c',"source {}/lib-language.sh; l '{}'".format(self.WORKING_DIR,text)]).decode('UTF-8').strip())

	def get_INFO(self,action):
		if action == 'ip':
			IP		= subprocess.check_output(['bash','-c',"source {}/lib-network.sh; get_ip".format(self.WORKING_DIR)]).decode('UTF-8').strip()
			STATUS	= subprocess.check_output(['bash','-c',"source {}/lib-network.sh; get_internet_status".format(self.WORKING_DIR)]).decode('UTF-8').strip()
			return('"s=b:{}" "s=b:{}"'.format(IP, STATUS))

	def display(self):
		self.check_timeout()

		self.set_shift()

		frame_time	= 0

		LINES = ''
		n	= 0

		# define title
		self.HEAD_LINES = 0
		if self.MENU_LEVEL > 0:
			if self.MENU[self.MENU_LEVEL - 1][self.MENU_POS[self.MENU_LEVEL - 1]]['type'] == 'menu':
				HEAD_LINE = self.translate(self.MENU[self.MENU_LEVEL - 1][self.MENU_POS[self.MENU_LEVEL - 1]]['title'].strip())
				if HEAD_LINE != '':
					self.HEAD_LINES = 1
					LINES = '"s=h,u:{}" '.format(HEAD_LINE)

		# generate content
		for item in self.MENU[self.MENU_LEVEL]:

			if (n >= self.MENU_SHIFT[self.MENU_LEVEL]) and (n < self.DISPLAY_LINES + self.MENU_SHIFT[self.MENU_LEVEL] - self.HEAD_LINES):

				# Title can be combined by more than one part to translate separately.
				# Parts are separated by "|"
				TITLE = ''
				titleparts = self.MENU[self.MENU_LEVEL][n]['title'].split('|')
				for titlepart in titleparts:
					TITLE = "{}{} ".format(TITLE,self.translate(titlepart))

				# confirm item
				if self.MENU[self.MENU_LEVEL][n]['type'] == 'confirmitem':
					LINES	= '{}"s=b:{}" "s=b:{}" "s=h:{}"'.format(LINES,self.translate('box_menu_confirm'),TITLE,self.translate('box_menu_yes'))
					break

				elif self.MENU[self.MENU_LEVEL][n]['type'] == 'shell':
					print(self.MENU[self.MENU_LEVEL][self.MENU_POS[self.MENU_LEVEL]]['action'])
					commands	= self.MENU[self.MENU_LEVEL][self.MENU_POS[self.MENU_LEVEL]]['action'].split(';')
					for command in commands:
						print(command)
						subprocess.Popen(command, shell=True)

					self.reset()
					break

				elif self.MENU[self.MENU_LEVEL][n]['type'] == 'info':
					LINES	= '{}"s=h:{}" {}'.format(LINES,TITLE,self.get_INFO(self.MENU[self.MENU_LEVEL][n]['action']))
					frame_time = self.conf_DISP_FRAME_TIME * 4

					self.reset()
					break

				# menu or item
				elif self.MENU[self.MENU_LEVEL][n]['type'] in ['menu','item']:
					line	= TITLE

					if self.MENU[self.MENU_LEVEL][n]['type'] == 'menu':
						line = "+ {}".format(line)

					if n == self.MENU_POS[self.MENU_LEVEL]:
						style	= 's=h'
					else:
						style	= 's=b'

					LINES='{}"{}:{}" '.format(LINES,style,line)
				else:
					print('No known menu type: {}'.format(self.MENU[self.MENU_LEVEL][n]['type']))

			n += 1

		os.system("eval '{}/lib-display-helper.sh 'set:clear,time={}' {}'".format(self.WORKING_DIR,frame_time,LINES))

	def move_down(self,channel):
		if not self.bouncing(channel):

			if len(self.MENU[self.MENU_LEVEL]) > (self.MENU_POS[self.MENU_LEVEL] + 1):
				self.MENU_POS[self.MENU_LEVEL] += 1
			else:
				self.MENU_POS[self.MENU_LEVEL] = 0

			self.display()

	def move_up(self,channel):
		if not self.bouncing(channel):
			if self.MENU_POS[self.MENU_LEVEL] > 0:
				self.MENU_POS[self.MENU_LEVEL] += -1
			elif (len(self.MENU[self.MENU_LEVEL]) > 0):
				self.MENU_POS[self.MENU_LEVEL] = len(self.MENU[self.MENU_LEVEL]) - 1
			self.display()

	def move_right(self,channel):
		if not self.bouncing(channel):
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

	def move_left(self,channel):
		if not self.bouncing(channel):
			if self.MENU_LEVEL > 0:
				self.MENU_LEVEL += -1
			self.display()

##debug
#if __name__ == "__main__":
	#menuobj	= menu(5)
	##menuobj.move_right(0)#debug
	#menuobj.move_down(0)#debug
	#menuobj.move_down(0)#debug
	##menuobj.move_right(0)#debug
	##menuobj.move_down(0)#debug
	###menuobj.move_right(0)#debug
	###menuobj.move_right(0)#debug
	#time.sleep(10)
	#print('End.')
