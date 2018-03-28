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

from bottle import post, route, request, redirect, template, run
import os

@route('/')
@route('/', method='POST')
def remote_control():
    st = os.statvfs("/home")
    free = "%.2f" % float((st.f_bavail * st.f_frsize)/1.073741824e9)
    if (request.POST.get("cardbackup")):
        os.system("sudo /home/pi/little-backup-box/scripts/card-backup.sh")
        return ('Backup started. You can close this page.')

    if (request.POST.get("camerabackup")):
        os.system("sudo /home/pi/little-backup-box/scripts/camera-backup.sh")
        return ('Backup started. You can close this page.')
    if (request.POST.get("devicebackup")):
        os.system("sudo /home/pi/little-backup-box/scripts/device-backup.sh")
        return ('Transfer started. You can close this page.')
    if (request.POST.get("shutdown")):
        os.system("sudo shutdown -h now")
        return ('Shutdown request sent. You can close this page.')
    return template('rc.tpl', freespace=free)
run(host="0.0.0.0", port=8080, debug=True, reloader=True)
