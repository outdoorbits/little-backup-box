#!/usr/bin/env bash
set -euo pipefail

PROFILE_DIR="${HOME}/.mozilla/firefox/kiosk.default"
CHROME_DIR="${PROFILE_DIR}/chrome"
USER_JS="${PROFILE_DIR}/user.js"

mkdir -p "${CHROME_DIR}"

# Helper: set or replace a pref in user.js (idempotent)
set_pref() {
  local key="$1"
  local value="$2"
  if [[ -f "${USER_JS}" ]] && grep -qE "user_pref\\(\"${key}\"," "${USER_JS}"; then
    sed -i -E "s|user_pref\\(\"${key}\",.*\\);|user_pref(\"${key}\", ${value});|g" "${USER_JS}"
  else
    echo "user_pref(\"${key}\", ${value});" >> "${USER_JS}"
  fi
}

# --- Required for profile CSS ---
set_pref "toolkit.legacyUserProfileCustomizations.stylesheets" "true"

# Prefer classic (non-overlay) scrollbars where supported
set_pref "widget.gtk.overlay-scrollbars.enabled" "false"

# Optional but recommended kiosk hardening (ESR-safe)
set_pref "browser.shell.checkDefaultBrowser" "false"
set_pref "browser.startup.homepage_override.mstone" "\"ignore\""
set_pref "browser.aboutwelcome.enabled" "false"
set_pref "browser.firstrun.didSkipDefaultBrowserCheck" "true"
set_pref "browser.firstrun.guide.enabled" "false"
set_pref "datareporting.policy.dataSubmissionEnabled" "false"
set_pref "toolkit.telemetry.enabled" "false"
set_pref "app.shield.optoutstudies.enabled" "false"

# --- Scrollbar styling for web content (ESR-compatible) ---
cat > "${CHROME_DIR}/userContent.css" <<'EOF'
/* Always-visible, very wide scrollbar with red thumb (Firefox ESR) */
:root, * {
  scrollbar-width: auto !important;
  scrollbar-color: #ff0000 #000000 !important;
  /*                ^ thumb   ^ track */
}

/* Width control (geometry only) */
scrollbar:vertical {
  -moz-appearance: none !important;
  width: 52px !important;
  min-width: 52px !important;
}
EOF

# Optional: UI-level scrollbar consistency
cat > "${CHROME_DIR}/userChrome.css" <<'EOF'
:root {
  scrollbar-width: auto !important;
}
EOF

echo "Firefox ESR kiosk profile configured:"
echo "  Profile : ${PROFILE_DIR}"
