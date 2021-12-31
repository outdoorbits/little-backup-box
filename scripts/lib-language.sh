#!/bin/bash

# Author: Stefan Saam, github@saams.de
# License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt

# lib-language.sh and lib-language-helper.php are a bridge from php i18n to bash

function l() {
	WORKING_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)

	KEY="${1}";
	echo $(sudo php "${WORKING_DIR}/lib-language-helper.php" "${KEY}" 2> /dev/null)
}

