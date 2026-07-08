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
import time
from pathlib import Path


ADDON_ID					= "kiosk-keyboard@little-backup-box"
XPI_FILE					= Path("/opt/little-backup-box/firefox-keyboard/lbb-kiosk-keyboard.xpi")
POLICY_FILE					= Path("/etc/firefox/policies/policies.json")
DESKTOP_USER_DEFAULT		= "lbb-desktop"
SUPPORTED_KEYBOARD_LAYOUTS	= {"de", "en", "fr", "es"}
DEFAULT_KEYBOARD_LAYOUT		= "en"

def ensure_root() -> None:
	if os.geteuid() == 0:
		return

	script	= Path(sys.argv[0]).resolve()
	os.execvp("sudo", ["sudo", sys.executable, str(script), *sys.argv[1:]])


def load_policy() -> dict:
	if not POLICY_FILE.exists():
		return {"policies": {}}

	with POLICY_FILE.open("r", encoding="utf-8") as f:
		data	= json.load(f)

	if not isinstance(data, dict):
		raise ValueError(f"{POLICY_FILE} does not contain a JSON object")

	if "policies" not in data or not isinstance(data["policies"], dict):
		data["policies"]	= {}

	return data


def save_policy(data: dict) -> None:
	POLICY_FILE.parent.mkdir(parents=True, exist_ok=True)

	if POLICY_FILE.exists():
		backup	= POLICY_FILE.with_suffix(".json.bak")
		shutil.copy2(POLICY_FILE, backup)

	tmp	= POLICY_FILE.with_suffix(".json.tmp")

	with tmp.open("w", encoding="utf-8") as f:
		json.dump(data, f, indent=2, ensure_ascii=False)
		f.write("\n")

	tmp.chmod(0o644)
	os.replace(tmp, POLICY_FILE)


def cleanup_empty_dict(parent: dict, key: str) -> None:
	value	= parent.get(key)
	if isinstance(value, dict) and not value:
		del parent[key]

def normalize_keyboard_layout(layout: str | None) -> str:
	if not layout:
		return DEFAULT_KEYBOARD_LAYOUT

	layout	= layout.strip().lower()

	if layout not in SUPPORTED_KEYBOARD_LAYOUTS:
		print(
			f"Unsupported keyboard layout {layout!r}; falling back to {DEFAULT_KEYBOARD_LAYOUT!r}.",
			file=sys.stderr
		)
		return DEFAULT_KEYBOARD_LAYOUT

	return layout

def set_unsigned_extension_policy(policies: dict) -> None:
	"""
	Required for local, non-AMO-signed XPI files under Firefox ESR.
	This can be removed later for a signed release XPI.
	"""
	preferences = policies.setdefault("Preferences", {})
	preferences["xpinstall.signatures.required"] = {
		"Value":	False,
		"Status":	"locked",
		"Type":		"boolean"
	}


def remove_unsigned_extension_policy_if_ours(policies: dict) -> None:
	preferences = policies.get("Preferences")
	if not isinstance(preferences, dict):
		return

	value	= preferences.get("xpinstall.signatures.required")

	if (
		isinstance(value, dict)
		and value.get("Value") is False
		and value.get("Status") == "locked"
	):
		del preferences["xpinstall.signatures.required"]

	cleanup_empty_dict(policies, "Preferences")

def set_keyboard_layout_policy(policies: dict, layout: str) -> None:
	layout								= normalize_keyboard_layout(layout)

	thirdparty							= policies.setdefault("3rdparty", {})
	extensions							= thirdparty.setdefault("Extensions", {})
	extension_policy					= extensions.setdefault(ADDON_ID, {})

	extension_policy["keyboardLayout"]	= layout

def enable_keyboard(allow_unsigned: bool, layout: str | None = None) -> None:
	if not XPI_FILE.exists():
		raise FileNotFoundError(
			f"{XPI_FILE} does not exist. Run install-firefox-kioskboard-extension.sh first."
		)

	data							= load_policy()
	policies						= data.setdefault("policies", {})

	extension_settings				= policies.setdefault("ExtensionSettings", {})
	extension_settings[ADDON_ID]	= {
		"installation_mode":	"force_installed",
		"install_url":			XPI_FILE.resolve().as_uri(),
		"private_browsing":		True
		# "updates_disabled":	True
	}

	if allow_unsigned:
		set_unsigned_extension_policy(policies)
	else:
		remove_unsigned_extension_policy_if_ours(policies)

	set_keyboard_layout_policy(
		policies,
		normalize_keyboard_layout(layout)
	)

	save_policy(data)

	print("Firefox keyboard extension enabled.")
	print(f"Policy file: {POLICY_FILE}")
	print(f"XPI:         {XPI_FILE}")
	print("Restart Firefox for the change to take effect.")


def disable_keyboard() -> None:
	data							= load_policy()
	policies						= data.setdefault("policies", {})

	extension_settings				= policies.setdefault("ExtensionSettings", {})
	extension_settings[ADDON_ID]	= {
		"installation_mode": "blocked"
	}

	remove_unsigned_extension_policy_if_ours(policies)

	save_policy(data)

	print("Firefox keyboard extension disabled/blocked.")
	print(f"Policy file: {POLICY_FILE}")
	print("Restart Firefox for the change to take effect.")

def set_layout(layout: str) -> None:
	layout		= normalize_keyboard_layout(layout)

	data		= load_policy()
	policies	= data.setdefault("policies", {})

	set_keyboard_layout_policy(policies, layout)

	save_policy(data)

	print(f"Firefox keyboard layout set to: {layout}")
	print(f"Policy file: {POLICY_FILE}")
	print("Restart Firefox for the change to take effect.")

def restart_firefox(desktop_user: str) -> None:
	home_dir	= Path(f"/home/{desktop_user}")
	profile_dir	= home_dir / ".mozilla/firefox-esr/kiosk.default"
	kiosk_url	= "http://localhost:8080"

	uid			= subprocess.check_output(
		["id", "-u", desktop_user],
		text=True
	).strip()

	runtime_dir	= Path(f"/run/user/{uid}")

	wayland_display	= None
	for candidate in sorted(runtime_dir.glob("wayland-*")):
		if candidate.is_socket():
			wayland_display = candidate.name
			break

	if wayland_display is None:
		print(
			f"No Wayland socket found in {runtime_dir}. Cannot restart Firefox kiosk.",
			file=sys.stderr
		)
		sys.exit(1)

	# Stop running Firefox instance for the kiosk user.
	for process_name in ("firefox-esr", "firefox"):
		subprocess.run(
			["pkill", "-u", desktop_user, "-x", process_name],
			check=False,
			stdout=subprocess.DEVNULL,
			stderr=subprocess.DEVNULL
		)

	# Wait for LBB web server, same logic as labwc autostart.
	for _ in range(60):
		result = subprocess.run(
			["curl", "-fsS", kiosk_url],
			check=False,
			stdout=subprocess.DEVNULL,
			stderr=subprocess.DEVNULL
		)

		if result.returncode == 0:
			break

		time.sleep(1)

	time.sleep(1)

	subprocess.Popen(
		[
			"sudo",
			"-u", desktop_user,
			"env",
			f"HOME={home_dir}",
			f"XDG_RUNTIME_DIR={runtime_dir}",
			f"WAYLAND_DISPLAY={wayland_display}",
			"MOZ_ENABLE_WAYLAND=1",
			"firefox-esr",
			"--profile", str(profile_dir),
			"--kiosk",
			"--private-window", kiosk_url
		],
		stdout=subprocess.DEVNULL,
		stderr=subprocess.DEVNULL,
		start_new_session=True
	)

	print(f"Firefox kiosk restarted for user {desktop_user!r}.")

def show_status() -> None:
	data				= load_policy()
	policies			= data.get("policies", {})
	extension_settings	= policies.get("ExtensionSettings", {})
	preferences			= policies.get("Preferences", {})

	print(f"Policy file: {POLICY_FILE}")
	print(f"Add-on ID:   {ADDON_ID}")
	print(f"XPI file:    {XPI_FILE}")
	print()

	addon_policy	= extension_settings.get(ADDON_ID)
	if addon_policy:
		print("ExtensionSettings:")
		print(json.dumps(addon_policy, indent=2, ensure_ascii=False))
	else:
		print("ExtensionSettings: not configured")

	print()

	signature_policy	= preferences.get("xpinstall.signatures.required")
	if signature_policy is not None:
		print("xpinstall.signatures.required:")
		print(json.dumps(signature_policy, indent=2, ensure_ascii=False))
	else:
		print("xpinstall.signatures.required: default")

		print()

	thirdparty			= policies.get("3rdparty", {})
	extensions			= thirdparty.get("Extensions", {}) if isinstance(thirdparty, dict) else {}
	extension_policy	= extensions.get(ADDON_ID, {}) if isinstance(extensions, dict) else {}

	keyboard_layout		= extension_policy.get("keyboardLayout")
	if keyboard_layout:
		print(f"Keyboard layout: {keyboard_layout}")
	else:
		print(f"Keyboard layout: default ({DEFAULT_KEYBOARD_LAYOUT})")


def parse_args() -> argparse.Namespace:
	parser = argparse.ArgumentParser(
		description="Enable or disable the LBB Firefox kiosk keyboard extension."
	)

	parser.add_argument(
		"command",
		choices=["enable", "disable", "restart", "status", "layout"],
		help="Action to perform"
	)

	parser.add_argument(
		"layout",
		nargs="?",
		choices=sorted(SUPPORTED_KEYBOARD_LAYOUTS),
		help=f"Keyboard layout: {', '.join(sorted(SUPPORTED_KEYBOARD_LAYOUTS))}"
	)

	parser.add_argument(
		"--layout",
		dest="enable_layout",
		choices=sorted(SUPPORTED_KEYBOARD_LAYOUTS),
		help=f"Keyboard layout for 'enable' ({', '.join(sorted(SUPPORTED_KEYBOARD_LAYOUTS))}), default: {DEFAULT_KEYBOARD_LAYOUT}"
	)

	parser.add_argument(
		"--signed",
		action="store_true",
		help="Use this if the XPI is AMO-signed. Does not disable Firefox signature checks."
	)

	parser.add_argument(
		"--restart",
		action="store_true",
		help="Restart Firefox after enable, disable, or layout change."
	)

	parser.add_argument(
		"--desktop-user",
		default=os.environ.get("LBB_DESKTOP_USER", DESKTOP_USER_DEFAULT),
		help=f"Firefox desktop user, default: {DESKTOP_USER_DEFAULT}"
	)

	return parser.parse_args()


def main() -> None:
	args = parse_args()

	if args.command in ("enable", "disable", "restart", "layout"):
		ensure_root()

	if args.command != "layout" and args.layout is not None:
		print(
			"The positional layout argument is only valid with the 'layout' command.",
			file=sys.stderr
		)
		sys.exit(1)

	if args.command == "enable":
		enable_keyboard(
			allow_unsigned=not args.signed,
			layout=args.enable_layout
		)

		if args.restart:
			restart_firefox(args.desktop_user)

	elif args.command == "disable":
		disable_keyboard()

		if args.restart:
			restart_firefox(args.desktop_user)

	elif args.command == "layout":
		if args.layout is None:
			print(
				f"Missing layout. Use one of: {', '.join(sorted(SUPPORTED_KEYBOARD_LAYOUTS))}",
				file=sys.stderr
			)
			sys.exit(1)

		set_layout(args.layout)

		if args.restart:
			restart_firefox(args.desktop_user)

	elif args.command == "restart":
		restart_firefox(args.desktop_user)

	elif args.command == "status":
		show_status()


if __name__ == "__main__":
	main()
