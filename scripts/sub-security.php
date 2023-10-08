<!-- Author: Stefan Saam, github@saams.de
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<?php
	function clean_argument($string,$Allows=array()) {

		$Removes	= array(
			'"',
			"'",
			' ',
			';',
			'\n',
			'&',
			'|',
			'<',
			'>'
		);

		$Removes	= array_diff($Removes, $Allows);

		foreach ($Removes as $Remove) {
			$string	= str_replace($Remove,'',$string);
		}

		return($string);
	}
?>

