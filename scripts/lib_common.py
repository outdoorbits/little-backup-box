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

import subprocess
import time

def pipe(SourceCommand, FilterCommand):
	if SourceCommand and FilterCommand:
		SourceProcess	= subprocess.Popen(SourceCommand, stdout=subprocess.PIPE)
		result	= subprocess.check_output(FilterCommand,stdin=SourceProcess.stdout)
		SourceProcess.wait()
		return(result)
	else:
		return()

def join_threads(display, lan, threads, timeout):
	waiting_for_outgoing_mails	= False
	TimeLimit	= time.time() + timeout

	for thread in threads:
		if thread is None:
			continue

		while thread.is_alive() and time.time() <= TimeLimit:

			if not waiting_for_outgoing_mails:
				waiting_for_outgoing_mails	= True
				display.message([f":{lan.l('box_poweroff_waiting_outgoing_mails1')}", f":{lan.l('box_poweroff_waiting_outgoing_mails2')}"])

			thread.join(timeout=TimeLimit-time.time())
