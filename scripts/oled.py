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
Format  = ["","","","",""] # elements 0..4 (we need indexes 1..4)
Line    = ["","","","",""] # elements 0..4 (we need indexes 1..4)

for n in range(1,5):
	Format[n]	= sys.argv[(n-1)*2+1]
	Line[n]		= sys.argv[(n-1)*2+2]

# Create the I2C interface.
i2c = busio.I2C(SCL, SDA)

# Create the SSD1306 OLED class.
# The first two parameters are the pixel width and pixel height.  Change these
# to the right size for your display!
disp = adafruit_ssd1306.SSD1306_I2C(128, 32, i2c)

# Clear display.
disp.fill(0)
disp.show()

# Create blank image for drawing.
# Make sure to create image with mode '1' for 1-bit color.
width = disp.width
height = disp.height
image = Image.new("1", (width, height))

# Get drawing object to draw on image.
draw = ImageDraw.Draw(image)

# Draw some shapes.
# First define some constants to allow easy resizing of shapes.
top = -2

# define font
font = ImageFont.load_default()
# Alternatively load a TTF font.  Make sure the .ttf font file is in the
# same directory as the python script!
# Some other nice fonts to try: http://www.dafont.com/bitmap.php
# font = ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', 9)

# Move left to right keeping track of the current x position for drawing shapes.
x			= 0
line_height	= int((height - top)/4)
y_shift		= 0

# Write lines
for n in range(1,5):
	if Format[n]	== "pos":
		fg_fill	= 255
		bg_fill	= 0
	else:
		fg_fill	= 0
		bg_fill	= 255

	y	= (n-1)*line_height

	# Draw a filled box to clear the image.
	draw.rectangle((x, top + y + y_shift, width, top + y + y_shift + line_height), outline=bg_fill, fill=bg_fill)

	# Write text
	draw.text((x + 2, top + y), Line[n], font=font, fill=fg_fill)

	y_shift	+= 1

# Display image.
disp.image(image)
disp.show()
