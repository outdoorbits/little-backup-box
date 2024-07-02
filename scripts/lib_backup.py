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

import lib_mail
import lib_system

#import lib_debug
#xx=lib_debug.debug()

class progressmonitor(object):
	def __init__(self,
			setup,
			display,
			log,
			lan,
			FilesToProcess,
			DisplayLine1,
			DisplayLine2,
			SourceDevice=None,
			TargetDevice=None,
			vpn=False
		):
		self.__setup	= setup
		self.const_IMAGE_DATABASE_FILENAME			= self.__setup.get_val('const_IMAGE_DATABASE_FILENAME')
		self.conf_MAIL_NOTIFICATIONS				= self.__setup.get_val('conf_MAIL_NOTIFICATIONS')
		self.__conf_DISP_FRAME_TIME					= self.__setup.get_val('conf_DISP_FRAME_TIME')

		self.__display					= display	# display object
		self.__log						= log		# log object
		self.__lan						= lan		# language object
		self.FilesToProcess				= FilesToProcess
		self.SourceDevice				= SourceDevice
		self.TargetDevice				= TargetDevice
		self.vpn						= vpn

		self.StartTime					= lib_system.get_uptime_sec()
		self.StopTime					= 0
		self.CountProgress				= 0
		self.CountProgress_OLD			= -1
		self.CountJustCopied			= 0
		self.CountFilesConfirmed		= 0
		self.CountFilesNotConfirmed		= 0
		self.countFilesMissing			= 0
		self.LastMessageTime			= 0
		self.TransferRate				= ''
		self.TIMSCopied					= False

		self.DisplayLine1	= DisplayLine1
		self.DisplayLine2	= DisplayLine2

		self.FilesList		= []

		# start screen
		self.progress(TransferMode='init', CountProgress=0)


	def progress(self, TransferMode=None, SyncOutputLine='', CountProgress=None):
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
					(SyncOutputLine[0:13] != 'total size is')
				):
					# interpret line as file
					self.CountProgress		+= 1

					if not self.TIMSCopied:
						self.TIMSCopied	= 'tims/' in SyncOutputLine

				elif 'Number of regular files transferred:' in SyncOutputLine:
					try:
						self.CountJustCopied	= int(SyncOutputLine.split(':')[1].strip())
					except:
						pass


		elif TransferMode == 'rclone':
			if len(SyncOutputLine) > 0:
				if SyncOutputLine[:2] == ' *' or SyncOutputLine.startswith == 'Transferred:':
					# transfer info line? - get transfer data
					try:
						self.TransferRate	= SyncOutputLine.split(',')[-2].strip()
					except:
						pass
				elif SyncOutputLine.endswith(': Copied (new)'):
					# interpret line as file
					self.CountProgress		+= 1
					self.CountJustCopied	+= 1

					if not self.TIMSCopied:
						self.TIMSCopied	= 'tims/' in SyncOutputLine

				elif SyncOutputLine.endswith(': Unchanged skipping'):
					self.CountProgress		+= 1

		elif TransferMode == 'gphoto2':
			if SyncOutputLine[0:6] == 'Saving' or  SyncOutputLine[0:4] == 'Skip':
				self.CountProgress	+= 1

				if SyncOutputLine[0:6] == 'Saving':
					self.CountJustCopied	+= 1

					self.FilesList	+= [SyncOutputLine.replace('Saving file as ', '')]
		elif TransferMode is None:
			self.CountProgress	+= 1

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
				TimeRemainingFormatted	= str(timedelta(seconds=TimeRemaining)).split('.')[0]
			else:

				self.CountProgress	= 0
				TimeRemainingFormatted	= '?'

			# DisplayLine4
			DisplayLine4	= f"{self.__lan.l('box_backup_time_remaining')}: {TimeRemainingFormatted}"

			# DisplayLinesExtra
			DisplayLinesExtra	= []
			if self.vpn:
				DisplayLinesExtra.append(f"s=hc:VPN: {self.vpn.check_status(10)}")

			# FrameTime
			FrameTime	= self.__conf_DISP_FRAME_TIME
			if self.FilesToProcess == self.CountProgress:
				FrameTime	= self.__conf_DISP_FRAME_TIME * 1.5

			# Display
			self.__display.message([f"set:clear,time={FrameTime}", f"s=hc:{self.DisplayLine1}", f"s=hc:{self.DisplayLine2}", f"s=hc:{DisplayLine3}", f"s=hc:{DisplayLine4}", f"s=hc:{DisplayLine5}"] + DisplayLinesExtra)

			self.LastMessageTime=lib_system.get_uptime_sec()

class reporter(object):
	# collects information during the backup process and provides ready to use summarys
	def __init__(self, lan, SourceStorageType, SourceCloudService, SourceDeviceLbbDeviceID, TargetStorageType, TargetCloudService, TargetDeviceLbbDeviceID, TransferMode, move_files, SourceWasTarget, SyncLog=True):

		self.__lan						= lan

		self.__SourceStorageType		= SourceStorageType
		self.__SourceCloudService		= SourceCloudService
		self.__SourceDeviceLbbDeviceID	= SourceDeviceLbbDeviceID

		self.__TargetStorageType		= TargetStorageType
		self.__TargetCloudService		= TargetCloudService
		self.__TargetDeviceLbbDeviceID	= TargetDeviceLbbDeviceID

		self.__TransferMode				= TransferMode

		self.__move_files				= move_files
		self.__SourceWasTarget			= SourceWasTarget

		self.__SyncLog				= SyncLog

		self.__Folder				=	None

		self.__BackupReports	= {}

		self.StartTime			= lib_system.get_uptime_sec()
		self.StopTime			= 0

		#shared values to use as output
		self.mail_subject			= ''
		self.mail_content_PLAIN		= ''
		self.mail_content_HTML		= ''

		self.display_summary		= []

	def new_folder(self, Folder):
		if not Folder:
			Folder	= '/'
		self.__Folder	= Folder

		self.__BackupReports[Folder]	= []


	def new_try(self):
		# append report
		self.__BackupReports[self.__Folder].append({
			'FilesToProcess':		0,
			'FilesProcessed':		0,
			'FilesCopied'	:		0,
			'FilesToProcessPost':	None,
			'SyncReturnCode':		0,
			'SyncLogs':				[],
			'Results':				[],
			'Errors':				[]
		})

	def set_values(self, FilesToProcess=None, FilesProcessed=None, FilesCopied=None, FilesToProcessPost=None, SyncReturnCode=None):
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

	def get_time_elapsed(self):
		if self.StopTime == 0:
			self.StopTime	= lib_system.get_uptime_sec()

		TimeElapsed	=  self.StopTime - self.StartTime

		return(str(timedelta(seconds=TimeElapsed)).split('.')[0].replace('day','d'))

	def prepare_mail(self):
		# provides self.mail_subject and self.mail_content_HTML

		CSS_margins_left_1		= 'margin-left:10px;margin-top:0;margin-bottom:0;'
		CSS_font_format_alert	= 'font-weight: bold; color: #ff0000;'

		BackupComplete	= True

		# mail content
		self.mail_content_HTML	= f"<h2>{self.__lan.l('box_backup_mail_summary')}:</h2>"

		self.mail_content_HTML	+= f"\n  <b>{self.__lan.l('box_backup_mail_backup_type')}:</b>"
		self.mail_content_HTML	+= f"\n    <p style='{CSS_margins_left_1}'><b>{self.__lan.l(f'box_backup_mode_{self.__SourceStorageType}')} '{self.__SourceCloudService}{' ' if self.__SourceDeviceLbbDeviceID else ''}{self.__SourceDeviceLbbDeviceID}'</b> {self.__lan.l('box_backup_mail_to')} <b>{self.__lan.l(f'box_backup_mode_{self.__TargetStorageType}')} '{self.__TargetCloudService}{' ' if self.__TargetDeviceLbbDeviceID else ''}{self.__TargetDeviceLbbDeviceID}'</b> ({self.__TransferMode})</br> \
		{self.__lan.l(f'box_backup_report_time_elapsed')}: {self.get_time_elapsed()}</b></p></br>\n"

		if self.__move_files:
			if self.__SourceWasTarget:
				self.mail_content_HTML	+= f"\n<p><b>{self.__lan.l('box_backup_mail_removed_source_blocked')}</b></p></br>\n"
			else:
				self.mail_content_HTML	+= f"\n<p><b>{self.__lan.l('box_backup_mail_removed_source')}</b></p></br>\n"

		separator	= False

		if not self.__BackupReports:
			self.new_folder('None')
			self.new_try()
			self.add_error('Err.: No backup!')

		for Folder in self.__BackupReports:

			BackupComplete	= BackupComplete and (not self.__BackupReports[Folder][-1]['Errors'])

			if separator:
				self.mail_content_HTML	+= '\n</br>\n<hr style="width:50%;">\n</br>\n'

			separator	= True

			# folder
			self.mail_content_HTML	+= f"\n  <h3>{self.__lan.l('box_backup_folder')}: &quot;{Folder}&quot;</h3>"

			# Tries
			tryNumber	= len(self.__BackupReports[Folder]) + 1
			for Report in reversed(self.__BackupReports[Folder]):

				tryNumber	+= -1

				self.mail_content_HTML	+= f"\n\n  <h4>{tryNumber}. {self.__lan.l('box_backup_try')}</h4>"

				if not Report['Errors']:
					self.mail_content_HTML	+= f"\n    <p style='{CSS_margins_left_1}'>{self.__lan.l('box_backup_mail_backup_complete')}.</p>"
				else:
					if 'Err.: No backup!' in Report['Errors']:
						self.mail_content_HTML	+= f"\n    <p style='{CSS_margins_left_1} {CSS_font_format_alert}'>{self.__lan.l('box_backup_mail_no_backup')}</p>"

					if 'Err.: Lost device!' in Report['Errors']:
						self.mail_content_HTML	+= f"\n    <p style='{CSS_margins_left_1} {CSS_font_format_alert}'>{self.__lan.l('box_backup_mail_lost_device')}</p>"

					if 'Err.: Remounting device failed!' in Report['Errors']:
						self.mail_content_HTML	+= f"\n    <p style='{CSS_margins_left_1} {CSS_font_format_alert}'>{self.__lan.l('box_backup_mail_remount_device_failed')}</p>"

					if 'Err.: Files missing!' in Report['Errors']:
						self.mail_content_HTML	+= f"\n    <p style='{CSS_margins_left_1} {CSS_font_format_alert}'>{self.__lan.l('box_backup_mail_files_missing')}</p>"

					if 'Err.: File validation(s) failed!' in Report['Errors']:
						self.mail_content_HTML	+= f"\n    <p style='{CSS_margins_left_1} {CSS_font_format_alert}'>{self.__lan.l('box_backup_mail_files_validation_failed')}</p>"

					if 'Exception' in Report['Errors']:
						self.mail_content_HTML	+= f"\n    <p style='{CSS_margins_left_1} {CSS_font_format_alert}'>{self.__lan.l('box_backup_mail_exception')} {Report['SyncReturnCode']} ({self.sync_return_code_decoder(Report['SyncReturnCode'])}).</p>"

				FilesCopiedAll	= Report['FilesToProcess'] - Report['FilesToProcessPost'] if not Report['FilesToProcessPost'] is None else '?'
				self.mail_content_HTML	+= f"\n    <p style='{CSS_margins_left_1}'>{FilesCopiedAll} {self.__lan.l('box_backup_of')} {Report['FilesToProcess']} {self.__lan.l('box_backup_files_copied')}. ({Report['FilesCopied']} {self.__lan.l('box_backup_files_just_copied')})</p>"

			self.mail_content_HTML	+= f"<br>\n\n    <p style='{CSS_margins_left_1}'>{len(self.__BackupReports[Folder])} {self.__lan.l('box_backup_mail_tries_needed')}.</p>"

		# SyncLog
		if self.__SyncLog:
			self.mail_content_HTML	+= f"\n\n<br><h2>{self.__lan.l('box_backup_mail_log')}:</h2>"

			separator	= False
			for Folder in self.__BackupReports:

				if separator:
					self.mail_content_HTML	+= '\n</br>\n<hr style="width:50%;">\n</br>\n'

				separator	= True

				# folder
				self.mail_content_HTML	+= f"\n  <h3>{self.__lan.l('box_backup_folder')}: &quot;{Folder}&quot;</h3>"

				# Tries
				tryNumber	= len(self.__BackupReports[Folder]) + 1
				for Report in reversed(self.__BackupReports[Folder]):

					tryNumber	+= -1

					self.mail_content_HTML	+= f"\n\n  <h4>{tryNumber}. {self.__lan.l('box_backup_try')}</h4>"

					self.mail_content_HTML	+= f'\n<p style="{CSS_margins_left_1}">    '
					self.mail_content_HTML	+= '</br>\n    '.join(Report['SyncLogs'])
					self.mail_content_HTML	+= '</p>'

		self.mail_content_PLAIN	= lib_mail.remove_HTML_tags(self.mail_content_HTML)

		# mail subject
		self.mail_subject	= 'Little Backup Box: '
		self.mail_subject	+= self.__lan.l('box_backup_mail_backup_complete') if BackupComplete else self.__lan.l('box_backup_mail_error')
		self.mail_subject	+= f" {self.__SourceDeviceLbbDeviceID} -> {self.__TargetDeviceLbbDeviceID}"

	def prepare_display_summary(self):
		# provides self.display_summary

		FailedTriesCountAll	= 0
		FilesToProcess	= 0
		FilesProcessed	= 0
		Errors			= []
		ErrorMessages	= []
		Completed		= True
		for Folder in self.__BackupReports:

			Completed	= True if Completed and not self.__BackupReports[Folder][-1]['Errors'] else False

			TriesCount		= 0
			for Report in (self.__BackupReports[Folder]): # tries

				TriesCount		+= 1

				FilesToProcessReport	= 0
				if Report['FilesToProcess'] > FilesToProcessReport:
					FilesToProcessReport	= Report['FilesToProcess']

				if Report['FilesProcessed'] > 0:
					FilesProcessed	+= Report['FilesProcessed']

				if Report['Errors']:
					FailedTriesCountAll	+= 1

					for Error in Report['Errors']:
						Errors	+= [f"{Report['SyncReturnCode']}:{Error}"]

			FilesToProcess	= FilesToProcessReport

		if Completed:
			self.display_summary.append(f":{self.__lan.l('box_backup_complete')}.")
		else:
			#remove duplicates in Errors
			Errors	= list(dict.fromkeys(Errors))

			for Error in Errors:
				ErrorSign	= Error.split(':',1)[1]
				if ErrorSign == 'Err.: Lost device!':
					self.display_summary	+= [f":{self.__lan.l('box_backup_lost_device')}."]
				elif ErrorSign == 'Err.: Files missing!':
					self.display_summary	+= [f":{self.__lan.l('box_backup_files_missing')}."]
				elif ErrorSign == 'Exception':
					self.display_summary	+= [f":{self.sync_return_code_decoder(Error.split(':',1)[0])}"]

		self.display_summary.append(f":{FilesProcessed} {self.__lan.l('box_backup_of')} {FilesToProcess} {self.__lan.l('box_backup_files_copied')}")
		self.display_summary.append(f":{FailedTriesCountAll} {self.__lan.l('box_backup_failed_attempts')}")
		self.display_summary.append(f":{self.__lan.l(f'box_backup_report_time_elapsed')}: {self.get_time_elapsed()}")


	def sync_return_code_decoder(self,Code):

		if not self.__TransferMode in ['rsync', 'rclone', 'gphoto2']:
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
		elif self.__TransferMode == 'rclone':
			#rclone-codes
			ERROR_TEXT	= {
				0:	'success',
				1:	'Syntax or usage error',
				2:	'Error not otherwise categorised',
				3:	'Directory not found',
				4:	'File not found',
				5:	'Temporary error (one that more retries might fix) (Retry errors)',
				6:	'Less serious errors (like 461 errors from dropbox) (NoRetry errors)',
				7:	'Fatal error (one that more retries won\'t fix, like account suspended) (Fatal errors)',
				8:	'Transfer exceeded - limit set by --max-transfer reached',
				9:	'Operation successful, but no files transferred (Requires --error-on-no-transfer)',
				10:	'Duration exceeded - limit set by --max-duration reached'
			}

		try:
			return(f"{ERROR_TEXT[int(Code)]}, {self.__lan.l('box_backup_error_code')} '{Code}'")
		except:
			return(f"{self.__lan.l('box_backup_error_code')} '{Code}', {self.__lan.l('box_backup_exception')}")


