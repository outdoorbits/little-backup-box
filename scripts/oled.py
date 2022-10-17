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
import subprocess
import sys

from board import SCL, SDA
import busio
from PIL import Image, ImageDraw, ImageFont
import adafruit_ssd1306

# get arguments
# accepts 5 pairs of arguments: Format ("pos" or "neg") and Line (Text)
# for progressbar use the Line-pattern "PGBAR:PERCENT", e.g. "PGBAR:61"
Format  = ["","","","","",""] # elements 0..4 (we need indexes 1..5)
Line    = ["","","","","",""] # elements 0..4 (we need indexes 1..5)

for n in range(1,6):
	Format[n]	= sys.argv[(n-1)*2+1]
	Line[n]		= sys.argv[(n-1)*2+2]

# Create the I2C interface.
i2c = busio.I2C(SCL, SDA)

# Create the SSD1306 OLED class.
# The first two parameters are the pixel width and pixel height.  Change these
# to the right size for your display!
disp = adafruit_ssd1306.SSD1306_I2C(128, 64, i2c)

# Clear display.
disp.fill(0)
disp.show()

# Create blank image for drawing.
# Make sure to create image with mode '1' for 1-bit color.
width = disp.width
height = disp.height

if Line[1][0:6] == "IMAGE:":
	# PRINT IMAGE FROM FILE
	# Only the first line can be interpreted as image. In This case no further text will be printed.
	# FORMAT: "IMAGE:filename"
	ImageLine = Line[1].split(":")

	ImageFilename = ImageLine[1]

	image = Image.open(ImageFilename)
	image = image.convert('1')

	draw = ImageDraw.Draw(image)

else:
	# PRINT TEXT LINES OR SPECIAL LINES
	image = Image.new("1", (width, height))

	# Get drawing object to draw on image.
	draw = ImageDraw.Draw(image)

	# define constants
	top = 0

	# define font
	font = ImageFont.load_default()
	# Alternatively load a TTF font.  Make sure the .ttf font file is in the
	# same directory as the python script!
	# Some other nice fonts to try: http://www.dafont.com/bitmap.php
	font = ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', 12)
	# Move left to right keeping track of the current x position for drawing shapes.
	x			= 0
	line_height	= int((height - top)/5)
	y_shift		= 0

	# Write lines
	for n in range(1,6):
		if Format[n] == "pos":
			fg_fill	= 255
			bg_fill	= 0
		else:
			fg_fill	= 0
			bg_fill	= 255

		y	= (n-1)*line_height

		# Draw a filled box to clear the image-line.
		draw.rectangle((x, top + y + y_shift, width, top + y + y_shift + line_height), outline=bg_fill, fill=bg_fill)

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
			pgbar_x_r	= width - 2
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
		draw.text((x + 2, top + y), Line[n], font=font, fill=fg_fill)

		y_shift	+= 1

# save image ### for documentation only
#image.save("/media/internal/{}.gif".format(time.time()))

# Display image.
disp.image(image)
disp.show()
