import { shouldUseMocks } from './systemDetector.js';
import { getRunningSimulatedBackups, formatSimulatedBackupForPs } from './simulatedBackups.js';

const mockData = {
  partitions: [
    '/dev/sda1: USB-DRIVE-001',
    '/dev/sda2: USB-DRIVE-002',
    '/dev/sdb1: BACKUP-DRIVE',
    '/dev/nvme0n1p1: NVME-DRIVE',
  ],
  devices: [
    '/dev/sda',
    '/dev/sdb',
    '/dev/nvme0n1',
  ],
  mounts: ' target_usb source_usb ',
  deviceInfo: `NAME   FSTYPE UUID                                 MODEL
sda    vfat   1234-5678                              USB Drive
sdb    ext4   abcd-efgh-ijkl-mnop                    Backup Drive
nvme0n1 ext4  9876-5432-1098-7654                    NVMe SSD`,
  systemInfo: {
    model: 'Raspberry Pi 4 Model B (Mock)',
    temp: 45.2,
    cpuusage: 25.5,
    memRam: '45.2 % * 4096 MB',
    memSwap: '12.3 % * 1024 MB',
    abnormalConditions: 'None',
  },
  diskSpace: `NAME        SIZE FSAVAIL FSUSED FSUSE% MOUNTPOINT
sda         64G   50G    10G    17%   /media/usb_target
sdb        128G   100G   20G    17%   /media/usb_source
nvme0n1    256G   200G   40G    17%   /media/nvme_target`,
  cloudServices: ['google-drive', 'dropbox', 'onedrive'],
  socialServices: ['telegram', 'matrix'],
  wifiCountry: 'US',
  wifiCountries: [
    { code: 'US', name: 'United States' },
    { code: 'GB', name: 'United Kingdom' },
    { code: 'DE', name: 'Germany' },
    { code: 'FR', name: 'France' },
  ],
  cameras: [
    {
      model: 'Canon_EOS_Rebel_T7i',
      port: 'usb:001,005',
      serial: '12345678',
      storages: ['/store_00010001', '/store_00010002'],
    },
  ],
  wifi: [
    {
      interface: 'wlan0',
      info: 'wlan0     IEEE 802.11  ESSID:"MockNetwork"  Mode:Managed  Frequency:2.437 GHz',
    },
  ],
};

export function mockCommand(command, options = {}) {
  const { logger } = options;
  
  if (logger) {
    logger.info(`[MOCK] Executing: ${command}`);
  }
  
  if (command.includes('lib_storage.py --Action get_available_partitions')) {
    const skipMounted = command.includes('--skipMounted True');
    const ignoreFs = command.includes('--ignore-fs True');
    
    let partitions = [...mockData.partitions];
    if (skipMounted) {
      partitions = partitions.filter(p => !p.includes('USB-DRIVE-001'));
    }
    
    return {
      success: true,
      stdout: partitions.join('\n'),
      stderr: '',
    };
  }
  
  if (command.includes('lib_storage.py --Action get_available_devices')) {
    return {
      success: true,
      stdout: mockData.devices.join('\n'),
      stderr: '',
    };
  }
  
  if (command.includes('lib_storage.py --Action get_mounts_list')) {
    return {
      success: true,
      stdout: mockData.mounts,
      stderr: '',
    };
  }
  
  if (command.includes('lib_storage.py --Action mount')) {
    return {
      success: true,
      stdout: 'Mounted successfully (mock)',
      stderr: '',
    };
  }
  
  if (command.includes('lib_storage.py --Action umount')) {
    return {
      success: true,
      stdout: 'Unmounted successfully (mock)',
      stderr: '',
    };
  }
  
  if (command.includes('lib_system.py --get_pi_model')) {
    return {
      success: true,
      stdout: mockData.systemInfo.model,
      stderr: '',
    };
  }
  
  if (command.includes('lib_system.py --get_abnormal_system_conditions')) {
    return {
      success: true,
      stdout: mockData.systemInfo.abnormalConditions,
      stderr: '',
    };
  }
  
  if (command.includes('cat /sys/class/thermal/thermal_zone')) {
    return {
      success: true,
      stdout: String(Math.round(mockData.systemInfo.temp * 1000)),
      stderr: '',
    };
  }
  
  if (command.includes('vmstat')) {
    const idle = 100 - mockData.systemInfo.cpuusage;
    return {
      success: true,
      stdout: ` 0  0      0 ${idle}`,
      stderr: '',
    };
  }
  
  if (command.includes('free | grep Mem')) {
    if (command.includes('$3/$2 * 100.0')) {
      const percent = parseFloat(mockData.systemInfo.memRam.split('%')[0]);
      return {
        success: true,
        stdout: String(percent / 100),
        stderr: '',
      };
    }
    if (command.includes('$2 / 1024')) {
      const total = parseInt(mockData.systemInfo.memRam.split('*')[1].trim().split(' ')[0]);
      return {
        success: true,
        stdout: String(total),
        stderr: '',
      };
    }
  }
  
  if (command.includes('free | grep Swap')) {
    if (command.includes('$3/$2 * 100.0')) {
      const percent = parseFloat(mockData.systemInfo.memSwap.split('%')[0]);
      return {
        success: true,
        stdout: String(percent / 100),
        stderr: '',
      };
    }
    if (command.includes('$2 / 1024')) {
      const total = parseInt(mockData.systemInfo.memSwap.split('*')[1].trim().split(' ')[0]);
      return {
        success: true,
        stdout: String(total),
        stderr: '',
      };
    }
  }
  
  if (command.includes('lsblk') && command.includes('NAME,SIZE,FSAVAIL,FSUSED,FSUSE%,MOUNTPOINT')) {
    if (command.includes('-P')) {
      return {
        success: true,
        stdout: `NAME="sda" SIZE="64G" FSAVAIL="50G" FSUSED="10G" FSUSE%="17%" MOUNTPOINT="/media/usb_target"
NAME="sdb" SIZE="128G" FSAVAIL="100G" FSUSED="20G" FSUSE%="17%" MOUNTPOINT="/media/usb_source"
NAME="nvme0n1" SIZE="256G" FSAVAIL="200G" FSUSED="40G" FSUSE%="17%" MOUNTPOINT="/media/nvme_target"`,
        stderr: '',
      };
    }
    return {
      success: true,
      stdout: mockData.diskSpace,
      stderr: '',
    };
  }
  
  if (command.includes('lsblk') && command.includes('NAME,FSTYPE,UUID,MODEL')) {
    if (command.includes('-P')) {
      return {
        success: true,
        stdout: `NAME="sda" FSTYPE="vfat" UUID="1234-5678" MODEL="USB Drive"
NAME="sdb" FSTYPE="ext4" UUID="abcd-efgh-ijkl-mnop" MODEL="Backup Drive"
NAME="nvme0n1" FSTYPE="ext4" UUID="9876-5432-1098-7654" MODEL="NVMe SSD"`,
        stderr: '',
      };
    }
    return {
      success: true,
      stdout: mockData.deviceInfo,
      stderr: '',
    };
  }
  
  if (command.includes('smartctl -a')) {
    return {
      success: true,
      stdout: `SMART overall-health self-assessment test result: PASSED
Device Model:     Mock Drive
Serial Number:    MOCK123456
Firmware Version: MOCK1.0
User Capacity:    64,000,000,000 bytes
Power_On_Hours:   1234
Power_Cycle_Count: 56`,
      stderr: '',
    };
  }
  
  if (command.includes('gphoto2 --auto-detect')) {
    return {
      success: true,
      stdout: `Model                          Port
----------------------------------------------------------
Canon EOS Rebel T7i              usb:001,005`,
      stderr: '',
    };
  }
  
  if (command.includes('gphoto2 --camera') && command.includes('--summary')) {
    if (command.includes('grep \'Model\'')) {
      return {
        success: true,
        stdout: 'Canon EOS Rebel T7i',
        stderr: '',
      };
    }
    if (command.includes('grep \'Serial Number\'')) {
      return {
        success: true,
        stdout: '12345678',
        stderr: '',
      };
    }
  }
  
  if (command.includes('gphoto2 --camera') && command.includes('--storage-info')) {
    return {
      success: true,
      stdout: 'basedir=/store_00010001\nbasedir=/store_00010002',
      stderr: '',
    };
  }
  
  if (command.includes('iw dev')) {
    return {
      success: true,
      stdout: 'Interface wlan0',
      stderr: '',
    };
  }
  
  if (command.includes('iwconfig')) {
    return {
      success: true,
      stdout: mockData.wifi[0].info,
      stderr: '',
    };
  }
  
  if (command.includes('raspi-config nonint get_wifi_country')) {
    return {
      success: true,
      stdout: mockData.wifiCountry,
      stderr: '',
    };
  }
  
  if (command.includes('sed \'/^#/d\' /usr/share/zoneinfo/iso3166.tab')) {
    return {
      success: true,
      stdout: mockData.wifiCountries.map(c => `${c.code}\t${c.name}`).join('\n'),
      stderr: '',
    };
  }
  
  if (command.includes('rclone config show')) {
    const services = mockData.cloudServices.map(s => `[${s}]`).join('\n');
    return {
      success: true,
      stdout: services,
      stderr: '',
    };
  }
  
  if (command.includes('lib_socialmedia.py --action get_social_services_configured')) {
    return {
      success: true,
      stdout: mockData.socialServices.join('\n'),
      stderr: '',
    };
  }
  
  if (command.includes('lib_socialmedia.py --action get_social_services')) {
    return {
      success: true,
      stdout: mockData.socialServices.join('\n'),
      stderr: '',
    };
  }
  
  if (command.includes('lib_view.py --action init')) {
    return {
      success: true,
      stdout: 'View initialized (mock)',
      stderr: '',
    };
  }
  
  if (command.includes('lib_metadata.py')) {
    return {
      success: true,
      stdout: 'Metadata updated (mock)',
      stderr: '',
    };
  }
  
  if (command.includes('lib_display.py')) {
    return {
      success: true,
      stdout: 'Display updated (mock)',
      stderr: '',
    };
  }
  
  if (command.includes('lib_mail.py')) {
    return {
      success: true,
      stdout: 'Mail sent (mock)',
      stderr: '',
    };
  }
  
  if (command.includes('lib_git.py')) {
    if (command.includes('--update-available')) {
      return {
        success: true,
        stdout: 'False',
        stderr: '',
      };
    }
    return {
      success: true,
      stdout: 'Git operation completed (mock)',
      stderr: '',
    };
  }
  
  if (command.includes('lib_log.py')) {
    return {
      success: true,
      stdout: 'Logged (mock)',
      stderr: '',
    };
  }
  
  if (command.includes('stop_backup.sh')) {
    return {
      success: true,
      stdout: 'Backup stopped (mock)',
      stderr: '',
    };
  }
  
  if (command.includes('backup.py')) {
    return {
      success: true,
      stdout: 'Backup started (mock)',
      stderr: '',
    };
  }
  
  if (command.includes('pkill -f firefox')) {
    return {
      success: true,
      stdout: '',
      stderr: '',
    };
  }
  
  if (command.includes('dos2unix') || command.includes('unzip') || command.includes('cp ')) {
    return {
      success: true,
      stdout: 'Operation completed (mock)',
      stderr: '',
    };
  }
  
  if (command.includes('fsck') || command.includes('mkfs') || command.includes('f3probe')) {
    return {
      success: true,
      stdout: 'Filesystem operation completed (mock)',
      stderr: '',
    };
  }
  
  if (command.includes('raspi-config nonint do_change_timezone') || 
      command.includes('raspi-config nonint do_wifi_country')) {
    return {
      success: true,
      stdout: '',
      stderr: '',
    };
  }
  
  if (command.includes('set_hardware.sh')) {
    return {
      success: true,
      stdout: 'Hardware configured (mock)',
      stderr: '',
    };
  }
  
  if (command.includes('start-rclone-gui.py')) {
    return {
      success: true,
      stdout: 'Rclone GUI started (mock)',
      stderr: '',
    };
  }
  
  if (command.includes('lib_password.py')) {
    return {
      success: true,
      stdout: 'Password updated (mock)',
      stderr: '',
    };
  }
  
  if (command.includes('lib_comitup.py')) {
    return {
      success: true,
      stdout: 'False',
      stderr: '',
    };
  }
  
  if (command.includes('df -h')) {
    return {
      success: true,
      stdout: `/dev/sda1       64G   10G   50G  17% /media/usb_target
/dev/sdb1      128G   20G  100G  17% /media/usb_source`,
      stderr: '',
    };
  }
  
  if (command.includes('ps -ef') && command.includes('backup.py') && command.includes('grep')) {
    const runningBackups = getRunningSimulatedBackups();
    if (runningBackups.length === 0) {
      return {
        success: true,
        stdout: '',
        stderr: '',
      };
    }
    const psOutput = runningBackups.map(backup => formatSimulatedBackupForPs(backup)).join('\n');
    return {
      success: true,
      stdout: psOutput,
      stderr: '',
    };
  }
  
  if (command.includes('kill') && shouldUseMocks()) {
    return {
      success: true,
      stdout: '',
      stderr: '',
    };
  }
  
  return {
    success: true,
    stdout: `[MOCK] Command executed: ${command}`,
    stderr: '',
  };
}

export function shouldMock() {
  return shouldUseMocks();
}

