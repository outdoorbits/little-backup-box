#!/usr/bin/env python
import argparse
import subprocess
import sys
import os

import lib_setup

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

	parser = argparse.ArgumentParser(description='Little Backup Box backup script', add_help=True)

	SourceChoices	= ['usb', 'internal', 'camera'] + CloudServices + ['cloud_rsync', 'thumbnails', 'database', 'exif']
	parser.add_argument(
		'--SourceName',
		'-s',
		metavar='source',
		choices		= SourceChoices,
		required=True,
		default='',
		help=f'Source name, one of {SourceChoices}'
	)

	TargetChoices	= ['usb', 'internal'] + CloudServices + ['cloud_rsync']
	parser.add_argument(
		'--TargetName',
		'-t',
		metavar		= 'target',
		choices		= TargetChoices,
		required=True,
		default='',
		help		= f'Target name, one of {TargetChoices}'
	)

	parser.add_argument(
		'--sync-database',
		'-sd',
		action		= 'store_true',
		required	= False,
		default		= False,
		help		= 'Should the View database be synchronized after backup?'
	)

	parser.add_argument(
		'--generate-thumbnails',
		'-gt',
		action		= 'store_true',
		required	= False,
		default		= False,
		help		= 'Create thumbnails for View after backup (Local storages only)'
	)

	parser.add_argument(
		'--update-exif',
		'-ue',
		action		= 'store_true',
		required=False,
		default=False,
		help='New media without their own rating receive the standard rating. If possible, this is written to the original file.'
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
		action		= 'store_true',
		required	= False,
		default		= False,
		help		= 'Power off after backup?'
	)

	parser.add_argument(
		'--secondary-backup-follows',
		'-sb',
		action		= 'store_true',
		required	= False,
		default		= False,
		help		= 'Will another backup follow? If not, the process can be completed.'
	)

	args = vars(parser.parse_args())

	#print(args)

	print(f"backupObj=backup(SourceName={args['SourceName']}, TargetName={args['TargetName']}, DoSyncDatabase={args['sync_database']}, DoGenerateThumbnails={args['generate_thumbnails']}, DoUpdateEXIF={args['update_exif']}, DeviceIdentifierPresetSource={args['device_identifier_preset_source']}, DeviceIdentifierPresetTarget={args['device_identifier_preset_target']}, PowerOff={args['power_off']}, SecundaryBackupFollows={args['secondary_backup_follows']})")
