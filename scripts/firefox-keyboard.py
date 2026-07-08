#!/usr/bin/env python3


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

import argparse
import json
import os
import shutil
import subprocess
import sys
from pathlib import Path


ADDON_ID = "kiosk-keyboard@little-backup-box"
XPI_FILE = Path("/opt/little-backup-box/firefox-keyboard/lbb-kiosk-keyboard.xpi")
POLICY_FILE = Path("/etc/firefox/policies/policies.json")
DESKTOP_USER_DEFAULT = "lbb-desktop"


def ensure_root() -> None:
	if os.geteuid() == 0:
		return

	script = Path(sys.argv[0]).resolve()
	os.execvp("sudo", ["sudo", sys.executable, str(script), *sys.argv[1:]])


def load_policy() -> dict:
	if not POLICY_FILE.exists():
		return {"policies": {}}

	with POLICY_FILE.open("r", encoding="utf-8") as f:
		data = json.load(f)

	if not isinstance(data, dict):
		raise ValueError(f"{POLICY_FILE} does not contain a JSON object")

	if "policies" not in data or not isinstance(data["policies"], dict):
		data["policies"] = {}

	return data


def save_policy(data: dict) -> None:
	POLICY_FILE.parent.mkdir(parents=True, exist_ok=True)

	if POLICY_FILE.exists():
		backup = POLICY_FILE.with_suffix(".json.bak")
		shutil.copy2(POLICY_FILE, backup)

	tmp = POLICY_FILE.with_suffix(".json.tmp")

	with tmp.open("w", encoding="utf-8") as f:
		json.dump(data, f, indent=2, ensure_ascii=False)
		f.write("\n")

	tmp.chmod(0o644)
	os.replace(tmp, POLICY_FILE)


def cleanup_empty_dict(parent: dict, key: str) -> None:
	value = parent.get(key)
	if isinstance(value, dict) and not value:
		del parent[key]


def set_unsigned_extension_policy(policies: dict) -> None:
	"""
	Required for local, non-AMO-signed XPI files under Firefox ESR.
	This can be removed later for a signed release XPI.
	"""
	preferences = policies.setdefault("Preferences", {})
	preferences["xpinstall.signatures.required"] = {
		"Value": False,
		"Status": "locked",
		"Type": "boolean"
	}


def remove_unsigned_extension_policy_if_ours(policies: dict) -> None:
	preferences = policies.get("Preferences")
	if not isinstance(preferences, dict):
		return

	value = preferences.get("xpinstall.signatures.required")

	if (
		isinstance(value, dict)
		and value.get("Value") is False
		and value.get("Status") == "locked"
	):
		del preferences["xpinstall.signatures.required"]

	cleanup_empty_dict(policies, "Preferences")


def enable_keyboard(allow_unsigned: bool) -> None:
	if not XPI_FILE.exists():
		raise FileNotFoundError(
			f"{XPI_FILE} does not exist. Run install-firefox-kioskboard-extension.sh first."
		)

	data = load_policy()
	policies = data.setdefault("policies", {})

	extension_settings = policies.setdefault("ExtensionSettings", {})
	extension_settings[ADDON_ID] = {
		"installation_mode": "force_installed",
		"install_url": XPI_FILE.resolve().as_uri(),
		"updates_disabled": True
	}

	if allow_unsigned:
		set_unsigned_extension_policy(policies)
	else:
		remove_unsigned_extension_policy_if_ours(policies)

	save_policy(data)

	print("Firefox keyboard extension enabled.")
	print(f"Policy file: {POLICY_FILE}")
	print(f"XPI:         {XPI_FILE}")
	print("Restart Firefox for the change to take effect.")


def disable_keyboard() -> None:
	data = load_policy()
	policies = data.setdefault("policies", {})

	extension_settings = policies.setdefault("ExtensionSettings", {})
	extension_settings[ADDON_ID] = {
		"installation_mode": "blocked"
	}

	remove_unsigned_extension_policy_if_ours(policies)

	save_policy(data)

	print("Firefox keyboard extension disabled/blocked.")
	print(f"Policy file: {POLICY_FILE}")
	print("Restart Firefox for the change to take effect.")


def restart_firefox(desktop_user: str) -> None:
	for process_name in ("firefox-esr", "firefox"):
		subprocess.run(
			["pkill", "-u", desktop_user, "-x", process_name],
			check=False,
			stdout=subprocess.DEVNULL,
			stderr=subprocess.DEVNULL
		)

	print(f"Firefox processes for user {desktop_user!r} terminated.")


def show_status() -> None:
	data = load_policy()
	policies = data.get("policies", {})
	extension_settings = policies.get("ExtensionSettings", {})
	preferences = policies.get("Preferences", {})

	print(f"Policy file: {POLICY_FILE}")
	print(f"Add-on ID:   {ADDON_ID}")
	print(f"XPI file:    {XPI_FILE}")
	print()

	addon_policy = extension_settings.get(ADDON_ID)
	if addon_policy:
		print("ExtensionSettings:")
		print(json.dumps(addon_policy, indent=2, ensure_ascii=False))
	else:
		print("ExtensionSettings: not configured")

	print()

	signature_policy = preferences.get("xpinstall.signatures.required")
	if signature_policy is not None:
		print("xpinstall.signatures.required:")
		print(json.dumps(signature_policy, indent=2, ensure_ascii=False))
	else:
		print("xpinstall.signatures.required: default")


def parse_args() -> argparse.Namespace:
	parser = argparse.ArgumentParser(
		description="Enable or disable the LBB Firefox kiosk keyboard extension."
	)

	parser.add_argument(
		"command",
		choices=["enable", "disable", "restart", "status"],
		help="Action to perform"
	)

	parser.add_argument(
		"--signed",
		action="store_true",
		help="Use this if the XPI is AMO-signed. Does not disable Firefox signature checks."
	)

	parser.add_argument(
		"--restart",
		action="store_true",
		help="Restart Firefox after enable/disable"
	)

	parser.add_argument(
		"--desktop-user",
		default=os.environ.get("LBB_DESKTOP_USER", DESKTOP_USER_DEFAULT),
		help=f"Firefox desktop user, default: {DESKTOP_USER_DEFAULT}"
	)

	return parser.parse_args()


def main() -> None:
	args = parse_args()

	if args.command in ("enable", "disable", "restart"):
		ensure_root()

	if args.command == "enable":
		enable_keyboard(allow_unsigned=not args.signed)
		if args.restart:
			restart_firefox(args.desktop_user)

	elif args.command == "disable":
		disable_keyboard()
		if args.restart:
			restart_firefox(args.desktop_user)

	elif args.command == "restart":
		restart_firefox(args.desktop_user)

	elif args.command == "status":
		show_status()


if __name__ == "__main__":
	main()
