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

from datetime import datetime, timedelta
import re

import lib_system

class progressmonitor(object):
	def __init__(self,setup,display,log,lan,FilesToProcess,DisplayLine1,DisplayLine2,SourceDevice=None,TargetDevice=None,vpn=False):
		self.__setup	= setup
		self.const_IMAGE_DATABASE_FILENAME			= self.__setup.get_val('const_IMAGE_DATABASE_FILENAME')
		self.conf_MAIL_NOTIFICATIONS				= self.__setup.get_val('conf_MAIL_NOTIFICATIONS')
		self.__conf_DISP_FRAME_TIME					= self.__setup.get_val('conf_DISP_FRAME_TIME')

		self.__display			= display	# display object
		self.__log				= log		# log object
		self.__lan				= lan		# language object
		self.FilesToProcess		= FilesToProcess
		self.SourceDevice		= SourceDevice
		self.TargetDevice		= TargetDevice
		self.vpn				= vpn

		self.StartTime			= lib_system.get_uptime_sec()
		self.StopTime			= 0
		self.CountProgress		= 0
		self.CountProgress_OLD	= -1
		self.CountJustCopied		= 0
		self.LastMessageTime	= 0
		self.TransferRate		= ''

		self.DisplayLine1	= DisplayLine1
		self.DisplayLine2	= DisplayLine2

		# start screen
		self.progress(CountProgress=0)


	def progress(self,TransferMode=None,SyncOutputLine='',CountProgress=None):
		SyncOutputLine	= SyncOutputLine.strip('\n')

		if CountProgress:
			self.CountProgress	= CountProgress

		if TransferMode == 'rsync':
			if len(SyncOutputLine) > 0:
				if SyncOutputLine[0] == ' ':
					# transfer info line? - get transfer data
					try:
						self.TransferRate	= SyncOutputLine.strip().split()[2]
					except:
						pass
				elif (
					(not ":" in SyncOutputLine) and
					(SyncOutputLine[-1] != '/') and
					(SyncOutputLine != 'Ignoring "log file" setting.') and
					(SyncOutputLine[0:5] != 'sent ') and
					(SyncOutputLine[0:14] != 'total size is ')
				):
					# interprete line as file
					self.CountProgress	+= 1
					self.CountJustCopied	+= 1

		elif TransferMode == 'gphoto2':
			if SyncOutputLine[0:6] == 'Saving' or  SyncOutputLine[0:4] == 'Skip':
				self.CountProgress	+= 1

				if SyncOutputLine[0:6] == 'Saving':
					self.CountJustCopied	+= 1

		if self.CountProgress > self.CountProgress_OLD:
			self.CountProgress_OLD	= self.CountProgress

			self.__display_progress()

	def __display_progress(self):
		if (
				(lib_system.get_uptime_sec() - self.LastMessageTime >= self.__conf_DISP_FRAME_TIME) or
				(self.CountProgress == 0) or
				(self.FilesToProcess == self.CountProgress)
		): # print changed progress

			if self.TransferRate in ['','0.00kB/s']:
				self.TransferRate	= ''
			else:
				self.TransferRate	= f", {self.TransferRate}"

			DisplayLine3	= f"{self.CountProgress} " + self.__lan.l('box_backup_of') + f" {self.FilesToProcess}{self.TransferRate}"

			# calculate progress
			PercentFinished	= None
			if self.FilesToProcess > 0:
				if self.CountProgress > 0:
					PercentFinished	= str(round(self.CountProgress / self.FilesToProcess * 100,1))
					DisplayLine5	= f"PGBAR={PercentFinished}"
				else:
					DisplayLine5	= self.__lan.l('box_backup_checking_old_files')

			else:
				DisplayLine5="PGBAR=0"

			# calculte remaining time
			if self.CountProgress > 0:

				TimeElapsed		= lib_system.get_uptime_sec() - self.StartTime
				TimeRemaining	= TimeElapsed * (self.FilesToProcess - self.CountProgress) / self.CountProgress
				TimeRemainingFormated	= str(timedelta(seconds=TimeRemaining)).split('.')[0]
			else:

				self.CountProgress	= 0
				TimeRemainingFormated	= '?'

			# DisplayLine4
			DisplayLine4	= f"{self.__lan.l('box_backup_time_remaining')}: {TimeRemainingFormated}"

			# DisplayLinesExtra
			DisplayLinesExtra	= []
			if self.vpn:
				DisplayLinesExtra.append(f"s=hc:VPN: {self.vpn.check_status(10)}")

			# FrameTime
			FrameTime	= self.__conf_DISP_FRAME_TIME
			if self.FilesToProcess == self.CountProgress:
				FrameTime	= self.__conf_DISP_FRAME_TIME * 2

			# Display
			self.__display.message([f"set:clear,time={FrameTime}",f"s=hc:{self.DisplayLine1}",f"s=hc:{self.DisplayLine2}",f"s=hc:{DisplayLine3}",f"s=hc:{DisplayLine4}",f"s=hc:{DisplayLine5}"] + DisplayLinesExtra)

			self.LastMessageTime=lib_system.get_uptime_sec()

class reporter(object):
	# collects information during the backup process and provides ready to use summarys
	def __init__(self,lan,SourceStorageType,SourceCloudService,SourceDeviceLbbDeviceID,TargetStorageType,TargetCloudService,TargetDeviceLbbDeviceID,TransferMode,SyncLog=True):

		self.__lan						= lan

		self.__SourceStorageType		= SourceStorageType
		self.__SourceCloudService		= SourceCloudService
		self.__SourceDeviceLbbDeviceID	= SourceDeviceLbbDeviceID

		self.__TargetStorageType		= TargetStorageType
		self.__TargetCloudService		= TargetCloudService
		self.__TargetDeviceLbbDeviceID	= TargetDeviceLbbDeviceID

		self.__TransferMode				= TransferMode

		self.__SyncLog				= SyncLog

		self.__Folder				=	None

		self.__BackupReports	= {}

		#shared values to use as output
		self.mail_subject			= ''
		self.mail_content_PLAIN		= ''
		self.mail_content_HTML		= ''

		self.display_summary		= []

	def new_folder(self,Folder):
		if not Folder:
			Folder	= '/'
		self.__Folder	= Folder

		self.__BackupReports[Folder]	= []


	def new_try(self):

		self.__BackupReports[self.__Folder].append({
			'FilesToProcess':		0,
			'FilesProcessed':		0,
			'FilesCopied'	:		0,
			'FilesToProcessPost':	0,
			'SyncReturnCode':		0,
			'SyncLogs':				[],
			'Results':				[],
			'Errors':				[]
		})

	def set_values(self,FilesToProcess=None,FilesProcessed=None,FilesCopied=None,FilesToProcessPost=None,SyncReturnCode=None):
		if not FilesToProcess is None:
			self.__BackupReports[self.__Folder][-1]['FilesToProcess']	= FilesToProcess

		if not FilesProcessed is None:
			self.__BackupReports[self.__Folder][-1]['FilesProcessed']	= FilesProcessed

		if not FilesCopied is None:
			self.__BackupReports[self.__Folder][-1]['FilesCopied']	= FilesCopied

		if not FilesToProcessPost is None:
			self.__BackupReports[self.__Folder][-1]['FilesToProcessPost']	= FilesToProcessPost

		if not SyncReturnCode is None:
			self.__BackupReports[self.__Folder][-1]['SyncReturnCode']	= SyncReturnCode

	def add_synclog(self,SyncLog=''):
		SyncLog	= SyncLog.strip()
		if self.__SyncLog and SyncLog:
			self.__BackupReports[self.__Folder][-1]['SyncLogs'].append(SyncLog)

	def add_result(self,Result=''):
		self.__BackupReports[self.__Folder][-1]['Results'].append(Result)

	def add_error(self,Error=''):
		self.__BackupReports[self.__Folder][-1]['Errors'].append(Error)

	def get_errors(self):
		return(self.__BackupReports[self.__Folder][-1]['Errors'])

	def prepare_mail(self):
		# provides self.mail_subject and self.mail_content_HTML

		CSS_margins_left_1	= 'margin-left:10px;margin-top:0;margin-bottom:0;'

		BackupComplete	= True

		# mail content
		self.mail_content_HTML	= f"<b>{self.__lan.l('box_backup_mail_backup_type')}:</b>"
		self.mail_content_HTML	+= f"\n<p style='{CSS_margins_left_1}'><b><u>{self.__lan.l(f'box_backup_mode_{self.__SourceStorageType}')} {self.__SourceCloudService} {self.__SourceDeviceLbbDeviceID}</u> {self.__lan.l('box_backup_mail_to')} <u>{self.__lan.l(f'box_backup_mode_{self.__TargetStorageType}')} {self.__TargetCloudService} {self.__TargetDeviceLbbDeviceID}</u></b></p></br>"

		separator	= False
		for Folder in self.__BackupReports:

			BackupComplete	= BackupComplete and (not self.__BackupReports[Folder][-1]['Errors'])

			if len(self.__BackupReports) > 1:

				if separator:
					self.mail_content_HTML	+= '\n</br>\n<hr style="width:50%;">\n</br>\n'

				separator	= True

				self.mail_content_HTML	+= f"<h3>{self.__lan.l('box_backup_folder')}: &quot;{Folder}&quot;</h3>"

			# Tries
			tryNumber	= len(self.__BackupReports[Folder]) + 1
			for Report in reversed(self.__BackupReports[Folder]):

				tryNumber	+= -1

				self.mail_content_HTML	+= f"\n\n  <h4>{tryNumber}. {self.__lan.l('box_backup_try')}</h4>\n"


				if not Report['Errors']:
					self.mail_content_HTML	+= f"\n    <p style='{CSS_margins_left_1}'>{self.__lan.l('box_backup_mail_backup_complete')}.</p>"
				else:
					if 'Err.Lost device!' in Report['Errors']:
						self.mail_content_HTML	+= f"\n    <p style='{CSS_margins_left_1}'>{self.__lan.l('box_backup_mail_lost_device')}.</p>"

					if 'Files missing!' in Report['Errors']:
						self.mail_content_HTML	+= f"\n    <p style='{CSS_margins_left_1}'>{self.__lan.l('box_backup_mail_files_missing')}.</p>"

					if 'Exception' in Report['Errors']:
						self.mail_content_HTML	+= f"\n    <p style='{CSS_margins_left_1}'>{self.__lan.l('box_backup_mail_exception')} {Report['SyncReturnCode']} ({self.sync_return_code_decoder(Report['SyncReturnCode'])}).</p>"

				if Report['FilesCopied'] == Report['FilesProcessed']:
					self.mail_content_HTML	+= f"\n    <p style='{CSS_margins_left_1}'>{Report['FilesToProcess'] - Report['FilesToProcessPost']} {self.__lan.l('box_backup_of')} {Report['FilesToProcess']} {self.__lan.l('box_backup_files_copied')}.</p>"
				else:
					self.mail_content_HTML	+= f"\n    <p style='{CSS_margins_left_1}'>{Report['FilesToProcess'] - Report['FilesToProcessPost']} {self.__lan.l('box_backup_of')} {Report['FilesToProcess']} {self.__lan.l('box_backup_files_copied')} ({Report['FilesCopied']} {self.__lan.l('box_backup_files_just_copied')})</p>"

			self.mail_content_HTML	+= f"<br>\n\n    <p style='{CSS_margins_left_1}'>{len(self.__BackupReports[Folder])} {self.__lan.l('box_backup_mail_tries_needed')}.</p>"

		# SyncLog
		if self.__SyncLog:
			self.mail_content_HTML	+= f"\n\n<br><h3>{self.__lan.l('box_backup_mail_log')}:</h3>"

			separator	= False
			for Folder in self.__BackupReports:

				if len(self.__BackupReports) > 1:

					if separator:
						self.mail_content_HTML	+= '\n</br>\n<hr style="width:50%;">\n</br>\n'

					separator	= True

					self.mail_content_HTML	+= f"<h3>{self.__lan.l('box_backup_folder')}: &quot;{Folder}&quot;</h3>"

				# Tries
				tryNumber	= len(self.__BackupReports[Folder]) + 1
				for Report in reversed(self.__BackupReports[Folder]):

					tryNumber	+= -1

					self.mail_content_HTML	+= f"\n\n  <h4>{tryNumber}. {self.__lan.l('box_backup_try')}</h4>\n"

					self.mail_content_HTML	+= f'<p style="{CSS_margins_left_1}">    '
					self.mail_content_HTML	+= '</br>\n    '.join(Report['SyncLogs'])
					self.mail_content_HTML	+= '</p>'

		self.mail_content_PLAIN	= self.__reformat_PLAIN(self.mail_content_HTML)

		# mail subject
		self.mail_subject	= 'Little Backup Box: '
		self.mail_subject	+= self.__lan.l('box_backup_mail_backup_complete') if BackupComplete else self.__lan.l('box_backup_mail_error')


	def __reformat_PLAIN(self,HTML):
		# translate HTML to text formatting

		replaceings	= {
			'<hr style="width:50%;">':			' *****',
			'&quot;':							'"',
		}

		PLAIN	= HTML
		for replaceing in replaceings:
			PLAIN	= PLAIN.replace(replaceing, replaceings[replaceing])

		#remove all HTML
		PLAIN	= re.sub(re.compile('<.*?>'),'',PLAIN)

		return(PLAIN)

	def prepare_display_summary(self):
		# provides self.display_summary

		ReTriesCountAll	= 0
		FilesToProcess	= 0
		FilesProcessed	= 0
		Errors			= []
		ErrorMessages	= []
		Completed		= True
		for Folder in self.__BackupReports:

			Completed	= True if Completed and not self.__BackupReports[Folder][-1]['Errors'] else False

			TriesCount		= 0
			for Report in (self.__BackupReports[Folder]):

				TriesCount		+= 1
				TriesCountAll	+= 1

				if (TriesCount == 1) and (Report['FilesToProcess'] > 0):
					FilesToProcess	+= Report['FilesToProcess']

				if Report['FilesProcessed'] > 0:
					FilesProcessed	+= Report['FilesProcessed']

				for Error in Report['Errors']:
					Errors	+= [f"{Report['SyncReturnCode']}:{Error}"]

		if Completed:
			self.display_summary.append(f":{self.__lan.l('box_backup_complete')}.")
		else:
			#remove duplicates in Errors
			Errors	= list(dict.fromkeys(Errors))

			for Error in Errors:
				ErrorSign	= Error.split(':',1)[1]
				if ErrorSign == 'Err.Lost device!':
					self.display_summary	+= [f":{self.__lan.l('box_backup_lost_device')}."]
				elif ErrorSign == 'Files missing!':
					self.display_summary	+= [f":{self.__lan.l('box_backup_files_missing')}."]
				elif ErrorSign == 'Exception':
					self.display_summary	+= [f":{self.sync_return_code_decoder(Error.split(':',1)[0])}"]

		self.display_summary.append(f":{FilesProcessed} {self.__lan.l('box_backup_of')} {FilesToProcess} {self.__lan.l('box_backup_files_copied')}")

		FailedAttemptsCount	= TriesCountAll - len(self.__BackupReports) # one try per folder is no retry
		self.display_summary.append(f":{FailedAttemptsCount} {self.__lan.l('box_backup_failed_attempts')}")

	def sync_return_code_decoder(self,Code):

		if not self.__TransferMode in ['rsync','gphoto2']:
			Code	= -1
			ERROR_TEXT	= {
				Code: "-"
			}

		if self.__TransferMode == 'gphoto2':
			#gphoto2-codes
			ERROR_TEXT	= {
				1:	'Err.: No camera found'
			}

		elif self.__TransferMode == 'rsync':
			#rsync-codes
			ERROR_TEXT	= {
				0:	'Success',
				1:	'Syntax or usage error',
				2:	'Protocol incompatibility',
				3:	'Err. selecting input/output files, dirs',
				4:	'Requested action not supported: an attempt was made to manipulate 64-bit files on a platform that cannot support them or an option was specified that is supported by the client and not by the server.',
				5:	'Err. starting client-server protocol',
				6:	'Daemon unable to append to log-file',
				10:	'Err. in socket I/O',
				11:	'Err. in file I/O',
				12:	'Err. in rsync protocol data stream',
				13:	'Err. with program diagnostics',
				14:	'Err. in IPC code',
				20:	'Received SIGUSR1 or SIGINT',
				21:	'Some error returned by waitpid()',
				22:	'Err. allocating core memory buffers',
				23:	'Partial transfer due to error',
				24:	'Partial transfer due to vanished source files',
				25:	'The --max-delete limit stopped deletions',
				30:	'Timeout in data send/receive',
				35:	'Timeout waiting for daemon connection'
			}

		try:
			return(f"{ERROR_TEXT[int(Code)]}, {self.__lan.l('box_backup_error_code')} '{Code}'")
		except:
			return(f"{self.__lan.l('box_backup_error_code')} '{Code}', {self.__lan.l('box_backup_exception')}")


