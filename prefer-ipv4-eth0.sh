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

# Ensure eth0 gets IPv4 via DHCP and is preferred over wlan0.
# Also keep gai.conf preference for IPv4. Safe to re-run.

set -euo pipefail

require_root() { [[ ${EUID:-$(id -u)} -eq 0 ]] || { echo "Please run with sudo."; exit 1; }; }
is_active() { systemctl is-active --quiet "$1"; }

set_gai_conf() {
local f="/etc/gai.conf" want="precedence ::ffff:0:0/96  100"
local pat='^[[:space:]]*#?[[:space:]]*precedence[[:space:]]+::ffff:0:0/96([[:space:]]+[0-9]+)?[[:space:]]*$'
[[ -f $f ]] || echo "# created by prefer-ipv4-eth0" > "$f"
cp -a "$f" "${f}.bak.$(date +%Y%m%d-%H%M%S)" 2>/dev/null || true
if grep -Eq "$pat" "$f"; then sed -ri "s|$pat|$want|" "$f"; else printf "\n# Prefer IPv4 (%s)\n%s\n" "$(date -Is)" "$want" >> "$f"; fi
}

nm_configure() {
command -v nmcli >/dev/null || return 1
is_active NetworkManager || return 1

local ethcon wlancon
ethcon="$(nmcli -t -f NAME,DEVICE,TYPE con show | awk -F: '$2=="eth0" && $3=="802-3-ethernet"{print $1; exit}')"
wlancon="$(nmcli -t -f NAME,DEVICE,TYPE con show --active | awk -F: '$2=="wlan0" && $3=="wifi"{print $1; exit}')"

if [[ -z "${ethcon:-}" ]]; then
	ethcon="eth0"
	nmcli con add type ethernet ifname eth0 con-name "$ethcon" ipv4.method auto ipv6.method auto
else
	nmcli con mod "$ethcon" ipv4.method auto ipv6.method auto
fi

# Set metrics: eth0 = 100, wlan0 = 600
nmcli con mod "$ethcon" ipv4.route-metric 100
[[ -n "${wlancon:-}" ]] && nmcli con mod "$wlancon" ipv4.route-metric 600

nmcli dev set eth0 managed yes || true
ip link set eth0 up || true
nmcli con up "$ethcon" || nmcli dev reapply eth0 || true
return 0
}

dhcpcd_configure() {
is_active dhcpcd || return 1
local f="/etc/dhcpcd.conf"
touch "$f"; cp -a "$f" "${f}.bak.$(date +%Y%m%d-%H%M%S)" 2>/dev/null || true

awk '
	BEGIN{in_e=0; seen_e=0; seen_w=0}
	/^[ \t]*denyinterfaces[ \t]+.*eth0/ { next }   # don’t deny eth0
	/^interface[ \t]+eth0[ \t]*$/ { print; in_e=1; seen_e=1; next }
	/^interface[ \t]+wlan0[ \t]*$/ { print; in_e=0; seen_w=1; next }
	/^interface[ \t]+/ { in_e=0 }
	{ print }
	END{
	if(!seen_e){ print ""; print "interface eth0"; print "  # DHCPv4 on eth0"; print "  noipv6rs" }
	if(!seen_w){ print ""; print "interface wlan0"; print "  metric 600" }
	}
' "$f" > "${f}.tmp" && mv "${f}.tmp" "$f"

systemctl restart dhcpcd || true
ip link set eth0 up || true
dhclient -4 -nw eth0 2>/dev/null || true
return 0
}

verify() {
echo "=== Devices ==="; ip link show dev eth0 | sed 's/^/  /'
echo "=== IPv4 on eth0 ==="; ip -4 addr show dev eth0 | sed 's/^/  /'
echo "=== IPv4 routes ==="; ip -4 route show default | sed 's/^/  /'
echo "=== NM status (if available) ==="
command -v nmcli >/dev/null && nmcli -t -f DEVICE,STATE,CONNECTION dev status
}

main() {
require_root
set_gai_conf
if ! nm_configure; then
	dhcpcd_configure || true
fi
verify
echo
echo "Note: If eth0 still shows no IPv4, the issue is likely upstream (switch port, DHCP server, or cable)."
echo "Try a different cable/port and check again: ip -4 addr show eth0"
}

main "$@"
