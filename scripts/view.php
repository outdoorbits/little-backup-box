<!doctype html>

<?php
/*
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
#######################################################################*/

	$WORKING_DIR=dirname(__FILE__);
	$config = parse_ini_file("config.cfg", false);
	$constants = parse_ini_file("constants.sh", false);
	$theme = $config["conf_THEME"];
	$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='" . $constants["const_MEDIA_DIR"] . '/' . $constants["const_BACKGROUND_IMAGES_DIR"] . "/" . $config["conf_BACKGROUND_IMAGE"] . "'";

	# read parameters
	extract ($_POST,EXTR_SKIP);
	extract ($_GET,EXTR_SKIP);

	include("sub-popup.php");
	include("sub-common.php");

	$NVMe_available	= nvme_available($WORKING_DIR, $constants);

	# symlink to media-folder
	if (! file_exists("./media")) {
		symlink("/media", "./media");
	}


	function urlencode_keep_slashes($href) {
		return(
			str_replace('//','/',
				str_replace('%2F','/',rawurlencode($href))
			)
		);
	}

	function navigator($view_mode,$imagecount,$filter_images_per_page,$select_offset,$filter_rating,$IMAGE_ID_PRE,$IMAGE_ID,$IMAGE_ID_POST,$IMAGE_ID_FIRST,$IMAGE_ID_LAST,$GET_PARAMETER,$order_by,$order_dir,$label_filename,$label_creationdate,$label_id) {
		if ($view_mode == "grid") {
			?>
			<div class="card" style="margin-top: 2em;display: inline-block;width: 100%">
				<div style="float:left;width: 50%;padding: 5px;">
					<a href="<?php echo $GET_PARAMETER . "&ID=" . $IMAGE_ID_FIRST; ?>">&lt;&lt;</a>
					&nbsp;&nbsp;&nbsp;&nbsp;
					<a href="<?php echo $GET_PARAMETER . "&ID=" . $IMAGE_ID_PRE; ?>">&lt;</a>
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

						$page	= intval(($select_offset + 2) / $filter_images_per_page) + 1;
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

						echo "<a href='" . $link_order . "'>" . $link_order_text . "</a>&nbsp;&nbsp;&nbsp;&nbsp;";

						$link_order_text	= $label_id;
						$link_order			= $GET_PARAMETER . "&order_by=ID&order_dir=";
						if ($order_by=="ID") {
							$link_order_text	.= $order_dir == "ASC"?"&darr;":"&uarr;"; #symbol reversed because of print-direction up to down
							$link_order_text	= "<b>" . $link_order_text . "</b>";
							$link_order			.= $order_dir == "ASC"?"DESC":"ASC";
						} else {
							$link_order			.= "ASC";
						}
						echo "<a href='" . $link_order . "'>" . $link_order_text . "</a>";
					?>
					&nbsp;&nbsp;&nbsp;&nbsp;
					<a href="<?php echo $GET_PARAMETER . "&ID=" . $IMAGE_ID_POST; ?>">&gt;</a>
					&nbsp;&nbsp;&nbsp;&nbsp;
					<a href="<?php echo $GET_PARAMETER . "&ID=" . $IMAGE_ID_LAST; ?>">&gt;&gt;</a>
				</div>

				<div style="display: flow-root;width: 100%">

					<div style="float:left;width: 50%;padding: 5px">
						<?php
							if ($filter_rating == 1) {
								echo "<button style=\"margin-top: 2em;\" type=\"submit\" name=\"delete_ratings_1\" class=\"danger\">" . L::view_ratings_1_delete_button . "</button>";
							}
						?>
					</div>

					<div style="float:right;width: 50%;padding: 5px;text-align: right;">
						<button style="margin-top: 2em;" type="submit" name="save_ratings"><?php echo L::view_ratings_save_button; ?></button>
					</div>

				</div>
			</div>
			<?php
		} else {
// 			$view_mode="single"
			?>
			<div class="card" style="margin-top: 2em;display: inline-block;width: 100%">
				<div style="float:left;width: 50%;padding: 5px;">
					<a href="<?php echo $GET_PARAMETER . '&view_mode=grid&ID=' . $IMAGE_ID; ?>">
						<?php echo L::view_images_back_to_grid; ?>
					</a>
				</div>

				<div style="float:left;width: 100%;padding: 5px;text-align: right;">
					<div style="float:left;width: 33%;text-align: left;padding: 0;">
						<?php
							if ($IMAGE_ID_PRE > 0) {
								echo "<button style=\"margin-top: 2em;\" type=\"submit\" name=\"ID\" value=\"" . $IMAGE_ID_PRE . "\">&lt; " . L::view_ratings_save_button . "</button>";
							} else {
								echo "&nbsp;";
							}
						?>
					</div>

					<div style="float:left;width: 34%;text-align: center;padding: 0;">
						<button style="margin-top: 2em;" type="submit" name="ID" value="<?php echo $IMAGE_ID; ?>"><?php echo L::view_ratings_save_button ?></button>
					</div>

					<div style="float:left;width: 33%;text-align: right;padding: 0;">
						<?php
							if ($IMAGE_ID_POST > 0) {
								echo "<button style=\"margin-top: 2em;\" type=\"submit\" name=\"ID\" value=\"" . $IMAGE_ID_POST . "\">" . L::view_ratings_save_button . " &gt;</button>";
							} else {
								echo "&nbsp;";
							}
						?>
					</div>
				</div>
			</div>
			<?php
		}
	}

	function rating_radio($IMAGE_ID, $IMAGE_RATING) {
		?>
			<div style="float:left;padding: 2px;" class="rating">
				<input id="rating_1_<?php echo $IMAGE_ID; ?>" type="radio" name="rating_<?php echo $IMAGE_ID; ?>" value="1" <?php echo $IMAGE_RATING>=1?"checked":""; ?>>
				<label for="rating_1_<?php echo $IMAGE_ID; ?>"></label>
				<input id="rating_2_<?php echo $IMAGE_ID; ?>" type="radio" name="rating_<?php echo $IMAGE_ID; ?>" value="2" <?php echo $IMAGE_RATING>=2?"checked":""; ?>>
				<label for="rating_2_<?php echo $IMAGE_ID; ?>"></label>
				<input id="rating_3_<?php echo $IMAGE_ID; ?>" type="radio" name="rating_<?php echo $IMAGE_ID; ?>" value="3" <?php echo $IMAGE_RATING>=3?"checked":""; ?>>
				<label for="rating_3_<?php echo $IMAGE_ID; ?>"></label>
				<input id="rating_4_<?php echo $IMAGE_ID; ?>" type="radio" name="rating_<?php echo $IMAGE_ID; ?>" value="4" <?php echo $IMAGE_RATING>=4?"checked":""; ?>>
				<label for="rating_4_<?php echo $IMAGE_ID; ?>"></label>
				<input id="rating_5_<?php echo $IMAGE_ID; ?>" type="radio" name="rating_<?php echo $IMAGE_ID; ?>" value="5" <?php echo $IMAGE_RATING>=5?"checked":""; ?>>
				<label for="rating_5_<?php echo $IMAGE_ID; ?>"></label>
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

	# setup
	$IMAGES_PER_PAGE_OPTIONS	= array ($constants['const_VIEW_GRID_COLUMNS']*5,$constants['const_VIEW_GRID_COLUMNS']*10,$constants['const_VIEW_GRID_COLUMNS']*20,$constants['const_VIEW_GRID_COLUMNS']*50);

	# standard values
	$filter_medium					= isset($filter_medium) ? $filter_medium : "-";
	$view_mode						= isset($view_mode) ? $view_mode :  "grid";
	$filter_images_per_page			= isset($filter_images_per_page) ? $filter_images_per_page :  $IMAGES_PER_PAGE_OPTIONS[1];
	$filter_directory				= isset($filter_directory) ? $filter_directory :  "";
	$filter_date					= isset($filter_date) ? $filter_date :  "all";
	$filter_rating					= isset($filter_rating) ? $filter_rating :  "all";
	$filter_file_type				= isset($filter_file_type) ? $filter_file_type :  "all";
	$filter_file_type_extension		= isset($filter_file_type_extension) ? $filter_file_type_extension :  "all";
	$filter_camera_model_name		= isset($filter_camera_model_name) ? $filter_camera_model_name :  "all";
	$filter_variable_field			= isset($filter_variable_field) ? $filter_variable_field :  "";
	$filter_variable_value			= isset($filter_variable_value) ? $filter_variable_value :  "";
	$ID								= isset($ID) ? $ID :  0;
	$IMAGE_ID						= isset($IMAGE_ID) ? $IMAGE_ID :  0;
	$IMAGE_ID_PRE					= isset($IMAGE_ID_PRE) ? $IMAGE_ID_PRE :  0;
	$IMAGE_ID_POST					= isset($IMAGE_ID_POST) ? $IMAGE_ID_POST :  0;
	$IMAGE_ID_FIRST					= isset($IMAGE_ID_FIRST) ? $IMAGE_ID_FIRST :  0;
	$IMAGE_ID_LAST					= isset($IMAGE_ID_LAST) ? $IMAGE_ID_LAST :  0;
	$imagecount						= isset($imagecount) ? $imagecount :  0;
	$order_by          				= isset($order_by) ? $order_by :  "Create_Date";
	$order_dir						= isset($order_dir) ? $order_dir :  "DESC";
	$select_offset					= isset($select_offset) ? $select_offset :  0;

	$FIELDS_BLOCKED_ARRAY	= array(
		"ID",
		"ExifTool_Version_Number"
	);

	## security-checks
	if (! in_array($filter_images_per_page,$IMAGES_PER_PAGE_OPTIONS)) {
		$filter_images_per_page	= $IMAGES_PER_PAGE_OPTIONS[0];
	}

	$ID			= intval($ID);

	if (! in_array($order_by,array('File_Name','Create_Date','ID'))) {$order_by='Create_Date';}

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
		if (strpos($mounted_devices,$constants['const_MEDIA_DIR'] . '/' . $constants['const_MOUNTPOINT_SUBPATH_LOCAL_USB_TARGET'])) {$filter_medium="target_usb";}
		elseif (strpos($mounted_devices,$constants['const_MEDIA_DIR'] . '/' . $constants['const_MOUNTPOINT_SUBPATH_LOCAL_USB_SOURCE'])) {$filter_medium="source_usb";}
		elseif (strpos($mounted_devices,$constants['const_MEDIA_DIR'] . '/' . $constants['const_MOUNTPOINT_SUBPATH_LOCAL_NVME_TARGET'])) {$filter_medium="target_nvme";}
		elseif (strpos($mounted_devices,$constants['const_MEDIA_DIR'] . '/' . $constants['const_MOUNTPOINT_SUBPATH_LOCAL_NVME_SOURCE'])) {$filter_medium="source_nvme";}
		else {$filter_medium="internal";}
	}

	#generate WHERE
	$WHERE['images']				= "";
	$WHERE['directories']			= "";
	$WHERE['dates']					= "";
	$WHERE['ratings']				= "";
	$WHERE['file_types']			= "";
	$WHERE['file_type_extensions']	= "";
	$WHERE['camera_model_name']		= "";
	$WHERE['variable']				= "";

	if ($filter_directory != "") {
		$filter_directory	= str_replace("+","=",$filter_directory);
		$filter_directory	= base64_decode($filter_directory);

		add_to_where("Directory='" . $filter_directory . "'",array('images','dates','ratings','file_types','file_type_extensions','camera_model_name','variable'));
	}

	if ($filter_date != "all") {add_to_where("substr(Create_Date,1,10) like '" . str_replace("-","_",$filter_date) . "'",array('images','directories','ratings','file_types','file_type_extensions','camera_model_name','variable'));}

	if (isset($delete_ratings_1)) {$filter_rating="all";} # after delete remove rating-filter

	if ($filter_rating != "all") {add_to_where("LbbRating = " . $filter_rating,array('images','dates','file_types','file_type_extensions','camera_model_name','directories'));}

	if ($filter_file_type != "all") {add_to_where("File_Type = '" . $filter_file_type . "'",array('images','dates','ratings','file_type_extensions','camera_model_name','directories'));}

	if ($filter_file_type_extension != "all") {add_to_where("File_Type_Extension = '" . $filter_file_type_extension . "'",array('images','dates','ratings','file_types','camera_model_name','directories'));}

	if ($filter_camera_model_name != "all") {add_to_where("Camera_Model_Name = '" . $filter_camera_model_name . "'",array('images','dates','ratings','file_types','file_type_extensions','directories'));}

	if ($filter_variable_value != "") {
		$filter_variable_value	= str_replace("+","=",$filter_variable_value);
		$filter_variable_value	= base64_decode($filter_variable_value);

		if (($filter_variable_field != "") and ($filter_variable_value != "")) {add_to_where($filter_variable_field . "='" . $filter_variable_value . "'",array('images','directories','dates','ratings','file_type_extensions','camera_model_name'));}
	}

	# generate select_limit
	$select_limit	=  $filter_images_per_page + 3;

	# define path of the database-file
	$STORAGE_PATH	= "";
	if ($filter_medium == "target_usb") {
		$STORAGE_PATH	= $constants['const_MEDIA_DIR'] . '/' . $constants['const_MOUNTPOINT_SUBPATH_LOCAL_USB_TARGET'];
	}
	elseif ($filter_medium == "source_usb") {
		$STORAGE_PATH	= $constants['const_MEDIA_DIR'] . '/' . $constants['const_MOUNTPOINT_SUBPATH_LOCAL_USB_SOURCE'];
	}
	elseif ($filter_medium == "target_nvme") {
		$STORAGE_PATH	= $constants['const_MEDIA_DIR'] . '/' . $constants['const_MOUNTPOINT_SUBPATH_LOCAL_NVME_TARGET'];
	}
	elseif ($filter_medium == "source_nvme") {
		$STORAGE_PATH	= $constants['const_MEDIA_DIR'] . '/' . $constants['const_MOUNTPOINT_SUBPATH_LOCAL_NVME_SOURCE'];
	}
	elseif ($filter_medium == "internal") {
		$STORAGE_PATH	= $constants['const_MEDIA_DIR'] . '/' . $constants['const_INTERNAL_BACKUP_DIR'];
	}

	# Database-query
	$DATABASE_CONNECTED=true;
	$DATABASE_FILE	= "";
	if ($STORAGE_PATH != "") {
		$DATABASE_FILE	= $STORAGE_PATH . '/' . $constants['const_IMAGE_DATABASE_FILENAME'];

		if (file_exists($DATABASE_FILE)) {
			# check database
			exec('sudo python3 ' . $WORKING_DIR . '/lib_view.py ' . $STORAGE_PATH);

			try {

				shell_exec("sudo chown www-data:www-data '" . $DATABASE_FILE ."'");
				$db = new SQLite3($DATABASE_FILE);
			} catch (Exception $e) {
				$DATABASE_CONNECTED=false;
			}
		} else {
			$DATABASE_CONNECTED=false;
		}

		if ($DATABASE_CONNECTED) {
			# save ratings
			foreach($RATINGS_ARRAY as $key=>$val) {
				$key	= intval($key);
				$val	= intval($val);

				if ($config['conf_VIEW_WRITE_RATING_EXIF'] == true) {
					# get database-values before update
					$statement			= $db->prepare("SELECT Directory, File_Name, Rating FROM EXIF_DATA where ID=" . $key . ";");
					$RATING_IMAGES		= $statement->execute();
					$RATING_IMAGE		= $RATING_IMAGES->fetchArray(SQLITE3_ASSOC);

					# define update-command
					$SQL_UPDATE	= "update EXIF_DATA set LbbRating=". $val . ", Rating=". $val . " where ID=" . $key . ";";
				} else {
					# define update-command
					$SQL_UPDATE	= "update EXIF_DATA set LbbRating=". $val . " where ID=" . $key . ";";
				}

				$db->exec($SQL_UPDATE);

				if (($config['conf_VIEW_WRITE_RATING_EXIF'] == true) and ((int)$RATING_IMAGE['Rating'] !== (int)$val)) {
					#update exif-data of original file
					shell_exec ("sudo exiftool -overwrite_original -Rating=" . (int)$val . " '".$STORAGE_PATH . '/' . $RATING_IMAGE['Directory']. '/' .$RATING_IMAGE['File_Name'] . "'");
				}
			}

			# delete media-files
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

			# database-queries
			$statement				= $db->prepare("SELECT ID FROM EXIF_DATA " . $WHERE['images'] . " order by " . $order_by . " " . $order_dir . ";");
			$IMAGES_ALL			= $statement->execute();

			#get first and last ID, define offset
			$n = 0;
			while ($FETCH_IMAGE = $IMAGES_ALL->fetchArray(SQLITE3_ASSOC)) {
				$n += 1;
				if ($n == 1) {
					$IMAGE_ID_FIRST = $FETCH_IMAGE['ID'];
				}
				if ($FETCH_IMAGE['ID'] == $ID) {
					$select_offset = intdiv($n,$filter_images_per_page) * $filter_images_per_page - 2;
				}
				$IMAGE_ID_LAST = $FETCH_IMAGE['ID'];
			}

			$imagecount = $n;

			$statement				= $db->prepare("SELECT * FROM EXIF_DATA " . $WHERE['images'] . " order by " . $order_by . " " . $order_dir . " limit " . $select_limit . " offset " . $select_offset . ";");
			$IMAGES_PAGE			= $statement->execute();

			$statement				= $db->prepare("SELECT Directory, count (ID) as FILECOUNT FROM EXIF_DATA " . $WHERE['directories'] . " group by Directory order by Directory;");
			$DIRECTORIES			= $statement->execute();

			$statement				= $db->prepare("SELECT LbbRating, count (ID) as FILECOUNT FROM EXIF_DATA " . $WHERE['ratings'] . " group by LbbRating order by LbbRating;");
			$RATINGS				= $statement->execute();

			$statement				= $db->prepare("SELECT substr(replace(Create_Date,':','-'),1,10) as Create_Day, count (ID) as FILECOUNT FROM EXIF_DATA " . $WHERE['dates'] . " group by Create_Day order by Create_Day desc;");
			$DATES					= $statement->execute();

			$statement				= $db->prepare("SELECT File_Type, count (ID) as FILECOUNT FROM EXIF_DATA " . $WHERE['file_types'] . " group by File_Type order by File_Type;");
			$FILE_TYPES				= $statement->execute();

			$statement				= $db->prepare("SELECT File_Type_Extension, count (ID) as FILECOUNT FROM EXIF_DATA " . $WHERE['file_type_extensions'] . " group by File_Type_Extension order by File_Type_Extension;");
			$FILE_TYPE_EXTENSIONS	= $statement->execute();

			$statement				= $db->prepare("SELECT Camera_Model_Name, count (ID) as FILECOUNT FROM EXIF_DATA " . $WHERE['camera_model_name'] . " group by Camera_Model_Name order by Camera_Model_Name;");
			$CAMERA_MODEL_NAMES		= $statement->execute();

			$statement				= $db->prepare("PRAGMA table_info(EXIF_DATA);");
			$VAR_FIELDS				= $statement->execute();

			if ($filter_variable_field != "") {
				$statement			= $db->prepare("SELECT " . $filter_variable_field . " as var_filter_value, count (ID) as FILECOUNT FROM EXIF_DATA " . $WHERE['variable'] . " group by " . $filter_variable_field . " order by " . $filter_variable_field . ";");
				$VAR_VALUES	= $statement->execute();
			}

						$IMAGES_ARRAY_ALL	= array();
			while ($FETCH_IMAGE = $IMAGES_PAGE->fetchArray(SQLITE3_ASSOC)) {
				$IMAGES_ARRAY_ALL[]	= $FETCH_IMAGE;
			}

			#manage images
			$IMAGES_ARRAY = array();
			if ($view_mode == "grid") {
				$n = 0;
				foreach ($IMAGES_ARRAY_ALL as $IMAGE) {
					$n += 1;
					if (($n == 1) and ($select_offset > 0)) {
						$IMAGE_ID_PRE = $IMAGE['ID'];
					} elseif ($n == ($select_offset <= 0 ? 1 : 3)) {
						$IMAGE_ID = $IMAGE['ID'];
					} elseif ($n == $filter_images_per_page + ($select_offset <= 0 ? 1 : 3)) {
						$IMAGE_ID_POST = $IMAGE['ID'];
					}

					if ((($n > 2) or ($select_offset <= 0)) and ($n <= $filter_images_per_page + ($select_offset <= 0 ? 0 : 2))) {
						$IMAGES_ARRAY[] = $IMAGE;
					}
				}
			} elseif ($view_mode == "single") {
				$IMAGE_ID	= $ID;
				$ID_OLD = 0;

				$n = 0;
				foreach ($IMAGES_ARRAY_ALL as $IMAGE) {
					$n += 1;
					if ($IMAGE['ID'] == $IMAGE_ID) {
						$IMAGE_ID_PRE = $ID_OLD;
						$IMAGES_ARRAY[] = $IMAGE;
					} elseif ($ID_OLD == $IMAGE_ID) {
						$IMAGE_ID_POST = $IMAGE['ID'];
					}
					$ID_OLD = $IMAGE['ID'];
				}
			}
		}
	}

	# define GET_PARAMETER
	$GET_PARAMETER	= "?filter_medium=$filter_medium";
	$GET_PARAMETER	.= "&view_mode=$view_mode";
	$GET_PARAMETER	.= "&order_by=$order_by";
	$GET_PARAMETER	.= "&order_dir=$order_dir";
	$GET_PARAMETER	.= "&filter_images_per_page=$filter_images_per_page";
	if ($filter_directory != "") {$GET_PARAMETER	.= "&filter_directory=" . str_replace("=","+",base64_encode($filter_directory));}
	if ($filter_date != "") {$GET_PARAMETER	.= "&filter_date=" . $filter_date;}
	if ($filter_rating != "") {$GET_PARAMETER	.= "&filter_rating=$filter_rating";}
	if ($filter_file_type_extension != "") {$GET_PARAMETER	.= "&filter_file_type_extension=$filter_file_type_extension";}
	if ($filter_camera_model_name != "") {$GET_PARAMETER	.= "&filter_camera_model_name=$filter_camera_model_name";}
	if ($filter_variable_field != "") {$GET_PARAMETER	.= "&filter_variable_field=$filter_variable_field";}
	if ($filter_variable_value != "") {$GET_PARAMETER	.= "&filter_variable_value=" . str_replace("=","+",base64_encode($filter_variable_value));}

	# define hidden HIDDEN_INPUTS
	$HIDDEN_INPUTS	="<input type=\"hidden\" name=\"filter_medium\" value=\"" . $filter_medium . "\">";
	$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"view_mode\" value=\"" . $view_mode . "\">";
	$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"order_by\" value=\"" . $order_by . "\">";
	$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"order_dir\" value=\"" . $order_dir . "\">";
	$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_images_per_page\" value=\"" . $filter_images_per_page . "\">";
	if ($filter_directory != "") {$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_directory\" value=\"" . str_replace("=","+",base64_encode($filter_directory)) . "\">";}
	if ($filter_date != "") {$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_date\" value=\"" . $filter_date . "\">";}
	if ($filter_rating != "") {$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_rating\" value=\"" . $filter_rating . "\">";}
	if ($filter_file_type_extension != "") {$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_file_type_extension\" value=\"" . $filter_file_type_extension . "\">";}
	if ($filter_camera_model_name != "") {$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_camera_model_name\" value=\"" . $filter_camera_model_name . "\">";}
	if ($filter_variable_field != "") {$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_variable_field\" value=\"" . $filter_variable_field . "\">";}
	if ($filter_variable_value != "") {$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_variable_value\" value=\"" . str_replace("=","+",base64_encode($filter_variable_value)) . "\">";}
?>

<html lang="<?php echo $config["conf_LANGUAGE"]; ?>" data-theme="<?php echo $theme; ?>">

<head>
	<?php include "${WORKING_DIR}/sub-standards-header-loader.php"; ?>
	<link rel="stylesheet" href="css/mglass.css">
	<script src="js/mglass.js"></script>
</head>

<body <?php echo $background; ?>>
	<?php include "${WORKING_DIR}/sub-standards-body-loader.php"; ?>
	<?php include "${WORKING_DIR}/sub-menu.php"; ?>

	<h1 class="text-center" style="margin-bottom: 1em; letter-spacing: 3px;"><?php echo L::view_view; ?></h1>

<!-- FILTER -->
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
								<option value="target_usb" <?php echo ($filter_medium == "target_usb"?" selected":""); ?>><?php echo L::view_filter_medium_target_usb; ?></option>";
								<option value="source_usb" <?php echo ($filter_medium == "source_usb"?" selected":""); ?>><?php echo L::view_filter_medium_source_usb; ?></option>";
								<?php
									if ($NVMe_available) {
										?>
											<option value="target_nvme" <?php echo ($filter_medium == "target_nvme"?" selected":""); ?>><?php echo L::view_filter_medium_target_nvme; ?></option>";
											<option value="source_nvme" <?php echo ($filter_medium == "source_nvme"?" selected":""); ?>><?php echo L::view_filter_medium_source_nvme; ?></option>";
										<?php
									}
								?>
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
							<label for="filter_file_type"><?php echo L::view_filter_file_type; ?></label><br>
								<select name="filter_file_type" id="filter_file_type" onchange="this.form.submit()">
									<option value="all" <?php echo ($filter_file_type == ""?" selected":""); ?>>-</option>
									<?php
										while ($FILE_TYPE = $FILE_TYPES->fetchArray(SQLITE3_ASSOC)) {
											echo "<option value=\"" . $FILE_TYPE['File_Type'] . "\" " . ($filter_file_type == $FILE_TYPE['File_Type']?" selected":"") . ">" . $FILE_TYPE['File_Type'] . " (" . $FILE_TYPE['FILECOUNT'] . ")</option>";
										}
									?>
								</select>
						</div>
					<?php } ?>

					<?php if ($DATABASE_CONNECTED) { ?>
						<div style="float:left;padding: 5px;">
							<label for="filter_file_type_extension"><?php echo L::view_filter_file_type_extension; ?></label><br>
								<select name="filter_file_type_extension" id="filter_file_type_extension" onchange="this.form.submit()">
									<option value="all" <?php echo ($filter_file_type_extension == ""?" selected":""); ?>>-</option>
									<?php
										while ($FILE_TYPE_EXTENSION = $FILE_TYPE_EXTENSIONS->fetchArray(SQLITE3_ASSOC)) {
											echo "<option value=\"" . $FILE_TYPE_EXTENSION['File_Type_Extension'] . "\" " . ($filter_file_type_extension == $FILE_TYPE_EXTENSION['File_Type_Extension']?" selected":"") . ">" . $FILE_TYPE_EXTENSION['File_Type_Extension'] . " (" . $FILE_TYPE_EXTENSION['FILECOUNT'] . ")</option>";
										}
									?>
								</select>
						</div>
					<?php } ?>

					<?php if ($DATABASE_CONNECTED) { ?>
						<div style="float:right;padding: 5px;">
							<label for="filter_rating"><?php echo L::view_filter_camera_model_name; ?></label><br>
								<select name="filter_camera_model_name" id="filter_camera_model_name" onchange="this.form.submit()">
									<?php
										echo "<option value=\"all\">-</option>";
										while ($CAMERA_MODEL_NAME = $CAMERA_MODEL_NAMES->fetchArray(SQLITE3_ASSOC)) {
											echo "<option value=\"" . $CAMERA_MODEL_NAME['Camera_Model_Name'] . "\" " . ($filter_camera_model_name == $CAMERA_MODEL_NAME['Camera_Model_Name']?" selected":"") . ">" . $CAMERA_MODEL_NAME['Camera_Model_Name'] . " (" . $CAMERA_MODEL_NAME['FILECOUNT'] . ")</option>";
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

<!-- IMAGES -->
	<?php if ($DATABASE_CONNECTED) { ?>
	<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">

		<?php
			echo $HIDDEN_INPUTS;
			navigator($view_mode,$imagecount,$filter_images_per_page,$select_offset,$filter_rating,$IMAGE_ID_PRE,$IMAGE_ID,$IMAGE_ID_POST,$IMAGE_ID_FIRST,$IMAGE_ID_LAST,$GET_PARAMETER,$order_by,$order_dir,L::view_filter_order_by_filename,L::view_filter_order_by_creationdate,L::view_filter_order_by_id);
		?>

		<div class="card" style="margin-top: 2em;display: inline-block">

			<?php

				if ($imagecount >= 1) {
					if ($view_mode == "grid") {
// GRID
						?>
						<?php
						$i	= 0;
						$LAST_DATE	= "";
						foreach ($IMAGES_ARRAY as $IMAGE) {

							$i	+= 1;
							$interpretedDate	= date_create($IMAGE['Create_Date']);
							if ($interpretedDate == false) {
								$Create_Date	= str_replace('-','_',$IMAGE['Create_Date']);
								$Create_Date	= str_replace(':','_',$Create_Date);
								$Create_Date	= str_replace(' ','_',$Create_Date);

								$DateExplode		= explode('_', $Create_Date);

								$interpretedDate	= date_create($DateExplode[0] . '-' . $DateExplode[1] . '-' . $DateExplode[2] . ' ' . $DateExplode[3] . ':' .  $DateExplode[4] . ':' . $DateExplode[5]);
							}
							$IMAGE_DATE	= date_format($interpretedDate,L::view_date_format);

							$Directory				= $STORAGE_PATH . $IMAGE['Directory'];
							$IMAGE_ID				= $IMAGE['ID'];
							$IMAGE_FILENAME_TIMS	= $Directory . '/tims/' . $IMAGE['File_Name'] . '.JPG';
							?>


								<div style="width: <?php echo 100/$constants['const_VIEW_GRID_COLUMNS']-1; ?>%;" class="floating">
									<div style="width: 100%" title="<?php echo $IMAGE['File_Name']; ?>">
										<?php
											if (($order_by == 'Create_Date') and ($LAST_DATE !== $IMAGE_DATE)) {
												echo '<b>' . $IMAGE_DATE . '</b>';
												$LAST_DATE	= $IMAGE_DATE;
											}
										?>
										<a href="<?php echo $GET_PARAMETER . '&view_mode=single&ID=' . ($IMAGE['ID']); ?>">
											<img style="max-width: 100%; border-radius: 5px;" class="rating<?php echo $IMAGE['LbbRating']; ?>" src="<?php echo urlencode_keep_slashes($IMAGE_FILENAME_TIMS); ?>">
										</a>
									</div>

									<div style="width: 100%; display: inline-block; padding-left: 2px;padding-right: 2px; padding-top: 2px; padding-bottom: 6px;">

										<?php echo rating_radio($IMAGE['ID'],$IMAGE['LbbRating']); ?>

										<div style="float:right;padding: 2px;font-size:0.8em;" class="hidden-mobile">
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
// SINGLE
						$IMAGE = $IMAGES_ARRAY[0];
						$Directory				= $STORAGE_PATH . $IMAGE['Directory'];
						$IMAGE_ID				= $IMAGE['ID'];
						$IMAGE_FILENAME			= $Directory . '/' . $IMAGE['File_Name'];
						$IMAGE_FILENAME_TIMS	= $Directory . '/tims/' . $IMAGE['File_Name'] . '.JPG';
						$IMAGE_TYPE				= $IMAGE['File_Type'] !== ""?strtolower($IMAGE['File_Type']):strtolower($IMAGE_FILENAME_PARTS['extension']);
						$IMAGE_FILENAME_PARTS	= pathinfo($IMAGE_FILENAME);
						?>

						<div style="float:left;width: 100%;padding: 5px;">
							<?php
								if (strpos(";" . $constants['const_FILE_EXTENSIONS_LIST_WEB_IMAGES'] . ";" . $constants['const_FILE_EXTENSIONS_LIST_HEIC'] . ";" . $constants['const_FILE_EXTENSIONS_LIST_RAW'] . ";" . $constants['const_FILE_EXTENSIONS_LIST_TIF'] . ";",";" . strtolower($IMAGE_FILENAME_PARTS['extension']) . ";") !== false ) {
// 										image-file

									if (strpos(";" . $constants['const_FILE_EXTENSIONS_LIST_WEB_IMAGES'] . ";",";" . strtolower($IMAGE_FILENAME_PARTS['extension']) . ";") !== false ) {
										$FILENAME_DISPLAY	= $IMAGE_FILENAME;
									} else {
										$FILENAME_DISPLAY	= $IMAGE_FILENAME_TIMS;
									}
							?>
									<div style="width: 100%;text-align:center;" title="<?php echo $IMAGE['File_Name']; ?>">

										<div class="img-magnifier-container">
											<img id="fullsizeimage" onClick="magnify('fullsizeimage', <?php echo $constants['const_VIEW_MAGNIFYING_GLASS_ZOOM']; ?>)" style="max-width: 100%;border-radius: 5px;" class="rating<?php echo $IMAGE['LbbRating']; ?>" src="<?php echo urlencode_keep_slashes($FILENAME_DISPLAY); ?>">
										</div>

									</div>

									<?php
										if (strpos(";" . $constants['const_FILE_EXTENSIONS_LIST_RAW'] . ";" . $constants['const_FILE_EXTENSIONS_LIST_TIF'] . ";",";" . strtolower($IMAGE_FILENAME_PARTS['extension']) . ";") !== false ) {
// 											RAW-image or TIF image
											echo "<div style=\"width: 100%\">";
												echo "<p style=\"text-align: center;font-weight: bold;\">" . L::view_images_preview_low_resolution_image . "</p>";
											echo "</div>";
										}
									?>

									<div style="width=100%;padding: 30px;font-size:0.8em;">
										<div style="float:left;width: 33%;text-align: left;padding: 0;padding-top: 0.5em;">
											<?php echo rating_radio($IMAGE['ID'],$IMAGE['LbbRating']); ?>
										</div>

										<div style="float:left;width: 34%;text-align: center;padding: 0;">
											<a href="<?php echo urlencode_keep_slashes($IMAGE_FILENAME); ?>" target="_blank">
												<?php echo L::view_images_download; ?>
											</a>
										</div>

										<div style="float:left;width: 33%;text-align: right;padding: 0;">
											<?php echo L::view_images_magnifying_glass; ?>
										</div>
									</div>

							<?php
								} elseif (strpos(";" . $constants['const_FILE_EXTENSIONS_LIST_VIDEO'] . ";",";" . strtolower($IMAGE_FILENAME_PARTS['extension']) . ";") !== false ) {
// 										video-file
									$IMAGE_FILENAME_PREVIEW	= $IMAGE_FILENAME;
									$LOW_RES	= false;
									if (isset($IMAGE['Compressor_Name']) and (strpos($IMAGE['Compressor_Name'],"GoPro H.265 encoder") !== false)) {
										#GoPro: GoPro H.265 encoder not supported by most browsers
										$IMAGE_FILENAME_PREVIEW_CANDIDATE	= $IMAGE_FILENAME_PARTS['dirname'] . DIRECTORY_SEPARATOR . substr_replace($IMAGE_FILENAME_PARTS['filename'],'L',1,1) . ".LRV";
										if (file_exists($IMAGE_FILENAME_PREVIEW_CANDIDATE)) {
											$IMAGE_FILENAME_PREVIEW	= $IMAGE_FILENAME_PREVIEW_CANDIDATE;
											$LOW_RES	= true;
										}
									}
									?>
										<div style="width: 100%;text-align:center;" title="<?php echo $IMAGE['File_Name']; ?>">
											<video width="100%" class="rating<?php echo $IMAGE['LbbRating']; ?>" controls autoplay>
												<source src="<?php echo urlencode_keep_slashes($IMAGE_FILENAME_PREVIEW); ?>" type="video/<?php echo $IMAGE_TYPE; ?>"></source>
											</video>

											<?php
											if ($LOW_RES) {
	// 											RAW-image
												echo "<p style=\"text-align: center;font-weight: bold;\">" . L::view_images_preview_low_resolution_video . "</p>";
											}
											?>
										</div>

										<div padding: 5px;font-size:0.8em;">
											<?php echo rating_radio($IMAGE['ID'],$IMAGE['LbbRating']); ?>

											<a href="<?php echo urlencode_keep_slashes($IMAGE_FILENAME); ?>" target="_blank">
												<?php echo L::view_images_download; ?>
											</a>
										</div>

									<?php
								} elseif (strpos(";" . $constants['const_FILE_EXTENSIONS_LIST_AUDIO'] . ";",";" . strtolower($IMAGE_FILENAME_PARTS['extension']) . ";") !== false ) {
// 										audio-file
									?>
										<div style="width: 100%;text-align:center;" title="<?php echo $IMAGE['File_Name']; ?>">
											<audio width="100%" class="rating<?php echo $IMAGE['LbbRating']; ?>" controls autoplay>
												<source src="<?php echo urlencode_keep_slashes($IMAGE_FILENAME); ?>" type="audio/<?php echo $IMAGE_FILENAME_PARTS['extension']; ?>">">
											</audio>
										</div>

										<div padding: 5px;font-size:0.8em;">
											<?php echo rating_radio($IMAGE['ID'],$IMAGE['LbbRating']); ?>

											<a href="<?php echo urlencode_keep_slashes($IMAGE_FILENAME); ?>" target="_blank">
												<?php echo L::view_images_download; ?>
											</a>
										</div>
									<?php
								}
							?>
						</div>

						<div style="float:left;width: 100%;padding: 5px;">
							<table style="table-layout: fixed;max-width: 100%;">
							<?php
								foreach ($IMAGE as $FIELD => $VALUE) {
									if (($VALUE != "") and (! in_array($FIELD,$FIELDS_BLOCKED_ARRAY))) {
										$FIELD	= str_replace('_',' ',$FIELD);
										echo "<tr><td style='word-break: break-all;width: 40%;vertical-align: top;'><u>$FIELD:</u></td><td style='word-break: break-all;width: 60%;vertical-align: top;'><b>$VALUE</b></td></tr>";
									}
								}
							?>
							</table>
						</div>

						<?php

					}
				} else {
// 					imagecount=0
					?>
						<?php echo L::view_images_empty; ?>
					<?php
				}
			?>

		</div>

		<?php navigator($view_mode,$imagecount,$filter_images_per_page,$select_offset,$filter_rating,$IMAGE_ID_PRE,$IMAGE_ID,$IMAGE_ID_POST,$IMAGE_ID_FIRST,$IMAGE_ID_LAST,$GET_PARAMETER,$order_by,$order_dir,L::view_filter_order_by_filename,L::view_filter_order_by_creationdate,L::view_filter_order_by_id); ?>

	</form>

	<?php } else { ?>
		<div class="card" style="margin-top: 2em;">
			<?php echo L::view_filter_no_medium; ?>
		</div>
	<?php } ?>

	<div class="card" style="margin-top: 2em;">
		<?php echo L::view_footer_footer; ?>
	</div>

	<?php
		include "sub-footer.php";
	?>

</body>

</html>
