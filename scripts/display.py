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

# Display messages are read from const_DISPLAY_CONTENT_FOLDER
#
# format:
# FORMAT-OPTIONS:TEXT
#
# multiple format options can be separated by "," (without spaces)
#
# for standard-format just use ":"
#
## s: style
# s=b: BASIC
# s=h: HIGHLIGHT
# s=a: ALERT
#
## u: underline
#
## PGBAR
# s=b:PGBAR=20
#
## IMAGE
# IMAGE=PATH
#
#
# To set global parameters you can use a 'set:'-line:
# multiple options can be separated by "," (without spaces)
# set:clear,time=1.5,temp
# clear:	Do not display lines from old page
# time=2:	Display time in seconds (empty for standard config)
# temp:		show previous screen again after this
# hidden:	markertext to write into const_DISPLAY_CONTENT_OLD_FILE

import os
import shutil
import sys
import threading
import time

import lib_setup

from luma.core.interface.serial import i2c, spi, pcf8574
from luma.core.interface.parallel import bitbang_6800
from luma.core.render import canvas
from luma.oled.device import ssd1306, ssd1309, ssd1322, ssd1331, sh1106

from PIL import Image, ImageFont

import displaymenu
from lib_display import display_content_files

WORKING_DIR = os.path.dirname(__file__)

class DISPLAY(object):

	def __init__(self):
		# objects
		self.__setup					= lib_setup.setup()
		self.__display_content_files	= display_content_files(self.__setup)

		# setup
		self.conf_DISP_CONNECTION			= self.__setup.get_val('conf_DISP_CONNECTION')
		self.conf_DISP_DRIVER				= self.__setup.get_val('conf_DISP_DRIVER')
		self.conf_DISP_I2C_ADDRESS			= self.__setup.get_val('conf_DISP_I2C_ADDRESS')
		self.conf_DISP_SPI_PORT				= self.__setup.get_val('conf_DISP_SPI_PORT')
		self.conf_DISP_RESOLUTION_X			= self.__setup.get_val('conf_DISP_RESOLUTION_X')
		self.conf_DISP_RESOLUTION_Y			= self.__setup.get_val('conf_DISP_RESOLUTION_Y')
		self.conf_DISP_ROTATE				= self.__setup.get_val('conf_DISP_ROTATE')
		self.conf_DISP_CONTRAST				= self.__setup.get_val('conf_DISP_CONTRAST')
		self.conf_DISP_COLOR_MODEL			= self.__setup.get_val('conf_DISP_COLOR_MODEL')
		self.conf_DISP_COLOR_TEXT			= self.__setup.get_val('conf_DISP_COLOR_TEXT')
		self.conf_DISP_COLOR_HIGH			= self.__setup.get_val('conf_DISP_COLOR_HIGH')
		self.conf_DISP_COLOR_ALERT			= self.__setup.get_val('conf_DISP_COLOR_ALERT')
		self.conf_DISP_FONT_SIZE			= self.__setup.get_val('conf_DISP_FONT_SIZE')
		self.conf_DISP_BLACK_ON_POWER_OFF	= self.__setup.get_val('conf_DISP_BLACK_ON_POWER_OFF')
		self.conf_DISP_FRAME_TIME			= self.__setup.get_val('conf_DISP_FRAME_TIME')
		self.conf_MENU_ENABLED				= self.__setup.get_val('conf_MENU_ENABLED')

		self.const_DISPLAY_CONTENT_OLD_FILE	= self.__setup.get_val('const_DISPLAY_CONTENT_OLD_FILE')
		self.const_DISPLAY_LINES_LIMIT		= self.__setup.get_val('const_DISPLAY_LINES_LIMIT')
		self.const_FONT_PATH				= self.__setup.get_val('const_FONT_PATH')

		#define colors
		color = {}
		color['blue'] = (0, 0, 255)
		color['green'] = (0, 255, 0)
		color['red'] = (255, 0, 0)
		color['yellow'] = (255, 255, 0)
		color['orange'] = (255, 94, 14)
		color['white'] = (255, 255, 255)
		color['black'] = (0, 0, 0)
		color['lightgrey'] = (127, 127, 127)
		color['grey'] = (70, 70, 70)

		if self.conf_DISP_COLOR_MODEL == '1':
			self.color_text = 255
			self.color_high = 255
			self.color_alert = 255
			self.color_bg = 0
		else:
			self.color_text 	= color[self.conf_DISP_COLOR_TEXT]
			self.color_high 	= color[self.conf_DISP_COLOR_HIGH]
			self.color_alert 	= color[self.conf_DISP_COLOR_ALERT]
			self.color_bg 		= color['black']

			if self.conf_DISP_COLOR_MODEL == 'RGBA':
				# add alpha-channel
				self.color_text		= (*self.color_text, 255)
				self.color_high		= (*self.color_high, 255)
				self.color_alert	= (*self.color_alert, 255)
				self.color_bg		= (*self.color_bg, 255)

		self.hardware_ready	= True

		try:
			if self.conf_DISP_CONNECTION == 'I2C':
				serial = i2c(port=1, address=self.conf_DISP_I2C_ADDRESS)
			elif self.conf_DISP_CONNECTION == 'SPI':
				serial = spi(port=self.conf_DISP_SPI_PORT, device=0)
			else:
				print('Error: No valid connection type for display',file=sys.stderr)

		except:
			self.hardware_ready	= False
			print(f'Display connection to {self.conf_DISP_CONNECTION} could not be enabled.', file=sys.stderr)

		try:
			if self.conf_DISP_DRIVER == "SSD1306":
				self.device = ssd1306(serial)
			elif self.conf_DISP_DRIVER == "SSD1309":
				self.device = ssd1309(serial)
			elif self.conf_DISP_DRIVER == "SSD1322":
				self.device = ssd1322(serial)
			elif self.conf_DISP_DRIVER == "SSD1331":
				self.device = ssd1331(serial)
			elif self.conf_DISP_DRIVER == "SH1106":
				self.device = sh1106(serial)
			else:
				print('Error: No valid display driver', file=sys.stderr)
		except:
			self.hardware_ready	= False
			print(f'Display driver {self.conf_DISP_DRIVER} could not be enabled.', file=sys.stderr)

		if self.hardware_ready:
			self.device.capabilities(self.conf_DISP_RESOLUTION_X,self.conf_DISP_RESOLUTION_Y,self.conf_DISP_ROTATE,mode=self.conf_DISP_COLOR_MODEL)

			self.device.contrast(self.conf_DISP_CONTRAST)

			if self.conf_DISP_BLACK_ON_POWER_OFF:
				self.device.persist = True

		# define font
		self.FONT = ImageFont.truetype(self.const_FONT_PATH, self.conf_DISP_FONT_SIZE)

		# calculate line dimensions
		self.calculate_LineSize()

		## start display menu
		if self.conf_MENU_ENABLED:
			# start displaymenu as iternal background process
			watch_thread	= threading.Thread(target=displaymenu.menu, args=(self.maxLines,self.__setup))
			watch_thread.start()

	def calculate_LineSize(self):
		if self.hardware_ready:
			# calculate size of text
			with canvas(self.device) as draw:
				(left, top, right, bottom) = draw.textbbox((0,0),"gG",font=self.FONT)

			self.line_height = bottom - top

			self.maxLines = int(self.device.height / self.line_height)
			if self.maxLines > self.const_DISPLAY_LINES_LIMIT:
				self.maxLines = self.const_DISPLAY_LINES_LIMIT
		else:
			self.maxLines = self.const_DISPLAY_LINES_LIMIT

	def show(self, Lines):

		if ":IMAGE=" in Lines[0]:
			# PRINT IMAGE FROM FILE
			# Only the first line can be interpreted as image. In This case no further text will be printed.
			# FORMAT: "IMAGE=filename"

			Formatstring, Content = Lines[0].split(':',1)

			ImageLine = Content.split("=",1)
			ImageFilename = ImageLine[1]

			image = Image.open(ImageFilename).convert(self.device.mode).resize((self.device.width, self.device.height))

			self.device.display(image)

		else:
			# Write lines
			with canvas(self.device) as draw:

				# define constants
				x = 0
				y_shift = 0

				y_space = 0
				if self.maxLines > 1:
					Spare_Y = self.device.height - self.maxLines * self.line_height
					y_space = int (Spare_Y / (self.maxLines - 1))

				for n in range(0, self.maxLines):

					Line = Lines[n]

					# basic color settings
					fg_fill	= self.color_text
					bg_fill	= self.color_bg

					# basic text decoration settings
					underline = False

					Formatstring, Content = Line.split(':',1)
					Formats = Formatstring.split(',')

					for Format in Formats:

						if '=' in Format:
							FormatType, FormatValue = Format.split('=',1)
						else:
							FormatType = Format

						if FormatType == 's':
							if self.device.mode == '1':
								# black and white
								if FormatValue == 'h': # highlight
									fg_fill = self.color_bg
									bg_fill = self.color_text
								if FormatValue == 'a': # alert
									underline = True
							else:
								# RGB(A)
								if FormatValue == 'h' or FormatValue == 'hc': # highlight or highlight color
									fg_fill = self.color_high
								elif FormatValue == 'a': # alert
									if self.color_alert not in [self.color_text, self.color_high]:
										fg_fill = self.color_alert
									elif self.color_alert != self.color_high:
										fg_fill = self.color_alert
										bg_fill = self.color_high
									else:
										underline = True

						if FormatType == 'u':
							underline = True

					y	= (n)*self.line_height + y_shift - 1 + y_space

					# Draw a filled box in case of inverted output
					if bg_fill != self.color_bg:
						draw.rectangle((x, y, self.device.width, y + self.line_height), outline=bg_fill, fill=bg_fill)

					if Content[0:6] == "IMAGE=":
						Content = ''

					if Content[0:6] == "PGBAR=":
						try:
							progress	= float(Content[6:])
						except ValueError:
							progress	= 0

						progress	= int(progress * 10 + 0.5) / 10

						if progress >= 100:
							# no decimals on 100%
							progress	= int(progress + 0.5)

						# define text to print
						Content	= "{}%".format(str(progress))

						(left, top, right, bottom) = draw.textbbox((0,0),"100%::",font=self.FONT)
						pgbar_text_length = right - left

						# draw progressbar
						pg_space	= 2
						pgbar_x_l	= x + pgbar_text_length
						pgbar_x_r	= self.device.width - pg_space
						pgbar_y_u	= y + pg_space
						pgbar_y_d	= y + self.line_height - pg_space

						## draw outer frame
						draw.rectangle((pgbar_x_l, pgbar_y_u, pgbar_x_r, pgbar_y_d), outline=fg_fill, fill=bg_fill)

						## draw inner frame
						pgbar_x_l	= pgbar_x_l + 1
						pgbar_x_r	= pgbar_x_r - 1
						pgbar_y_u	= pgbar_y_u + 1
						pgbar_y_d	= pgbar_y_d - 1

						pgbar_x_r	= pgbar_x_l + (pgbar_x_r - pgbar_x_l) * progress / 100

						draw.rectangle((pgbar_x_l, pgbar_y_u, pgbar_x_r, pgbar_y_d), outline=bg_fill, fill=fg_fill)

					# Write text
					draw.text((x + 1, y), Content, font=self.FONT, fill=fg_fill)

					if underline:
						(left, top, right, bottom) = draw.textbbox((0,0),Content,font=self.FONT)
						draw.line((x + 1, y + self.line_height + y_space, x + 1 + right - left, y + self.line_height + y_space), fill=fg_fill, width=1)

	def main(self):
		# start endless loop to display content
		while(True):
			import_old_file 		= True
			temp_screen	= False
			hidden_info				= ''

			# check for new files earlier than conf_DISP_FRAME_TIME
			FrameTime = self.conf_DISP_FRAME_TIME / 4

			ContentFile	= self.__display_content_files.get_next_file_name()

			if ContentFile:

				# file could be in writing process, wait for minimal file age
				if time.time() - os.stat(ContentFile).st_mtime < 0.2:
					time.sleep(0.2)

				Lines = []
				FrameTime = self.conf_DISP_FRAME_TIME
				# read new lines

				with open(ContentFile, 'r') as CF:
					for Line in CF:

						Line = Line.strip()

						if Line[0:4] == 'set:': # global settings line

							settingStr = Line[4:]
							settings = settingStr.split(',')

							for setting in settings:

								if '=' in setting:
									SettingType, SettingValue = setting.split('=',1)
								else:
									SettingType = setting

								if SettingType == 'clear':
									import_old_file = False

								if SettingType == 'temp':
									temp_screen = True

								if SettingType == 'hidden':
									hidden_info	= SettingValue

								if SettingType == 'time' and float(SettingValue) >= 0:
									FrameTime	= float(SettingValue)

						elif len (Lines) < self.const_DISPLAY_LINES_LIMIT: # content line

							if Line:
								if (Line[0:1] == ':'):
										Line = "s=h{}".format(Line)
								elif ":" not in Line:
									Line = "s=h:{}".format(Line)

								Lines.append(Line)

				# read old lines
				if import_old_file:
					if len(Lines) < self.const_DISPLAY_LINES_LIMIT and os.path.isfile(self.const_DISPLAY_CONTENT_OLD_FILE):
						with open(self.const_DISPLAY_CONTENT_OLD_FILE, 'r') as oCF:
							for Line in oCF:

								Line = Line.strip()

								if len(Lines) < self.const_DISPLAY_LINES_LIMIT:
									Line = Line.split(':',1)[-1]
									if Line:
										if Line[0:4] != 'set:':
											Line = "s=b:{}".format(Line)
											Lines.append(Line)

				# fill line count to const_DISPLAY_LINES_LIMIT
				while len(Lines) < self.const_DISPLAY_LINES_LIMIT:
					Lines.append("s=b:")

				# remove content file
				os.remove(ContentFile)

				# display temp only:
				if temp_screen and os.path.isfile(self.const_DISPLAY_CONTENT_OLD_FILE):
					shutil.copyfile(self.const_DISPLAY_CONTENT_OLD_FILE, f'{ContentFile}')

					if hidden_info:
						with open(ContentFile, 'a') as newCF:
							newCF.write(f"\nset:hidden={hidden_info}")

				# move lines to old lines file
				if hidden_info:
					Lines.append(f"set:hidden={hidden_info}")
				with open(self.const_DISPLAY_CONTENT_OLD_FILE, 'w') as oCF:
					oCF.write("\n".join(Lines))

				if self.hardware_ready:
					self.show(Lines)

			time.sleep(FrameTime)

if __name__ == "__main__":
	display	= DISPLAY()
	display.main()

