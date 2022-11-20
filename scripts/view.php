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


	function navigator($view_mode,$filter_images_per_page,$filter_rating,$offset,$imagecount,$GET_PARAMETER,$order_by,$order_dir,$label_filename,$label_creationdate) {
		if ($view_mode == "grid") {
			$offset_left	= $offset >= $filter_images_per_page?$offset-$filter_images_per_page:0;
			$offset_end		= $imagecount >= $filter_images_per_page?intval($imagecount / $filter_images_per_page) * $filter_images_per_page:0;
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

						$page	= intval($offset / $filter_images_per_page) + 1;
						$pages	= intval($imagecount / $filter_images_per_page) + 1;
						echo "&nbsp;&nbsp;&nbsp;&nbsp;" . L::view_images_page . ' ' . $page . '/' . $pages;
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

						echo $imagecount . ' ' . L::view_images_images . "&nbsp;&nbsp;&nbsp;&nbsp;";

						echo "<a href='" . $link_order . "'>" . $link_order_text . "</a>";
					?>
					&nbsp;&nbsp;&nbsp;&nbsp;
					<a href="<?php echo $GET_PARAMETER . "&offset=".$offset_right; ?>">&gt;</a>
					&nbsp;&nbsp;&nbsp;&nbsp;
					<a href="<?php echo $GET_PARAMETER . "&offset=".$offset_end; ?>">&gt;&gt;</a>
				</div>

				<div style="display: flow-root;width: 100%">
					<div style="float:left;width: 50%;padding: 5px;">
						<?php
							echo "&nbsp;&nbsp;&nbsp;&nbsp;<button style=\"margin-top: 2em;\" type=\"submit\" name=\"save_ratings\">" . L::view_ratings_save_button . "</button>";
						?>
					</div>

					<div style="float:right;width: 50%;padding: 5px;text-align: right">
						<?php
							if ($filter_rating == 1) {
								echo "<button style=\"margin-top: 2em;\" type=\"submit\" name=\"delete_ratings_1\" class=\"danger\">" . L::view_ratings_1_delete_button . "</button>";
							}
						?>
					</div>
				</div>
			</div>
			<?php
		} else {
			?>
			<div class="card" style="margin-top: 2em;display: inline-block;width: 100%">
				<div style="float:left;width: 50%;padding: 5px;">
					<a href="<?php echo $GET_PARAMETER . '&view_mode=grid'; ?>">
						<?php echo L::view_images_back_to_grid; ?>
					</a>
				</div>
			</div>
			<?php
		}
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

	# setup
	$IMAGES_PER_PAGE_OPTIONS	= array (10,25,50,100);

	# standard values
	$filter_medium			= "-";
	$view_mode				= "grid";
	$filter_images_per_page	= 10;
	$filter_directory		= "";
	$filter_date			= "all";
	$filter_rating			= "all";
	$filter_variable_field	= "";
	$filter_variable_value	= "";
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

	if ($filter_rating != "all") {
		$filter_rating	= intval($filter_rating);
		if (($filter_rating < 1) or ($filter_rating > 5)) {
			$filter_rating	= "all";
		}
	}



	# ratings-preparation
	$RATINGS_ARRAY=array();
	foreach ($_POST as $key=>$val) {
		if (substr($key, 0, 6 )=="rating") {
			$ID_RATING	= explode("_",$key)[1];
 			$RATINGS_ARRAY[$ID_RATING]=$val;
		}
	}

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
	$WHERE['images']		= "";
	$WHERE['directories']	= "";
	$WHERE['dates']			= "";
	$WHERE['ratings']		= "";
	$WHERE['variable']		= "";

	if (isset($ID)) {add_to_where("ID=" . $ID,array('images'));}

	if ($filter_directory != "") {
		$filter_directory	= str_replace("+","=",$filter_directory);
		$filter_directory	= base64_decode($filter_directory);

		add_to_where("Directory='" . $filter_directory . "'",array('images','dates','ratings','variable'));
	}

	if ($filter_date != "all") {add_to_where("substr(Create_Date,1,10) like '" . str_replace("-","_",$filter_date) . "'",array('images','directories','ratings','variable'));}

	if (isset($delete_ratings_1)) {$filter_rating="all";} # after delete remove rating-filter

	if ($filter_rating != "all") {add_to_where("LbbRating = " . $filter_rating,array('images','dates','directories'));}

	if ($filter_variable_value != "") {
		$filter_variable_value	= str_replace("+","=",$filter_variable_value);
		$filter_variable_value	= base64_decode($filter_variable_value);

		if (($filter_variable_field != "") and ($filter_variable_value != "")) {add_to_where($filter_variable_field . "='" . $filter_variable_value . "'",array('images','directories','dates','ratings'));}
	}

	# generate select_offset
	if ($view_mode=="grid") {$select_offset="offset " . $offset;} else {$select_offset="";}

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
			shell_exec("sudo chown www-data:www-data '" . $DATABASE_FILE ."'");
			$db = new SQLite3($DATABASE_FILE);

			# save ratings in any case
			foreach($RATINGS_ARRAY as $key=>$val) {
				$key	= intval($key);
				$val	= intval($val);
				$db->exec("update EXIF_DATA set LbbRating=". $val . " where ID=" . $key) . ";";
			}

			# delete
			if (isset($delete_ratings_1)) {
				foreach($RATINGS_ARRAY as $key=>$val) {
					$key	= intval($key);

					$statement		= $db->prepare("SELECT File_Name, Directory FROM EXIF_DATA WHERE ID=" . $key . ";");
					$IMAGES			= $statement->execute();
					if ($IMAGE = $IMAGES->fetchArray(SQLITE3_ASSOC)) {
						$DELETE_FILE	= $STORAGE_PATH . '/' . $IMAGE['Directory'] . '/' . $IMAGE['File_Name'];
						$DELETE_TIMS	= $STORAGE_PATH . '/' . $IMAGE['Directory'] . '/tims/' . $IMAGE['File_Name'] . '.JPG';
						shell_exec ("sudo rm '" . $DELETE_FILE . "'");
						shell_exec ("sudo rm '" . $DELETE_TIMS . "'");
						$db->exec("delete from EXIF_DATA where ID=" . $key . " and LbbRating=1;");
					}

				}
			}

			$statement			= $db->prepare("SELECT * FROM EXIF_DATA " . $WHERE['images'] . " order by " . $order_by . " " . $order_dir . " limit " . $filter_images_per_page . " " . $select_offset . ";");
			$IMAGES				= $statement->execute();

			$statement			= $db->prepare("SELECT count(ID) as IMAGECOUNT FROM EXIF_DATA " . $WHERE['images'] . ";");
			$IMAGECOUNTER		= $statement->execute();
			$imagecount			= $IMAGECOUNTER->fetchArray(SQLITE3_ASSOC)['IMAGECOUNT'];

			$statement			= $db->prepare("SELECT Directory, count (ID) as FILECOUNT FROM EXIF_DATA " . $WHERE['directories'] . " group by Directory order by Directory;");
			$DIRECTORIES		= $statement->execute();

			$statement			= $db->prepare("SELECT LbbRating, count (ID) as FILECOUNT FROM EXIF_DATA " . $WHERE['ratings'] . " group by LbbRating order by LbbRating;");
			$RATINGS			= $statement->execute();

			$statement			= $db->prepare("SELECT substr(replace(Create_Date,':','-'),1,10) as Create_Day, count (ID) as FILECOUNT FROM EXIF_DATA " . $WHERE['dates'] . " group by Create_Day order by Create_Day desc;");
			$DATES				= $statement->execute();

			$statement			= $db->prepare("PRAGMA table_info(EXIF_DATA);");
			$VAR_FIELDS			= $statement->execute();

			if ($filter_variable_field != "") {
				$statement			= $db->prepare("SELECT " . $filter_variable_field . " as var_filter_value, count (ID) as FILECOUNT FROM EXIF_DATA " . $WHERE['variable'] . " group by " . $filter_variable_field . " order by " . $filter_variable_field . ";");
				$VAR_VALUES	= $statement->execute();
			}

			$DATABASE_CONNECTED=true;
		} catch (Error $e) {
			$DATABASE_CONNECTED=false;
		}

	}

	# define GET_PARAMETER
	$GET_PARAMETER	= "?filter_medium=$filter_medium";
	$GET_PARAMETER	.= "&order_by=$order_by";
	$GET_PARAMETER	.= "&order_dir=$order_dir";
	$GET_PARAMETER	.= "&offset=$offset";
	if ($view_mode != "") {$GET_PARAMETER	.= "&view_mode=$view_mode";}
	$GET_PARAMETER	.= "&filter_images_per_page=$filter_images_per_page";
	if ($filter_directory != "") {$GET_PARAMETER	.= "&filter_directory=" . str_replace("=","+",base64_encode($filter_directory));}
	if ($filter_date != "") {$GET_PARAMETER	.= "&filter_date=" . $filter_date;}
	if ($filter_rating != "") {$GET_PARAMETER	.= "&filter_rating=$filter_rating";}
	if ($filter_variable_field != "") {$GET_PARAMETER	.= "&filter_variable_field=$filter_variable_field";}
	if ($filter_variable_value != "") {$GET_PARAMETER	.= "&filter_variable_value=" . str_replace("=","+",base64_encode($filter_variable_value));}


	# define hidden HIDDEN_INPUTS
	$HIDDEN_INPUTS	="<input type=\"hidden\" name=\"filter_medium\" value=\"" . $filter_medium . "\">";
	$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"order_by\" value=\"" . $order_by . "\">";
	$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"order_dir\" value=\"" . $order_dir . "\">";
	$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"offset\" value=\"" . $offset . "\">";
	if ($view_mode != "") {$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"view_mode\" value=\"" . $view_mode . "\">";}
	$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_images_per_page\" value=\"" . $filter_images_per_page . "\">";
	if ($filter_directory != "") {$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_directory\" value=\"" . str_replace("=","+",base64_encode($filter_directory)) . "\">";}
	if ($filter_date != "") {$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_date\" value=\"" . $filter_date . "\">";}
	if ($filter_rating != "") {$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_rating\" value=\"" . $filter_rating . "\">";}
	if ($filter_variable_field != "") {$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_variable_field\" value=\"" . $filter_variable_field . "\">";}
	if ($filter_variable_value != "") {$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_variable_value\" value=\"" . str_replace("=","+",base64_encode($filter_variable_value)) . "\">";}
?>

<html lang="<?php echo $config["conf_LANGUAGE"]; ?>" data-theme="<?php echo $theme; ?>">

<head>
	<?php include "${WORKING_DIR}/sub-standards-header-loader.php"; ?>
</head>

<body <?php echo $background; ?>>
	<?php include "${WORKING_DIR}/sub-standards-body-loader.php"; ?>
	<?php include "${WORKING_DIR}/sub-menu.php"; ?>
<?php print_r($constants['const_FILE_EXTENSIONS_ARRAY_JPG']); ?>

	<h1 class="text-center" style="margin-bottom: 1em; letter-spacing: 3px;"><?php echo L::view_view; ?></h1>

	<div class="card" style="margin-top: 2em">
		<details>
			<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::view_filter_section; ?></summary>
			<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
				<input type="hidden" name="order_by" value="<?php echo $order_by; ?>">
				<input type="hidden" name="order_dir" value="<?php echo $order_dir; ?>">

				<div style="display: flow-root">
					<div style="float:left;padding: 5px;">
						<label for="filter_medium"><?php echo L::view_filter_medium; ?></label><br>

							<select name="filter_medium" id="filter_medium" onchange="this.form.submit()">
								<option value="usb_storage" <?php echo ($filter_medium == "usb_storage"?" selected":""); ?>><?php echo L::view_filter_medium_usb_storage; ?></option>";
								<option value="usb_source" <?php echo ($filter_medium == "usb_source"?" selected":""); ?>><?php echo L::view_filter_medium_usb_source; ?></option>";
								<option value="internal" <?php echo ($filter_medium == "internal"?" selected":""); ?>><?php echo L::view_filter_medium_internal; ?></option>";
							</select>
					</div>

					<div style="float:right;padding: 5px;">
						<label for="filter_images_per_page"><?php echo L::view_filter_images_per_page; ?></label><br>
							<select name="filter_images_per_page" id="filter_images_per_page" onchange="this.form.submit()">
							<?php
								foreach ($IMAGES_PER_PAGE_OPTIONS as $IMAGES_PER_PAGE_OPTION) {
									echo "<option value=\"$IMAGES_PER_PAGE_OPTION\" " . ($filter_images_per_page == $IMAGES_PER_PAGE_OPTION?" selected":"") . ">$IMAGES_PER_PAGE_OPTION</option>";
								}
							?>
							</select>
					</div>
				</div>

				<div style="display: flow-root">
					<?php if ($DATABASE_CONNECTED) { ?>
						<div style="float:left;padding: 5px;">
							<label for="filter_date"><?php echo L::view_filter_date; ?></label><br>
								<select name="filter_date" id="filter_date" onchange="this.form.submit()">
									<option value="all" <?php echo ($filter_date == ""?" selected":""); ?>>-</option>
									<?php
										while ($DATE = $DATES->fetchArray(SQLITE3_ASSOC)) {
											echo "<option value=\"" . $DATE['Create_Day'] . "\" " . ($filter_date == $DATE['Create_Day']?" selected":"") . ">" . $DATE['Create_Day'] . " (" . $DATE['FILECOUNT'] . ")</option>";
										}
									?>
								</select>
						</div>
					<?php } ?>

					<?php if ($DATABASE_CONNECTED) { ?>
						<div style="float:right;padding: 5px;">
							<label for="filter_rating"><?php echo L::view_filter_rating; ?></label><br>
								<select name="filter_rating" id="filter_rating" onchange="this.form.submit()">
									<?php
										echo "<option value=\"all\">" . L::view_filter_rating_all . "</option>";
										while ($RATING = $RATINGS->fetchArray(SQLITE3_ASSOC)) {
											echo "<option value=\"" . $RATING['LbbRating'] . "\" " . ($filter_rating == $RATING['LbbRating']?" selected":"") . ">" . $RATING['LbbRating'] . " " . L::view_filter_rating_stars . " (" . $RATING['FILECOUNT'] . ")</option>";
										}
									?>
								</select>
						</div>
					<?php } ?>
				</div>

				<div style="display: flow-root">
					<?php if ($DATABASE_CONNECTED) { ?>
						<div style="float:left;padding: 5px;">
							<label for="filter_directory"><?php echo L::view_filter_directory; ?></label><br>
								<select name="filter_directory" id="filter_directory" onchange="this.form.submit()">
									<option value="" <?php echo ($filter_directory == ""?" selected":""); ?>>/</option>
									<?php
										while ($DIRECTORY = $DIRECTORIES->fetchArray(SQLITE3_ASSOC)) {
											echo "<option value=\"" . str_replace("=","+",base64_encode($DIRECTORY['Directory'])) . "\" " . ($filter_directory == $DIRECTORY['Directory']?" selected":"") . ">" . str_replace($STORAGE_PATH,"",$DIRECTORY['Directory']) . " (" . $DIRECTORY['FILECOUNT'] . ")</option>";
										}
									?>
								</select>
						</div>
					<?php } ?>
				</div>

				<div style="display: flow-root">
					<?php if ($DATABASE_CONNECTED) { ?>
						<div style="float:left;padding: 5px;">
							<label for="filter_variable_field"><?php echo L::view_filter_variable; ?></label><br>
								<select name="filter_variable_field" id="filter_variable_field" onchange="this.form.submit()">
									<option value="" <?php echo ($filter_variable_field == ""?" selected":""); ?>>-</option>
									<?php
										$FIELDS_ARRAY=array();
										while ($FIELD = $VAR_FIELDS->fetchArray(SQLITE3_ASSOC)) {
											$FIELDS_ARRAY[]=$FIELD['name'];
										}
										asort($FIELDS_ARRAY);
										foreach($FIELDS_ARRAY as $FIELD) {
											echo "<option value=\"" . $FIELD . "\" " . ($filter_variable_field == $FIELD?" selected":"") . ">" . $FIELD . "</option>";
										}
									?>
								</select>
								<?php
									if (isset($VAR_VALUES)) {
										?>
										<select name="filter_variable_value" id="filter_variable_value" onchange="this.form.submit()">
											<option value="">-</option>
											<?php
												while ($VALUE = $VAR_VALUES->fetchArray(SQLITE3_ASSOC)) {
													echo "<option value=\"" . str_replace("=","+",base64_encode($VALUE['var_filter_value'])) . "\" " . ($filter_variable_value == $VALUE['var_filter_value']?" selected":"") . ">" . $VALUE['var_filter_value'] . "(" . $VALUE['FILECOUNT'] . ")</option>";
												}
											?>
										</select>
										<?php
									}
								?>
						</div>
					<?php } ?>
				</div>

			</form>
		</details>
	</div>


	<?php if ($DATABASE_CONNECTED) { ?>
		<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">

			<?php echo $HIDDEN_INPUTS; ?>
			<?php navigator($view_mode,$filter_images_per_page,$filter_rating,$offset,$imagecount,$GET_PARAMETER,$order_by,$order_dir,L::view_filter_order_by_filename,L::view_filter_order_by_creationdate); ?>

			<div class="card" style="margin-top: 2em;display: inline-block">
				<?php
					if ($view_mode == "grid") {
						while ($IMAGE = $IMAGES->fetchArray(SQLITE3_ASSOC)) {
							$Directory				= $STORAGE_PATH . '/' . $IMAGE['Directory'];
							$IMAGE_ID				= $IMAGE['ID'];
							$IMAGE_FILENAME_TIMS	= $Directory . '/tims/' . $IMAGE['File_Name'] . '.JPG';
							?>

							<div style="float:left;width: 33.33%;padding: 5px;" title="<?php echo $IMAGE['File_Name']; ?>">

								<div style="width: 100%">
									<a href="<?php echo $GET_PARAMETER . '&view_mode=single&ID=' . $IMAGE_ID; ?>">
										<img style="max-width: 100%; border-radius: 5px;" <?php echo ($IMAGE['LbbRating']==1)?"class=\"delete\"":""; ?> src="<?php echo $IMAGE_FILENAME_TIMS; ?>">
									</a>
								</div>

								<div style="display: flow-root;width: 100%">
									<div style="float:left;padding: 5px;" class="rating">
										<input id="rating_1_<?php echo $IMAGE['ID']; ?>" type="radio" name="rating_<?php echo $IMAGE['ID']; ?>" value="1" <?php echo $IMAGE['LbbRating']>=1?"checked":""; ?>>
										<label for="rating_1_<?php echo $IMAGE['ID']; ?>"></label>
										<input id="rating_2_<?php echo $IMAGE['ID']; ?>" type="radio" name="rating_<?php echo $IMAGE['ID']; ?>" value="2" <?php echo $IMAGE['LbbRating']>=2?"checked":""; ?>>
										<label for="rating_2_<?php echo $IMAGE['ID']; ?>"></label>
										<input id="rating_3_<?php echo $IMAGE['ID']; ?>" type="radio" name="rating_<?php echo $IMAGE['ID']; ?>" value="3" <?php echo $IMAGE['LbbRating']>=3?"checked":""; ?>>
										<label for="rating_3_<?php echo $IMAGE['ID']; ?>"></label>
										<input id="rating_4_<?php echo $IMAGE['ID']; ?>" type="radio" name="rating_<?php echo $IMAGE['ID']; ?>" value="4" <?php echo $IMAGE['LbbRating']>=4?"checked":""; ?>>
										<label for="rating_4_<?php echo $IMAGE['ID']; ?>"></label>
										<input id="rating_5_<?php echo $IMAGE['ID']; ?>" type="radio" name="rating_<?php echo $IMAGE['ID']; ?>" value="5" <?php echo $IMAGE['LbbRating']>=5?"checked":""; ?>>
										<label for="rating_5_<?php echo $IMAGE['ID']; ?>"></label>
									</div>

									<div style="float:right;padding: 5px;font-size:0.8em;" class="hidden-mobile">
										<a href="<?php echo $GET_PARAMETER . '&view_mode=single&ID=' . $IMAGE_ID; ?>">
											<?php echo $IMAGE['File_Name']; ?>
										</a>
									</div>
								</div>
							</div>



							<?php
						}
					}
					elseif ($view_mode == "single") {
						while ($IMAGE = $IMAGES->fetchArray(SQLITE3_ASSOC)) {
							$Directory				= $STORAGE_PATH . '/' . $IMAGE['Directory'];
							$IMAGE_ID				= $IMAGE['ID'];
							$IMAGE_FILENAME			= $Directory . '/' . $IMAGE['File_Name'];
							$IMAGE_FILENAME_TIMS	= $Directory . '/tims/' . $IMAGE['File_Name'] . '.JPG';
							?>

							<div style="float:left;width: 100%;padding: 5px;">

								<?php
									$IMAGE_FILENAME_PARTS=pathinfo($IMAGE_FILENAME);

									if (strpos(" " . $constants['const_FILE_EXTENSIONS_LIST_JPG'] . " " . $constants['const_FILE_EXTENSIONS_LIST_RAW'] . " "," " . strtolower($IMAGE_FILENAME_PARTS['extension']) . " ") !== false ) {
// 										image-file
								?>

										<a href="<?php echo $GET_PARAMETER . '&view_mode=grid'; ?>">
											<img style="max-width: 100%; border-radius: 5px;" <?php echo ($IMAGE['LbbRating']==1)?"class=\"delete\"":""; ?> src="<?php echo $IMAGE_FILENAME_TIMS; ?>">
										</a>
										<br>
										<a href="<?php echo $IMAGE_FILENAME; ?>" target="_blank">
											<?php echo L::view_images_download; ?>
										</a>

								<?php
									} elseif (strpos(" " . $constants['const_FILE_EXTENSIONS_LIST_VIDEO'] . " "," " . strtolower($IMAGE_FILENAME_PARTS['extension']) . " ") !== false ) {
// 										video-file
										?>
											<video width="100%" controls autoplay>
												<source src="<?php echo $IMAGE_FILENAME; ?>" type="video/<?php echo $IMAGE_FILENAME_PARTS['extension']; ?>"></source>
											</video>
											<br>
											<a href="<?php echo $IMAGE_FILENAME; ?>" target="_blank">
												<?php echo L::view_images_download; ?>
											</a>

										<?php
									} elseif (strpos(" " . $constants['const_FILE_EXTENSIONS_LIST_AUDIO'] . " "," " . strtolower($IMAGE_FILENAME_PARTS['extension']) . " ") !== false ) {
// 										audio-file
										?>
											<audio width="100%" controls autoplay>
												<source src="<?php echo $IMAGE_FILENAME; ?>" type="audio/<?php echo $IMAGE_FILENAME_PARTS['extension']; ?>">">
											</audio>
											<br>
											<a href="<?php echo $IMAGE_FILENAME; ?>" target="_blank">
												<?php echo L::view_images_download; ?>
											</a>
										<?php
									}
								?>
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

			<?php navigator($view_mode,$filter_images_per_page,$filter_rating,$offset,$imagecount,$GET_PARAMETER,$order_by,$order_dir,L::view_filter_order_by_filename,L::view_filter_order_by_creationdate); ?>
		</form>

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
