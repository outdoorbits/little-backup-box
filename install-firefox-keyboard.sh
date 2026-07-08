#!/usr/bin/env bash

# Author: Stefan Saam, github@saams.de
# Original author: Dmitri Popov, dmpop@linux.com

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

set -euo pipefail

ADDON_ID="kiosk-keyboard@little-backup-box"
ADDON_NAME="LBB Kiosk Keyboard"
ADDON_VERSION="1.0.2"

BASE_DIR="/opt/little-backup-box/firefox-keyboard"
SRC_DIR="${BASE_DIR}/extension"
XPI_FILE="${BASE_DIR}/lbb-kiosk-keyboard.xpi"

KIOSKBOARD_VERSION="2.3.0"

VENDOR_DIR="${SRC_DIR}/vendor/kioskboard"
CONTENT_DIR="${SRC_DIR}/content"
KEYS_DST_DIR="${VENDOR_DIR}/keys"

require_tools() {
	sudo apt-get update
	sudo DEBIAN_FRONTEND=noninteractive \
		apt-get \
		-o "Dpkg::Options::=--force-confold" \
		-o "Dpkg::Options::=--force-confdef" \
		install -y -q \
			curl \
			ca-certificates \
			zip
}

download_with_fallback() {
	local output="$1"
	shift

	local url
	for url in "$@"; do
		if curl -fsSL "$url" -o "$output"; then
			return 0
		fi
	done

	echo "Download failed for ${output}" >&2
	exit 1
}

prepare_dirs() {
	rm -rf "${SRC_DIR}"

	mkdir -p "${VENDOR_DIR}"
	mkdir -p "${CONTENT_DIR}"
	mkdir -p "${KEYS_DST_DIR}"
}

download_kioskboard() {
	download_with_fallback \
		"${VENDOR_DIR}/kioskboard-aio.min.js" \
		"https://unpkg.com/kioskboard@${KIOSKBOARD_VERSION}/dist/kioskboard-aio-${KIOSKBOARD_VERSION}.min.js" \
		"https://cdn.jsdelivr.net/npm/kioskboard@${KIOSKBOARD_VERSION}/dist/kioskboard-aio-${KIOSKBOARD_VERSION}.min.js" \
		"https://unpkg.com/kioskboard@${KIOSKBOARD_VERSION}/src/all-in-one/kioskboard-aio.js"

	download_with_fallback \
		"${VENDOR_DIR}/kioskboard.min.css" \
		"https://unpkg.com/kioskboard@${KIOSKBOARD_VERSION}/dist/kioskboard-${KIOSKBOARD_VERSION}.min.css" \
		"https://cdn.jsdelivr.net/npm/kioskboard@${KIOSKBOARD_VERSION}/dist/kioskboard-${KIOSKBOARD_VERSION}.min.css" \
		"https://unpkg.com/kioskboard@${KIOSKBOARD_VERSION}/src/kioskboard.css"

	cat > "${VENDOR_DIR}/THIRD_PARTY_LICENSES.txt" <<EOF
KioskBoard ${KIOSKBOARD_VERSION}
https://github.com/furcan/KioskBoard
License: MIT

The KioskBoard package is bundled here only for local/offline use inside
the Little Backup Box Firefox kiosk extension.
EOF
}

download_kioskboard_keys() {
	local files=(
		"kioskboard-keys-german.json"
		"kioskboard-keys-english.json"
		"kioskboard-keys-french.json"
		"kioskboard-keys-spanish.json"
	)

	for file in "${files[@]}"; do
		download_with_fallback \
			"${KEYS_DST_DIR}/${file}" \
			"https://unpkg.com/kioskboard@${KIOSKBOARD_VERSION}/dist/${file}" \
			"https://cdn.jsdelivr.net/npm/kioskboard@${KIOSKBOARD_VERSION}/dist/${file}"
	done
}

write_manifest() {
	cat > "${SRC_DIR}/manifest.json" <<EOF
{
	"manifest_version": 2,
	"name": "${ADDON_NAME}",
	"version": "${ADDON_VERSION}",
	"description": "Injects KioskBoard into external web forms for Little Backup Box kiosk mode.",
	"browser_specific_settings": {
		"gecko": {
			"id": "${ADDON_ID}"
		}
	},
	"permissions": [
		"storage",
		"http://*/*",
		"https://*/*"
	],
	"web_accessible_resources": [
		"vendor/kioskboard/keys/*.json"
	],
	"content_scripts": [
		{
		"matches": [
			"http://*/*",
			"https://*/*"
		],
		"js": [
			"vendor/kioskboard/kioskboard-aio.min.js",
			"content/lbb-kioskboard-init.js"
		],
		"css": [
			"vendor/kioskboard/kioskboard.min.css",
			"content/lbb-kioskboard.css"
		],
		"run_at": "document_idle",
		"all_frames": true,
		"match_about_blank": true
		}
	]
}
EOF
}


write_content_script() {
	cat > "${CONTENT_DIR}/lbb-kioskboard-init.js" <<'EOF'
(() => {
	"use strict";

	const DEFAULT_KEYBOARD_LAYOUT = "en";

	const SUPPORTED_KEYBOARD_LAYOUTS = ["de", "en", "fr", "es"];

	function extensionUrl(path) {
		if (typeof browser !== "undefined" && browser.runtime?.getURL) {
			return browser.runtime.getURL(path);
		}

		if (typeof chrome !== "undefined" && chrome.runtime?.getURL) {
			return chrome.runtime.getURL(path);
		}

		throw new Error("No extension runtime URL API available");
	}

	const KEYBOARD_LAYOUT_FILE = {
		de: "kioskboard-keys-german.json",
		en: "kioskboard-keys-english.json",
		fr: "kioskboard-keys-french.json",
		es: "kioskboard-keys-spanish.json"
	};

	const KEYBOARD_LANGUAGE = {
		de: "de",
		en: "en",
		fr: "fr",
		es: "es"
	};

	async function getManagedKeyboardLayout() {
		try {
			const extensionApi =
				typeof browser !== "undefined"
					? browser
					: typeof chrome !== "undefined"
						? chrome
						: null;

			if (!extensionApi?.storage?.managed?.get) {
				return DEFAULT_KEYBOARD_LAYOUT;
			}

			const result = await extensionApi.storage.managed.get("keyboardLayout");
			const layout = String(result?.keyboardLayout || "").trim().toLowerCase();

			if (SUPPORTED_KEYBOARD_LAYOUTS.includes(layout)) {
				return layout;
			}
		} catch (err) {
			console.warn("LBB KioskBoard: could not read managed keyboard layout:", err);
		}

		return DEFAULT_KEYBOARD_LAYOUT;
	}

	function keyboardJsonUrl(layout) {
		const file = KEYBOARD_LAYOUT_FILE[layout] || KEYBOARD_LAYOUT_FILE[DEFAULT_KEYBOARD_LAYOUT];
		return extensionUrl(`vendor/kioskboard/keys/${file}`);
	}

	function keyboardLanguage(layout) {
		return KEYBOARD_LANGUAGE[layout] || DEFAULT_KEYBOARD_LAYOUT;
	}

	const INPUT_SELECTOR = [
		'textarea',
		'input:not([type])',
		'input[type="text"]',
		'input[type="search"]',
		'input[type="email"]',
		'input[type="url"]',
		'input[type="tel"]',
		'input[type="number"]',
		'input[type="password"]'
	].join(",");

	const CLASS_NAME = "js-lbb-kioskboard-input";

	function buildKioskboardOptions(layout) {
		return {
			keysArrayOfObjects: null,
			keysJsonUrl: keyboardJsonUrl(layout),

			keysSpecialCharsArrayOfStrings: [
				"@", ".", "-", "_", "/", ":", ";", "?", "!", "#", "+", "*", "=", "&",
				"ä", "ö", "ü", "Ä", "Ö", "Ü", "ß",
				"é", "è", "ê", "ë", "ç",
				"á", "à", "â", "í", "ì", "î",
				"ó", "ò", "ô", "ú", "ù", "û",
				"ñ", "¿", "¡"
			],

			keysNumpadArrayOfNumbers: null,

			language: keyboardLanguage(layout),
			theme: "light",
			autoScroll: true,
			capsLockActive: false,

			allowRealKeyboard: true,
			allowMobileKeyboard: false,

			cssAnimations: true,
			cssAnimationsDuration: 120,

			keysAllowSpacebar: true,
			keysSpacebarText: "Space",
			keysEnterText: "Enter",
			keysFontFamily: "sans-serif",
			keysFontSize: "18px",
			keysFontWeight: "normal"
		};
	}

	function isUsableInput(el) {
		if (!(el instanceof HTMLElement)) {
			return false;
		}

		if (!el.matches(INPUT_SELECTOR)) {
			return false;
		}

		if (el.readOnly || el.disabled) {
			return false;
		}

		if (el.closest("[data-lbb-kioskboard-ignore]")) {
			return false;
		}

		return true;
	}

	function keyboardTypeFor(el) {
		const tag = el.tagName.toLowerCase();
		const type = (el.getAttribute("type") || "text").toLowerCase();

		if (tag === "textarea") {
			return "all";
		}

		if (type === "number") {
			return "numpad";
		}

		return "all";
	}

	function prepareInput(el, kioskboardOptions) {
		if (!isUsableInput(el)) {
			return;
		}

		if (el.dataset.lbbKioskboardInitialized === "1") {
			return;
		}

		el.classList.add(CLASS_NAME);
		el.dataset.kioskboardType = el.dataset.kioskboardType || keyboardTypeFor(el);
		el.dataset.kioskboardPlacement = el.dataset.kioskboardPlacement || "bottom";
		el.dataset.kioskboardSpecialcharacters = el.dataset.kioskboardSpecialcharacters || "true";

		try {
			KioskBoard.run(el, kioskboardOptions);
			el.dataset.lbbKioskboardInitialized = "1";
		} catch (err) {
			console.warn("LBB KioskBoard init failed:", err);
		}
	}

	function prepareTree(root, kioskboardOptions) {
		if (!root) {
			return;
		}

		if (root instanceof HTMLElement && isUsableInput(root)) {
			prepareInput(root, kioskboardOptions);
		}

		if (root.querySelectorAll) {
			root.querySelectorAll(INPUT_SELECTOR).forEach((el) => {
				prepareInput(el, kioskboardOptions);
			});
		}
	}

	async function init() {
		if (typeof KioskBoard === "undefined") {
			console.warn("LBB KioskBoard: KioskBoard library not found");
			return;
		}

		const layout = await getManagedKeyboardLayout();
		console.info("LBB KioskBoard layout:", layout);

		const kioskboardOptions = buildKioskboardOptions(layout);

		prepareTree(document, kioskboardOptions);

		document.addEventListener("focusin", (event) => {
			prepareInput(event.target, kioskboardOptions);
		}, true);

		const observer = new MutationObserver((mutations) => {
			for (const mutation of mutations) {
				for (const node of mutation.addedNodes) {
					if (node instanceof HTMLElement) {
						prepareTree(node, kioskboardOptions);
					}
				}
			}
		});

		observer.observe(document.documentElement || document.body, {
			childList: true,
			subtree: true
		});

		console.info(`LBB KioskBoard active, layout=${layout}`);
	}

	init();
})();
EOF
}

write_content_css() {
	cat > "${CONTENT_DIR}/lbb-kioskboard.css" <<'EOF'
.kioskboard-wrapper,
.kioskboard,
.kioskboard-container {
	z-index: 2147483647 !important;
}

.kioskboard {
	font-family: sans-serif !important;
}

.kioskboard-row {
	max-width: 100vw !important;
}

.kioskboard-key {
	min-height: 42px !important;
	font-size: 18px !important;
}
EOF
}

build_xpi() {
	local tmp_dir
	local tmp_xpi

	tmp_dir="$(mktemp -d)"
	tmp_xpi="${tmp_dir}/lbb-kiosk-keyboard.xpi"

	(
		cd "${SRC_DIR}"
		zip -qr "${tmp_xpi}" .
	)

	sudo install -d -m 0755 "${BASE_DIR}"
	sudo install -m 0644 "${tmp_xpi}" "${XPI_FILE}"
	rm -rf "${tmp_dir}"

	sudo chown -R root:root "${BASE_DIR}"
	sudo chmod -R u=rwX,go=rX "${BASE_DIR}"

	echo "Created Firefox extension:"
	echo "  Add-on ID : ${ADDON_ID}"
	echo "  XPI       : ${XPI_FILE}"
}

main() {
	require_tools
	prepare_dirs
	download_kioskboard
	download_kioskboard_keys
	write_manifest
	write_content_script
	write_content_css
	build_xpi
}

main "$@"
