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

import glob
import datetime
import math
import os
import pathlib
import random
import re
from string import digits
import subprocess
import sys
import time

import lib_common
import lib_display
import lib_language
import lib_log
import lib_setup

### debug

#import lib_debug

### /debug

role_Source	= 'source'
role_Target	= 'target'

FS_Types_supported	= ['ext2','ext3','ext4','fat','vfat','exfat','ntfs','hfs','hfsplus']


class storage(object):

# exit codes:
# 101: storage type = usb but no role defined

	def __init__(self, StorageName, Role, WaitForDevice=True, DeviceIdentifierPresetThis=None, DeviceIdentifierPresetOther=None):
		#StorageName: 					one of ['usb', 'internal', 'camera', 'cloud:SERVICE_NAME', 'cloud_rsync']
		#Role:							[lib_storage.role_Source, lib_storage.role_Target]
		#DeviceIdentifierPresetThis:	['--uuid 123...', 'sda1', ...]
		#WaitForDevice:					True/False, retry until device is available

		self.StorageType, self.CloudServiceName		= extractCloudService(StorageName)
		self.Role									= Role
		self.WaitForDevice							= WaitForDevice
		self.DeviceIdentifierPresetThis				= DeviceIdentifierPresetThis
		self.DeviceIdentifierPresetOther			= DeviceIdentifierPresetOther

		self.__WORKING_DIR = os.path.dirname(__file__)

		self.__setup	= lib_setup.setup()
		self.__const_STORAGE_DEV_MASK					= self.__setup.get_val('const_STORAGE_DEV_MASK')

		self.__const_MEDIA_DIR							= self.__setup.get_val('const_MEDIA_DIR')
		self.__const_TECH_MOUNT_TARGET					= self.__setup.get_val('const_TECH_MOUNT_TARGET')
		self.__const_TECH_MOUNT_SOURCE					= self.__setup.get_val('const_TECH_MOUNT_SOURCE')

		self.__const_MOUNTPOINT_SUBPATH_LOCAL_TARGET	= self.__setup.get_val('const_MOUNTPOINT_SUBPATH_LOCAL_TARGET')
		self.__const_MOUNTPOINT_SUBPATH_LOCAL_SOURCE	= self.__setup.get_val('const_MOUNTPOINT_SUBPATH_LOCAL_SOURCE')
		self.__const_MOUNTPOINT_SUBPATH_CLOUD_TARGET	= self.__setup.get_val('const_MOUNTPOINT_SUBPATH_CLOUD_TARGET')
		self.__const_MOUNTPOINT_SUBPATH_CLOUD_SOURCE	= self.__setup.get_val('const_MOUNTPOINT_SUBPATH_CLOUD_SOURCE')
		self.__const_INTERNAL_BACKUP_DIR				= self.__setup.get_val('const_INTERNAL_BACKUP_DIR')
		self.__conf_DISP_FRAME_TIME						= self.__setup.get_val('conf_DISP_FRAME_TIME')

		self.__RCLONE_CONFIG_FILE						= f"{self.__setup.get_val('const_MEDIA_DIR')}/{self.__setup.get_val('const_RCLONE_CONFIG_FILE')}"

		self.__mount_user		= "www-data"
		self.__mount_group		= "www-data"
		self.__mount_uid		= int(subprocess.check_output(['id','-u',f"{self.__mount_user}"]).decode().strip())
		self.__mount_gid		= int(subprocess.check_output(['id','-g',f"{self.__mount_group}"]).decode().strip())

		self.__display	= lib_display.display()
		self.__log		= lib_log.log()
		self.__lan		= lib_language.language()

		self.DeviceIdentifier	= ''	# will be set on mounting, will never be automatic unset
		self.CameraPort		= ''	# will be set on mounting, will never be automatic unset

		# for use in cloud_rsync only:
		self.rsyncSSH	= []

		# global backup parameters #####################
		self.LbbDeviceID			= ''
		self.LbbSourceDescriptor	= ''
		self.__set_mountpoint()
		self.SubPathsAtSource		= []
		self.SubPathAtTarget		= '' # subpath below targets MountPoint ('internal/xyz')

		self.mountable	= self.StorageType in ['usb', 'internal' ,'cloud']
		self.isLocal	= self.StorageType in ['usb', 'internal', 'camera']
		self.FS_Type	= ''

		self.__camera_connected	= False

	def mount(self):
		mounted	= False

		if self.StorageType == 'usb':
			mounted	= self.__mount_USB_storage()
		elif self.StorageType == 'internal':
			mounted	= self.__mount_internal()
		elif self.StorageType == 'camera':
			mounted	= self.__mount_camera()
		elif self.StorageType == 'cloud':
			mounted	= self.__mount_cloud()
		elif self.StorageType == 'cloud_rsync':
			mounted	= self.__mount_cloud_rsync()

		if mounted and (self.StorageType in ['usb', 'internal', 'cloud']):
			self.__manage_lbb_device_ID()

		return(mounted)

	def __mount_USB_storage(self):
		# mounts the device, if WAIT_FOR_MOUNT=true, waits until the device is available
		# returns uuid or false (if not mounted), "na" for not available
		# checks and remounts all by UUID_USBX given devices

		self.__log.message (f"mount device: {self.StorageType}, {self.Role}; WaitForDevice={self.WaitForDevice}; DeviceIdentifierPresetThis={self.DeviceIdentifierPresetThis}; DeviceIdentifierPresetOther={self.DeviceIdentifierPresetOther}",3)

		self.umount()

		if self.Role == role_Target:
			l_box_backup_insert_1	= self.__lan.l('box_backup_insert_target_1')
			l_box_backup_insert_2	= self.__lan.l('box_backup_insert_target_2')
		elif self.Role == role_Source:
			l_box_backup_insert_1	= self.__lan.l('box_backup_insert_source_1')
			l_box_backup_insert_2	= self.__lan.l('box_backup_insert_source_2')
		else:
			sys.exit(101)

		if self.MountPoint:
			self.__display.message([f":{l_box_backup_insert_1}", f":{l_box_backup_insert_2}"])

		# identify device, wait for it if self.WaitForDevice is True
		USB_DeviceList_old			= []
		USB_Device_other_lum_Alpha	= None
		DeviceChosenIdentifier		= None
		retry						= True if self.MountPoint else False
		while retry:

			# force to re-scan usb-devices
			Command	= ['udevadm','trigger']
			subprocess.run(Command)

			Command	= f"lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE | grep 'MOUNTPOINT=\"\"' | grep '^PATH=\\\"/dev/{self.__const_STORAGE_DEV_MASK}'"
			try:
				# get all devices having MOUNTPOINT="" and starting with "PATH=\"...
				USB_DeviceList = subprocess.check_output(Command,shell=True).decode().split('\n')
			except:
				USB_DeviceList = []

			# log if list of devices changed
			if USB_DeviceList != USB_DeviceList_old:
				USB_DeviceListStr	= '\n'.join(USB_DeviceList)
				self.__log.message(f"pre mount {self.StorageType}, {self.Role} (device list changed)\n{USB_DeviceListStr}",3)

				USB_DeviceList_old = USB_DeviceList

				#get LUM-Alpha of the other device (like sda for sda1) to exclude partitions on that device
				if self.DeviceIdentifierPresetOther:
					SourceCommand	= ["lsblk", "-p", "-P", "-o", "PATH,MOUNTPOINT,UUID"]
					FilterCommand	= ["grep", self.DeviceIdentifierPresetOther.replace('--uuid', '')]
					try:
						USB_Device_other = lib_common.pipe(SourceCommand,FilterCommand).decode()
						USB_Device_other_lum = USB_Device_other.split('"')[1]
						USB_Device_other_lum_Alpha = lum_other.decode().translate(str.maketrans('', '', digits))
					except:
						USB_Device_other_lum_Alpha	= None

				# find USB
				for USB_Device in USB_DeviceList:
					#get lum, lum_alpha (without partition number),UUID and FS type
					try:
						USB_Device_lum	= USB_Device.split('PATH=',1)[1].split('"',2)[1]
					except:
						USB_Device_lum	= None

					try:
						USB_Device_lum_Alpha	= USB_Device_lum.translate(str.maketrans('', '', digits))
					except:
						USB_Device_lum_Alpha	= None

					try:
						USB_Device_UUID = USB_Device.split('UUID=',1)[1].split('"',2)[1]
					except:
						USB_Device_UUID	= None

					try:
						Device_FS_Type = USB_Device.split('FSTYPE=',1)[1].split('"',2)[1]
					except:
						Device_FS_Type	= None

					#Check filesystem-type to be accepted
					if not Device_FS_Type in FS_Types_supported:
						Device_FS_Type	= None

					if USB_Device_UUID:
						DeviceIdentifier = f"--uuid {USB_Device_UUID}"
					else:
						DeviceIdentifier = USB_Device_lum

					if (
						Device_FS_Type and
						(self.DeviceIdentifierPresetOther != DeviceIdentifier) and
						((USB_Device_lum_Alpha != USB_Device_other_lum_Alpha) or (self.DeviceIdentifierPresetThis and self.DeviceIdentifierPresetOther)) and
						((not self.DeviceIdentifierPresetThis) or (DeviceIdentifier == self.DeviceIdentifierPresetThis))
						):
							DeviceChosenIdentifier = DeviceIdentifier
							self.FS_Type = Device_FS_Type

							self.__log.message (f"DeviceChosenIdentifier='{DeviceChosenIdentifier}' ('{USB_Device_lum}') prepared to mount at '{self.MountPoint}', {self.FS_Type}")

					if DeviceChosenIdentifier:
						break

			# Check if device is identified
			if DeviceChosenIdentifier or not self.WaitForDevice:
				# success: device to mount identified
				retry = False

			if retry:
				time.sleep(0.5)

		# Mount USB device
		MOUNTED	= None

		if DeviceChosenIdentifier:
			try:
				l_mount_device = self.__lan.l(f"box_backup_{self.StorageType}")
			except:
				l_mount_device = f"{self.StorageType}, {self.Role}"

			if not self.mounted():
				# device not mounted

				# clean mountpoint
				self.__clean_mountpoint()

				self.createPath()

				Result	= None

				if l_mount_device:
					self.__display.message([f":{self.__lan.l('box_backup_mount')}:", f":{l_mount_device}"])

				if self.FS_Type in ['fat','vfat','exfat','ntfs']:
					#windows-filesystems
					Command	= f"mount {DeviceChosenIdentifier} {self.MountPoint} -o uid={self.__mount_uid},gid={self.__mount_gid},umask=0"
					self.__log.message(Command,3)
					try:
						Result	= subprocess.check_output(Command,shell=True).decode()
					except:
						Result	= 'Error mounting'

				elif self.FS_Type in ['hfs','hfsplus']:
					# mac-filesystems
					Command	= f"mount -t {self.FS_Type} {DeviceChosenIdentifier} {self.MountPoint} -o uid={self.__mount_uid},gid={self.__mount_gid},umask=0"
					self.__log.message(Command,3)
					try:
						Result	= subprocess.check_output(Command,shell=True).decode()
					except:
						Result	= 'Error mounting'

				elif self.FS_Type in ['ext2','ext3','ext4']:
					# linux-filesystems
					# primary mount to TechMountPoint, then mount by "mount --bind" to MountPoint

					self.createPath(self.__TechMountPoint)

					CommandMount	= f"mount {DeviceChosenIdentifier} {self.__TechMountPoint}"
					self.__log.message(CommandMount,3)
					CommandBindFS	= f"bindfs --force-user={self.__mount_user} --force-group={self.__mount_group} --perms=0770 {self.__TechMountPoint} {self.MountPoint}"
					self.__log.message(CommandBindFS,3)
					try:
						subprocess.check_output(CommandMount,shell=True)
						Result	= subprocess.run(CommandBindFS,shell=True)
					except:
						Result	= 'Error mounting'

				self.__log.message(f"mounted {l_mount_device} '{DeviceChosenIdentifier}' at '{self.MountPoint}': Msg.='${Result}'", 2)
			else:
				self.__log.message(f"{l_mount_device} already mounted, nothing to do.",3)

			# Check finally for success
			if self.mounted():
				MOUNTED  = DeviceChosenIdentifier
				self.DeviceIdentifier = DeviceChosenIdentifier

				self.__display_storage_properties()
			else:
				MOUNTED	= None

		self.__log.message(f"mount USB device: {MOUNTED}")
		return(MOUNTED)

	def __mount_cloud(self):
		self.umount()

		MOUNTED	= self.mounted()
		if not MOUNTED:
			# device not mounted

			# clean mountpoint
			self.__clean_mountpoint()
			self.createPath()

			if self.CloudServiceName:
				Command	= f'rclone mount {self.CloudServiceName}: {self.MountPoint} --umask=0 --read-only=false --uid={self.__mount_uid} --gid={self.__mount_gid} --allow-other --config {self.__RCLONE_CONFIG_FILE}'
				Command=f"sh -c '{Command} &'"

				subprocess.run(Command,shell=True)

				EndTime	= time.time()+self.__setup.get_val('const_CLOUD_MOUNT_TIMEOUT')
				while (not MOUNTED) and (EndTime > time.time()):
					MOUNTED	= self.mounted()
					time.sleep(0.5)

		if MOUNTED:
			self.__display_storage_properties()

		self.__log.message(f"mount cloud {self.CloudServiceName}: {MOUNTED}", 3)

		return(MOUNTED)

	def __mount_cloud_rsync(self):

		conf_RSYNC_USER				= self.__setup.get_val('conf_RSYNC_USER')
		conf_RSYNC_PASSWORD			= self.__setup.get_val('conf_RSYNC_PASSWORD')
		conf_RSYNC_SERVER			= self.__setup.get_val('conf_RSYNC_SERVER')
		conf_RSYNC_PORT				= self.__setup.get_val('conf_RSYNC_PORT')
		conf_RSYNC_SERVER_MODULE	= self.__setup.get_val('conf_RSYNC_SERVER_MODULE')

		configured	= (
			conf_RSYNC_USER and
			conf_RSYNC_PASSWORD and
			conf_RSYNC_SERVER and
			conf_RSYNC_PORT and
			conf_RSYNC_SERVER_MODULE
		)

		if configured:
			self.rsyncSSH	= ["sshpass", "-p", conf_RSYNC_PASSWORD]
			self.MountPoint	= f"rsync://{conf_RSYNC_USER}@{conf_RSYNC_SERVER}:{conf_RSYNC_PORT}/{conf_RSYNC_SERVER_MODULE}"

		return(configured)

	def __mount_internal(self):

		self.createPath()

		self.__display_storage_properties()

		return(True)

	def __mount_camera(self):
		self.__display.message([f":{self.__lan.l('box_backup_connect_camera_1')}", f":{self.__lan.l('box_backup_connect_camera_2')}"])

		CameraModel	= ''
		while not CameraModel:
			Cameras	= get_available_cameras()

			if Cameras:
				if self.DeviceIdentifierPresetThis:
					if any(self.DeviceIdentifierPresetThis == Cam for Cam in Cameras):
						CameraModelPort	= self.DeviceIdentifierPresetThis

				else:
					CameraModelPort		= Cameras[0]

				CameraModel, self.CameraPort = split_CameraAutoDetect(CameraModelPort)

			if not CameraModel:
				time.sleep(1)

		self.__camera_connected	= True
		self.DeviceIdentifier	= CameraModel
		self.__log.message(f"gphoto2: Got camera model identifier '{self.DeviceIdentifier}'.", 3)

		self.__display.message([f":{self.__lan.l('box_backup_camera_ok')}", f":{self.__lan.l('box_backup_working')}..."])

		Command	= ["gphoto2", "--camera", self.DeviceIdentifier, "--port", self.CameraPort, "--summary"]

		try:
			CameraSummaryList	= subprocess.check_output(Command).decode().strip().split('\n')
		except:
			return(False)

		# camera model
		try:
			CameraModel	= [CameraSummaryLine for CameraSummaryLine in CameraSummaryList if CameraSummaryLine.strip().startswith('Model')][0]
			CameraModel	= CameraModel.strip().split(' ',1)[1]
			CameraModel	= re.sub('[^a-zA-Z0-9-]', '_', CameraModel)
		except:
			CameraModel	= 'Camera'

		# camera manufacturer
		try:
			CameraManufacturer	= [CameraSummaryLine for CameraSummaryLine in CameraSummaryList if CameraSummaryLine.strip().startswith('Manufacturer')][0]
			CameraManufacturer	= CameraManufacturer.strip().split(' ',1)[1]
			CameraManufacturer	= re.sub('[^a-zA-Z0-9-]', '_', CameraManufacturer)
		except:
			CameraManufacturer	= 'unkn. Man.'

		# camera serial
		try:
			CameraSerial	= [CameraSummaryLine for CameraSummaryLine in CameraSummaryList if CameraSummaryLine.strip().startswith('Serial Number')][0]
			CameraSerial	= CameraSerial.split(':',1)[1].strip()
			CameraSerial	= re.sub('[^a-zA-Z0-9-]', '_', CameraSerial)
			CameraSerial	= CameraSerial.strip('_').lstrip('0')
		except:
			CameraSerial	= 'unkn. SN.'

		CameraSerialDisp = (CameraSerial[-13:]) if len(CameraSerial) > 13 else CameraSerial

		self.__display.message([f":{CameraModel}", f":{CameraManufacturer}", f":SN: {CameraSerialDisp}"])

		# define camera backup folders
		SourceCommand	= ["gphoto2", "--camera", self.DeviceIdentifier, "--port", self.CameraPort, "--storage-info"]
		FilterCommand	= ["grep", "basedir"]
		try:
			CameraBaseDirs	= lib_common.pipe(SourceCommand,FilterCommand).decode().strip().replace('basedir=','').split('\n')
		except:
			CameraBaseDirs	= []

		## conf_BACKUP_CAMERA_FOLDER_MASK
		CameraSearchFolders	= []
		conf_BACKUP_CAMERA_FOLDER_MASK	= self.__setup.get_val('conf_BACKUP_CAMERA_FOLDER_MASK')

		if conf_BACKUP_CAMERA_FOLDER_MASK:
			CameraFolderMasks	= conf_BACKUP_CAMERA_FOLDER_MASK.split(';')

			for CameraFolderMask in CameraFolderMasks:
				MaskCamera, MaskFolder	= CameraFolderMask.split(':',1)

				if (MaskCamera == CameraModel) or (MaskCamera == '*'):
					# static defined folders (quick)
					if MaskFolder.startswith('!/'):

						#check if path exists
						CameraBaseDirExists	= False
						for CameraBaseDir in CameraBaseDirs:
							if CameraBaseDir in MaskFolder:
								CameraBaseDirExists	= True

						if CameraBaseDirExists:
							self.SubPathsAtSource.append(MaskFolder[1:])
						else:
							self.__display.message([
								f":{self.__lan.l('box_backup_camera_storage_not_exists_1')}",
								f":{MaskFolder[1:]}",
								f":{self.__lan.l('box_backup_camera_storage_not_exists_2')}"
							])

					# for later dynamic search in defined subfolders (slow)
					elif MaskFolder:
						CameraSearchFolders.append(MaskFolder)

			# dynamic search in defined subfolders: only if no self.SubPathsAtSource are set
			if len(self.SubPathsAtSource) == 0:
				self.__display.message([f":{self.__lan.l('box_backup_camera_scanning_folders')}"])

				SourceCommand	= ["gphoto2", "--camera", self.DeviceIdentifier, "--port", self.CameraPort, "--list-folders"]
				FilterCommand	= ["grep", "^There are"]
				try:
					CameraFoldersRaw	= lib_common.pipe(SourceCommand,FilterCommand).decode().strip().split('\n')
				except:
					CameraFoldersRaw	= []

				CameraFolders	= []
				for CameraFolder in CameraFoldersRaw:
					try:
						CameraFolders.append(CameraFolder.split("'")[1])
						self.__log.message(f"Found folder: {CameraFolder}", 3)
					except:
						continue

				for CameraFolder in CameraFolders:
					for CameraSearchFolder in CameraSearchFolders:

						if CameraSearchFolder in CameraFolder:

							SourcePathKnown	= False
							for SourcePath in self.SubPathsAtSource:
								SourcePathKnown	= CameraFolder in SourcePath # if not already in SubPathsAtSource add to it

							if not SourcePathKnown:
								self.SubPathsAtSource.append(CameraFolder)

				#clean SubPathsAtSource
				SubPathsAtSourceDel	= []
				for SubPathsAtSource1 in self.SubPathsAtSource:
					SubPathsAtSourceClean1	= SubPathsAtSource1 if SubPathsAtSource1[0] != '!' else SubPathsAtSource1[1:]
					for SubPathsAtSource2 in self.SubPathsAtSource:
						SubPathsAtSourceClean2	= SubPathsAtSource2 if SubPathsAtSource2[0] != '!' else SubPathsAtSource2[2:]
						if (SubPathsAtSourceClean2 != SubPathsAtSourceClean1) and (SubPathsAtSourceClean2.startswith(SubPathsAtSourceClean1)):
							SubPathsAtSourceDel.append(SubPathsAtSource2)
				self.SubPathsAtSource	= list(set(self.SubPathsAtSource) - set(SubPathsAtSourceDel))

		# set global backup parameters
		self.LbbDeviceID			= f"{CameraModel}_{CameraSerial}"
		self.LbbSourceDescriptor	= f"{self.__lan.l('box_backup_mode_camera')}: {CameraModel}, {self.__lan.l('box_backup_serial')}: {CameraSerial}"
		if len(self.SubPathsAtSource) == 0:
			self.SubPathsAtSource=['/']
		self.SubPathAtTarget		= f"{self.LbbDeviceID}"
		# log self.SubPathsAtSource
		self.__log.message(f"Folders to sync from camera '${CameraModel}':", 1)
		for SourcePath in self.SubPathsAtSource:
			self.__log.message(f"*** - {SourcePath} - For use as pattern in Settings ({self.__lan.l('config_backup_camera_folder_mask_header')}): '{CameraModel}:!{SourcePath}'", 1)

		return(True)


	def __clean_mountpoint(self):
		Command	= ["rm", "-R", f"{self.MountPoint}/*"]
		subprocess.run(Command,stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)

	def createPath(self,MountPoint='',SubPathBelowMountPoint=''):
		MountPoint = MountPoint if MountPoint else self.MountPoint

		pathlib.Path(f"{MountPoint}/{SubPathBelowMountPoint}").mkdir(parents=True, exist_ok=True)
		self.set_perms_mountpoint(MountPoint)

		Command	= ['service','smbd','restart']
		subprocess.run(Command)

	def set_perms_mountpoint(self, MountPoint=''):
		MountPoint	= MountPoint if MountPoint else self.MountPoint

		Command	= ["chown", f"{self.__mount_user}:{self.__mount_group}", MountPoint, "-R"]
		subprocess.run(Command)

	def __manage_lbb_device_ID(self):
		if self.mounted():

			LbbID_FileList	= glob.glob(f"{self.MountPoint}/????[0-9]*[0-9]-[0-9]*[0-9].id")

			if len(LbbID_FileList) > 1:
				LbbID_FileList.sort(reverse=True)

			if len(LbbID_FileList) > 0: # ID file exists

				# set global backup parameters
				self.LbbDeviceID			= LbbID_FileList[0].split('/')[-1].replace('.id','')

			else:	# create ID file
				DatePart	= datetime.datetime.now().strftime("%Y%m%d%H%M%S")

				random.seed()
				RandomPart	= random.randrange(10000,100000,1)

				# set global backup parameters
				self.LbbDeviceID	= f"lbb_{DatePart}-{RandomPart}"

				open(f"{self.MountPoint}/{self.LbbDeviceID}.id",'w').close()

			self.LbbSourceDescriptor	= f"{self.__lan.l('box_backup_source_id')}: {self.LbbDeviceID}"
			self.SubPathsAtSource	= ['']

			if self.StorageType == 'internal':
				self.SubPathAtTarget		= f"internal/{self.LbbDeviceID}"
			else:
				self.SubPathAtTarget		= f"{self.LbbDeviceID}"

	def mounted(self,MountPoint=''):
		# returns DeviceIdentifier, if device is mounted

		# internal is always mounted
		if self.StorageType == 'internal':
			return({f"{self.__const_MEDIA_DIR}/{self.__const_INTERNAL_BACKUP_DIR}"})

		# camera: hold value of mount process
		if self.StorageType == 'camera':
			return(self.__camera_connected)

		# define relevant MountPoint
		if MountPoint:
			pass
		elif self.MountPoint:
			MountPoint	= self.MountPoint
		else:
			return(False)

		# prepare mount check
		if (self.StorageType == 'cloud') and self.CloudServiceName:
			MountPointSearch	= f" {MountPoint} "
			Command	= f"mount -l | grep '{MountPointSearch}' | grep '{self.CloudServiceName}'"
		else:
			MountPointSearch	= f'MOUNTPOINT="{MountPoint}" \| MOUNTPOINT="{self.__TechMountPoint}"' if self.__TechMountPoint else f'MOUNTPOINT="{MountPoint}"'
			Command	= f"lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE | grep '{MountPointSearch}'"

		# mount check
		MOUNTED = True
		try:
			subprocess.check_output(Command, shell=True) # raises an error if grep has no match
		except:
			MOUNTED	= False

		self.__log.message(f"MOUNTED?: '{MountPoint}' = {MOUNTED}",2)
		return(MOUNTED)

	def umount(self):
		if self.StorageType in ['internal', 'cloud_rsync']:
			return(True)

		Result = ''
		if self.mounted():

			# define FS_Type
			if self.mounted(self.__TechMountPoint):
				self.FS_Type	= self.FS_Type if self.FS_Type else getFS_Type(self.__TechMountPoint)
			else:
				self.FS_Type	= self.FS_Type if self.FS_Type else getFS_Type(self.MountPoint)

			# message
			if self.StorageType == 'usb':
				l_box_backup_MountPointDescription=self.__lan.l(f"box_backup_{self.StorageType}_{self.Role}")
			else:
				l_box_backup_MountPointDescription=self.__lan.l(f"box_backup_{self.StorageType}")

			self.__display.message([f":{self.__lan.l('box_backup_umount')}", f":{l_box_backup_MountPointDescription}"])

			# smbd stop
			Command	= ['service','smbd','stop']
			subprocess.run(Command)

			# umount self.MountPoint
			if self.FS_Type in ['hfs','hfsplus'] + ['ext2','ext3','ext4']:
				Command	= ['fusermount','-uz', self.MountPoint]
				try:
					Result	= subprocess.check_output(Command, stderr=subprocess.DEVNULL).decode()
					os.rmdir(MountPoint)
				except:
					Result	= ''
			else:
				if self.MountPoint:
					Command	= ['umount',self.MountPoint]
					try:
						Result	= subprocess.check_output(Command, stderr=subprocess.DEVNULL).decode()
						os.rmdir(MountPoint)
					except:
						Result	= ''

			# umount TechMountPoint
			if self.FS_Type in ['ext2','ext3','ext4']:
				if self.__TechMountPoint:
					Command	= [ 'umount',self.__TechMountPoint]
					try:
						Result	= subprocess.check_output(Command, stderr=subprocess.DEVNULL).decode()
						os.rmdir(MountPoint)
					except:
						Result	= ''

			# smbd start
			Command	= ['service','smbd','start']
			subprocess.run(Command)

			self.__log.message(f"umount: {self.MountPoint}? {Result}", 2)
			self.__display.message([f":{self.__lan.l('box_finished')}"])
		else:
			self.__log.message(f"umount {self.MountPoint}: Not mounted.", 3)

		return(Result)

	def __set_mountpoint(self):
		baseDir	= self.__const_MEDIA_DIR

		if self.StorageType in ['usb']:
			self.__TechMountPoint	= self.__const_TECH_MOUNT_TARGET if self.Role == role_Target else self.__const_TECH_MOUNT_SOURCE
			self.MountPoint			= f"{baseDir}/{self.__const_MOUNTPOINT_SUBPATH_LOCAL_SOURCE}" if self.Role == role_Source else f"{baseDir}/{self.__const_MOUNTPOINT_SUBPATH_LOCAL_TARGET}"
		elif self.StorageType == 'cloud':
			self.__TechMountPoint	= ''
			self.MountPoint			= f"{baseDir}/{self.__const_MOUNTPOINT_SUBPATH_CLOUD_SOURCE}" if self.Role == role_Source else f"{baseDir}/{self.__const_MOUNTPOINT_SUBPATH_CLOUD_TARGET}"
		elif self.StorageType == 'internal':
			self.__TechMountPoint	= ''
			self.MountPoint			= f"{baseDir}/{self.__const_INTERNAL_BACKUP_DIR}"
		else:
			self.__TechMountPoint	= ''
			self.MountPoint			= ''

	def __HumanReadableNumber(self,Number,Factor=1):
		PowersNames = ['','k','M','G','T','P','E']

		Number	= re.sub('[^0-9\.]', '', f"0{Number}")
		Number = float(Number) * Factor
		Powers = max(
						0,
						min(len(PowersNames)-1, int(math.floor(0 if Number == 0 else math.log10(abs(Number))/3)))
					)

		return '{:.0f}{}'.format(Number / 10**(3 * Powers), PowersNames[Powers])


	def __get_storage_properties(self):
		if self.MountPoint:
			Command	= [ 'df', self.MountPoint, '--output=size']
			try:
				storsize		= subprocess.check_output(Command).decode().split('\n')[1].strip()
			except:
				storsize		= '?'

			Command	= ['df',f"{self.MountPoint}",'--output=used']
			try:
				storused		= subprocess.check_output(Command).decode().split('\n')[1].strip()
			except:
				storused		= '?'

			Command	= ['df',f"{self.MountPoint}",'--output=avail']
			try:
				storfree		= subprocess.check_output(Command).decode().split('\n')[1].strip()
			except:
				storfree		= '?'

			Command	= ['df',f"{self.MountPoint}",'--output=fstype']
			try:
				storfstype		= subprocess.check_output(Command).decode().split('\n')[1].strip()
				if storfstype == 'fuse' and self.StorageType == 'usb':
					# check __TechMountPoint for FileSystem
					if self.__TechMountPoint:
						storfstype	= getFS_Type(self.__TechMountPoint)

			except:
				storfstype		= '?'

			return(storsize, storused, storfree, storfstype)
		else:
			return('-','-','-','-')

	def __display_storage_properties(self):
		storsize, storused, storfree, storfstype = self.__get_storage_properties()
		storsize	= re.sub('[^0-9\.]', '', f"0{storsize}")

		if int(storsize) > 0:
			PercentInUse	= str(round(int(storused) / int(storsize) * 100,1))
		else:
			PercentInUse	= '?'

		storsize	= f"{self.__lan.l('box_backup_storage_size')}: {self.__HumanReadableNumber(storsize,1000)}"
		storused	= f"{self.__lan.l('box_backup_storage_used')}: {self.__HumanReadableNumber(storused,1000)}"
		storfree	= f"{self.__lan.l('box_backup_storage_free')}: {self.__HumanReadableNumber(storfree,1000)}"
		storfstype	= f"{self.__lan.l('box_backup_storage_filesystem_short')}: {storfstype}"

		if self.StorageType == 'usb':
			l_drive_ok	= self.__lan.l(f"box_backup_{self.StorageType}_{self.Role}_ok")
		else:
			l_drive_ok	= self.__lan.l(f"box_backup_{self.StorageType}_ok")

		self.__display.message([f"set:clear,time={self.__conf_DISP_FRAME_TIME * 2}", f":{l_drive_ok}", f":{storsize}", f":{storused}", f":{storfree}", f":{storfstype}", f"PGBAR={PercentInUse}"])

#########################

def umount(setup, MountPoints):
	#setup:			setup-object
	#MountPoints:	'all' or a MountPoint or an array of MountPoints
	if type(MountPoints) != "<class 'list'>":
		if MountPoints == 'all':
			MountPoints	= [
				f"{setup.get_val('const_MEDIA_DIR')}/{setup.get_val('const_MOUNTPOINT_SUBPATH_LOCAL_TARGET')}",
				f"{setup.get_val('const_MEDIA_DIR')}/{setup.get_val('const_MOUNTPOINT_SUBPATH_LOCAL_SOURCE')}",
				f"{setup.get_val('const_MEDIA_DIR')}/{setup.get_val('const_MOUNTPOINT_SUBPATH_CLOUD_TARGET')}",
				f"{setup.get_val('const_MEDIA_DIR')}/{setup.get_val('const_MOUNTPOINT_SUBPATH_CLOUD_SOURCE')}",
				setup.get_val('const_TECH_MOUNT_TARGET'),
				setup.get_val('const_TECH_MOUNT_SOURCE')
			]
		else:
			MountPoints	= [MountPoints]

	for MountPoint in MountPoints:
		try:
			if getFS_Type(MountPoint) in ['hfs','hfsplus']:
				subprocess.run(['fusermount','-uz',MountPoint], stderr=subprocess.DEVNULL)
				os.rmdir(MountPoint)

			else:
				subprocess.run(['umount',MountPoint], stderr=subprocess.DEVNULL)
				os.rmdir(MountPoint)
		except:
			pass

def remove_all_mountpoints(setup):
	umount(setup,'all')
	#deletes all mountpoints and their content! For use directly after boot and before any mount only!!!
	MountPoints	= [
				f"{setup.get_val('const_MEDIA_DIR')}/{setup.get_val('const_MOUNTPOINT_SUBPATH_LOCAL_TARGET')}",
				f"{setup.get_val('const_MEDIA_DIR')}/{setup.get_val('const_MOUNTPOINT_SUBPATH_LOCAL_SOURCE')}",
				f"{setup.get_val('const_MEDIA_DIR')}/{setup.get_val('const_MOUNTPOINT_SUBPATH_CLOUD_TARGET')}",
				f"{setup.get_val('const_MEDIA_DIR')}/{setup.get_val('const_MOUNTPOINT_SUBPATH_CLOUD_SOURCE')}",
				setup.get_val('const_TECH_MOUNT_TARGET'),
				setup.get_val('const_TECH_MOUNT_SOURCE')
			]

	for MountPoint in MountPoints:
		Command	= ['rm','-R',MountPoint]
		subprocess.run(Command)

def get_mounts_list():
	mountsList	= [] # space!

	setup	= lib_setup.setup()

	TargetLocal				= f"{setup.get_val('const_MEDIA_DIR')}/{setup.get_val('const_MOUNTPOINT_SUBPATH_LOCAL_TARGET')}"
	SourceLocal				= f"{setup.get_val('const_MEDIA_DIR')}/{setup.get_val('const_MOUNTPOINT_SUBPATH_LOCAL_SOURCE')}"
	TargetCloud				= f"{setup.get_val('const_MEDIA_DIR')}/{setup.get_val('const_MOUNTPOINT_SUBPATH_CLOUD_TARGET')}"
	SourceCloud				= f"{setup.get_val('const_MEDIA_DIR')}/{setup.get_val('const_MOUNTPOINT_SUBPATH_CLOUD_SOURCE')}"

	const_TECH_MOUNT_TARGET	= setup.get_val('const_TECH_MOUNT_TARGET')
	const_TECH_MOUNT_SOURCE	= setup.get_val('const_TECH_MOUNT_SOURCE')

	MountPointListLocal	= f"{TargetLocal}\|{SourceLocal}\|{const_TECH_MOUNT_TARGET}\|{const_TECH_MOUNT_SOURCE}"
	MountPointListCloud	= f"{TargetCloud}\|{SourceCloud}"

	SourceCommand	= ["mount"]
	FilterCommand	= ["grep", MountPointListLocal]
	try:
		MountsLocal	= lib_common.pipe(SourceCommand,FilterCommand).decode().split('\n')
	except:
		MountsLocal	= []

	SourceCommand	= ["mount"]
	FilterCommand	= ["grep", MountPointListCloud]
	try:
		MountsCloud	= lib_common.pipe(SourceCommand,FilterCommand).decode().split('\n')
	except:
		MountsCloud	= []

	for MountLocal in MountsLocal:
		if (f" {TargetLocal} " in MountLocal) or (f" {const_TECH_MOUNT_TARGET} " in MountLocal):
			mountsList.append('target_usb')
		if (f" {SourceLocal} " in MountLocal) or (f" {const_TECH_MOUNT_SOURCE} " in MountLocal):
			mountsList.append('source_usb')

	for MountCloud in MountsCloud:
		try:
			CloudService	= MountCloud.split(':',1)[0]
			if f" {TargetCloud} " in MountCloud:
				mountsList.append(f'target_cloud:{CloudService}')
			if f" {SourceCloud} " in MountCloud:
				mountsList.append(f'source_cloud:{CloudService}')
		except:
			pass

	mountsList	= list(dict.fromkeys(mountsList))
	return(f" {' '.join(mountsList)} ")

def get_available_partitions(excludeTarget='', excludeSources=[], addLum=False, skipMounted=False):
	setup	= lib_setup.setup()

	class continueUSB_DeviceListRaw(Exception):
		pass

	Exception_continueUSB_DeviceListRaw	= continueUSB_DeviceListRaw()

	TargetDevice_lum_alpha	= ''

	availablePartitions	= []

	skipMounted_Command	= "| grep 'MOUNTPOINT=\"\"'" if skipMounted else ''
	Command	= f"lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE  {skipMounted_Command} | grep '^PATH=\\\"/dev/{setup.get_val('const_STORAGE_DEV_MASK')}'"

	try:
		# get all devices having MOUNTPOINT="" and starting with "PATH=\"...
		USB_DeviceListRaw = subprocess.check_output(Command,shell=True).decode().split('\n')
	except:
		USB_DeviceListRaw = []

	# generate list of devices and properties
	USB_DeviceList	= []
	for USB_Device in USB_DeviceListRaw:

		try:
			lum			= USB_Device.split('PATH=',1)[1].split('"',2)[1]
			lum_alpha	= lum.translate(str.maketrans('', '', digits))
		except:
			lum			= ''

		try:
			uuid		= USB_Device.split('UUID=',1)[1].split('"',2)[1]
		except:
			uuid		= ''

		try:
			fs_type		= Device_FS_Type = USB_Device.split('FSTYPE=',1)[1].split('"',2)[1]
		except:
			fs_type		= ''

		USB_DeviceList.append(
			{
				'lum':			lum,
				'lum_alpha':	lum_alpha,
				'uuid':			uuid,
				'fs_type':		fs_type
			}
		)

		if excludeTarget and ((f"/dev/{excludeTarget}" in [lum, lum_alpha]) or (excludeTarget == f"--uuid {uuid}")):
			TargetDevice_lum_alpha	= lum_alpha

	# check devices
	for USB_Device in USB_DeviceList:
		# exclude empty lines
		if (not USB_Device['uuid']) and (not USB_Device['lum']):
			continue

		# exclude not supported file systems
		if (USB_Device['fs_type'] not in FS_Types_supported):
			continue

		# exclude all partitions at target device
		if TargetDevice_lum_alpha == USB_Device['lum_alpha']:
			continue

		# exclude all excludeSources devices
		if (USB_Device['lum'] in excludeSources) or (f"--uuid {USB_Device['uuid']}" in excludeSources):
			continue

		if addLum:
			availablePartitions.append(f"{USB_Device['lum']}:--uuid {USB_Device['uuid']}" if USB_Device['uuid'] else USB_Device['lum'])
		else:
			availablePartitions.append(f"--uuid {USB_Device['uuid']}" if USB_Device['uuid'] else USB_Device['lum'])

	return(availablePartitions)

def format_CameraIdentifier(Model, Port):
	return(f"{Model} {Port}")

def split_CameraAutoDetect(AutoDetect):
	try:
		Model, Port	= AutoDetect.split(' usb:')
		Model	= Model.strip()
		Port	= f"usb:{Port.strip()}"
		return([Model, Port])
	except:
		return(['',''])

def get_available_cameras():
	SourceCommand	= ["gphoto2", "--auto-detect"]
	FilterCommand	= ["grep", "usb"]

	try:
		Cameras	= lib_common.pipe(SourceCommand,FilterCommand).decode().split('\n')
	except:
		return([])

	available_cameras	= []
	for Camera in Cameras:
		CameraModelPort	= Camera.split(' usb:')
		try:
			CameraModel		= CameraModelPort[0].strip()
			CameraPort		= f"usb:{CameraModelPort[1]}".strip()
		except:
			CameraModel		= ''
			CameraPort		= ''

		if not CameraModel: # Model empty -> retry
			continue

		Identifier	= format_CameraIdentifier(CameraModel, CameraPort)

		if Identifier in available_cameras: # unknown devices only
			continue

		available_cameras.append(Identifier)

	return(available_cameras)

def getFS_Type(MountPoint):
	SourceCommand	= ["lsblk", "-p", "-P", "-o", "PATH,MOUNTPOINT,UUID,FSTYPE"]
	FilterCommand	= ["grep", f'MOUNTPOINT="{MountPoint}"']
	try:
		FS_Type	= lib_common.pipe(SourceCommand,FilterCommand).decode().split('FSTYPE=',1)[1].split('"')[1]
	except:
		FS_Type	= ''

	return(FS_Type)

def extractCloudService(DeviceName):
	DeviceSplit	= DeviceName.split(':',1)
	DevicePart	= DeviceSplit[0]
	if len(DeviceSplit) > 1:
		CloudPart	= DeviceSplit[1]
	else:
		CloudPart	= ''

	return(DevicePart, CloudPart)

###########################

if __name__ == "__main__":
	#storage('usb', 'source' , True, '', '').mount()
	#print(subprocess.check_output('mount').decode())
	#sys.exit()

	if len(sys.argv) > 1:
		Action					= sys.argv[1]

		if Action in ['mount','umount','mounted'] and len(sys.argv) > 2:

			StorageName				= sys.argv[2]

			try:
				Role				= sys.argv[3]
			except:
				Role		= ''

			try:
				WaitForDevice		= (sys.argv[4] == 'True')
			except:
				WaitForDevice		= True

			try:
				DeviceIdentifierPresetThis		= sys.argv[5]
			except:
				DeviceIdentifierPresetThis		= ''

			try:
				DeviceIdentifierPresetOther		= sys.argv[6]
			except:
				DeviceIdentifierPresetOther		= ''

			if Action == 'mount':
				storage(StorageName=StorageName, Role=Role, WaitForDevice=WaitForDevice, DeviceIdentifierPresetThis=DeviceIdentifierPresetThis, DeviceIdentifierPresetOther=DeviceIdentifierPresetOther).mount()

			elif Action == 'umount':
				storage(StorageName=StorageName, Role=Role, WaitForDevice=WaitForDevice, DeviceIdentifierPresetThis=DeviceIdentifierPresetThis, DeviceIdentifierPresetOther=DeviceIdentifierPresetOther).umount()

			elif Action == 'mounted':
				print(storage(StorageName=StorageName, Role=Role, WaitForDevice=WaitForDevice, DeviceIdentifierPresetThis=DeviceIdentifierPresetThis, DeviceIdentifierPresetOther=DeviceIdentifierPresetOther).mounted())

		elif Action == 'get_mounts_list':
			print(get_mounts_list())

		elif Action == 'get_available_partitions':
			try:
				skipMounted		= (sys.argv[2] == 'True')
			except:
				skipMounted		= False

			print(get_available_partitions(addLum=True, skipMounted=skipMounted))



