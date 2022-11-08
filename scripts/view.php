<!doctype html>

<!-- Author: Stefan Saam, github@saams.de
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<?php
	$WORKING_DIR=dirname(__FILE__);
	$config = parse_ini_file("config.cfg", false);
	$constants = parse_ini_file("constants.sh", false);

	$theme = $config["conf_THEME"];
	$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='/img/backgrounds/" . $config["conf_BACKGROUND_IMAGE"] . "'";

	include("sub-popup.php");

	include("get-cloudservices.php");

	# symlink to media-folder
	if (! file_exists("./media")) {
		symlink("/media", "./media");
	}


	function navigator($filter_images_per_page,$offset,$imagecount,$GET_PARAMETER,$order_by,$order_dir,$label_filename,$label_creationdate) {
		$offset_left	= $offset >= $filter_images_per_page?$offset-$filter_images_per_page:0;
		$offset_end		= $imagecount >= $filter_images_per_page?($imagecount % $filter_images_per_page) * $filter_images_per_page:0;
		$offset_right	= $offset + $filter_images_per_page < $imagecount?$offset+$filter_images_per_page:$offset_end;

		?>
		<div class="card" style="margin-top: 2em;display: inline-block;width: 100%">
			<div style="float:left;width: 50%;padding: 5px;">
				<a href="<?php echo $GET_PARAMETER . "&offset=0"; ?>">&lt;&lt;</a>
				&nbsp;&nbsp;&nbsp;&nbsp;
				<a href="<?php echo $GET_PARAMETER . "&offset=" . $offset_left; ?>">&lt;</a>
				&nbsp;&nbsp;&nbsp;&nbsp;
				<?php
					$link_order_text	= $label_creationdate;
					$link_order			= $GET_PARAMETER . "&order_by=Create_Date&order_dir=";
					if ($order_by=="Create_Date") {
						$link_order_text	.= $order_dir == "ASC"?"&darr;":"&uarr;"; #symbol reversed because of print-direction up to down
						$link_order_text	= "<b>" . $link_order_text . "</b>";
						$link_order			.= $order_dir == "ASC"?"DESC":"ASC";
					} else {
						$link_order			.= "ASC";
					}
					echo "<a href='" . $link_order . "'>" . $link_order_text . "</a>";
				?>
			</div>

			<div style="float:right;width: 50%;padding: 5px;text-align: right">
				<?php
					$link_order_text	= $label_filename;
					$link_order			= $GET_PARAMETER . "&order_by=File_Name&order_dir=";
					if ($order_by=="File_Name") {
						$link_order_text	.= $order_dir == "ASC"?"&darr;":"&uarr;"; #symbol reversed because of print-direction up to down
						$link_order_text	= "<b>" . $link_order_text . "</b>";
						$link_order			.= $order_dir == "ASC"?"DESC":"ASC";
					} else {
						$link_order			.= "ASC";
					}
					echo "<a href='" . $link_order . "'>" . $link_order_text . "</a>";
				?>
				&nbsp;&nbsp;&nbsp;&nbsp;
				<a href="<?php echo $GET_PARAMETER . "&offset=".$offset_right; ?>">&gt;</a>
				&nbsp;&nbsp;&nbsp;&nbsp;
				<a href="<?php echo $GET_PARAMETER . "&offset=".$offset_end; ?>">&gt;&gt;</a>
			</div>
		</div>
		<?php
	}

	function add_to_where($new_where,$target_array) {
		global $WHERE;

		foreach ($target_array as $target) {
			if ($WHERE[$target] != "") {
				$WHERE[$target]	.= " and ";
			} else {
				$WHERE[$target]	= "where ";
			}

			$WHERE[$target]	.= "($new_where)";
		}
	}

	function fix_medium_in_dir($ImageDir,$STORAGE_PATH) {
		global $constants;
		if (strpos($ImageDir, $STORAGE_PATH) === 0) {
			return $ImageDir;
		}
		elseif (strpos($ImageDir, $constants['const_USB_TARGET_MOUNT_POINT']) === 0) {
			return (substr_replace($ImageDir, $STORAGE_PATH, 0, strlen($constants['const_USB_TARGET_MOUNT_POINT'])));
		}
		elseif (strpos($ImageDir, $constants['const_USB_SOURCE_MOUNT_POINT']) === 0) {
			return (substr_replace($ImageDir, $STORAGE_PATH, 0, strlen($constants['const_USB_SOURCE_MOUNT_POINT'])));
		}
	}

	# setup
	$IMAGES_PER_PAGE_OPTIONS	= array (10,25,50,100);

	# standard values
	$filter_medium			= "-";
	$view_mode				= "grid";
	$filter_images_per_page	= 10;
	$filter_directory		= "";
	$filter_date			= "all";
	$offset					= 0;
	$imagecount				= 0;
	$order_by          		= "Create_Date";
	$order_dir				= "DESC";

	$FIELDS_BLOCKED_ARRAY	= array(
		"ID",
		"ExifTool_Version_Number"
	);

	# read parameters
	extract ($_POST);
	extract ($_GET);

	## security-checks
	if (! in_array($filter_images_per_page,$IMAGES_PER_PAGE_OPTIONS)) {
		$filter_images_per_page	= $IMAGES_PER_PAGE_OPTIONS[0];
	}
	$offset	= intval($offset);
	if (! in_array($order_by,array('File_Name','Create_Date'))) {$order_by='Create_Date';}
	if (! in_array($order_dir,array('ASC','DESC'))) {$order_dir='DESC';}
	
	#auto-set filter_medium
	if ($filter_medium == "-") {
		$mounted_devices_array	= array();
		exec("df -h", $mounted_devices_array);

		$mounted_devices	= implode("|",$mounted_devices_array);

		if (strpos($mounted_devices,$constants['const_USB_TARGET_MOUNT_POINT'])!== false) {$filter_medium="usb_storage";}
		elseif (strpos($mounted_devices,$constants['const_USB_SOURCE_MOUNT_POINT'])!== false) {$filter_medium="usb_source";}
		else {$filter_medium="internal";}
	}

	#generate WHERE
	$WHERE['images']	= "";
	$WHERE['directories']	= "";
	$WHERE['dates']	= "";

	if (isset($ID)) {add_to_where("ID=" . $ID,array('images'));}
	if ($filter_directory != "") {
		$filter_directory	= str_replace("+","=",$filter_directory);
		$filter_directory	= base64_decode($filter_directory);
		add_to_where("Directory='" . $filter_directory . "'",array('images','dates'));
	}
	if ($filter_date != "all") {add_to_where("substr(Create_Date,1,10) like '" . str_replace("-","_",$filter_date) . "'",array('images','directories'));}

	# define path of the database-file
	$STORAGE_PATH	= "";
	if ($filter_medium == "usb_storage") {
		$STORAGE_PATH	=$constants['const_USB_TARGET_MOUNT_POINT'];
	}
	elseif ($filter_medium == "usb_source") {
		$STORAGE_PATH	=$constants['const_USB_SOURCE_MOUNT_POINT'];
	}
	elseif ($filter_medium == "internal") {
		$STORAGE_PATH	=$constants['const_INTERNAL_BACKUP_DIR'];
	}

	# Database-query
	$DATABASE_CONNECTED=false;
	$DATABASE_FILE	= "";
	if ($STORAGE_PATH != "") {
		$DATABASE_FILE	= $STORAGE_PATH . '/' . $constants['const_IMAGE_DATABASE_FILENAME'];

		try {
			$db = new SQLite3($DATABASE_FILE);

			$statement		= $db->prepare("SELECT * FROM EXIF_DATA " . $WHERE['images'] . " order by " . $order_by . " " . $order_dir . " limit " . $filter_images_per_page . " offset " . $offset . ";");
			$IMAGES			= $statement->execute();

			$statement		= $db->prepare("SELECT count(ID) as IMAGECOUNT FROM EXIF_DATA " . $WHERE['images'] . ";");
			$IMAGECOUNTER	= $statement->execute();
			$imagecount		= $IMAGECOUNTER->fetchArray(SQLITE3_ASSOC)['IMAGECOUNT'];

			$statement		= $db->prepare("SELECT Directory, count (ID) as FILECOUNT FROM EXIF_DATA " . $WHERE['directories'] . " group by Directory;");
			$DIRECTORIES	= $statement->execute();

			$statement		= $db->prepare("SELECT substr(replace(Create_Date,':','-'),1,10) as Create_Day, count (ID) as FILECOUNT FROM EXIF_DATA " . $WHERE['dates'] . " group by Create_Day order by Create_Day desc;");
			$DATES			= $statement->execute();

			$DATABASE_CONNECTED=true;
		} catch (Error $e) {
			$DATABASE_CONNECTED=false;
		}

	}

	# define GET_PARAMETER
	$GET_PARAMETER	= "?filter_medium=$filter_medium";
	$GET_PARAMETER	.= "&order_by=$order_by";
	$GET_PARAMETER	.= "&order_dir=$order_dir";
	if ($view_mode != "") {$GET_PARAMETER	.= "&view_mode=$view_mode";}
	$GET_PARAMETER	.= "&filter_images_per_page=$filter_images_per_page";
	if ($filter_directory != "") {$GET_PARAMETER	.= "&filter_directory=" . str_replace("=","+",base64_encode($filter_directory));}
	if ($filter_date != "") {$GET_PARAMETER	.= "&filter_date=" . $filter_date;}
?>

<html lang="<?php echo $config["conf_LANGUAGE"]; ?>" data-theme="<?php echo $theme; ?>">

<head>
	<?php include "${WORKING_DIR}/sub-standards-header-loader.php"; ?>
</head>

<body <?php echo $background; ?>>
	<?php include "${WORKING_DIR}/sub-standards-body-loader.php"; ?>
	<?php include "${WORKING_DIR}/sub-menu.php"; ?>

	<h1 class="text-center" style="margin-bottom: 1em; letter-spacing: 3px;"><?php echo L::view_view; ?></h1>



	<div class="card" style="margin-top: 2em;">
		<details>
			<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::view_filter_section; ?></summary>
			<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
				<input type="hidden" name="order_by" value="<?php echo $order_by; ?>">
				<input type="hidden" name="order_dir" value="<?php echo $order_dir; ?>">
				<div>


					<label for="filter_medium"><?php echo L::view_filter_medium; ?></label><br>

						<select name="filter_medium" id="filter_medium" onchange="this.form.submit()">
							<option value="usb_storage" <?php echo ($filter_medium == "usb_storage"?" selected":""); ?>><?php echo L::view_filter_medium_usb_storage; ?></option>";
							<option value="usb_source" <?php echo ($filter_medium == "usb_source"?" selected":""); ?>><?php echo L::view_filter_medium_usb_source; ?></option>";
							<option value="internal" <?php echo ($filter_medium == "internal"?" selected":""); ?>><?php echo L::view_filter_medium_internal; ?></option>";
						</select>

				</div>

				<div>
					<label for="filter_images_per_page"><?php echo L::view_filter_images_per_page; ?></label><br>
						<select name="filter_images_per_page" id="filter_images_per_page" onchange="this.form.submit()">
						<?php
							foreach ($IMAGES_PER_PAGE_OPTIONS as $IMAGES_PER_PAGE_OPTION) {
								echo "<option value=\"$IMAGES_PER_PAGE_OPTION\" " . ($filter_images_per_page == $IMAGES_PER_PAGE_OPTION?" selected":"") . ">$IMAGES_PER_PAGE_OPTION</option>";
							}
						?>
						</select>
				</div>

				<?php if ($DATABASE_CONNECTED) { ?>
					<div>
						<label for="filter_date"><?php echo L::view_filter_date; ?></label><br>
							<select name="filter_date" id="filter_date" onchange="this.form.submit()">
								<option value="all" <?php echo ($filter_date == ""?" selected":""); ?>>-</option>
								<?php
									$i=0;
									while ($DATE = $DATES->fetchArray(SQLITE3_ASSOC)) {
										$i+=1;
										echo "<option value=\"" . $DATE['Create_Day'] . "\" " . ($filter_date == $DATE['Create_Day']?" selected":"") . ">" . $DATE['Create_Day'] . " (" . $DATE['FILECOUNT'] . ")</option>";
									}
								?>
							</select>
					</div>
				<?php } ?>

				<?php if ($DATABASE_CONNECTED) { ?>
					<div>
						<label for="filter_directory"><?php echo L::view_filter_directory; ?></label><br>
							<select name="filter_directory" id="filter_directory" onchange="this.form.submit()">
								<option value="" <?php echo ($filter_directory == ""?" selected":""); ?>>/</option>
								<?php
									$i=0;
									while ($DIRECTORY = $DIRECTORIES->fetchArray(SQLITE3_ASSOC)) {
										$i+=1;
										echo "<option value=\"" . str_replace("=","+",base64_encode($DIRECTORY['Directory'])) . "\" " . ($filter_directory == $DIRECTORY['Directory']?" selected":"") . ">" . str_replace($STORAGE_PATH,"",$DIRECTORY['Directory']) . " (" . $DIRECTORY['FILECOUNT'] . ")</option>";
									}
								?>
							</select>
					</div>
				<?php } ?>

			</form>
		</details>
	</div>


	<?php if ($DATABASE_CONNECTED) { ?>
		<?php navigator($filter_images_per_page,$offset,$imagecount,$GET_PARAMETER,$order_by,$order_dir,L::view_filter_order_by_filename,L::view_filter_order_by_creationdate); ?>

		<div class="card" style="margin-top: 2em;display: inline-block">
			<?php
				if ($view_mode == "grid") {
					while ($IMAGE = $IMAGES->fetchArray(SQLITE3_ASSOC)) {
						$Directory				= fix_medium_in_dir($IMAGE['Directory'],$STORAGE_PATH);
						$IMAGE_ID				= $IMAGE['ID'];
						$IMAGE_FILENAME_TIMS	= $Directory . '/tims/' . $IMAGE['File_Name'];
						?>

						<div style="float:left;width: 33.33%;padding: 5px;" title="<?php echo $IMAGE['File_Name']; ?>">
							<a href="<?php echo $GET_PARAMETER . '&view_mode=single&ID=' . $IMAGE_ID; ?>">
								<img style="max-width: 100%; border-radius: 5px;" src="<?php echo $IMAGE_FILENAME_TIMS; ?>">
							</a>
						</div>

						<?php
					}
				}
				elseif ($view_mode == "single") {
					while ($IMAGE = $IMAGES->fetchArray(SQLITE3_ASSOC)) {
						$Directory				= fix_medium_in_dir($IMAGE['Directory'],$STORAGE_PATH);
						$IMAGE_ID				= $IMAGE['ID'];
						$IMAGE_FILENAME			= $Directory . '/' . $IMAGE['File_Name'];
						$IMAGE_FILENAME_TIMS	= $Directory . '/tims/' . $IMAGE['File_Name'];
						?>

						<div style="float:left;width: 100%;padding: 5px;">
							<a href="<?php echo $GET_PARAMETER . '&view_mode=grid'; ?>">
								<img style="max-width: 100%; border-radius: 5px;" src="<?php echo $IMAGE_FILENAME_TIMS; ?>">
							</a>
							<br>
							<a href="<?php echo $IMAGE_FILENAME; ?>" target="_blank">
								<?php echo L::view_images_download; ?>
							</a>
						</div>

						<div style="float:left;width: 100%;padding: 5px;">
							<table>
							<?php
								foreach ($IMAGE as $FIELD => $VALUE) {
									if (($VALUE != "") and (! in_array($FIELD,$FIELDS_BLOCKED_ARRAY))) {
										$FIELD	= str_replace('_',' ',$FIELD);
										echo "<tr><td valign='top' width='30%'>$FIELD:</td><td valign='top' width='70%'><b>$VALUE</b></td></tr>";
									}
								}
							?>
							</table>
						</div>

						<?php
					}
				}
			?>
		</div>

		<?php navigator($filter_images_per_page,$offset,$imagecount,$GET_PARAMETER,$order_by,$order_dir,L::view_filter_order_by_filename,L::view_filter_order_by_creationdate); ?>

	<?php } else { ?>
		<div class="card" style="margin-top: 2em;">
			<?php echo L::view_filter_no_medium; ?>
		</div>
	<?php } ?>

	<div class="card" style="margin-top: 2em;">
			<?php echo L::view_footer_footer; ?>
	</div>

	<?php include "sub-footer.php"; ?>

</body>

</html>
