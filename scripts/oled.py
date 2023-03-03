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

def main(device, FontSize, Lines):

	if Lines[0][1:7] == "IMAGE:":
		# PRINT IMAGE FROM FILE
		# Only the first line can be interpreted as image. In This case no further text will be printed.
		# FORMAT: "IMAGE:filename"

		ImageLine = Lines[0].split(":")

		ImageFilename = ImageLine[1]

		image = Image.open(ImageFilename)
		image = image.convert('1')

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
			if maxLines > 5:
				maxLines = 5

			y_space = 0
			if maxLines > 1:
				Spare_Y = device.height - maxLines * line_height
				y_space = int (Spare_Y / (maxLines - 1))




			for n in range(0,maxLines):

				if Lines[n][0] == "+":
					fg_fill	= 255
					bg_fill	= 0
				else:
					fg_fill	= 0
					bg_fill	= 255

				y	= (n)*line_height + y_shift - 1 + y_space

				# Draw a filled box in case of inverted output
				if Lines[n][0] == "-":
					draw.rectangle((x, y, device.width, y + line_height), outline=bg_fill, fill=bg_fill)

				if Lines[n][1:7] == "IMAGE:":
					# it's not first line (else it would be catched above), just extract filename
					Lines[n] = ''

				if Lines[n][1:7] == "PGBAR:":
					try:
						progress	= float(Lines[n][7:])
					except ValueError:
						progress	= 0

					progress	= int(progress * 10 + 0.5) / 10

					if progress >= 100:
						# no decimals on 100%
						progress	= int(progress + 0.5)

					# define text to print
					Lines[n]	= " {}%".format(str(progress))

					(left, top, right, bottom) = draw.textbbox((0,0),"100%:",font=FONT)
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
				draw.text((x + 1, y), Lines[n][1:], font=FONT, fill=fg_fill)

	# save image ### for documentation only
	#image.save("/media/internal/{}.gif".format(time.time()))

if __name__ == "__main__":

	config = ConfigObj("{}/config.cfg".format(WORKING_DIR))
	conf_DISP_CONNECTION			= config['conf_DISP_CONNECTION']
	conf_DISP_DRIVER				= config['conf_DISP_DRIVER']
	conf_DISP_I2C_ADDRESS			= int(config['conf_DISP_I2C_ADDRESS'], 16)
	conf_DISP_SPI_PORT				= int(config['conf_DISP_SPI_PORT'])
	conf_DISP_RESOLUTION_X			= int(config['conf_DISP_RESOLUTION_X'])
	conf_DISP_RESOLUTION_Y			= int(config['conf_DISP_RESOLUTION_Y'])
	conf_DISP_COLOR_MODEL			= str(config['conf_DISP_COLOR_MODEL'])
	conf_DISP_FONT_SIZE				= int(config['conf_DISP_FONT_SIZE'])
	conf_DISP_BLACK_ON_POWER_OFF	= config['conf_DISP_BLACK_ON_POWER_OFF'] == "true"

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
	elif conf_DISP_DRIVER == "SH1106":
		device = sh1106(serial)
	else:
		exit('Error: No valid display driver')

	device.capabilities(conf_DISP_RESOLUTION_X,conf_DISP_RESOLUTION_Y,0,mode=conf_DISP_COLOR_MODEL)

	if conf_DISP_BLACK_ON_POWER_OFF:
		device.persist = True

	const_DISPLAY_CONTENT_FILE = "{}/tmp/display-content.txt".format(WORKING_DIR)

	# wait for file changed
	FileTime=0
	FileTimeNew=2

	while(True):
		if (os.path.isfile(const_DISPLAY_CONTENT_FILE)):
			FileTimeNew=os.path.getmtime(const_DISPLAY_CONTENT_FILE)

		if(FileTimeNew > (FileTime + 0.1)):

			FileTime = FileTimeNew

			Lines = ["+","+","+","+","+"]
			n=0
			with open(const_DISPLAY_CONTENT_FILE, 'r') as f:
				for Line in f:
					if n < 5:
						Lines[n] = Line.replace("\n", "")
					n = n + 1

			main(device,conf_DISP_FONT_SIZE,Lines)

		time.sleep(0.5)



