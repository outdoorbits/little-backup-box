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

from luma.core.interface.serial import i2c, spi, pcf8574
from luma.core.interface.parallel import bitbang_6800
from luma.core.render import canvas
from luma.oled.device import ssd1306, ssd1309, ssd1325, ssd1331, sh1106, sh1107, ws0010

from PIL import Image, ImageFont

I2C_ADDRESS=int(sys.argv[1], 16)

#SPI
#serial = spi(port=0, device=0, gpio_DC=23, gpio_RST=24)
#device = pcd8544(serial)

def main(device, Lines):

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
		# define constants
		top = 0

		# define font
		font_path = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'
		FONT = ImageFont.truetype(font_path, 12)

		x = 0
		line_height = int((device.height - top)/5)
		y_shift = 0

		# Write lines
		with canvas(device) as draw:

			for n in range(0,5):

				if Lines[n][0] == "+":
					fg_fill	= 255
					bg_fill	= 0
				else:
					fg_fill	= 0
					bg_fill	= 255

				y	= (n)*line_height

				# Draw a filled box in case of inverted output
				if Lines[n][0] == "-":
					draw.rectangle((x, top + y + y_shift, device.width, top + y + y_shift + line_height), outline=bg_fill, fill=bg_fill)

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

					# draw progressbar
					pg_y_space	= 2
					pgbar_x_l	= x + 42
					pgbar_x_r	= device.width - 2
					pgbar_y_u	= top + y + y_shift + pg_y_space
					pgbar_y_d	= top + y + y_shift + line_height - pg_y_space

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
				draw.text((x + 2, top + y), Lines[n][1:], font=FONT, fill=fg_fill)

				y_shift	+= 1

	# save image ### for documentation only
	#image.save("/media/internal/{}.gif".format(time.time()))

if __name__ == "__main__":
	try:
		serial = i2c(port=1, address=0x3C)
		device = ssd1306(serial)
	except:
		pass

	device.capabilities(128,64,0,mode='1')
	device.persist = True

	const_DISPLAY_CONTENT_FILE = '/var/www/little-backup-box/tmp/display-content.txt'

	# wait for file changed
	FileTime=0
	FileTimeNew=2

	while(True):
		if (os.path.isfile(const_DISPLAY_CONTENT_FILE)):
			FileTimeNew=os.path.getmtime(const_DISPLAY_CONTENT_FILE)

		if(FileTimeNew > (FileTime + 1)):

			FileTime = FileTimeNew

			Lines = ["+","+","+","+","+"]
			n=0
			with open(const_DISPLAY_CONTENT_FILE, 'r') as f:
				for Line in f:
					if n < 5:
						Lines[n] = Line.replace("\n", "")
					n = n + 1

			main(device,Lines)

		time.sleep(0.5)



