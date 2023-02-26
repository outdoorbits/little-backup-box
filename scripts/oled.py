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

from luma.core.interface.serial import i2c, spi, pcf8574
from luma.core.interface.parallel import bitbang_6800
from luma.core.render import canvas
from luma.oled.device import ssd1306, ssd1309, ssd1325, ssd1331, sh1106, sh1107, ws0010

from PIL import Image, ImageFont

# get arguments
# accepts 5 pairs of arguments: Format ("pos" or "neg") and Line (Text)
# for progressbar use the Line-pattern "PGBAR:PERCENT", e.g. "PGBAR:61"
Format  = ["","","","","",""] # elements 0..4 (we need indexes 1..5)
Line    = ["","","","","",""] # elements 0..4 (we need indexes 1..5)

I2C_ADDRESS=int(sys.argv[1], 16)

#I2C
serial = i2c(port=1, address=0x3C)
# substitute ssd1331(...) or sh1106(...) below if using that device
device = ssd1306(serial)

device.persist = True

#SPI
#serial = spi(port=0, device=0, gpio_DC=23, gpio_RST=24)
#device = pcd8544(serial)

for n in range(1,6):
	Format[n]	= sys.argv[(n-1)*2+2]
	Line[n]		= sys.argv[(n-1)*2+3]

def main(device):
	if Line[1][0:6] == "IMAGE:":
		# PRINT IMAGE FROM FILE
		# Only the first line can be interpreted as image. In This case no further text will be printed.
		# FORMAT: "IMAGE:filename"
		ImageLine = Line[1].split(":")

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

		# clear screen
		#device.clear()

		# Write lines
		with canvas(device) as draw:
			for n in range(1,6):
				if Format[n] == "pos":
					fg_fill	= 255
					bg_fill	= 0
				else:
					fg_fill	= 0
					bg_fill	= 255

				y	= (n-1)*line_height

				# Draw a filled box in case of inverted output
				if Format[n] == "neg":
					draw.rectangle((x, top + y + y_shift, device.width, top + y + y_shift + line_height), outline=bg_fill, fill=bg_fill)

				if Line[n][0:6] == "PGBAR:":
					try:
						progress	= float(Line[n][6:])
					except ValueError:
						progress	= 0

					progress	= int(progress * 10 + 0.5) / 10

					if progress >= 100:
						# no decimals on 100%
						progress	= int(progress + 0.5)

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

					# define text to print
					Line[n]	= str(progress) + "%"

				# Write text
				draw.text((x + 2, top + y), Line[n], font=FONT, fill=fg_fill)

				y_shift	+= 1

	# save image ### for documentation only
	#image.save("/media/internal/{}.gif".format(time.time()))

if __name__ == "__main__":
	try:
		serial = i2c(port=1, address=0x3C)

		device = ssd1306(serial)
		device.capabilities(128,64,0,mode='1')
		device.persist = True


		main(device)

	except KeyboardInterrupt:
		pass
