<!-- Author: Stefan Saam, github@saams.de
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<?php
	function popup($MESSAGE,$POPUP_ALLOWED=true) {
		$MESSAGE=str_replace("\n","\\n",$MESSAGE);
		if ($POPUP_ALLOWED) {
			echo "<script>";
				echo 'alert("' . $MESSAGE . '")';
			echo "</script>";
		}
	}
?>

