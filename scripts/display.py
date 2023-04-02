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
# set:clear,time=1.5

import time
import sys
import os

from configobj import ConfigObj

from luma.core.interface.serial import i2c, spi, pcf8574
from luma.core.interface.parallel import bitbang_6800
from luma.core.render import canvas
from luma.oled.device import ssd1306, ssd1309, ssd1325, ssd1331, sh1106, sh1107, ws0010

from PIL import Image, ImageFont

WORKING_DIR = os.path.dirname(__file__)

def main(device, color_text, color_high, color_alert, color_bg, FontSize, Lines):

	if ":IMAGE=" in Lines[0]:
		# PRINT IMAGE FROM FILE
		# Only the first line can be interpreted as image. In This case no further text will be printed.
		# FORMAT: "IMAGE=filename"

		Formatstring, Content = Lines[0].split(':',1)

		ImageLine = Content.split("=",1)
		ImageFilename = ImageLine[1]

		image = Image.open(ImageFilename).convert(device.mode).resize((device.width, device.height))

		device.display(image)

	else:
		# Write lines
		with canvas(device) as draw:

			# define constants
			x = 0
			y_shift = 0

			# define font
			font_path = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'
			FONT = ImageFont.truetype(font_path, FontSize)

			# calculate size of text
			(left, top, right, bottom) = draw.textbbox((0,0),"gG",font=FONT)
			line_height = bottom - top

			maxLines = int(device.height / line_height)
			if maxLines > const_DISPLAY_LINES_LIMIT:
				maxLines = const_DISPLAY_LINES_LIMIT

			y_space = 0
			if maxLines > 1:
				Spare_Y = device.height - maxLines * line_height
				y_space = int (Spare_Y / (maxLines - 1))

			for n in range(0, maxLines):

				Line = Lines[n]

				# basic color settings
				fg_fill	= color_text
				bg_fill	= color_bg

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
						if device.mode == '1':
							# black and white
							if FormatValue == 'h': # highlight
								fg_fill = color_bg
								bg_fill = color_text
							if FormatValue == 'a': # alert
								underline = True
						else:
							# RGB(A)
							if FormatValue == 'h' or FormatValue == 'hc': # highlight or highlight color
								fg_fill = color_high
							elif FormatValue == 'a': # alert
								if color_alert != color_text and color_alert != color_high:
									fg_fill = color_alert
								elif color_alert != color_high:
									fg_fill = color_alert
									bg_fill = color_high
								else:
									underline = True

					if FormatType == 'u':
						underline = True

				y	= (n)*line_height + y_shift - 1 + y_space

				# Draw a filled box in case of inverted output
				if bg_fill != color_bg:
					draw.rectangle((x, y, device.width, y + line_height), outline=bg_fill, fill=bg_fill)

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

					(left, top, right, bottom) = draw.textbbox((0,0),"100%::",font=FONT)
					pgbar_text_length = right - left

					# draw progressbar
					pg_space	= 2
					pgbar_x_l	= x + pgbar_text_length
					pgbar_x_r	= device.width - pg_space
					pgbar_y_u	= y + pg_space
					pgbar_y_d	= y + line_height - pg_space

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
				draw.text((x + 1, y), Content, font=FONT, fill=fg_fill)

				if underline:
					(left, top, right, bottom) = draw.textbbox((0,0),Content,font=FONT)
					draw.line((x + 1, y + line_height + y_space, x + 1 + right - left, y + line_height + y_space), fill=fg_fill, width=1)



if __name__ == "__main__":

	config = ConfigObj("{}/config.cfg".format(WORKING_DIR))
	conf_DISP_CONNECTION			= config['conf_DISP_CONNECTION']
	conf_DISP_DRIVER				= config['conf_DISP_DRIVER']
	conf_DISP_I2C_ADDRESS			= int(config['conf_DISP_I2C_ADDRESS'], 16)
	conf_DISP_SPI_PORT				= int(config['conf_DISP_SPI_PORT'])
	conf_DISP_RESOLUTION_X			= int(config['conf_DISP_RESOLUTION_X'])
	conf_DISP_RESOLUTION_Y			= int(config['conf_DISP_RESOLUTION_Y'])
	conf_DISP_CONTRAST				= int(config['conf_DISP_CONTRAST'])
	conf_DISP_COLOR_MODEL			= str(config['conf_DISP_COLOR_MODEL'])
	conf_DISP_COLOR_TEXT			= str(config['conf_DISP_COLOR_TEXT'])
	conf_DISP_COLOR_HIGH			= str(config['conf_DISP_COLOR_HIGH'])
	conf_DISP_COLOR_ALERT			= str(config['conf_DISP_COLOR_ALERT'])
	conf_DISP_FONT_SIZE				= int(config['conf_DISP_FONT_SIZE'])
	conf_DISP_BLACK_ON_POWER_OFF	= config['conf_DISP_BLACK_ON_POWER_OFF'] == "true"
	conf_DISP_FRAME_TIME			= float(config['conf_DISP_FRAME_TIME'])

	constants = ConfigObj("{}/constants.sh".format(WORKING_DIR))
	const_DISPLAY_CONTENT_FOLDER	= constants['const_DISPLAY_CONTENT_FOLDER']
	const_DISPLAY_CONTENT_OLD_FILE	= constants['const_DISPLAY_CONTENT_OLD_FILE']
	const_DISPLAY_LINES_LIMIT		= int(constants['const_DISPLAY_LINES_LIMIT'])

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

	if conf_DISP_COLOR_MODEL == '1':
		color_text = 255
		color_high = 255
		color_alert = 255
		color_bg = 0
	else:
		color_text = color[conf_DISP_COLOR_TEXT]
		color_high = color[conf_DISP_COLOR_HIGH]
		color_alert = color[conf_DISP_COLOR_ALERT]
		color_bg = color['black']

		if conf_DISP_COLOR_MODEL == 'RGBA':
			# add alpha-channel
			color_text = (*color_text, 255)
			color_high = (*color_high, 255)
			color_alert = (*color_alert, 255)
			color_bg = (*color_bg, 255)

	if conf_DISP_CONNECTION == 'I2C':
		serial = i2c(port=1, address=conf_DISP_I2C_ADDRESS)
	elif conf_DISP_CONNECTION == 'SPI':
		serial = spi(port=conf_DISP_SPI_PORT, device=0)
	else:
		exit ('Error: No valid connection type for display')

	if conf_DISP_DRIVER == "SSD1306":
		device = ssd1306(serial)
	elif conf_DISP_DRIVER == "SSD1309":
		device = ssd1309(serial)
	elif conf_DISP_DRIVER == "SSD1322":
		device = ssd1322(serial)
	elif conf_DISP_DRIVER == "SSD1331":
		device = ssd1331(serial)
	elif conf_DISP_DRIVER == "SH1106":
		device = sh1106(serial)
	else:
		exit('Error: No valid display driver')

	device.capabilities(conf_DISP_RESOLUTION_X,conf_DISP_RESOLUTION_Y,0,mode=conf_DISP_COLOR_MODEL)

	device.contrast(conf_DISP_CONTRAST)

	if conf_DISP_BLACK_ON_POWER_OFF:
		device.persist = True

	while(True):
		frame_time = conf_DISP_FRAME_TIME
		import_old_file = True

		ContenFileList	= os.listdir(const_DISPLAY_CONTENT_FOLDER)
		if len(ContenFileList):

			ContenFileList.sort()
			ContentFile = "{}/{}".format(const_DISPLAY_CONTENT_FOLDER,ContenFileList[0])

			Lines = []
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

							if SettingType == 'time' and float(SettingValue) >= 0:
								frame_time = float(SettingValue)

					elif len (Lines) < const_DISPLAY_LINES_LIMIT: # content line

						if Line:
							if (Line[0:1] == ':'):
									Line = "s=h{}".format(Line)
							elif ":" not in Line:
								Line = "s=h:{}".format(Line)

							Lines.append(Line)

			# read old lines
			if import_old_file:
				if len(Lines) < const_DISPLAY_LINES_LIMIT and os.path.isfile(const_DISPLAY_CONTENT_OLD_FILE):
					with open(const_DISPLAY_CONTENT_OLD_FILE, 'r') as oCF:
						for Line in oCF:

							Line = Line.strip()

							if len(Lines) < const_DISPLAY_LINES_LIMIT:
								Line = Line.split(':',1)[-1]
								if Line:
									Line = "s=b:{}".format(Line)
									Lines.append(Line)

			# fill line count to const_DISPLAY_LINES_LIMIT
			while len(Lines) < const_DISPLAY_LINES_LIMIT:
				Lines.append("s=b:")

			# remove content file
			os.remove(ContentFile)

			# move lines to old lines file
			with open(const_DISPLAY_CONTENT_OLD_FILE, 'w') as oCF:
				oCF.write("\n".join(Lines))
				print("\n".join(Lines))

			main(device, color_text, color_high, color_alert, color_bg, conf_DISP_FONT_SIZE, Lines)

		time.sleep(frame_time)
