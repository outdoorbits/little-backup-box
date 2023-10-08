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
import os
import pathlib
import random
from string import digits
import subprocess
import sys
import time

import lib_common
import lib_cron_ip
import lib_display
import lib_language
import lib_log
import lib_mail
import lib_network
import lib_poweroff
import lib_setup
import lib_storage
import lib_system
import lib_view
import lib_vpn

# Error codes
# 101:		No valid Source
# 102:		No valid Target
# 103:		VPN failed
# 104:		Invalid combination of Source and target

class backup(object):

	class progressmonitor(object):
		def __init__(self,setup,display,log,lan,FilesToSync,DisplayLine1,DisplayLine2,SourceDevice=None,TargetDevice=None,vpn=False):
			self.__setup	= setup
			self.const_IMAGE_DATABASE_FILENAME			= self.__setup.get_val('const_IMAGE_DATABASE_FILENAME')
			self.conf_MAIL_NOTIFICATIONS				= self.__setup.get_val('conf_MAIL_NOTIFICATIONS')
			self.__conf_DISP_FRAME_TIME					= self.__setup.get_val('conf_DISP_FRAME_TIME')

			self.__display			= display	# display object
			self.__log				= log		# log object
			self.__lan				= lan		# language object
			self.FilesToSync		= FilesToSync
			self.SourceDevice		= SourceDevice
			self.TargetDevice		= TargetDevice
			self.vpn				= vpn

			self.StartTime			= lib_system.get_uptime_sec()
			self.StopTime			= 0
			self.CountProgress		= 0
			self.CountProgress_OLD	= -1
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

			elif TransferMode == 'gphoto2':
				if SyncOutputLine[0:6] == 'Saving' or  SyncOutputLine[0:4] == 'Skip':
					self.CountProgress	+= 1

			if self.CountProgress > self.CountProgress_OLD:
				self.CountProgress_OLD	= self.CountProgress
				self.__display_progress()

			return(self.CountProgress)


		def __display_progress(self):
			if (
					(lib_system.get_uptime_sec() - self.LastMessageTime >= self.__conf_DISP_FRAME_TIME) or
					(self.CountProgress == 0) or
					(self.FilesToSync == self.CountProgress)
			): # print changed progress

				if self.TransferRate in ['','0.00kB/s']:
					self.TransferRate	= ''
				else:
					self.TransferRate	= f", {self.TransferRate}"

				DisplayLine3	= f"{self.CountProgress} " + self.__lan.l('box_backup_of') + f" {self.FilesToSync}{self.TransferRate}"

				# calculate progress
				PercentFinished	= None
				if self.FilesToSync > 0:
					if self.CountProgress > 0:
						PercentFinished	= str(round(self.CountProgress / self.FilesToSync * 100,1))
						DisplayLine5	= f"PGBAR={PercentFinished}"
					else:
						DisplayLine5	= self.__lan.l('box_backup_checking_old_files')

				else:
					DisplayLine5="PGBAR=0"

				# calculte remaining time
				if self.CountProgress > 0:

					TimeElapsed		= lib_system.get_uptime_sec() - self.StartTime
					TimeRemaining	= TimeElapsed * (self.FilesToSync - self.CountProgress) / self.CountProgress
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
				if self.FilesToSync == self.CountProgress:
					FrameTime	= self.__conf_DISP_FRAME_TIME * 2

				# Display
				self.__display.message([f"set:clear,time={FrameTime}",f"s=hc:{self.DisplayLine1}",f"s=hc:{self.DisplayLine2}",f"s=hc:{DisplayLine3}",f"s=hc:{DisplayLine4}",f"s=hc:{DisplayLine5}"] + DisplayLinesExtra)

				self.LastMessageTime=lib_system.get_uptime_sec()

	def __init__(self, SourceName, TargetName, DoSyncDatabase=True, DoGenerateThumbnails=True, DoUpdateEXIF=True, DeviceIdentifierPresetSource=None, DeviceIdentifierPresetTarget=None, PowerOff=False, SecundaryBackupFollows=False):

		# SourceName:	one of ['usb', 'internal', 'camera', 'cloud:SERVICE_NAME', 'cloud_rsync'] or functions: ['thumbnails', 'database', 'exif']
		# TargetName:	one of ['usb', 'internal', 'cloud:SERVICE_NAME', 'cloud_rsync']
		# DoSyncDatabase, DoGenerateThumbnails, DoUpdateEXIF	True/False

		# Arguments
		self.SourceStorageType, CloudServiceSource			= lib_storage.extractCloudService(SourceName)
		TargetStorageType, CloudServiceTarget				= lib_storage.extractCloudService(TargetName)
		self.DoSyncDatabase									= DoSyncDatabase
		self.DoGenerateThumbnails							= DoGenerateThumbnails
		self.DoUpdateEXIF									= DoUpdateEXIF
		self.DeviceIdentifierPresetSource					= DeviceIdentifierPresetSource
		self.DeviceIdentifierPresetSource_blocked			= (self.DeviceIdentifierPresetSource != '')
		self.DeviceIdentifierPresetTarget					= DeviceIdentifierPresetTarget
		self.PowerOff										= PowerOff
		self.SecundaryBackupFollows							= SecundaryBackupFollows

		# Basics
		self.__WORKING_DIR	= os.path.dirname(__file__)

		# Setup
		self.__setup		= lib_setup.setup()
		self.const_SYNC_TIME_OVERHEATING_THRESHOLD_SEC	= self.__setup.get_val('const_SYNC_TIME_OVERHEATING_THRESHOLD_SEC')
		self.const_SYNC_TIME_OVERHEATING_WAIT_SEC		= self.__setup.get_val('const_SYNC_TIME_OVERHEATING_WAIT_SEC')
		self.const_IMAGE_DATABASE_FILENAME				= self.__setup.get_val('const_IMAGE_DATABASE_FILENAME')
		self.const_BACKUP_MAX_TRIES						= self.__setup.get_val('const_BACKUP_MAX_TRIES')

		self.conf_MAIL_NOTIFICATIONS					= self.__setup.get_val('conf_MAIL_NOTIFICATIONS')
		self.conf_LOG_SYNC								= self.__setup.get_val('conf_LOG_SYNC')

		if self.SourceStorageType == 'database':
			self.DoSyncDatabase			= True

		if self.DoGenerateThumbnails == 'setup':
			self.DoGenerateThumbnails					= self.__setup.get_val('conf_BACKUP_GENERATE_THUMBNAILS')
		if self.SourceStorageType == 'thumbnails':
			self.DoGenerateThumbnails	= True

		if self.DoUpdateEXIF == 'setup':
			self.DoUpdateEXIF							= self.__setup.get_val('conf_BACKUP_UPDATE_EXIF')
		if self.SourceStorageType == 'exif':
			self.DoUpdateEXIF			= True

		if self.PowerOff == 'setup':
			self.PowerOff								= self.__setup.get_val('conf_POWER_OFF')

		# Objects
		self.__display	= lib_display.display()
		self.__log		= lib_log.log()
		self.__lan		= lib_language.language()

		# Common variables
		self.SourceDevice		= None
		self.TargetDevice		= None
		self.SummaryDisplay		= []
		self.TransferInfoDisplay	= []

		# define TransferMode
		if self.SourceStorageType == 'camera':
			self.TransferMode	= 'gphoto2'
		else:
			self.TransferMode	= 'rsync'

		# Unmount devices
		lib_storage.umount(self.__setup,'all')

		# message mode info
		l_box_backup_mode_SOURCE_MODE	= f"box_backup_mode_{self.SourceStorageType}"
		l_box_backup_mode_TARGET_MODE	= f"box_backup_mode_{TargetStorageType}"

		self.__display.message([f":{self.__lan.l(l_box_backup_mode_SOURCE_MODE)} {CloudServiceSource}", f": > {self.__lan.l(l_box_backup_mode_TARGET_MODE)} {CloudServiceTarget}"])

		self.__log.message(f"Source: {self.SourceStorageType} {CloudServiceSource}")
		self.__log.message(f"Target: {TargetStorageType} {CloudServiceTarget}")

		# VPN start
		VPN_Mode	= None
		if 'cloud' in [self.SourceStorageType,TargetStorageType]:
			VPN_Mode	= self.__setup.get_val('conf_VPN_TYPE_CLOUD')
		elif 'cloud_rsync' in [self.SourceStorageType,TargetStorageType]:
			VPN_Mode	= self.__setup.get_val('conf_VPN_TYPE_RSYNC')

		self.vpn	= False
		if VPN_Mode in ['OpenVPN','WireGuard']:
			self.vpn	= lib_vpn.vpn(VPN_Mode)
			if self.vpn.start():
				lib_cron_ip.display_ip()
				lib_cron_ip.mail_ip()
			else:
				sys.exit(103)

		# MANAGE TARGET DEVICE
		# Set the PWR LED to blink short to indicate waiting for the target device
		lib_system.rpi_leds(trigger='timer',delay_on=250,delay_off=750)

		if TargetStorageType in ['usb', 'internal', 'cloud', 'cloud_rsync']:
			self.TargetDevice	= lib_storage.storage(TargetName, lib_storage.role_Target, True, self.DeviceIdentifierPresetSource, self.DeviceIdentifierPresetTarget)
			self.TargetDevice.mount()
		else:
			self.__display.message([self.__lan.l('box_backup_invalid_mode_combination_1'), self.__lan.l('box_backup_invalid_mode_combination_2'), self.__lan.l('box_backup_invalid_mode_combination_3')])
			sys.exit(102)

	def run(self):
		# Set the PWR LED ON to indicate that the backup has not yet started
		lib_system.rpi_leds(trigger='none',brightness=1)

		if self.TargetDevice and (self.SourceStorageType not in ['thumbnails', 'database', 'exif']):
			self.backup()

		if self.TargetDevice and self.DoSyncDatabase:
			self.syncDatabase()

		if self.TargetDevice and self.DoGenerateThumbnails:
			self.generateThumbnails()

		if self.TargetDevice and self.DoUpdateEXIF:
			self.updateEXIF()

		self.finish()

	def getRsyncOptions(self):
		RsyncOptions		= ["-avh", "--info=FLIST0,PROGRESS2", "--stats", "--no-owner", "--no-group", "--no-perms", "--mkpath", "--min-size=1", "--exclude", "*.id", "--exclude", self.const_IMAGE_DATABASE_FILENAME]

		# use compression for cloud syncs only
		if self.TargetDevice.isLocal and self.SourceDevice.isLocal:
			RsyncOptions	+= ["--no-compress", "--whole-file"]
		else:
			RsyncOptions	+= ["--compress"]

		return(RsyncOptions)

	def get_excludeTIMS(self):
		## don't sync tims to cloud
		excludeTIMS	= [] if self.TargetDevice.isLocal else ["--exclude", "*tims/"]

		return(excludeTIMS)

	def backup(self):
		if self.TargetDevice:

			# loop to backup multiple sources
			completedSources		= []
			RepeatBackupNextSource	= True
			while RepeatBackupNextSource:

				# MANAGE SOURCE DEVICE
				# Set the PWR LED to blink long to indicate waiting for the source device
				lib_system.rpi_leds(trigger='timer',delay_on=750,delay_off=250)

				if self.SourceStorageType in ['usb', 'internal', 'camera', 'cloud', 'cloud_rsync']:
					self.SourceDevice	= lib_storage.storage(self.SourceStorageType, lib_storage.role_Source, True, self.DeviceIdentifierPresetSource, self.DeviceIdentifierPresetTarget)

					self.SourceDevice.mount()

				elif self.SourceStorageType in ['thumbnails', 'database', 'exif']:
					pass
				else:
					self.__display.message([self.__lan.l('box_backup_invalid_mode_combination_1'), self.__lan.l('box_backup_invalid_mode_combination_2'), self.__lan.l('box_backup_invalid_mode_combination_3')])
					sys.exit(101)

				# check invalid combinations of Source and Target
				if (
					(self.SourceDevice.StorageType == self.TargetDevice.StorageType and not (self.SourceDevice.StorageType in ['usb', 'cloud'])) or				# usb to usb and cloud to cloud are the only methods where type of Source and Target can be equal
					(
						self.SourceDevice.StorageType == 'cloud' and
						self.TargetDevice.StorageType == 'cloud' and
						self.SourceDevice.CloudServiceName == self.TargetDevice.CloudServiceName
					) or																													# cloud to cloud only for different cloud services
					(self.SourceDevice.StorageType == 'cloud_rsync') or																		# cant write device identifier to rsync server
					(self.TargetDevice.StorageType == 'camera') or																			# camera never can be target
					(self.SourceDevice.StorageType	== 'camera' and self.TargetDevice.StorageType == 'cloud_rsync')							# camera can't rsync to rsyncserver as this is not supported by gphoto2
				):
					self.__display.message([f":{self.__lan.l('box_backup_invalid_mode_combination_1')}", f":{self.__lan.l('box_backup_invalid_mode_combination_2')}", f":{self.__lan.l('box_backup_invalid_mode_combination_3')}"])
					sys.exit(104)

				#run backup
				lib_system.rpi_leds(trigger='heartbeat')

				# define variables for backup
				SourceFolderNumber		= 0
				TriesCount				= []
				SyncLog					= ''
				FilesCountStoragePre	= 0
				FilesCountStoragePost	= 0
				FilesToSync				= []
				TransferInfoLong		= []
				MessageMailList			= []
				SyncErrorFinalRun		= []

				# define specific parameters
				RsyncOptions	= self.getRsyncOptions()
				excludeTIMS		= self.get_excludeTIMS()

				# SubPaths loop
				for SubPathAtSource in self.SourceDevice.SubPathsAtSource:

					self.__log.message(f"Backup from {self.SourceDevice.StorageType}: {SubPathAtSource}",3)

					#define SubPathAtSource specific values
					SourceFolderNumber	+= 1
					SourceFolderFracture	= f"{SourceFolderNumber}/{len(self.SourceDevice.SubPathsAtSource)}" if len(self.SourceDevice.SubPathsAtSource) > 1 else ''

					FilesToSync.append([])			# results in FilesToSync[SourceFolderNumber - 1]
					TransferInfoLong.append([])		# results in TransferInfoLong[SourceFolderNumber - 1]
					MessageMailList.append([])		# results in MessageMailList[SourceFolderNumber - 1]
					SyncErrorFinalRun.append([])	# results in SyncErrorFinalRun[SourceFolderNumber - 1]

					# prepare retry loop
					TriesCount.append(0) # index is (SourceFolderNumber - 1)
					SyncErrorTmp	= '-'

					while TriesCount[SourceFolderNumber - 1] < self.const_BACKUP_MAX_TRIES and SyncErrorTmp != '':
						TriesCount[SourceFolderNumber - 1]	+= 1
						FilesToSync[SourceFolderNumber - 1].append([])	# results in FilesToSync[SourceFolderNumber - 1][TriesCount[SourceFolderNumber - 1] - 1]
						TransferInfoLong[SourceFolderNumber - 1].append([])	# results in TransferInfoLong[SourceFolderNumber - 1][TriesCount - 1]
						MessageMailList[SourceFolderNumber - 1].append([])	# results in MessageMailList[SourceFolderNumber - 1][TriesCount - 1]
						MessageMailList[SourceFolderNumber - 1][TriesCount[SourceFolderNumber - 1] - 1]	= ''

						if SyncLog:
							SyncLog	+= '\n'

						SyncLog	+= "---- {} {} {} ----\n".format(self.__lan.l('box_backup_try'),TriesCount[SourceFolderNumber - 1],SubPathAtSource)
						if self.vpn:
							SyncLog	+= f"** VPN: {self.vpn.check_status(0)} **\n\n"

						if TriesCount[SourceFolderNumber - 1] > 1:
							self.__display.message([f"s=a:{self.__lan.l('box_backup_try_backup')} {TriesCount[SourceFolderNumber - 1]} {self.__lan.l('box_backup_of')} {self.const_BACKUP_MAX_TRIES}"])
							time.sleep(2)

						# Remount devices if "Err.Lost device"
						if "Err.Lost device" in SyncErrorTmp:
							self.__log.execute('Lost device: pre remount','lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE',3)

							if not self.TargetDevice.mounted():
								self.__log.message(f"remount target device {self.TargetDevice.StorageType} {self.TargetDevice.CloudServiceName} {self.TargetDevice.DeviceIdentifier}",3)
								self.TargetDevice.mount()

							if not self.SourceDevice.mounted():
								self.__log.message(f"remount source device {self.SourceDevice.StorageType} {self.SourceDevice.CloudServiceName} {self.SourceDevice.DeviceIdentifier}",3)
								self.SourceDevice.mount()

						# RUN BACKUP
						SyncErrorTmp	= ''
						SyncReturnCode	= 0

						if self.TargetDevice.mountable: # not cloud_rsync
							FilesCountStoragePre = self.get_FilesCount(f"{self.TargetDevice.MountPoint}/{self.SourceDevice.SubPathAtTarget}")

						self.__display.message([f":{self.__lan.l('box_backup_working')}..."])

						FilesToSync[SourceFolderNumber - 1][TriesCount[SourceFolderNumber - 1] - 1]	= self.calculate_files_to_sync(SubPathAtSource)

						self.__log.message(f"Files to sync before backup: {FilesToSync[SourceFolderNumber - 1][TriesCount[SourceFolderNumber - 1] - 1]}",3)

						#define progress object

						if self.SourceDevice.StorageType == 'camera' and self.SourceDevice.LbbDeviceID:
							SourceLabel	= self.SourceDevice.LbbDeviceID
						elif self.SourceDevice.StorageType == 'camera' and self.SourceDevice.DeviceIdentifier:
							SourceLabel	= self.SourceDevice.DeviceIdentifier
						else:
							if self.SourceDevice.StorageType in ['usb','camera']:
								SourceLabel	= self.__lan.l(f"box_backup_mode_{self.SourceDevice.StorageType}s")
							else:
								SourceLabel	= self.__lan.l(f"box_backup_mode_{self.SourceDevice.StorageType}")

						DisplayLine1	= SourceLabel + f" {self.SourceDevice.CloudServiceName}{SourceFolderFracture}"		# header1
						DisplayLine2	= ' > ' + self.__lan.l(f"box_backup_mode_{self.TargetDevice.StorageType}") + f" {self.TargetDevice.CloudServiceName}"	# header2

						progress	= self.progressmonitor(self.__setup, self.__display, self.__log, self.__lan, FilesToSync[SourceFolderNumber - 1][TriesCount[SourceFolderNumber - 1] - 1], DisplayLine1, DisplayLine2, SourceDevice=self.SourceDevice, TargetDevice=self.TargetDevice, vpn=self.vpn)

						SyncStartTime	= lib_system.get_uptime_sec()

						# RUN BACKUP
						## create target path if not exists and enter dir
						pathlib.Path(f"{self.TargetDevice.MountPoint}/{self.SourceDevice.SubPathAtTarget}").mkdir(parents=True, exist_ok=True)
						self.TargetDevice.set_perms_mountpoint()

	#					# gphoto2 backup
						if self.SourceDevice.StorageType == 'camera':
							# not mountable targets are excluded.

							os.chdir(f"{self.TargetDevice.MountPoint}/{self.SourceDevice.SubPathAtTarget}")

							# gphoto2: Filename-format at backup; %F is undocumented? = path of the file at the camera; $f = filename without suffix; %C=suffix
							Command	= ["gphoto2", "--camera", self.SourceDevice.DeviceIdentifier, "--filename", "%F/%f.%C", "--get-all-files", "--folder", SubPathAtSource, "--skip-existing"]
							self.__log.message(' '.join(Command),3)

							with subprocess.Popen(Command, stdout=subprocess.PIPE, stderr=subprocess.PIPE, bufsize=1, text=True) as BackupProcess:

								FilesSynced	= 0
								while True:
									SyncOutputLine = BackupProcess.stdout.readline()
									if not SyncOutputLine:
										break

									FilesSynced	= progress.progress(TransferMode='gphoto2',SyncOutputLine=SyncOutputLine)

									if self.conf_LOG_SYNC and SyncOutputLine.strip():
										SyncLog	+= f"{SyncOutputLine}"

								BackupProcess.wait()
								SyncReturnCode	= BackupProcess.returncode

							os.chdir(os.path.expanduser('~'))

	#					# rsync backup
						else:
							Command	= self.TargetDevice.rsyncSSH + ["rsync"] + RsyncOptions + excludeTIMS + [f"{self.SourceDevice.MountPoint}/{SubPathAtSource}", f"{self.TargetDevice.MountPoint}/{self.SourceDevice.SubPathAtTarget}"]
							self.__log.message(' '.join(Command),3)

							with subprocess.Popen(Command, stdout=subprocess.PIPE, stderr=subprocess.PIPE, bufsize=1, text=True)  as BackupProcess:

								FilesSynced	= 0
								while True:
									SyncOutputLine = BackupProcess.stdout.readline()
									if not SyncOutputLine:
										break

									FilesSynced	= progress.progress(TransferMode='rsync',SyncOutputLine=SyncOutputLine)

									if self.conf_LOG_SYNC and SyncOutputLine.strip():
										if (SyncOutputLine[0] != ' ') and (SyncOutputLine != './\n'):
											SyncLog	+= f"{SyncOutputLine}"

								BackupProcess.wait()
								SyncReturnCode	= BackupProcess.returncode

						SyncStopTime	= lib_system.get_uptime_sec()

						# VPN check
						if self.vpn:
							SyncLog	+= f"\n** VPN: {self.vpn.check_status(0)} **\n\n"

						# Remove empty files (maybe can result from disconnection of a source-device)
						if self.TargetDevice.isLocal and self.TargetDevice.mountable:
							Command	= ['find',f'{self.TargetDevice.MountPoint}/{self.SourceDevice.SubPathAtTarget}','-size','0','-delete']
							try:
								subprocess.run(Command)
							except:
								self.__log.message(f"Error: '{' '.join(Command)}'")

						# Re-calculate FilesToSync[SourceFolderNumber - 1][TriesCount[SourceFolderNumber - 1] - 1]
						FilesToSyncNew	= 0
						if self.SourceDevice.StorageType == 'camera':
							FilesToSyncNew	= FilesToSync[SourceFolderNumber - 1][TriesCount[SourceFolderNumber - 1] - 1] - FilesSynced
						elif self.TargetDevice.mountable:
							FilesCountStoragePost	= self.get_FilesCount(f"{self.TargetDevice.MountPoint}/{self.SourceDevice.SubPathAtTarget}")
							FilesToSyncNew	= FilesToSync[SourceFolderNumber - 1][TriesCount[SourceFolderNumber - 1] - 1] - FilesCountStoragePost + FilesCountStoragePre
						else:
							FilesToSyncNew	= self.calculate_files_to_sync(SubPathAtSource)

						FilesTransferred	= FilesToSync[SourceFolderNumber - 1][TriesCount[SourceFolderNumber - 1] - 1] - FilesToSyncNew

						TransferInfoLong[SourceFolderNumber - 1][TriesCount[SourceFolderNumber - 1] - 1]	= (f"{FilesTransferred} {self.__lan.l('box_backup_of')} {FilesToSync[SourceFolderNumber - 1][TriesCount[SourceFolderNumber - 1] - 1]} {self.__lan.l('box_backup_files_copied')}.") # results in 2 dimensional list [SourceFolderNumber - 1][TriesCount - 1]

						self.TransferInfoDisplay.append(f":{SourceFolderNumber}.: {FilesTransferred} {self.__lan.l('box_backup_of')} {FilesToSync[SourceFolderNumber - 1][TriesCount[SourceFolderNumber - 1] - 1]} {self.__lan.l('box_backup_files_copied')}")

						if FilesToSyncNew > 0 and self.SourceDevice.StorageType != 'camera': # camera FilesToSyncNew from calculate_files_to_sync is always the number of images at the camera
							SyncErrorTmp	+= " Files missing!"
							self.__log.message(f"Files missing: {FilesToSyncNew} files not synced.")
							self.__log.execute('Files missing', 'lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE', 3)
							self.__log.message(lib_system.get_abnormal_system_conditions(self.__lan),1)

						# check Returncode
						if SyncReturnCode != 0:
							SyncErrorTmp	+= " Exception"
							self.__log.message(f"Exception: {self.sync_return_code_decoder(SyncReturnCode)}")
							self.__log.message(lib_system.get_abnormal_system_conditions(self.__lan),1)

						# Check for lost devices
						lostTargetDevice	= False
						if self.TargetDevice.mountable:
							lostTargetDevice = not self.TargetDevice.mounted()
							self.__log.message(f"Lost target device {self.TargetDevice.StorageType}? {lostTargetDevice}",3)

						lostSourceDevice	= False
						if self.SourceDevice.mountable:
							lostSourceDevice = not self.SourceDevice.mounted()
							self.__log.message(f"Lost source device {self.SourceDevice.StorageType}? {lostSourceDevice}",3)

						if lostTargetDevice or lostSourceDevice:
							SyncErrorTmp	+= ' Err.Lost device!'
							self.__log.execute("Lost device", "lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE",3)
							self.__log.message(lib_system.get_abnormal_system_conditions(self.__lan),1)


						# Controller- overheating-error?
						SyncTimeDiff	= SyncStopTime - SyncStartTime

						if (
							'Err.Lost device!' in SyncErrorTmp and
							SyncReturnCode > 0 and
							SyncTimeDiff >= self.const_SYNC_TIME_OVERHEATING_THRESHOLD_SEC and
							self.const_BACKUP_MAX_TRIES >  TriesCount[SourceFolderNumber - 1]
							):
								self.__display.message([
									f"s=a:{self.__lan.l('box_backup_error_cooling_1')}",
									f":{self.__lan.l('box_backup_error_cooling_2')} {self.__setup.get_val('const_SYNC_TIME_OVERHEATING_WAIT_SEC')} {self.__lan.l('seconds_short')} ...",
									f":{self.__lan.l('box_backup_error_cooling_3')}",f":{self.__lan.l('box_backup_error_cooling_4')}"
									])
								time.sleep(self.const_SYNC_TIME_OVERHEATING_WAIT_SEC)

						# prepare message for mail and for power off
						SourceFolderFractureTmp	= f"{self.__lan.l('box_backup_folder')} {SourceFolderFracture}: " if SourceFolderFracture else ''

						self.SummaryDisplay.append(f":{self.__lan.l('box_backup_try')} {TriesCount[SourceFolderNumber - 1]}")

						if not SyncErrorTmp:
							SyncErrorFinalRun[SourceFolderNumber - 1]	= False

							MessageMailList[SourceFolderNumber - 1][TriesCount[SourceFolderNumber - 1] - 1]	+= f" {SourceFolderFractureTmp}{self.__lan.l('box_backup_mail_backup_complete')}.\n"
							self.SummaryDisplay.append(f":{SourceFolderFractureTmp}{self.__lan.l('box_backup_complete')}.")
							SourceFolderFractureTmp	= ''

						else:
							SyncErrorFinalRun[SourceFolderNumber - 1]	= True

							if 'Err.Lost device!' in SyncErrorTmp:
								MessageMailList[SourceFolderNumber - 1][TriesCount[SourceFolderNumber - 1] - 1]	+= f" {SourceFolderFractureTmp}{self.__lan.l('box_backup_mail_lost_device')}.\n"
								self.SummaryDisplay.append(f":{SourceFolderFractureTmp}{self.__lan.l('box_backup_lost_device')}.")
								SourceFolderFractureTmp	= ''

							if 'Files missing!' in SyncErrorTmp:
								MessageMailList[SourceFolderNumber - 1][TriesCount[SourceFolderNumber - 1] - 1]	+= f" {SourceFolderFractureTmp}{self.__lan.l('box_backup_mail_files_missing')}.\n"
								self.SummaryDisplay.append(f":{SourceFolderFractureTmp}{self.__lan.l('box_backup_files_missing')}.")
								SourceFolderFractureTmp	= ''

							if 'Exception' in SyncErrorTmp:
								ErrorCodeInfo	= f"{SyncReturnCode} ({self.sync_return_code_decoder(SyncReturnCode)})"
								MessageMailList[SourceFolderNumber - 1][TriesCount[SourceFolderNumber - 1] - 1]	+= f" {SourceFolderFractureTmp}{self.__lan.l('box_backup_mail_exception')} {ErrorCodeInfo}.\n"
								self.SummaryDisplay.append(f":{SourceFolderFractureTmp}{self.__lan.l('box_backup_exception')} {ErrorCodeInfo}.")
								SourceFolderFractureTmp	= ''


						del progress

				# Mail result
				if self.conf_MAIL_NOTIFICATIONS:
					mail	= lib_mail.mail()
					if (
						len(MessageMailList) and
						mail.mail_configured() and
						lib_network.get_internet_status()
						): # Check internet connection

						# prepare subject
						if True in SyncErrorFinalRun:
							MailSubject	= self.__lan.l('box_backup_mail_error')
						else:
							MailSubject	= self.__lan.l('box_backup_mail_backup_complete')

						# create mail summary
						SubPathAtSourceNumber	= 0
						MailSummary=''
						for SubPathAtSource in self.SourceDevice.SubPathsAtSource:
							SubPathAtSourceNumber	+= 1
							if MailSummary:
								MailSummary	+= '\n'

							# Tries
							MailSummary	+= f"{SubPathAtSourceNumber}.: {SubPathAtSource}\n"
							for Try in range(TriesCount[SubPathAtSourceNumber - 1]):
								MailSummary	+= f" {Try + 1}. {self.__lan.l('box_backup_try')}\n"
								MailSummary	+= f"{MessageMailList[SubPathAtSourceNumber - 1][Try]}"
								MailSummary	+= f" {TransferInfoLong[SubPathAtSourceNumber - 1][Try]}\n"

							MailSummary	+= f"\n {TriesCount[SubPathAtSourceNumber - 1]} {self.__lan.l('box_backup_mail_tries_needed')}.\n"


						MailBody	= "{}: ".format(self.__lan.l('box_backup_mail_backup_type'))
						MailBody	+= self.__lan.l(f"box_backup_mode_{self.SourceDevice.StorageType}") + f" {self.SourceDevice.CloudServiceName} {self.SourceDevice.LbbDeviceID}" + ' ' + self.__lan.l('box_backup_mail_to') + ' ' + self.__lan.l(f"box_backup_mode_{self.TargetDevice.StorageType}") + f" {self.TargetDevice.CloudServiceName} {self.TargetDevice.LbbDeviceID}" + '\n\n'

						MailBody	+= MailSummary + '\n\n'

						MailBody	+= self.__lan.l('box_backup_mail_log') + ':\n'
						MailBody	+= SyncLog

						mail.sendmail(f"Little Backup Box: {MailSubject}",MailBody)

				#end loop RepeatBackupNextSource
				if (self.SourceDevice.StorageType in ['usb', 'camera']):
					completedSources.append(self.SourceDevice.DeviceIdentifier)

					if self.SourceDevice.StorageType == 'usb':
						availableSources	= lib_storage.get_available_partitions(self.__setup,self.TargetDevice.DeviceIdentifier,completedSources)
					elif self.SourceDevice.StorageType == 'camera':
						availableSources	= lib_storage.get_available_cameras()
						# remove disconnected cameras from completedSources
						completedSources	= list(set(completedSources) & set(availableSources))

					todoSources	= list(set(availableSources) - set(completedSources))

					if todoSources and (not self.DeviceIdentifierPresetSource_blocked):
						self.DeviceIdentifierPresetSource	= todoSources[0]
					else:
						RepeatBackupNextSource	= False

				else:
					RepeatBackupNextSource	= False

			self.__display.message([f":{self.__lan.l('box_finished')}"])

		# VPN stop
		if self.vpn:
			self.vpn.stop()
			del self.vpn
			lib_cron_ip.display_ip()
			lib_cron_ip.mail_ip()

	def syncDatabase(self):
		if self.TargetDevice:
			if self.TargetDevice.isLocal:
				lib_system.rpi_leds(trigger='timer',delay_on=100,delay_off=900)
				# prepare database
				db	= lib_view.viewdb(self.__setup,self.__log,self.TargetDevice.MountPoint)

				## clean database
				# remove duplicates
				db.dbExecute("delete from EXIF_DATA where ID not in (select min(ID) from EXIF_DATA group by File_Name, Directory);")

				KnownFilesList	= db.dbSelect("select ID, Directory || '/' || File_Name as DirFile from EXIF_DATA")

				FilesToSync	=	len(KnownFilesList)
				DisplayLine1	= self.__lan.l('box_backup_cleaning_database')						# header1
				DisplayLine2	= self.__lan.l(f"box_backup_mode_{self.TargetDevice.StorageType}")	# header2

				progress	= self.progressmonitor(self.__setup,self.__display,self.__log,self.__lan,FilesToSync,DisplayLine1,DisplayLine2)

				CountProgress	= 0
				for KnownFile in KnownFilesList:
					CountProgress	+= 1

					ID			= KnownFile[0]
					FileName	= f"{self.TargetDevice.MountPoint}/{KnownFile[1].strip('/')}"

					if not os.path.isfile(FileName):
						db.dbExecute(f"DELETE from EXIF_DATA WHERE ID={ID};")
						self.__log.message(f"DELETE from EXIF_DATA WHERE ID={ID};", 3)

					progress.progress(CountProgress=CountProgress)

				del progress

				# vacuum database
				db.dbExecute('VACUUM;')

				# import preexisting tims into database
				self.__display.message(['set:clear',f":{self.__lan.l('box_backup_generating_database_finding_images1')}",':' + self.__lan.l(f"box_backup_mode_{self.TargetDevice.StorageType}"),f":{self.__lan.l('box_backup_counting_images')}",f":{self.__lan.l('box_backup_generating_database_finding_images3')}"])

				# find all tims and convert their filename to the estimated original filename:
				## 1. replace only last '/tims/' by '/'
				## 2. remove last part of file extension

				BannedPathsViewCaseInsensitive	= self.get_BannedPathsViewCaseInsensitive()

				Command	= ["find", self.TargetDevice.MountPoint, "-type", "f", "-iname", "*.jpg", "-path", "*/tims/*"] + BannedPathsViewCaseInsensitive
				TIMSList	= subprocess.check_output(Command).decode().strip().split('\n')
				# prepare loop to insert images into the database
				FilesToSync	= len(TIMSList)

				DisplayLine1	= self.__lan.l('box_backup_generating_database')					# header1
				DisplayLine2	= self.__lan.l(f'box_backup_mode_{self.TargetDevice.StorageType}')	# header2

				progress	= self.progressmonitor(self.__setup,self.__display,self.__log,self.__lan,FilesToSync,DisplayLine1,DisplayLine2)

				CountProgress	= 0
				for TimsFileName in TIMSList:
					CountProgress	+= 1
					OrigFileName	= TimsFileName.replace(self.TargetDevice.MountPoint,'',1).rsplit('.',1)[0]	# remove mountpoint and remove second extension from tims
					OrigFileName	= '/'.join(OrigFileName.rsplit('/tims/', 1)) 								# remove /tims from folder
					ImageFilePath	= os.path.dirname(OrigFileName)
					ImageFileName	= os.path.basename(OrigFileName)
					if not db.dbSelect(f"select ID from EXIF_DATA where File_Name='{ImageFileName}' and Directory='{ImageFilePath}'"):
						db.dbInsertImage(OrigFileName)

					progress.progress(CountProgress=CountProgress)

				del progress
				self.__display.message([f":{self.__lan.l('box_finished')}"])

	def generateThumbnails(self):
		if self.TargetDevice:
			if self.TargetDevice.isLocal:

				lib_system.rpi_leds(trigger='timer',delay_on=900,delay_off=100)

				const_FILE_EXTENSIONS_LIST_WEB_IMAGES		= self.__setup.get_val('const_FILE_EXTENSIONS_LIST_WEB_IMAGES')
				const_FILE_EXTENSIONS_LIST_HEIC		= self.__setup.get_val('const_FILE_EXTENSIONS_LIST_HEIC')
				const_FILE_EXTENSIONS_LIST_RAW		= self.__setup.get_val('const_FILE_EXTENSIONS_LIST_RAW')
				const_FILE_EXTENSIONS_LIST_TIF		= self.__setup.get_val('const_FILE_EXTENSIONS_LIST_TIF')
				const_FILE_EXTENSIONS_LIST_VIDEO	= self.__setup.get_val('const_FILE_EXTENSIONS_LIST_VIDEO')
				const_FILE_EXTENSIONS_LIST_AUDIO	= self.__setup.get_val('const_FILE_EXTENSIONS_LIST_AUDIO')
				conf_VIEW_CONVERT_HEIC				= self.__setup.get_val('conf_VIEW_CONVERT_HEIC')

				# prepare database
				db	= lib_view.viewdb(self.__setup,self.__log,self.TargetDevice.MountPoint)


				self.__display.message([
					"set:clear",
					f":{self.__lan.l('box_backup_generating_thumbnails_finding_images1')}",
					':' + self.__lan.l(f"box_backup_mode_{self.TargetDevice.StorageType}"),
					f":{self.__lan.l('box_backup_counting_images')}",
					f":{self.__lan.l('box_backup_generating_thumbnails_finding_images3')}"
					])

				#find all images; replace "space" by substitute of space "##**##"
				AllowedExtensionsList	= (
					const_FILE_EXTENSIONS_LIST_WEB_IMAGES + ';' +
					const_FILE_EXTENSIONS_LIST_HEIC + ';' +
					const_FILE_EXTENSIONS_LIST_RAW + ';' +
					const_FILE_EXTENSIONS_LIST_TIF + ';' +
					const_FILE_EXTENSIONS_LIST_VIDEO + ';' +
					const_FILE_EXTENSIONS_LIST_AUDIO
					)
				AllowedExtensionsList	= AllowedExtensionsList.split(';')

				# create list of valid extensions
				AllowedExtensionsOptions	= []
				for AllowedExtension in AllowedExtensionsList:
					if AllowedExtensionsOptions:
						AllowedExtensionsOptions	+= ["-o"]

					AllowedExtensionsOptions	+= ["-iname", f"*.{AllowedExtension}"]

				BannedPathsViewCaseInsensitive	= self.get_BannedPathsViewCaseInsensitive()
				Command	= f"find '{self.TargetDevice.MountPoint}' -type f \( {' '.join(AllowedExtensionsOptions)} \) -not -path */tims/* {' '.join(BannedPathsViewCaseInsensitive)}"

				ImagesList	= subprocess.check_output(Command,shell=True).decode().strip().split('\n')
				ImagesList.sort()

				ImagesList = [i.replace(self.TargetDevice.MountPoint,'',1) for i in ImagesList]

				# find all tims
				Command	= f"find '{self.TargetDevice.MountPoint}' -type f \( -iname *.jpg -o -iname *.jpeg \) -path */tims/* {' '.join(BannedPathsViewCaseInsensitive)}"
				TIMSList	= subprocess.check_output(Command,shell=True).decode().strip().split('\n')
				TIMSList.sort()

				#convert tims filesnames to original filenames
				for i, TIMS in enumerate(TIMSList):
					TIMSList[i]	= TIMS.replace(self.TargetDevice.MountPoint,'',1).rsplit('.',1)[0] 			# remove self.TargetDevice.MountPoint and second extension
					TIMSList[i]	= '/'.join(TIMSList[i].rsplit('/tims/', 1))										# remove /tims from folder

				#remove from ImagesList all items known in TIMSList
				MissingTIMSList	= list(set(ImagesList) - set(TIMSList))

				#prepare loop to create thumbnails
				FilesToSync	= len(MissingTIMSList)

				DisplayLine1	= self.__lan.l('box_backup_generating_thumbnails') # header1
				DisplayLine2	= self.__lan.l(f'box_backup_mode_{self.TargetDevice.StorageType}') # header2

				progress	= self.progressmonitor(self.__setup,self.__display,self.__log,self.__lan,FilesToSync,DisplayLine1,DisplayLine2)

				CountProgress	= 0
				for SourceFilePathName in MissingTIMSList:
					CountProgress	+= 1

					#extract Extension from filename
					try:
						SourceFilePathNameExt	= SourceFilePathName.rsplit('.',1)[1].lower()
					except:
						SourceFilePathNameExt	= ''

					TIMS_Dir				= f"{os.path.dirname(SourceFilePathName)}/tims".strip('/')
					pathlib.Path(f"{self.TargetDevice.MountPoint}/{TIMS_Dir}").mkdir(parents=True, exist_ok=True)

					FileName				= os.path.basename(SourceFilePathName)
					TIMS_SubpathFilename	= f"{TIMS_Dir}/{FileName}.JPG"

					if SourceFilePathNameExt in f"{const_FILE_EXTENSIONS_LIST_WEB_IMAGES};{const_FILE_EXTENSIONS_LIST_TIF}".split(';'):
						# file-types: jpeg, tif image
						Command	= ["convert", f"{self.TargetDevice.MountPoint}/{SourceFilePathName}[0]", "-resize", "800>", f"{self.TargetDevice.MountPoint}/{TIMS_SubpathFilename}"]
						try:
							subprocess.run(Command)
						except:
							print(f"Error: {' '.join(Command)}",file=sys.stderr)
					elif SourceFilePathNameExt in const_FILE_EXTENSIONS_LIST_HEIC.split(';'):

						# file-type: heic/heif
						# convert heif to jpg
						Command	= ["heif-convert", f"{self.TargetDevice.MountPoint}/{SourceFilePathName}", f"{self.TargetDevice.MountPoint}/{SourceFilePathName}.JPG"]
						try:
							subprocess.run(Command)
						except:
							print(f"Error: {' '.join(Command)}",file=sys.stderr)

						# transfer exif from heif to jpg
						Command	= ["exiftool", "-overwrite_original", "-TagsFromFile", f"{self.TargetDevice.MountPoint}/{SourceFilePathName}", f"{self.TargetDevice.MountPoint}/{SourceFilePathName}.JPG"]
						try:
							subprocess.run(Command)
						except:
							print(f"Error: {' '.join(Command)}",file=sys.stderr)

						# create tims file
						Command	= ["convert", f"{self.TargetDevice.MountPoint}/{SourceFilePathName}.JPG", "-resize", "800>", f"{self.TargetDevice.MountPoint}/{TIMS_SubpathFilename}"]
						try:
							subprocess.run(Command)
						except:
							print(f"Error: {' '.join(Command)}",file=sys.stderr)

						if conf_VIEW_CONVERT_HEIC:
							MissingTIMSList.append(f"{SourceFilePathName}.JPG")
						else:
							Command	= ["rm", f"{self.TargetDevice.MountPoint}/{SourceFilePathName}.JPG"]
							try:
								subprocess.run(Command)
							except:
								print(f"Error: {' '.join(Command)}",file=sys.stderr)

					elif SourceFilePathNameExt in const_FILE_EXTENSIONS_LIST_RAW.split(';'):
						# file-type: raw-image
						SourceCommand	= ["/usr/lib/libraw/dcraw_emu", "-w", "-Z", "-", f"{self.TargetDevice.MountPoint}/{SourceFilePathName}"]
						FilterCommand	= ["convert", "-", "-resize", "800", f"{self.TargetDevice.MountPoint}/{TIMS_SubpathFilename}"]
						try:
							lib_common.pipe(SourceCommand,FilterCommand)
						except:
							print(f"Error: {' '.join(SourceCommand) + ' | ' + ' '.join(FilterCommand)}",file=sys.stderr)

					elif SourceFilePathNameExt in const_FILE_EXTENSIONS_LIST_VIDEO.split(';'):
						# file-type: video
						Command	= ["ffmpeg", "-i", f"{self.TargetDevice.MountPoint}/{SourceFilePathName}", "-ss", "00:00:01", "-vframes", "1", f"{self.TargetDevice.MountPoint}/{TIMS_SubpathFilename}"]
						try:
							subprocess.run(Command)
						except:
							print(f"Error: {' '.join(Command)}",file=sys.stderr)

						if not os.path.isfile(f"{self.TargetDevice.MountPoint}/{TIMS_SubpathFilename}"):
							# tims file not generated. Video too short? Try at second 0
							Command	= ["ffmpeg", "-i", f"{self.TargetDevice.MountPoint}/{SourceFilePathName}", "-ss", "00:00:00", "-vframes", "1", f"{self.TargetDevice.MountPoint}/{TIMS_SubpathFilename}"]
							try:
								subprocess.run(Command)
							except:
								print(f"Error: {' '.join(Command)}",file=sys.stderr)

						Command	= ["mogrify", "-resize", "800>", f"{self.TargetDevice.MountPoint}/{TIMS_SubpathFilename}"]
						subprocess.run(Command)

						Command	=["composite", "-gravity", "center", "/var/www/little-backup-box/img/play.png", f"{self.TargetDevice.MountPoint}/{TIMS_SubpathFilename}", f"{self.TargetDevice.MountPoint}/{TIMS_SubpathFilename}"]
						try:
							subprocess.run(Command)
						except:
							print(f"Error: {' '.join(Command)}",file=sys.stderr)

					elif SourceFilePathNameExt in const_FILE_EXTENSIONS_LIST_AUDIO.split(';'):
						Command	= ["cp", "/var/www/little-backup-box/img/audio.JPG", f"{self.TargetDevice.MountPoint}/{TIMS_SubpathFilename}"]
						try:
							subprocess.run(Command)
						except:
							print(f"Error: {' '.join(Command)}",file=sys.stderr)

						Command	= ["convert", f"{self.TargetDevice.MountPoint}/{TIMS_SubpathFilename}", "-gravity", "center", "-pointsize", "50", "-annotate", "0", FileName, f"{self.TargetDevice.MountPoint}/{TIMS_SubpathFilename}"]
						try:
							subprocess.run(Command)
						except:
							print(f"Error: {' '.join(Command)}",file=sys.stderr)

					if not os.path.isfile(f"{self.TargetDevice.MountPoint}/{TIMS_SubpathFilename}"):
						self.__log.message(f"ERROR: TIMS of '{self.TargetDevice.MountPoint}/{SourceFilePathName}' ('{self.TargetDevice.MountPoint}/{TIMS_SubpathFilename}') not regular created.")

						Command	= ["cp", "/var/www/little-backup-box/img/unknown.JPG", f"{self.TargetDevice.MountPoint}/{TIMS_SubpathFilename}"]
						try:
							subprocess.run(Command)
						except:
							print(f"Error: {' '.join(Command)}",file=sys.stderr)

						Command	= ["convert", f"{self.TargetDevice.MountPoint}/{TIMS_SubpathFilename}", "-gravity", "center", "-pointsize", "50", "-annotate", "0", FileName, f"{self.TargetDevice.MountPoint}/{TIMS_SubpathFilename}"]
						try:
							subprocess.run(Command)
						except:
							print(f"Error: {' '.join(Command)}",file=sys.stderr)

					db.dbInsertImage(SourceFilePathName)

					progress.progress(CountProgress=CountProgress)

				del progress
				self.__display.message([f":{self.__lan.l('box_finished')}"])

	def updateEXIF(self):	# update exif-information in original files on local drive

		if self.TargetDevice:
			if self.TargetDevice.isLocal:

				lib_system.rpi_leds(trigger='timer',delay_on=100,delay_off=900)

				# prepare database
				db	= lib_view.viewdb(self.__setup,self.__log,self.TargetDevice.MountPoint)

				# select directory and filename as DirFile
				FilesTupleList	= db.dbSelect("select ID, Directory || '/' || File_Name as DirFile, LbbRating from EXIF_DATA where LbbRating != Rating or Rating is null")

				#prepare loop to create thumbnails
				FilesToSync	= len(FilesTupleList)

				DisplayLine1	= self.__lan.l('box_backup_updating_exif') # header1
				DisplayLine2	= self.__lan.l(f'box_backup_mode_{self.TargetDevice.StorageType}') # header2

				progress	= self.progressmonitor(self.__setup,self.__display,self.__log,self.__lan,FilesToSync,DisplayLine1,DisplayLine2)

				CountProgress	= 0
				for FileTuple in FilesTupleList:
					CountProgress	+= 1

					#replace substitute of space by space
					MediaID			= FileTuple[0]
					MediaPathFile	= f"{self.TargetDevice.MountPoint}/{FileTuple[1]}"
					MediaLbbRating	= FileTuple[2]

					subprocess.run(['exiftool', '-overwrite_original', f'-Rating={MediaLbbRating}',MediaPathFile])
					db.dbExecute(f"update EXIF_DATA set Rating={MediaLbbRating} where ID={MediaID};")

					progress.progress(CountProgress=CountProgress)

				del progress
				self.__display.message([f":{self.__lan.l('box_finished')}"])

	def calculate_files_to_sync(self,singleSubPathsAtSource=None):
		if singleSubPathsAtSource:
			checkPathsList	= [singleSubPathsAtSource]
		else:
			checkPathsList	= self.SourceDevice.SubPathsAtSource

		FilesToSync		= 0
		FilesToSyncPart	= 0


		# To define a new method, add an elif block (example below)

		if self.SourceDevice.StorageType in ['usb','internal','cloud','cloud_rsync']:
			## Source is mounted (mountable) device

			RsyncOptions	= self.getRsyncOptions()
			excludeTIMS	= self.get_excludeTIMS()

			for SubPathAtSource in checkPathsList:
				SourceCommand	= self.TargetDevice.rsyncSSH + ["rsync"] + RsyncOptions + excludeTIMS + ["--dry-run", f"{self.SourceDevice.MountPoint}/{SubPathAtSource}", f"{self.TargetDevice.MountPoint}/{self.SourceDevice.SubPathAtTarget}"]
				FilterCommand		= ["grep", "Number of regular files transferred"]
				self.__log.message(' '.join(SourceCommand),3)
				self.__log.message(' '.join(FilterCommand),3)
				try:
					FilesToSyncPart	= int(lib_common.pipe(SourceCommand,FilterCommand).decode().split(':')[1].strip().replace(',',''))
				except:
					FilesToSyncPart	= 0

				self.__log.message(f"Files to sync from folder '{SubPathAtSource}': {FilesToSyncPart}")

				FilesToSync	+= FilesToSyncPart

		elif self.SourceDevice.StorageType == 'camera':	# Source camera

			for SubPathAtSource in checkPathsList:

				SourceCommand	= ["gphoto2", "--camera", self.SourceDevice.DeviceIdentifier, "--list-files", "--folder", f"{SubPathAtSource}"]
				FilterCommand		= ["grep", "^#"]
				self.__log.message(' '.join(SourceCommand),3)
				self.__log.message(' '.join(FilterCommand),3)
				try:
					gphoto2	= lib_common.pipe(SourceCommand,FilterCommand).decode()
				except:
					gphoto2	= 'error'

				FilesToSync		+= len(gphoto2.strip().split('\n'))

				self.__log.message(f"Files in folder '{SubPathAtSource}': {FilesToSyncPart}")

		return(FilesToSync)

	def get_FilesCount(self,Path):
		SourceCommand	= ["find", Path, "-type", "f"]
		FilterCommand	= ["wc", "-l"]
		FilesCount	= int(lib_common.pipe(SourceCommand, FilterCommand).decode())

		return(FilesCount)

	def sync_return_code_decoder(self,Code):
		if (not self.TransferMode) or (not Code):
			Code	= -1
			ERROR_TEXT	= {
				Code: "-"
				}

		if self.TransferMode == 'camera':
			#gphoto2-codes
			ERROR_TEXT	= {
				1:	'Error: No camera found.'
			}
		elif self.TransferMode == 'rsync':
			#rsync-codes
			ERROR_TEXT	= {
				0:	'Success',
				1:	'Syntax or usage error',
				2:	'Protocol incompatibility',
				3:	'Errors selecting input/output files, dirs',
				4:	'Requested action not supported: an attempt was made to manipulate 64-bit files on a platform that cannot support them or an option was specified that is supported by the client and not by the server.',
				5:	'Error starting client-server protocol',
				6:	'Daemon unable to append to log-file',
				10:	'Error in socket I/O',
				11:	'Error in file I/O',
				12:	'Error in rsync protocol data stream',
				13:	'Errors with program diagnostics',
				14:	'Error in IPC code',
				20:	'Received SIGUSR1 or SIGINT',
				21:	'Some error returned by waitpid()',
				22:	'Error allocating core memory buffers',
				23:	'Partial transfer due to error',
				24:	'Partial transfer due to vanished source files',
				25:	'The --max-delete limit stopped deletions',
				30:	'Timeout in data send/receive',
				35:	'Timeout waiting for daemon connection'
			}

		try:
			return(f"{self.__lan.l('box_backup_error_code')} '{Code}', {ERROR_TEXT[Code]}")
		except:
			return(f"{self.__lan.l('box_backup_error_code')} '{Code}'")

	def get_BannedPathsViewCaseInsensitive(self):
		# create list of banned paths
		BannedPathsList		= []

		BannedPathsArray	= self.__setup.get_val('const_VIEW_BANNED_PATHS').split(';')

		for BannedPath in BannedPathsArray:
			BannedPathsList += ["-not", "-ipath", BannedPath]

		return(BannedPathsList)

	def finish(self):
		# Set the PWR LED ON to indicate that the backup has finished
		lib_system.rpi_leds(trigger='none',brightness='1')

		if self.SecundaryBackupFollows:

			lib_storage.umount(self.__setup,'all')

			self.__display.message(self.TransferInfoDisplay)
			self.__display.message(self.SummaryDisplay)
		else:
			# Power off
			if self.PowerOff:
				Action	= 'poweroff'
			else:
				Action	= 'None'

			lib_poweroff.poweroff(Action, self.TransferInfoDisplay, self.SummaryDisplay).poweroff()

if __name__ == "__main__":
	SourceName						= sys.argv[1]
	TargetName						= sys.argv[2]

	try:
		DoSyncDatabase					= (sys.argv[3] == 'True')
	except:
		DoSyncDatabase					= False

	try:
		DoGenerateThumbnails			= (sys.argv[4] == 'True')
	except:
		DoGenerateThumbnails			= 'setup'

	try:
		DoUpdateEXIF					= (sys.argv[5] == 'True')
	except:
		DoUpdateEXIF					= 'setup'

	try:
		DeviceIdentifierPresetSource	= sys.argv[6]
	except:
		DeviceIdentifierPresetSource	= ''

	try:
		DeviceIdentifierPresetTarget	= sys.argv[7]
	except:
		DeviceIdentifierPresetTarget	= ''

	try:
		PowerOff						= (sys.argv[8] == 'True')
	except:
		PowerOff						= 'setup'

	try:
		SecundaryBackupFollows						= (sys.argv[9] == 'True')
	except:
		SecundaryBackupFollows						= False

	backupObj= backup(SourceName, TargetName, DoSyncDatabase, DoGenerateThumbnails, DoUpdateEXIF, DeviceIdentifierPresetSource, DeviceIdentifierPresetTarget, PowerOff, SecundaryBackupFollows)
	backupObj.run()


