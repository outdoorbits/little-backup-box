#!/usr/bin/env python
import argparse
import subprocess
import sys
import os

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description='Little Backup Box backup script')

    parser.add_argument('source', metavar='source',
                        choices=['usb', 'internal', 'camera', 'cloud', 'cloud_rsync', 'thumbnails', 'database', 'exif'],
                        help='Source name, one of ["usb", "internal", "camera", "cloud:SERVICE_NAME", "cloud_rsync"] or functions: ["thumbnails", "database", "exif"]')
    parser.add_argument('target', metavar='target', choices=['usb', 'internal', 'cloud', 'cloud_rsync'],
                        help='Target name, one of ["usb", "internal", "cloud", "cloud_rsync"]')

    parser.add_argument('--cloud-service', required=False, default='')
    parser.add_argument('--sync-database', action='store_true', required=False, default=True)
    parser.add_argument('--generate-thumbnails', action='store_true', required=False, default=False,
                        help='Create thumbnails for View after backup (Local storages only)')
    parser.add_argument('--update-exif', action='store_true', required=False, default=False,
                        help='New media without their own rating receive the standard rating. If possible, this is written to the original file.')

    parser.add_argument('--device-identifier-preset-source', required=False, default='',
                        help='Device identifier preset for source, e.g --uuid 123..., sda1, etc.')
    parser.add_argument('--device-identifier-preset-target', required=False, default='',
                        help='Device identifier preset for source, e.g --uuid 123..., sda1, etc.')

    parser.add_argument('--power-off', action='store_true', required=False, default=False,
                        help='Power off after backup')

    parser.add_argument('--secondary-backup-follows', action='store_true', required=False, default=False)

    args = parser.parse_args()

    argStrings = map(lambda x: str(x),
                     [args.source, args.target, args.sync_database, args.generate_thumbnails, args.update_exif,
                      args.device_identifier_preset_source, args.device_identifier_preset_target, args.power_off,
                      args.secondary_backup_follows])

    subprocess.call(["python3", os.path.join(sys.path[0], "backup.py")] + list(argStrings))
