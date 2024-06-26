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

class backup(object):

	def __init__(self, SourceName, TargetName, move_files=False, DoSyncDatabase=True, DoGenerateThumbnails=True, DoUpdateEXIF=True, DeviceIdentifierPresetSource=None, DeviceIdentifierPresetTarget=None, PowerOff=False, SecundaryBackupFollows=False):

		# SourceName:	one of ['usb', 'internal', 'camera', 'cloud:SERVICE_NAME', 'cloud_rsync'] or functions: ['thumbnails', 'database', 'exif']
		# TargetName:	one of ['usb', 'internal', 'cloud:SERVICE_NAME', 'cloud_rsync']
		# DoSyncDatabase, DoGenerateThumbnails, DoUpdateEXIF	True/False

		# Objects
		self.__setup	= lib_setup.setup()
		self.__display	= lib_display.display()
		self.__log		= lib_log.log()
		self.__lan		= lib_language.language()
		self.__reporter	= None

		# Arguments
		self.SourceName										= SourceName
		self.SourceStorageType, self.SourceCloudService		= lib_storage.extractCloudService(SourceName)
		TargetStorageType, TargetCloudService				= lib_storage.extractCloudService(TargetName)

		self.move_files										= move_files

		self.DoSyncDatabase									= DoSyncDatabase
		self.DoGenerateThumbnails							= DoGenerateThumbnails
		self.DoUpdateEXIF									= DoUpdateEXIF

		self.DeviceIdentifierPresetSource					= DeviceIdentifierPresetSource

		if self.DeviceIdentifierPresetSource:
			self.__log.message(f'Preset source: {self.DeviceIdentifierPresetSource}')

		self.DeviceIdentifierPresetTarget					= DeviceIdentifierPresetTarget

		if self.DeviceIdentifierPresetTarget:
			self.__log.message(f'Preset target: {self.DeviceIdentifierPresetTarget}')

		self.PowerOff										= PowerOff
		self.SecundaryBackupFollows							= SecundaryBackupFollows

		# Basics
		self.__WORKING_DIR	= os.path.dirname(__file__)

		# Setup
		self.conf_BACKUP_SYNC_METHOD_CLOUDS				= self.__setup.get_val('conf_BACKUP_SYNC_METHOD_CLOUDS')
		self.const_SYNC_TIME_OVERHEATING_THRESHOLD_SEC	= self.__setup.get_val('const_SYNC_TIME_OVERHEATING_THRESHOLD_SEC')
		self.const_SYNC_TIME_OVERHEATING_WAIT_SEC		= self.__setup.get_val('const_SYNC_TIME_OVERHEATING_WAIT_SEC')
		self.const_IMAGE_DATABASE_FILENAME				= self.__setup.get_val('const_IMAGE_DATABASE_FILENAME')
		self.const_BACKUP_MAX_TRIES						= self.__setup.get_val('const_BACKUP_MAX_TRIES')

		self.conf_BACKUP_MOVE_FILES						= self.__setup.get_val('conf_BACKUP_MOVE_FILES')
		self.conf_MAIL_NOTIFICATIONS					= self.__setup.get_val('conf_MAIL_NOTIFICATIONS')
		self.conf_MAIL_TIMEOUT_SEC						= self.__setup.get_val('conf_MAIL_TIMEOUT_SEC')
		self.__conf_LOG_SYNC							= self.__setup.get_val('conf_LOG_SYNC')
		self.__RCLONE_CONFIG_FILE						= os.path.join(self.__setup.get_val('const_MEDIA_DIR'), self.__setup.get_val('const_RCLONE_CONFIG_FILE'))

		if self.move_files == 'setup':
			self.move_files				= self.conf_BACKUP_MOVE_FILES

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

		# Common variables
		self.SourceDevice			= None
		self.TargetDevice			= None
		self.__TIMSCopied			= False
		self.__mail_threads_started	= []

		# define TransferMode
		if self.SourceStorageType == 'camera':
			self.TransferMode	= 'gphoto2'
		else:
			self.TransferMode	= 'rsync'

			CloudSyncMethods	= self.conf_BACKUP_SYNC_METHOD_CLOUDS.split('|;|')
			for CloudSyncMethod in CloudSyncMethods:
				try:
					CloudServiceCandidate, CloudSyncMethodCandidate	= CloudSyncMethod.split('|=|')
					if (CloudSyncMethodCandidate == 'rclone') and (CloudServiceCandidate in [self.SourceCloudService, TargetCloudService]):
						self.TransferMode	= 'rclone'
				except:
					pass

		# Unmount devices, clean before backup
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
				ip_info	= lib_cron_ip.ip_info()
				ip_info.display_ip()
				self.__mail_threads_started.append( ip_info.mail_ip() )
			else:
				self.__display.message([self.__lan.l('box_backup_break1'), self.__lan.l('box_backup_break2'), self.__lan.l('box_backup_vpn_connecting_failed')])

				# Mail result
				if self.conf_MAIL_NOTIFICATIONS:
					mail	= lib_mail.mail()
					self.__mail_threads_started.append(
						mail.sendmail(f"Little Backup Box: {self.__lan.l('box_backup_break1')} {self.__lan.l('box_backup_break2')}",f"{self.__lan.l('box_backup_break1')} {self.__lan.l('box_backup_break2')}: {self.__lan.l('box_backup_vpn_connecting_failed')}")
					)

				return(None)

		# MANAGE TARGET DEVICE
		# Set the PWR LED to blink short to indicate waiting for the target device
		lib_system.rpi_leds(trigger='timer',delay_on=250,delay_off=750)

		if TargetStorageType in ['usb', 'internal', 'nvme', 'cloud', 'cloud_rsync']:
			self.TargetDevice	= lib_storage.storage(StorageName=TargetName, Role=lib_storage.role_Target, WaitForDevice=True, DeviceIdentifierPresetThis=self.DeviceIdentifierPresetTarget, DeviceIdentifierPresetOther=self.DeviceIdentifierPresetSource)
			self.TargetDevice.mount()
		else:
			self.__display.message([f":{self.__lan.l('box_backup_invalid_mode_combination_1')}", f":{self.__lan.l('box_backup_invalid_mode_combination_2')}", f":{self.__lan.l('box_backup_invalid_mode_combination_3')}"])
			return(None)

	def run(self):
		# Set the PWR LED ON to indicate that the backup has not yet started
		lib_system.rpi_leds(trigger='none',brightness=1)

		if self.TargetDevice and (self.SourceStorageType not in ['thumbnails', 'database', 'exif']):
			self.backup()

		if (
				self.TargetDevice and
				(
					self.DoSyncDatabase or
					self.__TIMSCopied
				)
			):
			self.syncDatabase()

		if self.TargetDevice and self.DoGenerateThumbnails:
			self.generateThumbnails()

		if self.TargetDevice and self.DoUpdateEXIF:
			self.updateEXIF()

		self.finish()

	def get_syncOptions(self, TransferMode, dry_run=False):
		syncOptions	= []

		if TransferMode	== 'rsync':
			syncOptions		= ['-avh', '--info=FLIST0,PROGRESS2', '--stats', '--no-owner', '--no-group', '--no-perms', '--mkpath', '--min-size=1', '--exclude', '*.id', '--exclude', '*.lbbid', '--exclude', '*.lbbflag', '--exclude', self.const_IMAGE_DATABASE_FILENAME]

			# use compression for cloud syncs only
			if self.TargetDevice.isLocal and self.SourceDevice.isLocal:
				syncOptions	+= ['--no-compress', '--whole-file']
			else:
				syncOptions	+= ['--compress']

			if self.move_files and not self.SourceDevice.wasTarget:
				syncOptions	+= ['--remove-source-files']

			if dry_run:
				syncOptions	+= ['--dry-run']

		elif TransferMode == 'rclone':
			if dry_run:
				syncOptions	= ['check']
			else:
				if self.move_files and not self.SourceDevice.wasTarget:
					syncOptions	= ['move']
				else:
					syncOptions	= ['copy']

			syncOptions		+= ['-vv', '--min-size=1B', '--exclude', '*.id', '--exclude', '*.lbbid', '--exclude', '*.lbbflag', '--exclude', self.const_IMAGE_DATABASE_FILENAME]

		return(syncOptions)

	def get_excludeTIMS(self, SourceStorageType):
		## don't sync tims to cloud
		if self.TargetDevice.isLocal and SourceStorageType in ['usb', 'internal', 'nvme']:
			excludeTIMS	= []
		else:
			excludeTIMS	= ['--exclude', "*tims/"]

		return(excludeTIMS)

	def backup(self):
		if self.TargetDevice:

			# loop to backup multiple sources
			SourceStorageName			= self.SourceName
			SourceStorageType			= self.SourceStorageType
			completedSources_usb		= []
			completedSources_camera		= []
			Identifier					= self.DeviceIdentifierPresetSource
			Identifier_OLD				= ''

			dynamicSources	= self.SourceStorageType in ['anyusb', 'usb', 'nvme', 'camera'] and not self.DeviceIdentifierPresetSource

			# message to connect sources
			if dynamicSources:
				if SourceStorageName == 'anyusb':
					l_box_backup_connect_1	= self.__lan.l('box_backup_connect_source_any_1')
					l_box_backup_connect_2	= self.__lan.l('box_backup_connect_source_any_2')
				elif SourceStorageName == 'camera':
					l_box_backup_connect_1	= self.__lan.l('box_backup_connect_camera_1')
					l_box_backup_connect_2	= self.__lan.l('box_backup_connect_camera_2')
				else: # if SourceStorageName == 'usb':
					l_box_backup_connect_1	= self.__lan.l('box_backup_connect_source_1')
					l_box_backup_connect_2	= self.__lan.l('box_backup_connect_source_2')

				self.__display.message([f":{l_box_backup_connect_1}", f":{l_box_backup_connect_2}"])

			while True: # backup loops until break

				# define next source
				if dynamicSources:
					# add last run to completedSources
					if Identifier_OLD:
						if SourceStorageName == 'camera':
							completedSources_camera.append(Identifier_OLD)
						else:
							completedSources_usb.append(Identifier_OLD)

					# get available sources
					todoSources	= []
					if self.SourceName in ['anyusb', 'camera']:
						availableSources_camera	= lib_storage.get_available_cameras()

						# remove disconnected cameras from completedSources_camera
						completedSources_camera	= list(set(completedSources_camera) & set(availableSources_camera))
						todoSources				= list(set(availableSources_camera) - set(completedSources_camera))
						SourceStorageName		= 'camera'
						SourceStorageType		= SourceStorageName

					if self.SourceName in ['anyusb', 'usb', 'nvme'] and not todoSources:
						todoSources			= lib_storage.get_available_partitions(StorageType=self.SourceName, TargetDeviceIdentifier=self.TargetDevice.DeviceIdentifier, excludePartitions=completedSources_usb)
						SourceStorageName	= 'usb' if self.SourceName == 'anyusb' else self.SourceName
						SourceStorageType	= SourceStorageName

					if todoSources:
						Identifier	= todoSources[0]
					elif Identifier_OLD:
						# break if there is no futher source device (but wait if no source is done)
						break
					else:
						time.sleep(1)
						continue

				# MANAGE SOURCE DEVICE
				# Set the PWR LED to blink long to indicate waiting for the source device
				lib_system.rpi_leds(trigger='timer',delay_on=750,delay_off=250)

				if SourceStorageType in ['usb', 'internal','nvme', 'camera', 'cloud', 'cloud_rsync']:
					self.SourceDevice	= lib_storage.storage(StorageName=SourceStorageName, Role=lib_storage.role_Source, WaitForDevice=True, DeviceIdentifierPresetThis=Identifier, DeviceIdentifierPresetOther=self.TargetDevice.DeviceIdentifier)

					self.SourceDevice.mount()

				elif SourceStorageType in ['thumbnails', 'database', 'exif']:
					pass
				else:
					self.__display.message([f":{self.__lan.l('box_backup_invalid_mode_combination_1')}", f":{self.__lan.l('box_backup_invalid_mode_combination_2')}", f":{self.__lan.l('box_backup_invalid_mode_combination_3')}"])
					return()

				# remember SourceStorageName for next run
				if SourceStorageName=='camera':
					Identifier_OLD	= lib_storage.format_CameraIdentifier(self.SourceDevice.DeviceIdentifier, self.SourceDevice.CameraPort)
				elif SourceStorageName in ['usb', 'nvme']:
					Identifier_OLD	= self.SourceDevice.DeviceIdentifier

				# check invalid combinations of Source and Target
				if (
					(SourceStorageName == self.TargetDevice.StorageType and not (SourceStorageName in ['usb', 'cloud'])) or				# usb to usb and cloud to cloud are the only methods where type of Source and Target can be equal
					(																													# exclude cloud to cloud for equal cloud services
						SourceStorageName == 'cloud' and
						self.TargetDevice.StorageType == 'cloud' and
						self.SourceDevice.CloudServiceName == self.TargetDevice.CloudServiceName
					) or
					(SourceStorageName == 'cloud_rsync') or																				# can't write device identifier to rsync server
					(self.TargetDevice.StorageType == 'camera') or																		# camera never can be target
					(SourceStorageName	== 'camera' and self.TargetDevice.StorageType == 'cloud_rsync')									# camera can't rsync to rsyncserver as this is not supported by gphoto2
				):
					self.__display.message([f":{SourceStorageName}>{self.TargetDevice.StorageType}{self.__lan.l('box_backup_invalid_mode_combination_1')}", f":{self.__lan.l('box_backup_invalid_mode_combination_2')}", f":{self.__lan.l('box_backup_invalid_mode_combination_3')}"])
					return()

				#run backup
				self.__reporter	= lib_backup.reporter(
					lan						= self.__lan,
					SourceStorageType		= SourceStorageType,
					SourceCloudService		= self.SourceDevice.CloudServiceName,
					SourceDeviceLbbDeviceID	= self.SourceDevice.LbbDeviceID,
					TargetStorageType		= self.TargetDevice.StorageType,
					TargetCloudService		= self.TargetDevice.CloudServiceName,
					TargetDeviceLbbDeviceID = self.TargetDevice.LbbDeviceID,
					TransferMode			= self.TransferMode,
					move_files				= self.move_files,
					SourceWasTarget			= self.SourceDevice.wasTarget,
					SyncLog					= self.__conf_LOG_SYNC
				)

				lib_system.rpi_leds(trigger='heartbeat')

				# define variables for backup
				SourceFolderNumber		= 0

				FilesToProcess			= 0

				ErrorsOld				= []

				# define specific parameters
				syncOptions	= self.get_syncOptions(TransferMode=self.TransferMode)
				excludeTIMS		= self.get_excludeTIMS(SourceStorageType=SourceStorageType)

				# SubPaths loop
				for SubPathAtSource in self.SourceDevice.SubPathsAtSource:

					self.__reporter.new_folder(SubPathAtSource)

					self.__log.message(f"Backup from {SourceStorageName}: {SubPathAtSource}",3)

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
						if "Err.: Lost device!" in ErrorsOld:
							self.__log.execute('Lost device: pre remount','lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE',3)

							if not self.TargetDevice.mounted():
								self.__log.message(f"remount target device {self.TargetDevice.StorageType} {self.TargetDevice.CloudServiceName} {self.TargetDevice.DeviceIdentifier}",3)
								self.TargetDevice.mount(TimeOutActive=False)

							if not self.SourceDevice.mounted():
								self.__log.message(f"remount source device {SourceStorageName} {self.SourceDevice.CloudServiceName} {self.SourceDevice.DeviceIdentifier}",3)
								if not self.SourceDevice.mount(TimeOutActive=True):
									self.__reporter.add_error('Err.: Remounting device failed!')

						# Check for lost devices
						if self.__checkLostDevice():
							self.__reporter.add_error('Err.: Lost device!')
							self.__log.execute("Lost device", "lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE",3)
							self.__log.message(lib_system.get_abnormal_system_conditions(self.__lan),1)

							ErrorsOld	= self.__reporter.get_errors()

							continue

						self.__display.message([f":{self.__lan.l('box_backup_working')}..."])

						FilesToProcess	= self.calculate_files_to_sync(SubPathAtSource)
						self.__reporter.set_values(FilesToProcess=FilesToProcess)
						self.__log.message(f"Files to sync before backup: {FilesToProcess}",3)

						if SourceStorageName == 'camera' and self.SourceDevice.LbbDeviceID:
							SourceLabel	= self.SourceDevice.LbbDeviceID
						elif SourceStorageName == 'camera' and self.SourceDevice.DeviceIdentifier:
							SourceLabel	= self.SourceDevice.DeviceIdentifier
						else:
							if SourceStorageType in ['usb', 'nvme', 'camera']:
								SourceLabel	= self.__lan.l(f"box_backup_mode_{SourceStorageType}s")
							else:
								SourceLabel	= self.__lan.l(f"box_backup_mode_{SourceStorageType}")

						DisplayLine1	= SourceLabel + f" {self.SourceDevice.CloudServiceName}{SourceFolderFracture}"		# header1
						DisplayLine2	= ' > ' + self.__lan.l(f"box_backup_mode_{self.TargetDevice.StorageType}") + f" {self.TargetDevice.CloudServiceName}"	# header2

						#define progress object
						progress	= lib_backup.progressmonitor(
							setup	= self.__setup,
							display				= self.__display,
							log					= self.__log,
							lan					= self.__lan,
							FilesToProcess		= FilesToProcess,
							DisplayLine1		= DisplayLine1,
							DisplayLine2		= DisplayLine2,
							SourceDevice		= self.SourceDevice,
							TargetDevice		= self.TargetDevice,
							vpn					= self.vpn
						)

						SyncStartTime	= lib_system.get_uptime_sec()

						# RUN BACKUP

						## create target path if not exists and enter dir
						if self.TargetDevice.FilesStayInPlace:
							try:
								pathlib.Path(self.TargetDevice.MountPoint, self.SourceDevice.SubPathAtTarget).mkdir(parents=True, exist_ok=True)
							except:
								self.__reporter.add_error('Err.: Lost device!')

								ErrorsOld	= self.__reporter.get_errors()
								continue

							self.TargetDevice.set_mountpoint_permissions()

						SyncReturnCode	= 0

#						# gphoto2 backup
						if SourceStorageName == 'camera':
							# not mountable targets are excluded.

							os.chdir(os.path.join(self.TargetDevice.MountPoint, self.SourceDevice.SubPathAtTarget))

							# gphoto2: Filename-format at backup; %F is undocumented? = path of the file at the camera; $f = filename without suffix; %C=suffix
							Command	= ["gphoto2", "--camera", self.SourceDevice.DeviceIdentifier, "--port", self.SourceDevice.CameraPort, "--filename", "%F/%f.%C", "--get-all-files", "--folder", SubPathAtSource, "--skip-existing"]
							self.__log.message(' '.join(Command),3)

							with subprocess.Popen(Command, stdout=subprocess.PIPE, stderr=subprocess.PIPE, bufsize=1, text=True) as BackupProcess:

								while True:
									SyncOutputLine = BackupProcess.stdout.readline().decode()
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

#						# rsync or rclone backup
						else:
							if self.TransferMode == 'rsync':
								Command	= self.TargetDevice.rsyncSSH + ['rsync'] + syncOptions + excludeTIMS + [os.path.join(self.SourceDevice.MountPoint, SubPathAtSource), os.path.join(self.TargetDevice.MountPoint, self.SourceDevice.SubPathAtTarget)]

							elif self.TransferMode == 'rclone':
								Command	= ['rclone', '--config', self.__RCLONE_CONFIG_FILE] + syncOptions + excludeTIMS + \
									[
										f'{self.SourceDevice.CloudServiceName}:{self.SourceDevice.rclonePath}' if self.SourceDevice.CloudServiceName else os.path.join(self.SourceDevice.MountPoint, SubPathAtSource),
										f'{self.TargetDevice.CloudServiceName}:{os.path.join(self.TargetDevice.rclonePath, self.SourceDevice.SubPathAtTarget) if self.TargetDevice.FilesStayInPlace else self.TargetDevice.rclonePath}' if self.TargetDevice.CloudServiceName else os.path.join(self.TargetDevice.MountPoint, self.SourceDevice.SubPathAtTarget)
									]

							else:
								self.__log.message(f'Error: "{self.TransferMode}" is no valid transfer mode.', 1)
								return(None)

							self.__log.message(' '.join(Command),3)

							with subprocess.Popen(Command, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, bufsize=1, text=True)  as BackupProcess:
								while True:
									SyncOutputLine = BackupProcess.stdout.readline()
									if not SyncOutputLine:
										break

									progress.progress(TransferMode=self.TransferMode, SyncOutputLine=SyncOutputLine)

									if SyncOutputLine[0] != ' ' and not ' DEBUG : ' in SyncOutputLine and not ' NOTICE: ' in SyncOutputLine:
										LogLine	= SyncOutputLine.strip()
										LogLine	= LogLine if LogLine[20:24] != 'INFO' else LogLine[27:]

										if LogLine=='./':
											continue

										self.__reporter.add_synclog(LogLine)

								self.__reporter.set_values(FilesProcessed=progress.CountProgress)
								self.__reporter.set_values(FilesCopied=progress.CountJustCopied)
								self.__TIMSCopied	= progress.TIMSCopied

								BackupProcess.wait()
								SyncReturnCode	= BackupProcess.returncode
								self.__reporter.set_values(SyncReturnCode=SyncReturnCode)
							pass

						SyncStopTime	= lib_system.get_uptime_sec()

						# VPN check
						if self.vpn:
							self.__reporter.add_synclog(f"\n** VPN: {self.vpn.check_status(0)} **\n\n")

						# Remove empty files (maybe can result from disconnection of a source-device)
						if self.TargetDevice.mountable and self.TargetDevice.FilesStayInPlace:
							SourceCommand	= ['find',  os.path.join(self.TargetDevice.MountPoint,  self.SourceDevice.SubPathAtTarget), '-type', 'f','-size', '0']
							FilterCommand	= ['wc', '-l']

							try:
								progress.CountProgress	-= int(lib_common.pipe(SourceCommand, FilterCommand).decode())
							except:
								pass

							SourceCommand	+= ['-delete']
							try:
								subprocess.run(SourceCommand)
							except:
								self.__log.message(f"Error: '{' '.join(SourceCommand)}'")

						# Re-calculate FilesToProcess
						FilesToProcessPost	= FilesToProcess - progress.CountProgress

						self.__reporter.set_values(FilesToProcessPost=FilesToProcessPost)

						# Transfer completed?
						if FilesToProcessPost > 0:
							self.__reporter.add_error('Err.: Files missing!')
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
							self.__reporter.add_error('Err.: Lost device!')
							self.__log.execute("Lost device", "lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE",3)
							self.__log.message(lib_system.get_abnormal_system_conditions(self.__lan),1)


						# Controller-overheating-error?
						SyncTimeDiff	= SyncStopTime - SyncStartTime

						if (
							'Err.: Lost device!' in self.__reporter.get_errors() and
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

						FilesList	= progress.FilesList

						del progress

						# validate files after backup
						if SourceStorageName == 'camera':
							DisplayLine1	= self.__lan.l('box_backup_validate_files_from')		# header1
							DisplayLine2	= SourceLabel + f" {self.SourceDevice.CloudServiceName}{SourceFolderFracture}"	# header2
							progress	= lib_backup.progressmonitor(self.__setup, self.__display, self.__log, self.__lan, len(FilesList), DisplayLine1, DisplayLine2)

							FilesValidationFailed	= 0
							FilesListCopy	= FilesList.copy()
							for File in FilesListCopy:
								if (not os.path.isfile(os.path.join(self.TargetDevice.MountPoint, self.SourceDevice.SubPathAtTarget, File))):
									FilesValidationFailed	+= 1
									FilesList.remove(File) # do not remove this file from camera

								progress.progress(TransferMode='gphoto2')

							del progress

							if (FilesValidationFailed > 0):
								self.__reporter.add_error('Err.: File validation(s) failed!')
								self.__log.message(f"{FilesValidationFailed} file validation(s) failed.")

						# delete files from camera
						if SourceStorageName == 'camera' and self.move_files and FilesList:
							progress	= lib_backup.progressmonitor(self.__setup, self.__display, self.__log, self.__lan, len(FilesList), self.__lan.l('box_backup_camera_removing_files_1'), self.__lan.l('box_backup_camera_removing_files_2'))

							for FileRemove in FilesList:
								cam_folder	= os.path.dirname(FileRemove)
								cam_file	= os.path.basename(FileRemove)

								if not os.path.isfile(os.path.join(self.TargetDevice.MountPoint, self.SourceDevice.SubPathAtTarget, cam_folder, cam_file)):
									continue

								Command	= ["gphoto2", "--camera", self.SourceDevice.DeviceIdentifier, "--port", self.SourceDevice.CameraPort, '--folder', f"/{cam_folder}", '--delete-file', cam_file]
								try:
									subprocess.run(Command)
									progress.progress(TransferMode='gphoto2')
								except:
									pass

							del progress

				# umount source
				if self.SourceDevice.mountable:
					self.SourceDevice.umount()

				# Mail result
				if self.conf_MAIL_NOTIFICATIONS:
					mail	= lib_mail.mail()
					self.__reporter.prepare_mail()
					self.__mail_threads_started.append(
						mail.sendmail(self.__reporter.mail_subject,self.__reporter.mail_content_PLAIN,self.__reporter.mail_content_HTML)
					)

				# exit loop?
				if not dynamicSources:
					break

			self.__display.message([f":{self.__lan.l('box_finished')}"])

		# VPN stop
		if self.vpn:
			# Wait for running threads (mails to send)
			lib_common.join_threads(self.__display, self.__lan,self.__mail_threads_started, self.conf_MAIL_TIMEOUT_SEC)

			#stop VPN
			self.vpn.stop()
			del self.vpn
			ip_info	= lib_cron_ip.ip_info()
			ip_info.display_ip()
			self.__mail_threads_started.append( ip_info.mail_ip() )

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

				progress	= lib_backup.progressmonitor(self.__setup, self.__display, self.__log, self.__lan, FilesToProcess, DisplayLine1, DisplayLine2)

				for KnownFile in KnownFilesList:
					ID			= KnownFile[0]
					FileName	= os.path.join(self.TargetDevice.MountPoint, KnownFile[1].strip('/'))

					if not os.path.isfile(FileName):
						db.dbExecute(f"DELETE from EXIF_DATA WHERE ID={ID};")
						self.__log.message(f"DELETE from EXIF_DATA WHERE ID={ID};", 3)

					progress.progress()

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
				TIMSList[:]	= [element for element in TIMSList if element]

				# prepare loop to insert images into the database
				FilesToProcess	= len(TIMSList)

				DisplayLine1	= self.__lan.l('box_backup_generating_database')					# header1
				DisplayLine2	= self.__lan.l(f'box_backup_mode_{self.TargetDevice.StorageType}')	# header2

				progress	= lib_backup.progressmonitor(self.__setup,self.__display,self.__log,self.__lan,FilesToProcess,DisplayLine1,DisplayLine2)

				for TimsFileName in TIMSList:
					OrigFileName	= TimsFileName.replace(self.TargetDevice.MountPoint,'',1).rsplit('.',1)[0]	# remove mountpoint and remove second extension from tims
					OrigFileName	= '/'.join(OrigFileName.rsplit('/tims/', 1)) 								# remove /tims from folder
					ImageFilePath	= os.path.dirname(OrigFileName)
					ImageFileName	= os.path.basename(OrigFileName)
					if not db.dbSelect(f"select ID from EXIF_DATA where File_Name='{ImageFileName}' and Directory='{ImageFilePath}'"):
						db.dbInsertImage(OrigFileName)

					progress.progress()

				del progress
				self.__display.message([f":{self.__lan.l('box_finished')}"])

	def generateThumbnails(self):
		if self.TargetDevice:
			if self.TargetDevice.isLocal:

				lib_system.rpi_leds(trigger='timer',delay_on=900,delay_off=100)

				if os.path.isfile('/usr/lib/libraw/dcraw_emu'):
					DCRAW_EMU	= '/usr/lib/libraw/dcraw_emu'
				else:
					try:
						DCRAW_EMU	= subprocess.check_output(['whereis', 'dcraw_emu']).decode().strip().split('\n')[0].split(' ')[1]
					except:
						DCRAW_EMU	= 'dcraw_emu'

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
				ImagesList[:]	= [element for element in ImagesList if element]
				ImagesList.sort()
				ImagesList = [i.replace(self.TargetDevice.MountPoint,'',1) for i in ImagesList]

				# find all tims
				Command	= f"find '{self.TargetDevice.MountPoint}' -type f -iname '*.jpg' -path '*/tims/*' {' '.join(BannedPathsViewCaseInsensitive)}"

				TIMSList	= subprocess.check_output(Command,shell=True).decode().strip().split('\n')
				TIMSList[:]	= [element for element in TIMSList if element]
				TIMSList.sort()
				#convert tims filenames to original filenames
				for i, TIMS in enumerate(TIMSList):
					TIMSList[i]	= TIMS.replace(self.TargetDevice.MountPoint,'',1).rsplit('.',1)[0] 			# remove self.TargetDevice.MountPoint and second extension
					TIMSList[i]	= '/'.join(TIMSList[i].rsplit('/tims/', 1))									# remove /tims from folder

				#remove from ImagesList all items known in TIMSList
				MissingTIMSList	= list(set(ImagesList) - set(TIMSList))

				#prepare loop to create thumbnails
				FilesToProcess	= len(MissingTIMSList)

				DisplayLine1	= self.__lan.l('box_backup_generating_thumbnails') # header1
				DisplayLine2	= self.__lan.l(f'box_backup_mode_{self.TargetDevice.StorageType}') # header2

				progress	= lib_backup.progressmonitor(self.__setup,self.__display,self.__log,self.__lan,FilesToProcess,DisplayLine1,DisplayLine2)

				for SourceFilePathName in MissingTIMSList:
					SourceFilePathName	= SourceFilePathName.strip('/')
					#extract Extension from filename
					try:
						SourceFilePathNameExt	= SourceFilePathName.rsplit('.',1)[1].lower()
					except:
						SourceFilePathNameExt	= ''

					TIMS_Dir				= os.path.join(os.path.dirname(SourceFilePathName), 'tims')
					pathlib.Path(self.TargetDevice.MountPoint, TIMS_Dir).mkdir(parents=True, exist_ok=True)

					FileName				= os.path.basename(SourceFilePathName)
					TIMS_SubpathFilename	= os.path.join(TIMS_Dir, f"{FileName}.JPG")

					if SourceFilePathNameExt in f"{const_FILE_EXTENSIONS_LIST_WEB_IMAGES};{const_FILE_EXTENSIONS_LIST_TIF}".split(';'):
						# file-types: jpeg, tif image
						Command	= ["convert", f"{os.path.join(self.TargetDevice.MountPoint, SourceFilePathName)}[0]", "-resize", "800>", os.path.join(self.TargetDevice.MountPoint, TIMS_SubpathFilename)]
						try:
							subprocess.run(Command)
						except:
							print(f"Error: {' '.join(Command)}",file=sys.stderr)
					elif SourceFilePathNameExt in const_FILE_EXTENSIONS_LIST_HEIC.split(';'):

						# file-type: heic/heif
						# convert heif to jpg
						Command	= ["heif-convert", os.path.join(self.TargetDevice.MountPoint, SourceFilePathName), os.path.join(self.TargetDevice.MountPoint, f"{SourceFilePathName}.JPG")]
						try:
							subprocess.run(Command)
						except:
							print(f"Error: {' '.join(Command)}",file=sys.stderr)

						# transfer exif from heif to jpg
						Command	= ["exiftool", "-overwrite_original", "-TagsFromFile", os.path.join(self.TargetDevice.MountPoint, SourceFilePathName), os.path.join(self.TargetDevice.MountPoint, f"{SourceFilePathName}.JPG")]
						try:
							subprocess.run(Command)
						except:
							print(f"Error: {' '.join(Command)}",file=sys.stderr)

						# create tims file
						Command	= ["convert", os.path.join(self.TargetDevice.MountPoint, f"{SourceFilePathName}.JPG"), "-resize", "800>", os.path.join(self.TargetDevice.MountPoint, TIMS_SubpathFilename)]
						try:
							subprocess.run(Command)
						except:
							print(f"Error: {' '.join(Command)}",file=sys.stderr)

						if conf_VIEW_CONVERT_HEIC:
							MissingTIMSList.append(f"{SourceFilePathName}.JPG")
						else:
							Command	= ["rm", os.path.join(self.TargetDevice.MountPoint, f"{SourceFilePathName}.JPG")]
							try:
								subprocess.run(Command)
							except:
								print(f"Error: {' '.join(Command)}",file=sys.stderr)

					elif SourceFilePathNameExt in const_FILE_EXTENSIONS_LIST_RAW.split(';'):
						# file-type: raw-image
						SourceCommand	= [DCRAW_EMU, "-w", "-Z", "-", os.path.join(self.TargetDevice.MountPoint, SourceFilePathName)]
						FilterCommand	= ["convert", "-", "-resize", "800", os.path.join(self.TargetDevice.MountPoint, TIMS_SubpathFilename)]
						try:
							lib_common.pipe(SourceCommand,FilterCommand)
						except:
							print(f"Error: {' '.join(SourceCommand) + ' | ' + ' '.join(FilterCommand)}",file=sys.stderr)

					elif SourceFilePathNameExt in const_FILE_EXTENSIONS_LIST_VIDEO.split(';'):
						# file-type: video
						Command	= ["ffmpeg", "-i", os.path.join(self.TargetDevice.MountPoint, SourceFilePathName), "-ss", "00:00:01", "-vframes", "1", os.path.join(self.TargetDevice.MountPoint, TIMS_SubpathFilename)]
						try:
							subprocess.run(Command)
						except:
							print(f"Error: {' '.join(Command)}",file=sys.stderr)

						if not os.path.isfile(os.path.join(self.TargetDevice.MountPoint, TIMS_SubpathFilename)):
							# tims file not generated. Video too short? Try at second 0
							Command	= ["ffmpeg", "-i", f"{self.TargetDevice.MountPoint}/{SourceFilePathName}", "-ss", "00:00:00", "-vframes", "1", os.path.join(self.TargetDevice.MountPoint, TIMS_SubpathFilename)]
							try:
								subprocess.run(Command)
							except:
								print(f"Error: {' '.join(Command)}",file=sys.stderr)

						Command	= ["mogrify", "-resize", "800>", os.path.join(self.TargetDevice.MountPoint, TIMS_SubpathFilename)]
						subprocess.run(Command)

						Command	=["composite", "-gravity", "center", "/var/www/little-backup-box/img/play.png", os.path.join(self.TargetDevice.MountPoint, TIMS_SubpathFilename), os.path.join(self.TargetDevice.MountPoint, TIMS_SubpathFilename)]
						try:
							subprocess.run(Command)
						except:
							print(f"Error: {' '.join(Command)}",file=sys.stderr)

					elif SourceFilePathNameExt in const_FILE_EXTENSIONS_LIST_AUDIO.split(';'):
						Command	= ["cp", "/var/www/little-backup-box/img/audio.JPG", os.path.join(self.TargetDevice.MountPoint, TIMS_SubpathFilename)]
						try:
							subprocess.run(Command)
						except:
							print(f"Error: {' '.join(Command)}",file=sys.stderr)

						Command	= ["convert", os.path.join(self.TargetDevice.MountPoint, TIMS_SubpathFilename), "-gravity", "center", "-pointsize", "50", "-annotate", "0", FileName, os.path.join(self.TargetDevice.MountPoint, TIMS_SubpathFilename)]
						try:
							subprocess.run(Command)
						except:
							print(f"Error: {' '.join(Command)}",file=sys.stderr)

					if not os.path.isfile(os.path.join(self.TargetDevice.MountPoint, TIMS_SubpathFilename)):
						self.__log.message(f"ERROR: TIMS of '{os.path.join(self.TargetDevice.MountPoint, SourceFilePathName)}' ('{os.path.join(self.TargetDevice.MountPoint, TIMS_SubpathFilename)}') not regular created.")

						Command	= ["cp", "/var/www/little-backup-box/img/unknown.JPG", os.path.join(self.TargetDevice.MountPoint, TIMS_SubpathFilename)]
						try:
							subprocess.run(Command)
						except:
							print(f"Error: {' '.join(Command)}",file=sys.stderr)

						Command	= ["convert", os.path.join(self.TargetDevice.MountPoint, TIMS_SubpathFilename), "-gravity", "center", "-pointsize", "50", "-annotate", "0", FileName, os.path.join(self.TargetDevice.MountPoint, TIMS_SubpathFilename)]
						try:
							subprocess.run(Command)
						except:
							print(f"Error: {' '.join(Command)}",file=sys.stderr)

					db.dbInsertImage(SourceFilePathName)

					progress.progress()

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

				for FileTuple in FilesTupleList:
					#replace substitute of space by space
					MediaID			= FileTuple[0]
					MediaPathFile	= os.path.join(self.TargetDevice.MountPoint, FileTuple[1].strip('/'))
					MediaLbbRating	= FileTuple[2]

					subprocess.run(['exiftool', '-overwrite_original', f'-Rating={MediaLbbRating}',MediaPathFile])
					db.dbExecute(f"update EXIF_DATA set Rating={MediaLbbRating} where ID={MediaID};")

					progress.progress()

				del progress
				self.__display.message([f":{self.__lan.l('box_finished')}"])

	def calculate_files_to_sync(self, singleSubPathsAtSource=None):
		if singleSubPathsAtSource:
			checkPathsList	= [singleSubPathsAtSource]
		else:
			checkPathsList	= self.SourceDevice.SubPathsAtSource

		FilesToProcess		= 0
		FilesToProcessPart	= 0

		# To define a new method, add an elif block (example below)

		if self.SourceDevice.StorageType in ['usb','internal', 'nvme','cloud','cloud_rsync']:
			## Source is mounted (mountable) device

			syncOptions	= self.get_syncOptions(TransferMode=self.TransferMode, dry_run=True)
			excludeTIMS	= self.get_excludeTIMS(SourceStorageType=self.SourceDevice.StorageType)

			for SubPathAtSource in checkPathsList:
				if self.TransferMode == 'rsync':
					SourceCommand	= self.TargetDevice.rsyncSSH + ['rsync'] + syncOptions + excludeTIMS + [os.path.join(self.SourceDevice.MountPoint, SubPathAtSource), os.path.join(self.TargetDevice.MountPoint, self.SourceDevice.SubPathAtTarget)]
					FilterCommand		= ['grep', 'Number of regular files transferred:']
				elif self.TransferMode == 'rclone':
					SourceCommand	= ['rclone', '--config', self.__RCLONE_CONFIG_FILE] + syncOptions + excludeTIMS + \
									[
										f'{self.SourceDevice.CloudServiceName}:{self.SourceDevice.rclonePath}' if self.SourceDevice.CloudServiceName else os.path.join(self.SourceDevice.MountPoint, SubPathAtSource),
										f'{self.TargetDevice.CloudServiceName}:{os.path.join(self.TargetDevice.rclonePath, self.SourceDevice.SubPathAtTarget) if self.TargetDevice.FilesStayInPlace else self.TargetDevice.rclonePath}' if self.TargetDevice.CloudServiceName else os.path.join(self.TargetDevice.MountPoint, self.SourceDevice.SubPathAtTarget)
									]
					FilterCommand	= []

				self.__log.message(' '.join(SourceCommand),3)
				self.__log.message(' '.join(FilterCommand),3)
				try:
					if self.TransferMode == 'rsync':
						try:
							FilesToProcessPart	= int(lib_common.pipe(SourceCommand, FilterCommand).decode().split(':')[1].strip(' ,'))
						except:
							FilesToProcessPart	= 0

					elif self.TransferMode == 'rclone':
						rclone_output	= []
						with subprocess.Popen(SourceCommand, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, bufsize=1, text=True)  as BackupProcess:
							while True:
								OutputLine = BackupProcess.stdout.readline()
								if not OutputLine:
									break

								rclone_output	+= [OutputLine.strip()]

						FilesToProcessPart	= 0
						for line in reversed(rclone_output):
							if ': file not in webdav root' in line:
								FilesToProcessPart	+= 1
								continue
							if line.endswith('matching files'):
								try:
									FilesToProcessPart	= int(line.split()[-3])
									break
								except:
									pass


				except:
					FilesToProcessPart	= 0

				self.__log.message(f"Files to sync from folder '{SubPathAtSource}': {FilesToProcessPart}")

				FilesToProcess	+= FilesToProcessPart

		elif self.SourceDevice.StorageType == 'camera':	# Source camera

			for SubPathAtSource in checkPathsList:

				SourceCommand		= ["gphoto2", "--camera", self.SourceDevice.DeviceIdentifier, "--port", self.SourceDevice.CameraPort, "--list-files", "--folder", f"{SubPathAtSource}"]
				FilterCommand		= ["grep", "^#"]

				self.__log.message(' '.join(SourceCommand) + ' | ' + ' '.join(FilterCommand),3)

				try:
					gphoto2			= lib_common.pipe(SourceCommand,FilterCommand).decode()
					FilesToProcess	+= len(gphoto2.strip().split('\n'))
				except:
					FilesToProcess	= 0

				self.__log.message(f"Files in folder '{SubPathAtSource}': {FilesToProcessPart}")

		return(FilesToProcess)

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

		lib_storage.umount(self.__setup,'all')

		if self.__reporter is None:
			display_summary	= []
		else:
			self.__reporter.prepare_display_summary()
			display_summary	= self.__reporter.display_summary


		# Wait for running threads (mails to send)
		lib_common.join_threads(self.__display, self.__lan,self.__mail_threads_started, self.conf_MAIL_TIMEOUT_SEC)

		if self.SecundaryBackupFollows:
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
	lan		= lib_language.language()
	display	= lib_display.display()

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

	SourceChoices	= ['anyusb', 'usb', 'internal', 'nvme', 'camera'] + CloudServices + ['cloud_rsync', 'thumbnails', 'database', 'exif']
	parser.add_argument(
		'--SourceName',
		'-s',
		choices		= SourceChoices,
		required =	True,
		help=f'Source name, one of {SourceChoices}'
	)

	TargetChoices	= ['usb', 'internal', 'nvme'] + CloudServices + ['cloud_rsync']
	parser.add_argument(
		'--TargetName',
		'-t',
		choices		= TargetChoices,
		required	= True,
		help		= f'Target name, one of {TargetChoices}'
	)

	parser.add_argument(
		'--move-files',
		'-move',
		required	= False,
		default		= 'setup',
		help		= 'Remove source files after backup from primary storage? [\'True\', \'False\']'
	)

	parser.add_argument(
		'--sync-database',
		'-sd',
		required	= False,
		default		= 'False',
		help		= 'Should the View database be synchronized after backup? [\'True\', \'False\'] If not set, use config value.'
	)

	parser.add_argument(
		'--generate-thumbnails',
		'-gt',
		required	= False,
		default		= 'setup',
		help		= 'Create thumbnails for View after backup (Local storages only)? [\'True\', \'False\'] If not set, use config value.'
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
		help='Device identifier preset for source, e.g --uuid 123..., /dev/sdb1, etc.'
	)

	parser.add_argument(
		'--device-identifier-preset-target',
		'-ti',
		required	= False,
		default='',
		help='Device identifier preset for source, e.g --uuid 123..., /dev/sda1, etc.'
	)

	parser.add_argument(
		'--power-off',
		'-p',
		required	= False,
		default		= 'setup',
		help		= 'Power off after backup? [\'True\', \'False\']. If not set, use config value.'
	)

	SecSourceChoices	= ['usb', 'internal', 'nvme']
	parser.add_argument(
		'--SecSourceName',
		'-2s',
		choices		= SecSourceChoices,
		required =	False,
		help=f'Source name, one of {SecSourceChoices}'
	)

	SecTargetChoices	= CloudServices + ['cloud_rsync']
	parser.add_argument(
		'--SecTargetName',
		'-2t',
		choices		= SecTargetChoices,
		required	= False,
		help		= f'Target name, one of {SecTargetChoices}'
	)

	args	= vars(parser.parse_args())

	# clean boolean args
	args['move_files']			= args['move_files'].lower() == 'true'			if args['move_files'] != 'setup'	else 'setup'
	args['sync_database']		= args['sync_database'].lower() == 'true'
	args['generate_thumbnails']	= args['generate_thumbnails'].lower() == 'true'	if args['generate_thumbnails'] != 'setup'	else 'setup'
	args['update_exif']			= args['update_exif'].lower() == 'true'			if args['update_exif'] != 'setup'			else 'setup'
	args['power_off']			= args['power_off'].lower() == 'true'			if args['power_off'] != 'setup'				else 'setup'

	SecundaryBackupFollows	= args['SecSourceName'] and args['SecTargetName']

	# primary backup
	if SecundaryBackupFollows:
		display.message([f":{lan.l('box_backup_primary')}"])

	backupObj	= backup(
		SourceName=args['SourceName'],
		TargetName=args['TargetName'],
		move_files=args['move_files'],
		DoSyncDatabase=args['sync_database'],
		DoGenerateThumbnails=args['generate_thumbnails'],
		DoUpdateEXIF=args['update_exif'],
		DeviceIdentifierPresetSource=args['device_identifier_preset_source'],
		DeviceIdentifierPresetTarget=args['device_identifier_preset_target'],
		PowerOff=args['power_off'],
		SecundaryBackupFollows=SecundaryBackupFollows
	)
	backupObj.run()

	# secondary backup
	secSourceDeviceIdentifier	= None
	if SecundaryBackupFollows:
		display.message([f":{lan.l('box_backup_secondary')}"])

		secSourceDeviceIdentifier	= None
		if  args['TargetName'] == 'usb' and args['SecSourceName'] == 'usb':
			try:
				secSourceDeviceIdentifier	= backupObj.TargetDevice.DeviceIdentifier
			except:
				pass

		backup(
			SourceName=args['SecSourceName'],
			TargetName=args['SecTargetName'],
			move_files=args['move_files'],
			DoSyncDatabase=False,
			DoGenerateThumbnails=False,
			DoUpdateEXIF=False,
			DeviceIdentifierPresetSource=secSourceDeviceIdentifier,
			DeviceIdentifierPresetTarget=None,
			PowerOff=args['power_off'],
			SecundaryBackupFollows=False
		).run()


