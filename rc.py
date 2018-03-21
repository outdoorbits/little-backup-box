#!/usr/bin/python

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

from bottle import post, route, request, run
import os

@route('/')
@route('/', method='POST')
def remote_control():
    if (request.POST.get("cardbackup")):
            os.system("sudo /home/pi/little-backup-box/card-backup.sh")
    if (request.POST.get("camerabackup")):
            os.system("sudo /home/pi/little-backup-box/camera-backup.sh")
    if (request.POST.get("shutdown")):
            os.system("sudo shutdown -h now")
    return """
    <title>Little Backup Box</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <form method="POST" action="/">
    <div id="content"><p><input id="btn" name="cardbackup" type="submit" value="Card backup"></p>
    <p class="left">Back up a storage card connected via a card reader</p>
    <div id="content"><p><input id="btn" class="orange" name="camerabackup" type="submit" value="Camera backup"></p>
    <p class="left">Transfer files directly from the connected camera</p>
    <p><input id="btn" class="red" name="shutdown" value="Shut down" type="submit" /></p>
    <p class="left">Shut down the Little Backup Box</p>
    </form>
    <style>
    <link href="https://fonts.googleapis.com/css?family=Lato" rel="stylesheet">
    body {
        font: 15px/25px 'Lato', sans-serif;
    }
    p.left {
        text-align: left;
    }
    p.right {
        text-align: right;
    }
    #content {
        font: 15px/25px 'Open Sans', sans-serif;
        margin: 0px auto;
        width: 275px;
        text-align: left;
    }
    #btn {
        width: 11em;  height: 2em;
        background: #3399ff;
        border-radius: 5px;
        color: #fff;
        font-family: 'Lato', sans-serif; font-size: 25px; font-weight: 900;
        letter-spacing: 3px;
        border:none;
    }
    #btn.orange {
        background: #ff9900;
    }
    #btn.red {
        background: #cc0000;
    }
    </style>
    """
run(host="0.0.0.0", port=8080, debug=True, reloader=True)
