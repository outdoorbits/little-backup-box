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

import argparse
from datetime import datetime, timedelta
import os
import pathlib
import random
from string import digits
import subprocess
import sys
import time

import lib_backup
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

	def __init__(self, SourceName, TargetName, DoSyncDatabase=True, DoGenerateThumbnails=True, DoUpdateEXIF=True, DeviceIdentifierPresetSource=None, DeviceIdentifierPresetTarget=None, PowerOff=False, SecundaryBackupFollows=False):

		# SourceName:	one of ['usb', 'internal', 'camera', 'cloud:SERVICE_NAME', 'cloud_rsync'] or functions: ['thumbnails', 'database', 'exif']
		# TargetName:	one of ['usb', 'internal', 'cloud:SERVICE_NAME', 'cloud_rsync']
		# DoSyncDatabase, DoGenerateThumbnails, DoUpdateEXIF	True/False

		# Objects
		self.__setup		= lib_setup.setup()
		self.__display	= lib_display.display()
		self.__log		= lib_log.log()
		self.__lan		= lib_language.language()
		self.__reporter	= None

		# Arguments
		self.SourceName										= SourceName
		self.SourceStorageType, self.SourceCloudService		= lib_storage.extractCloudService(SourceName)
		TargetStorageType, TargetCloudService				= lib_storage.extractCloudService(TargetName)

		self.DoSyncDatabase									= DoSyncDatabase
		if not type(DoSyncDatabase) is bool:
			self.DoSyncDatabase	= self.DoSyncDatabase == 'True'

		self.DoGenerateThumbnails							= DoGenerateThumbnails
		if not type(DoGenerateThumbnails) is bool:
			self.DoGenerateThumbnails	= self.DoGenerateThumbnails == 'True'

		self.DoUpdateEXIF									= DoUpdateEXIF
		if not type(DoUpdateEXIF) is bool:
			self.DoUpdateEXIF	= self.DoUpdateEXIF == 'True'

		self.DeviceIdentifierPresetSource					= DeviceIdentifierPresetSource
		self.DeviceIdentifierPresetSource_blocked			= (self.DeviceIdentifierPresetSource != '')

		if self.DeviceIdentifierPresetSource:
			self.__log.message(f'Preset source: {self.DeviceIdentifierPresetSource}')

		self.DeviceIdentifierPresetTarget					= DeviceIdentifierPresetTarget

		if self.DeviceIdentifierPresetTarget:
			self.__log.message(f'Preset target: {self.DeviceIdentifierPresetTarget}')

		self.PowerOff										= PowerOff

		self.SecundaryBackupFollows							= SecundaryBackupFollows
		if not type(SecundaryBackupFollows) is bool:
			self.SecundaryBackupFollows	= self.SecundaryBackupFollows == 'True'

		# Basics
		self.__WORKING_DIR	= os.path.dirname(__file__)



		# Setup

		self.const_SYNC_TIME_OVERHEATING_THRESHOLD_SEC	= self.__setup.get_val('const_SYNC_TIME_OVERHEATING_THRESHOLD_SEC')
		self.const_SYNC_TIME_OVERHEATING_WAIT_SEC		= self.__setup.get_val('const_SYNC_TIME_OVERHEATING_WAIT_SEC')
		self.const_IMAGE_DATABASE_FILENAME				= self.__setup.get_val('const_IMAGE_DATABASE_FILENAME')
		self.const_BACKUP_MAX_TRIES						= self.__setup.get_val('const_BACKUP_MAX_TRIES')

		self.conf_MAIL_NOTIFICATIONS					= self.__setup.get_val('conf_MAIL_NOTIFICATIONS')
		self.__conf_LOG_SYNC								= self.__setup.get_val('conf_LOG_SYNC')

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
		elif not type(PowerOff) is bool:
			self.PowerOff	= self.PowerOff == 'True'

		# Common variables
		self.SourceDevice		= None
		self.TargetDevice		= None

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

		self.__display.message([f":{self.__lan.l(l_box_backup_mode_SOURCE_MODE)} {self.SourceCloudService}", f": > {self.__lan.l(l_box_backup_mode_TARGET_MODE)} {TargetCloudService}"])

		self.__log.message(f"Source: {self.SourceStorageType} {self.SourceCloudService}")
		self.__log.message(f"Target: {TargetStorageType} {TargetCloudService}")

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
				lib_cron_ip.ip_info().display_ip()
				lib_cron_ip.ip_info().mail_ip()
			else:
				sys.exit(103)

		# MANAGE TARGET DEVICE
		# Set the PWR LED to blink short to indicate waiting for the target device
		lib_system.rpi_leds(trigger='timer',delay_on=250,delay_off=750)

		if TargetStorageType in ['usb', 'internal', 'cloud', 'cloud_rsync']:
			self.TargetDevice	= lib_storage.storage(StorageName=TargetName, Role=lib_storage.role_Target, WaitForDevice=True, DeviceIdentifierPresetThis=self.DeviceIdentifierPresetTarget, DeviceIdentifierPresetOther=self.DeviceIdentifierPresetSource)
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
					self.SourceDevice	= lib_storage.storage(StorageName=self.SourceName, Role=lib_storage.role_Source, WaitForDevice=True, DeviceIdentifierPresetThis=self.DeviceIdentifierPresetSource, DeviceIdentifierPresetOther=self.DeviceIdentifierPresetTarget)

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
					(self.SourceDevice.StorageType == 'cloud_rsync') or																		# can't write device identifier to rsync server
					(self.TargetDevice.StorageType == 'camera') or																			# camera never can be target
					(self.SourceDevice.StorageType	== 'camera' and self.TargetDevice.StorageType == 'cloud_rsync')							# camera can't rsync to rsyncserver as this is not supported by gphoto2
				):
					self.__display.message([f":{self.__lan.l('box_backup_invalid_mode_combination_1')}", f":{self.__lan.l('box_backup_invalid_mode_combination_2')}", f":{self.__lan.l('box_backup_invalid_mode_combination_3')}"])
					sys.exit(104)

				#run backup
				self.__reporter	= lib_backup.reporter(
					self.__lan,
					self.SourceDevice.StorageType,
					self.SourceDevice.CloudServiceName,
					self.SourceDevice.LbbDeviceID,
					self.TargetDevice.StorageType,
					self.TargetDevice.CloudServiceName,
					self.TargetDevice.LbbDeviceID,
					self.TransferMode,
					SyncLog=self.__conf_LOG_SYNC
				)

				lib_system.rpi_leds(trigger='heartbeat')

				# define variables for backup
				SourceFolderNumber		= 0

				FilesCountStoragePre	= 0
				FilesCountStoragePost	= 0
				FilesToProcess			= 0

				ErrorsOld				= []

				# define specific parameters
				RsyncOptions	= self.getRsyncOptions()
				excludeTIMS		= self.get_excludeTIMS()

				# SubPaths loop
				for SubPathAtSource in self.SourceDevice.SubPathsAtSource:

					self.__reporter.new_folder(SubPathAtSource)

					self.__log.message(f"Backup from {self.SourceDevice.StorageType}: {SubPathAtSource}",3)

					#define SubPathAtSource specific values
					SourceFolderNumber	+= 1
					SourceFolderFracture	= f"{SourceFolderNumber}/{len(self.SourceDevice.SubPathsAtSource)}" if len(self.SourceDevice.SubPathsAtSource) > 1 else ''

					TriesCount				= 0
					while TriesCount < self.const_BACKUP_MAX_TRIES and (TriesCount == 0 or self.__reporter.get_errors()):
						TriesCount	+= 1

						self.__reporter.new_try()

						if self.vpn:
							self.__reporter.add_synclog(f"** VPN: {self.vpn.check_status(0)} **\n\n")

						if TriesCount > 1:
							self.__display.message([f"s=a:{self.__lan.l('box_backup_try_backup')} {TriesCount} {self.__lan.l('box_backup_of')} {self.const_BACKUP_MAX_TRIES}"])

							self.__display.wait_for_empty_stack()

							time.sleep(2)

						# Remount devices if "Err.Lost device"
						if "Err.Lost device!" in ErrorsOld:
							self.__log.execute('Lost device: pre remount','lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE',3)

							if not self.TargetDevice.mounted():
								self.__log.message(f"remount target device {self.TargetDevice.StorageType} {self.TargetDevice.CloudServiceName} {self.TargetDevice.DeviceIdentifier}",3)
								self.TargetDevice.mount()

							if not self.SourceDevice.mounted():
								self.__log.message(f"remount source device {self.SourceDevice.StorageType} {self.SourceDevice.CloudServiceName} {self.SourceDevice.DeviceIdentifier}",3)
								self.SourceDevice.mount()

						# Check for lost devices
						if self.__checkLostDevice():
							self.__reporter.add_error('Err.Lost device!')
							self.__log.execute("Lost device", "lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE",3)
							self.__log.message(lib_system.get_abnormal_system_conditions(self.__lan),1)

							ErrorsOld	= self.__reporter.get_errors()

							continue

						if self.TargetDevice.mountable: # not cloud_rsync
							FilesCountStoragePre = self.get_FilesCount(f"{self.TargetDevice.MountPoint}/{self.SourceDevice.SubPathAtTarget}")

						self.__display.message([f":{self.__lan.l('box_backup_working')}..."])

						FilesToProcess	= self.calculate_files_to_sync(SubPathAtSource)
						self.__reporter.set_values(FilesToProcess=FilesToProcess)
						self.__log.message(f"Files to sync before backup: {FilesToProcess}",3)

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

						#define progress object
						progress	= lib_backup.progressmonitor(self.__setup, self.__display, self.__log, self.__lan, FilesToProcess, DisplayLine1, DisplayLine2, SourceDevice=self.SourceDevice, TargetDevice=self.TargetDevice, vpn=self.vpn)

						SyncStartTime	= lib_system.get_uptime_sec()

						# RUN BACKUP

						## create target path if not exists and enter dir
						try:
							pathlib.Path(f"{self.TargetDevice.MountPoint}/{self.SourceDevice.SubPathAtTarget}").mkdir(parents=True, exist_ok=True)
						except:
							self.__reporter.add_error('Err.Lost device!')

							ErrorsOld	= self.__reporter.get_errors()
							continue

						self.TargetDevice.set_perms_mountpoint()

						SyncReturnCode	= 0

	#					# gphoto2 backup
						if self.SourceDevice.StorageType == 'camera':
							# not mountable targets are excluded.

							os.chdir(f"{self.TargetDevice.MountPoint}/{self.SourceDevice.SubPathAtTarget}")

							# gphoto2: Filename-format at backup; %F is undocumented? = path of the file at the camera; $f = filename without suffix; %C=suffix
							Command	= ["gphoto2", "--camera", self.SourceDevice.DeviceIdentifier, "--filename", "%F/%f.%C", "--get-all-files", "--folder", SubPathAtSource, "--skip-existing"]
							self.__log.message(' '.join(Command),3)

							with subprocess.Popen(Command, stdout=subprocess.PIPE, stderr=subprocess.PIPE, bufsize=1, text=True) as BackupProcess:

								while True:
									SyncOutputLine = BackupProcess.stdout.readline()
									if not SyncOutputLine:
										break

									progress.progress(TransferMode='gphoto2',SyncOutputLine=SyncOutputLine)

									self.__reporter.add_synclog(f"{SyncOutputLine}")

								self.__reporter.set_values(FilesProcessed=progress.CountProgress)
								self.__reporter.set_values(FilesCopied=progress.CountJustCopied)

								BackupProcess.wait()
								SyncReturnCode	= BackupProcess.returncode
								self.__reporter.set_values(SyncReturnCode=SyncReturnCode)

							os.chdir(os.path.expanduser('~'))

	#					# rsync backup
						else:
							Command	= self.TargetDevice.rsyncSSH + ["rsync"] + RsyncOptions + excludeTIMS + [f"{self.SourceDevice.MountPoint}/{SubPathAtSource}", f"{self.TargetDevice.MountPoint}/{self.SourceDevice.SubPathAtTarget}"]
							self.__log.message(' '.join(Command),3)

							with subprocess.Popen(Command, stdout=subprocess.PIPE, stderr=subprocess.PIPE, bufsize=1, text=True)  as BackupProcess:

								while True:
									SyncOutputLine = BackupProcess.stdout.readline()
									if not SyncOutputLine:
										break

									progress.progress(TransferMode='rsync',SyncOutputLine=SyncOutputLine)

									if (SyncOutputLine[0] != ' ') and (SyncOutputLine != './\n'):
										self.__reporter.add_synclog(SyncOutputLine)

								self.__reporter.set_values(FilesProcessed=progress.CountProgress)
								self.__reporter.set_values(FilesCopied=progress.CountJustCopied)

								BackupProcess.wait()
								SyncReturnCode	= BackupProcess.returncode
								self.__reporter.set_values(SyncReturnCode=SyncReturnCode)

						SyncStopTime	= lib_system.get_uptime_sec()

						# VPN check
						if self.vpn:
							self.__reporter.add_synclog(f"\n** VPN: {self.vpn.check_status(0)} **\n\n")

						# Remove empty files (maybe can result from disconnection of a source-device)
						if self.TargetDevice.isLocal and self.TargetDevice.mountable:
							Command	= ['find',f'{self.TargetDevice.MountPoint}/{self.SourceDevice.SubPathAtTarget}','-size','0','-delete']
							try:
								subprocess.run(Command)
							except:
								self.__log.message(f"Error: '{' '.join(Command)}'")

						# Re-calculate FilesToProcess
						FilesToProcessPost	= 0
						if self.SourceDevice.StorageType == 'camera':
							FilesToProcessPost	= FilesToProcess - progress.CountProgress
						elif self.TargetDevice.mountable:
							FilesCountStoragePost	= self.get_FilesCount(f"{self.TargetDevice.MountPoint}/{self.SourceDevice.SubPathAtTarget}")
							FilesToProcessPost	= FilesToProcess - FilesCountStoragePost + FilesCountStoragePre
						else:
							FilesToProcessPost	= self.calculate_files_to_sync(SubPathAtSource)

						self.__reporter.set_values(FilesToProcessPost=FilesToProcessPost)

						# Transfer completed?
						if FilesToProcessPost > 0:
							self.__reporter.add_error('Files missing!')
							self.__log.message(f"Files missing: {FilesToProcessPost} files not synced.")
							self.__log.execute('Files missing', 'lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE', 3)
							self.__log.message(lib_system.get_abnormal_system_conditions(self.__lan),1)

						# check Returncode
						if SyncReturnCode != 0:
							self.__reporter.add_error('Exception')
							self.__log.message(f"Exception: {self.__reporter.sync_return_code_decoder(SyncReturnCode)}")
							self.__log.message(lib_system.get_abnormal_system_conditions(self.__lan),1)

						# Re-Check for lost devices
						if self.__checkLostDevice():
							self.__reporter.add_error('Err.Lost device!')
							self.__log.execute("Lost device", "lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE",3)
							self.__log.message(lib_system.get_abnormal_system_conditions(self.__lan),1)


						# Controller-overheating-error?
						SyncTimeDiff	= SyncStopTime - SyncStartTime

						if (
							'Err.Lost device!' in self.__reporter.get_errors() and
							SyncReturnCode > 0 and
							SyncTimeDiff >= self.const_SYNC_TIME_OVERHEATING_THRESHOLD_SEC and
							self.const_BACKUP_MAX_TRIES >  TriesCount
							):
								self.__display.message([
									f"s=a:{self.__lan.l('box_backup_error_cooling_1')}",
									f":{self.__lan.l('box_backup_error_cooling_2')} {self.__setup.get_val('const_SYNC_TIME_OVERHEATING_WAIT_SEC')} {self.__lan.l('seconds_short')} ...",
									f":{self.__lan.l('box_backup_error_cooling_3')}",f":{self.__lan.l('box_backup_error_cooling_4')}"
									])
								time.sleep(self.const_SYNC_TIME_OVERHEATING_WAIT_SEC)

						ErrorsOld	= self.__reporter.get_errors()

						del progress

				# Mail result
				if self.conf_MAIL_NOTIFICATIONS:
					mail	= lib_mail.mail()
					if (
						mail.mail_configured() and
						lib_network.get_internet_status()
						): # Check internet connection

						self.__reporter.prepare_mail()

						mail.sendmail(self.__reporter.mail_subject,self.__reporter.mail_content_PLAIN,self.__reporter.mail_content_HTML)

				#end loop RepeatBackupNextSource
				if (self.SourceDevice.StorageType in ['usb', 'camera']):
					completedSources.append(self.SourceDevice.DeviceIdentifier)

					if self.SourceDevice.StorageType == 'usb':
						availableSources	= lib_storage.get_available_partitions(self.TargetDevice.DeviceIdentifier,completedSources)
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
			lib_cron_ip.ip_info().display_ip()
			lib_cron_ip.ip_info().mail_ip()

	def __checkLostDevice(self):
		lostTargetDevice	= False
		if self.TargetDevice.mountable:
			lostTargetDevice = not self.TargetDevice.mounted()
			self.__log.message(f"Lost target device {self.TargetDevice.StorageType}? {lostTargetDevice}",3)

		lostSourceDevice	= False
		if self.SourceDevice.mountable:
			lostSourceDevice = not self.SourceDevice.mounted()
			self.__log.message(f"Lost source device {self.SourceDevice.StorageType}? {lostSourceDevice}",3)

		return (lostTargetDevice or lostSourceDevice)

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

				FilesToProcess	=	len(KnownFilesList)
				DisplayLine1	= self.__lan.l('box_backup_cleaning_database')						# header1
				DisplayLine2	= self.__lan.l(f"box_backup_mode_{self.TargetDevice.StorageType}")	# header2

				progress	= lib_backup.progressmonitor(self.__setup,self.__display,self.__log,self.__lan,FilesToProcess,DisplayLine1,DisplayLine2)

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
				FilesToProcess	= len(TIMSList)

				DisplayLine1	= self.__lan.l('box_backup_generating_database')					# header1
				DisplayLine2	= self.__lan.l(f'box_backup_mode_{self.TargetDevice.StorageType}')	# header2

				progress	= lib_backup.progressmonitor(self.__setup,self.__display,self.__log,self.__lan,FilesToProcess,DisplayLine1,DisplayLine2)

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

					AllowedExtensionsOptions	+= ["-iname", f"'*.{AllowedExtension}'"]

				BannedPathsViewCaseInsensitive	= self.get_BannedPathsViewCaseInsensitive()
				Command	= f"find '{self.TargetDevice.MountPoint}' -type f \( {' '.join(AllowedExtensionsOptions)} \) -not -path '*/tims/*' {' '.join(BannedPathsViewCaseInsensitive)}"

				ImagesList	= subprocess.check_output(Command,shell=True).decode().strip().split('\n')
				ImagesList.sort()
				ImagesList = [i.replace(self.TargetDevice.MountPoint,'',1) for i in ImagesList]

				# find all tims
				Command	= f"find '{self.TargetDevice.MountPoint}' -type f -iname '*.jpg' -path '*/tims/*' {' '.join(BannedPathsViewCaseInsensitive)}"
				TIMSList	= subprocess.check_output(Command,shell=True).decode().strip().split('\n')
				TIMSList.sort()
				#convert tims filenames to original filenames
				for i, TIMS in enumerate(TIMSList):
					TIMSList[i]	= TIMS.replace(self.TargetDevice.MountPoint,'',1).rsplit('.',1)[0] 			# remove self.TargetDevice.MountPoint and second extension
					TIMSList[i]	= '/'.join(TIMSList[i].rsplit('/tims/', 1))										# remove /tims from folder

				#remove from ImagesList all items known in TIMSList
				MissingTIMSList	= list(set(ImagesList) - set(TIMSList))

				#prepare loop to create thumbnails
				FilesToProcess	= len(MissingTIMSList)

				DisplayLine1	= self.__lan.l('box_backup_generating_thumbnails') # header1
				DisplayLine2	= self.__lan.l(f'box_backup_mode_{self.TargetDevice.StorageType}') # header2

				progress	= lib_backup.progressmonitor(self.__setup,self.__display,self.__log,self.__lan,FilesToProcess,DisplayLine1,DisplayLine2)

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
				FilesToProcess	= len(FilesTupleList)

				DisplayLine1	= self.__lan.l('box_backup_updating_exif') # header1
				DisplayLine2	= self.__lan.l(f'box_backup_mode_{self.TargetDevice.StorageType}') # header2

				progress	= lib_backup.progressmonitor(self.__setup,self.__display,self.__log,self.__lan,FilesToProcess,DisplayLine1,DisplayLine2)

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

		FilesToProcess		= 0
		FilesToProcessPart	= 0


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
					FilesToProcessPart	= int(lib_common.pipe(SourceCommand,FilterCommand).decode().split(':')[1].strip().replace(',',''))
				except:
					FilesToProcessPart	= 0

				self.__log.message(f"Files to sync from folder '{SubPathAtSource}': {FilesToProcessPart}")

				FilesToProcess	+= FilesToProcessPart

		elif self.SourceDevice.StorageType == 'camera':	# Source camera

			for SubPathAtSource in checkPathsList:

				SourceCommand		= ["gphoto2", "--camera", self.SourceDevice.DeviceIdentifier, "--list-files", "--folder", f"{SubPathAtSource}"]
				FilterCommand		= ["grep", "^#"]
				self.__log.message(' '.join(SourceCommand) + ' | ' + ' '.join(FilterCommand),3)
				try:
					gphoto2			= lib_common.pipe(SourceCommand,FilterCommand).decode()
					FilesToProcess	+= len(gphoto2.strip().split('\n'))
				except:
					FilesToProcess	= 0

				self.__log.message(f"Files in folder '{SubPathAtSource}': {FilesToProcessPart}")

		return(FilesToProcess)

	def get_FilesCount(self,Path):
		SourceCommand	= ["find", Path, "-type", "f"]
		FilterCommand	= ["wc", "-l"]
		FilesCount	= int(lib_common.pipe(SourceCommand, FilterCommand).decode())

		return(FilesCount)

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

		if self.__reporter is None:
			display_summary	= []
		else:
			self.__reporter.prepare_display_summary()
			display_summary	= self.__reporter.display_summary

		if self.SecundaryBackupFollows:

			lib_storage.umount(self.__setup,'all')

			self.__display.message(display_summary)
		else:
			# Power off
			if self.PowerOff:
				Action	= 'poweroff'
			else:
				Action	= 'None'

			lib_poweroff.poweroff(Action, display_summary).poweroff()

if __name__ == "__main__":
	setup	= lib_setup.setup()

	const_MEDIA_DIR				= setup.get_val('const_MEDIA_DIR')
	const_RCLONE_CONFIG_FILE	= setup.get_val('const_RCLONE_CONFIG_FILE')

	#get possible CloudServices
	rclone_config	= subprocess.check_output(['sudo', 'rclone', 'config', 'show', '--config', os.path.join(const_MEDIA_DIR, const_RCLONE_CONFIG_FILE)]).decode().split('\n')

	CloudServices	= []

	for line in rclone_config:
		if len(line) > 0 and line[0] == '[':
			CloudServices.append(f"cloud:{line.strip('[]')}")

	parser = argparse.ArgumentParser(
		description	= 'Controls the entire backup process of Little Backup Box. Some parameters are taken from the configuration if they are not explicitly set as arguments. Please configure the standards in the web UI.',
		add_help	= True,
		epilog		= 'This script can ideally be configured and started via the Little Backup Box web UI.'
	)

	SourceChoices	= ['usb', 'internal', 'camera'] + CloudServices + ['cloud_rsync', 'thumbnails', 'database', 'exif']
	parser.add_argument(
		'--SourceName',
		'-s',
		choices		= SourceChoices,
		required =	True,
		help=f'Source name, one of {SourceChoices}'
	)

	TargetChoices	= ['usb', 'internal'] + CloudServices + ['cloud_rsync']
	parser.add_argument(
		'--TargetName',
		'-t',
		choices		= TargetChoices,
		required	= True,
		help		= f'Target name, one of {TargetChoices}'
	)

	parser.add_argument(
		'--sync-database',
		'-sd',
		required	= False,
		default		= False,
		help		= 'Should the View database be synchronized after backup? [\'True\', \'False\']'
	)

	parser.add_argument(
		'--generate-thumbnails',
		'-gt',
		required	= False,
		default		= 'setup',
		help		= 'Create thumbnails for View after backup (Local storages only) [\'True\', \'False\']. If not set, use config value.'
	)

	parser.add_argument(
		'--update-exif',
		'-ue',
		required=False,
		default		= 'setup',
		help='New media without their own rating receive the standard rating. If possible, this is written to the original file. [\'True\', \'False\']. If not set, use config value.'
	)

	parser.add_argument(
		'--device-identifier-preset-source',
		'-si',
		required=False,
		default='',
		help='Device identifier preset for source, e.g --uuid 123..., sda1, etc.'
	)

	parser.add_argument(
		'--device-identifier-preset-target',
		'-ti',
		required	= False,
		default='',
		help='Device identifier preset for source, e.g --uuid 123..., sda1, etc.'
	)

	parser.add_argument(
		'--power-off',
		'-p',
		required	= False,
		default		= 'setup',
		help		= 'Power off after backup? [\'True\', \'False\']. If not set, use config value.'
	)

	parser.add_argument(
		'--secondary-backup-follows',
		'-sb',
		required	= False,
		default		= False,
		help		= 'Will another backup follow? If not, the process can be completed. [\'True\', \'False\']'
	)

	args = vars(parser.parse_args())
	print('xxx',file=sys.stderr)
	print(args['power_off'],file=sys.stderr)
	backupObj	= backup(SourceName=args['SourceName'], TargetName=args['TargetName'], DoSyncDatabase=args['sync_database'], DoGenerateThumbnails=args['generate_thumbnails'], DoUpdateEXIF=args['update_exif'], DeviceIdentifierPresetSource=args['device_identifier_preset_source'], DeviceIdentifierPresetTarget=args['device_identifier_preset_target'], PowerOff=args['power_off'], SecundaryBackupFollows=args['secondary_backup_follows'])

	backupObj.run()


