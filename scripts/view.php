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

	include 'sub-socialmedia.php';
	$social_services			= get_social_services();
	$social_services_configured	= get_social_services_configured();

	include("sub-i18n-loader.php");

	# read parameters
	extract ($_POST, EXTR_SKIP);
	extract ($_GET, EXTR_SKIP);

	include("sub-popup.php");
	include("sub-common.php");

	$NVMe_available	= get_nvme_available($WORKING_DIR, $constants, false);

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

	function navigator(
		$view_mode,
		$imagecount,
		$gridcolumns,
		$filter_images_per_page,
		$select_offset,
		$filter_rating,
		$IMAGE_ID_PRE,
		$IMAGE_ID,
		$IMAGE_ID_POST,
		$IMAGE_ID_FIRST,
		$IMAGE_ID_LAST,
		$GET_PARAMETER,
		$order_by,
		$order_dir,
		$label_filename,
		$label_creationdate,
		$label_id,
		$slideshow_publish,
		$slideshow_timer
	) {
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
							if ($filter_rating == -1) {
								echo "<button style=\"margin-top: 2em;\" type=\"submit\" name=\"delete_ratings_reject\" class=\"danger\">" . L::view_ratings_1_delete_button . "</button>";
							}

							if ($gridcolumns > 1) {
								echo ("<a href=\"{$GET_PARAMETER}&ID={$IMAGE_ID}&gridcolumns=1\" title=\"" . L::view_grid_single . "\"><svg width=\"2em\" height=\"2em\" class=\"flex-shrink-0\"><use href=\"#icon-columns-one\"></use></svg></a>");
							} else {
								echo ("<a href=\"{$GET_PARAMETER}&ID={$IMAGE_ID}&gridcolumns=0\" title=\"" . L::view_grid_multi . "\"><svg width=\"2em\" height=\"2em\" class=\"flex-shrink-0\"><use href=\"#icon-columns-multi\"></use></svg></a>");
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
			<input type="hidden" name="slideshow_next_link" id="slideshow_next_link" value="<?php echo ($IMAGE_ID_POST ==  0 ? $GET_PARAMETER . "&ID=" . $IMAGE_ID_FIRST : $GET_PARAMETER . "&ID=" . $IMAGE_ID_POST); ?>">

			<div class="card" style="margin-top: 2em;display: inline-block;width: 100%">
				<div style="float:left;width: 50%;padding: 5px;">
					<a href="<?php echo $GET_PARAMETER . '&view_mode=grid&ID=' . $IMAGE_ID; ?>">
						<?php echo L::view_images_back_to_grid; ?>
					</a>
				</div>

				<?php
				if ($slideshow_publish) {
					?>
						<div style="float:right;width: 50%;padding: 5px;">
							<?php echo L::view_slideshow_header; ?>
							<select style="margin-top: 0;" onchange="slideshow_init();" id="slideshow_timer">
								<option value="-" <?php echo ($slideshow_timer=='-'?'selected':''); ?>>-</option>
								<?php
									$slideshow_options	= array('1', '2', '3', '4', '5');
									foreach($slideshow_options as $slideshow_option) {
									echo '<option value="'.$slideshow_option.'" '.($slideshow_timer==$slideshow_option?'selected':'').'>'.$slideshow_option.' '.L::seconds_short.'</option>';
									}
								?>
							</select>
						</div>
					<?php
				}

				?>

				<div style="float:left;width: 100%;padding: 5px;text-align: right;">
					<div style="float:left;width: 33%;text-align: left;padding: 0;">
<!-- 						previous -->
						<?php
							if ($IMAGE_ID_PRE > 0) {
								echo "<button style=\"margin-top: 2em;\" type=\"submit\" name=\"ID\" value=\"" . $IMAGE_ID_PRE . "\">&lt; " . L::view_ratings_save_button_back . "</button>";
							} else {
								echo "&nbsp;";
							}
						?>
					</div>

<!-- 					this -->
					<div style="float:left;width: 34%;text-align: center;padding: 0;">
						<button style="margin-top: 2em;" type="submit" name="ID" value="<?php echo $IMAGE_ID; ?>"><?php echo L::view_ratings_save_button ?></button>
					</div>

<!-- 					next -->
					<div style="float:left;width: 33%;text-align: right;padding: 0;">
						<?php
							if ($IMAGE_ID_POST > 0) {
								echo "<button style=\"margin-top: 2em;\" type=\"submit\" name=\"ID\" value=\"" . $IMAGE_ID_POST . "\">" . L::view_ratings_save_button_next . " &gt;</button>";
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
		$RATING	= <<<EOL
			<div class="meta">
				<div class="meta-inner d-flex align-items-center gap-2">
					<div class="rating d-flex align-items-center">
		EOL;

					foreach (range(5, -1, -1) as $i) {
						$checked	= ($IMAGE_RATING == $i) ? ' checked' : '';
						$default	= ($IMAGE_RATING == $i) ? '-default' : '';
						$id			= "rating_{$i}_{$IMAGE_ID}";
						$RATING .= "
							<input id='$id' type='radio' name='rating_{$IMAGE_ID}' value='{$i}'{$checked}>
							<label for='{$id}' aria-label='{$i}' title='{$i}'>
								<svg class='ico' viewBox='0 0 24 24' focusable='false'>";
									if ($i == -1) {
										$RATING .= "<svg class='ico' viewBox='0 0 24 24'><use href='#icon-reject'></use></svg>";
									} elseif ($i == 0) {
										$RATING .= "<svg class='ico' viewBox='0 0 24 24'><use href='#icon-star-outline{$default}'></use></svg>";
									} else {
										$RATING .= "<svg class='ico' viewBox='0 0 24 24'><use href='#icon-star-filled{$default}'></use></svg>";
									}
						$RATING .= "
								</svg>
							</label>";
					}

		$RATING	.= <<<EOL
					</div>
				</div>
			</div>
		EOL;
		return($RATING);
	}

	function social_pannel($IMAGE_ID, $PUBLISH, $PUBLISHED) {
		global $social_services;
		global $social_services_configured;

		$PUBLISH	= intval($PUBLISH);
		$PUBLISHED	= intval($PUBLISHED);

		echo '<div class="meta meta-social d-flex flex-wrap align-items-center ms-auto gap-1">';
		foreach($social_services as $bit => $ServiceName) {
			if (! in_array($ServiceName, $social_services_configured)) {continue;}
			?>
			<div class="meta-inner d-flex align-items-center gap-0">
				<input id="social_publish_<?php echo $IMAGE_ID . '_' . $bit . '_hidden'; ?>" name="social_publish_<?php echo $IMAGE_ID . '_' . $bit; ?>" type="hidden" value=0>
				<input id="social_publish_<?php echo $IMAGE_ID . '_' . $bit; ?>" name="social_publish_<?php echo $IMAGE_ID . '_' . $bit; ?>" type="checkbox" value=1 class="form-check-input m-0" <?php echo ($PUBLISH & (1 << $bit))?"checked":""; ?> title="<?php echo L::view_social_publish_on . ' ' . $ServiceName; ?>">

				<label for="social_publish_<?php echo $IMAGE_ID . '_' . $bit; ?>" class="d-inline-flex align-items-center m-0">
					<?php
					$iconVARIANT	= 'nothing';
					$iconTITLE		= L::view_social_mark_for_publish_on . ' ' . $ServiceName;
					if ($PUBLISH & (1 << $bit) and $PUBLISHED & (1 << $bit)) {
						$iconVARIANT	= 'combined';
						$iconTITLE		= L::view_social_publish_on  . ' &amp; ' . L::view_social_published_on . ' ' . $ServiceName;
					} elseif ($PUBLISH & (1 << $bit)) {
						$iconVARIANT	= 'publish';
						$iconTITLE		= L::view_social_publish_on . ' ' . $ServiceName;
					} elseif ($PUBLISHED & (1 << $bit)) {
						$iconVARIANT	= 'published';
						$iconTITLE		= L::view_social_published_on . ' ' . $ServiceName;
					}

					?>
					<svg width="20" height="20" class="flex-shrink-0" aria-labelledby="published-<?php echo $bit; ?>">
						<title id="<?php echo $ServiceName; ?>-<?php echo $iconVARIANT; ?>"><?php echo $iconTITLE; ?></title>
						<use href="#icon-<?php echo $ServiceName; ?>-<?php echo $iconVARIANT; ?>"></use>
					</svg>
					<?php

					?>
				</label>
			</div>
			<?php
		}
		echo '</div>';
	}

	function media_functions($mediatype, $IMAGE, $IMAGE_FILENAME) {
		# mediatype one of image, video, audio

		$IMAGE_ID						= $IMAGE['ID'];
		$Image_Lbb_Rating				= $IMAGE['LbbRating'];
		$IMAGE_Comment					= $IMAGE['Comment'];

		$RATING_RADIO					= rating_radio($IMAGE_ID, $Image_Lbb_Rating);
		$URL							= urlencode_keep_slashes($IMAGE_FILENAME);

		$view_images_download			= L::view_images_download;
		$view_images_magnifying_glass	= L::view_images_magnifying_glass;
		$view_images_comment			= L::view_images_comment;

		// 1 row
		echo <<<EOL
			<div style="width: 100%; font-size: 0.8em;">

				<!--Rating-->
				<div style="float: left; width: 33%; text-align: left; padding: 0; padding-top: 0.5em;">
					$RATING_RADIO
				</div>

				<!--Download-Link-->
				<div style="float:left;width: 30%;text-align: center;padding: 0;">
					<a href="$URL" target="_blank">
						$view_images_download
					</a>
				</div>
		EOL;

		if ($mediatype == 'image') {
			echo <<<EOL
				<!--magnifying_glass-->
				<div style="float:left;width: 30%;text-align: center;padding: 0;">
					$view_images_magnifying_glass
				</div>
			EOL;
		}

		?>
				<!-- maximise -->
				<div style="float:right;text-align: right;padding: 0;">
					<span class="slideshow-button" onclick="slideshow_display();">
						<svg width="1em" height="1em" class="flex-shrink-0"><use href="#icon-maximise"></use></svg>
					</span>
				</div>
			</div>

			<!-- Comment, 2. row -->
			<div style="clear: both; width: 100%; resize: vertical;">
				<textarea id="comment" name="comment_<?php echo $IMAGE_ID; ?>" title="<?php echo $view_images_comment; ?>" rows="4" style="width: 100%;"><?php echo $IMAGE_Comment; ?></textarea>
			</div>

			<!-- functions, 3. row -->
			<div class="d-flex align-items-center gap-2 w-100 mt-1">

				<div style="text-align: left;padding: 0;" class="me-auto"><span id="charCount">0</span> <?php echo L::view_characters; ?></div>

					<script>
						const textarea = document.getElementById("comment");
						const counter  = document.getElementById("charCount");

						function updateCount() {
						counter.textContent = textarea.value.length;
						}

						updateCount();

						textarea.addEventListener("input", updateCount);
					</script>

					<?php echo social_pannel($IMAGE['ID'], $IMAGE['social_publish'], $IMAGE['social_published']); ?>

				</div>
			</div>
		<?php
	}

	function add_to_where($new_where, $not_where_restricted, $WHERE_VARIANTS) {
		global $WHERE;

		foreach ($WHERE_VARIANTS as $WHERE_VARIANT) {
			if ($WHERE_VARIANT != $not_where_restricted) {
				if ($WHERE[$WHERE_VARIANT] != "") {
					$WHERE[$WHERE_VARIANT]	.= " and ";
				} else {
					$WHERE[$WHERE_VARIANT]	= "where ";
				}

				$WHERE[$WHERE_VARIANT]	.= "($new_where)";
			}
		}
	}

	function update_row(SQLite3 $db, string $table, int $id, array $data): int {
		// 1) allow collums from whitelist only
		$allowed	=['LbbRating', 'Rating', 'Comment', 'social_publish'];
		$data		= array_intersect_key($data, array_flip($allowed));

		if (empty($data)) {
			return 0; // nichts zu ändern
		}

		// 2) create set clause
		$setParts = [];
		foreach ($data as $col => $_) {
			$setParts[] = "\"$col\" = :set_$col";
		}
		$setSql = implode(', ', $setParts);

		// 3) prepare statement
		$sql = "UPDATE \"$table\" SET $setSql WHERE \"id\" = :where_id";

		$stmt = $db->prepare($sql);
		if (!$stmt) {
			throw new RuntimeException('Prepare failed: ' . $db->lastErrorMsg());
		}

		// 4) bind values (type, incl. NULL)
		foreach ($data as $col => $val) {

			$type = SQLITE3_TEXT;
			if ($val === null) {
				$type = SQLITE3_NULL;
			} elseif (is_int($val)) {
				$type = SQLITE3_INTEGER;
			} elseif (is_bool($val)) {
				$val  = $val ? 1 : 0;
				$type = SQLITE3_INTEGER;
			}

			$stmt->bindValue(":set_$col", $val, $type);
		}
		$stmt->bindValue(':where_id', $id, SQLITE3_INTEGER);

		// 5) execute
		$res = $stmt->execute();
		if ($res === false) {
			throw new RuntimeException('Execute failed: ' . $db->lastErrorMsg());
		}
		// return effected lines
		return $db->changes();
	}

// MAIN PART
	# setup
	$IMAGES_PER_PAGE_OPTIONS	= array ($constants['const_VIEW_GRID_COLUMNS']*5, $constants['const_VIEW_GRID_COLUMNS']*10, $constants['const_VIEW_GRID_COLUMNS']*20, $constants['const_VIEW_GRID_COLUMNS']*50, $constants['const_VIEW_GRID_COLUMNS']*100);

	# standard values
	$gridcolumns					= isset($gridcolumns) ? (int)$gridcolumns : (int)$constants['const_VIEW_GRID_COLUMNS'];
	$gridcolumns					= $gridcolumns > 0 ? $gridcolumns : (int)$constants['const_VIEW_GRID_COLUMNS'];
	$filter_medium					= isset($filter_medium) ? $filter_medium : "-";
	$view_mode						= isset($view_mode) ? $view_mode :  "grid";
	$filter_images_per_page			= isset($filter_images_per_page) ? $filter_images_per_page :  $IMAGES_PER_PAGE_OPTIONS[1];
	$filter_directory				= isset($filter_directory) ? $filter_directory :  "";
	$filter_date					= isset($filter_date) ? $filter_date :  "all";
	$filter_rating					= isset($filter_rating) ? $filter_rating :  "all";
	$filter_file_type				= isset($filter_file_type) ? $filter_file_type :  "all";
	$filter_file_type_extension		= isset($filter_file_type_extension) ? $filter_file_type_extension :  "all";
	$filter_camera_model_name		= isset($filter_camera_model_name) ? $filter_camera_model_name :  "all";
	$filter_social_publish			= isset($filter_social_publish) ? $filter_social_publish : 'all';
	$filter_social_published		= isset($filter_social_published) ? $filter_social_published : 'all';
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
	$slideshow_timer				= isset($slideshow_timer) ? $slideshow_timer : '-';

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
		if (($filter_rating < -1) or ($filter_rating > 5)) {
			$filter_rating	= "all";
		}
	}

	# ratings-preparation
	$EDIT_ARRAY=array();

	foreach ($_POST as $key=>$val) {
		if (substr($key, 0, 6 )=="rating") {
			$ID_IMAGE	= explode("_",$key)[1];
 			$EDIT_ARRAY[$ID_IMAGE]["Rating"]	= (int)$val;
		} elseif (substr($key, 0, 7 )=="comment") {
			$ID_IMAGE	= explode("_",$key)[1];
 			$EDIT_ARRAY[$ID_IMAGE]["Comment"]	= trim((string)$val);
		} elseif (substr($key, 0, 14 )=="social_publish") {
				list($social, $publish, $ID_IMAGE, $BIT)	= explode("_", $key);
				if (! isset($EDIT_ARRAY[$ID_IMAGE]["social_publish"])) {$EDIT_ARRAY[$ID_IMAGE]["social_publish"] = 0;}

				if ((int)$val == 1) {
					$EDIT_ARRAY[$ID_IMAGE]["social_publish"]	+= 2 ** (int)$BIT;
				}
		}
	}

	#auto-set filter_medium
	if ($filter_medium == "-") {
		$mounted_devices_array	= array();
		exec("df -h", $mounted_devices_array);

		$mounted_devices	= implode("|",$mounted_devices_array);
		if (strpos($mounted_devices,$constants['const_MEDIA_DIR'] . '/' . $constants['const_MOUNTPOINT_USB_TARGET'])) {$filter_medium="target_usb";}
		elseif (strpos($mounted_devices,$constants['const_MEDIA_DIR'] . '/' . $constants['const_MOUNTPOINT_USB_SOURCE'])) {$filter_medium="source_usb";}
		elseif (strpos($mounted_devices,$constants['const_MEDIA_DIR'] . '/' . $constants['const_MOUNTPOINT_NVME_TARGET'])) {$filter_medium="target_nvme";}
		elseif (strpos($mounted_devices,$constants['const_MEDIA_DIR'] . '/' . $constants['const_MOUNTPOINT_NVME_SOURCE'])) {$filter_medium="source_nvme";}
		else {$filter_medium="internal";}
	}

	#generate WHEREs for filter options
	$WHERE_VARIANTS	= array(
		'images',
		'directories',
		'dates',
		'ratings',
		'file_types',
		'file_type_extensions',
		'camera_model_name',
		'social_publish',
		'social_published',
		'variable'
	);
	foreach ($WHERE_VARIANTS as $WHERE_VARIANT) {
		$WHERE[$WHERE_VARIANT]	= "";
	}

	if ($filter_directory != "") {
		$filter_directory	= str_replace("+","=",$filter_directory);
		$filter_directory	= base64_decode($filter_directory);
		if ($filter_directory != "all") {add_to_where("Directory='" . $filter_directory . "'", 'directories', $WHERE_VARIANTS);}
	}

	if ($filter_date != "all") {add_to_where("substr(Create_Date,1,10) like '" . str_replace("-","_",$filter_date) . "'", 'dates', $WHERE_VARIANTS);}

	if (isset($delete_ratings_reject)) {$filter_rating="all";} # after delete remove rating-filter

	if ($filter_rating != "all") {add_to_where("LbbRating = " . $filter_rating, 'ratings', $WHERE_VARIANTS);}

	if ($filter_file_type != "all") {add_to_where("File_Type = '" . $filter_file_type . "'", 'file_types', $WHERE_VARIANTS);}

	if ($filter_file_type_extension != "all") {add_to_where("File_Type_Extension = '" . $filter_file_type_extension . "'", 'file_type_extensions', $WHERE_VARIANTS);}

	if ($filter_camera_model_name != "all") {add_to_where("Camera_Model_Name = '" . $filter_camera_model_name . "'" . ($filter_camera_model_name == '' ? ' OR Camera_Model_Name IS NULL' : ''), 'camera_model_name', $WHERE_VARIANTS);}

	if ($filter_social_publish != "all") {add_to_where("(social_publish & (1 << $filter_social_publish)) != 0", 'social_publish', $WHERE_VARIANTS);}
	if ($filter_social_published != "all") {add_to_where("(social_published & (1 << $filter_social_published)) != 0", 'social_published', $WHERE_VARIANTS);}

	if ($filter_variable_value != "") {
		$filter_variable_value	= str_replace("+","=",$filter_variable_value);
		$filter_variable_value	= base64_decode($filter_variable_value);

		if (($filter_variable_field != "") and ($filter_variable_value != "")) {add_to_where($filter_variable_field . "='" . $filter_variable_value . "'", 'variable', $WHERE_VARIANTS);}
	}

	# define path of the database-file
	$STORAGE_PATH	= "";
	if ($filter_medium == "target_usb") {
		$STORAGE_PATH	= $constants['const_MEDIA_DIR'] . '/' . $constants['const_MOUNTPOINT_USB_TARGET'];
	}
	elseif ($filter_medium == "source_usb") {
		$STORAGE_PATH	= $constants['const_MEDIA_DIR'] . '/' . $constants['const_MOUNTPOINT_USB_SOURCE'];
	}
	elseif ($filter_medium == "target_nvme") {
		$STORAGE_PATH	= $constants['const_MEDIA_DIR'] . '/' . $constants['const_MOUNTPOINT_NVME_TARGET'];
	}
	elseif ($filter_medium == "source_nvme") {
		$STORAGE_PATH	= $constants['const_MEDIA_DIR'] . '/' . $constants['const_MOUNTPOINT_NVME_SOURCE'];
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
			exec('sudo python3 ' . $WORKING_DIR . '/lib_view.py --action init --mountpoint ' . $STORAGE_PATH);

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
			$UPDATE_ARRAY	= array();

			# save changes to EXIF and define $UPDATE_ARRAY
			$UPDATE_ARRAY	= array();
			foreach($EDIT_ARRAY as $ID_IMAGE=>$Values) {

				# get database-values before update
				$statement	= $db->prepare("SELECT Directory, File_Name, Rating, LbbRating, Comment, social_publish FROM EXIF_DATA where ID=" . $ID_IMAGE . ";");
				$IMAGES		= $statement->execute();
				$IMAGE		= $IMAGES->fetchArray(SQLITE3_ASSOC);

				# Rating
				$Rating		= (int)$EDIT_ARRAY[$ID_IMAGE]["Rating"];
				if ($config['conf_VIEW_WRITE_RATING_EXIF'] == true) {
					# define update fields
					if ($IMAGE['LbbRating'] != $Rating or $IMAGE['Rating'] != $Rating) {
						$UPDATE_ARRAY[$ID_IMAGE]['LbbRating']	= $Rating;
						$UPDATE_ARRAY[$ID_IMAGE]['Rating']		= $Rating;
					}
				} else {
					# define update fields
					if ($IMAGE['LbbRating'] != $Rating) {
						$UPDATE_ARRAY[$ID_IMAGE]['LbbRating']	= $Rating;
					}
				}

				# Comment
				if (isset($EDIT_ARRAY[$ID_IMAGE]['Comment'])) {
					if ($EDIT_ARRAY[$ID_IMAGE]['Comment'] != $IMAGE['Comment']) {
						$UPDATE_ARRAY[$ID_IMAGE]['Comment']	= $EDIT_ARRAY[$ID_IMAGE]['Comment'];
					}
				}

				# social_upload
				if (isset($EDIT_ARRAY[$ID_IMAGE]['social_publish'])) {
					if ($EDIT_ARRAY[$ID_IMAGE]['social_publish'] != $IMAGE['social_publish']) {
						$UPDATE_ARRAY[$ID_IMAGE]['social_publish']	= $EDIT_ARRAY[$ID_IMAGE]['social_publish'];
					}
				}
			}

			foreach($UPDATE_ARRAY as $ID_IMAGE=>$UPDATE) {

				update_row($db, "EXIF_DATA", $ID_IMAGE, $UPDATE);

				# write comment into the file's metadata
				if (isset($UPDATE['Comment']) and isset($UPDATE['Rating'])) {
					shell_exec("sudo python3 {$WORKING_DIR}/lib_metadata.py '{$STORAGE_PATH}/{$IMAGE['Directory']}/{$IMAGE['File_Name']}' --comment '{$UPDATE['Comment']}' --rating '{$UPDATE['Rating']}'");
				} elseif (isset($UPDATE['Rating'])) {
					shell_exec("sudo python3 lib_metadata.py '" . $STORAGE_PATH . '/' . $IMAGE['Directory'] . '/' . $IMAGE['File_Name'] . "' --rating '" . $UPDATE['Rating'] . "'");
				} elseif (isset($UPDATE['Comment'])) {
					shell_exec("sudo python3 lib_metadata.py '" . $STORAGE_PATH . '/' . $IMAGE['Directory'] . '/' . $IMAGE['File_Name'] . "' --comment '" . $UPDATE['Comment'] . "'");
				}
			}

			if (count($UPDATE_ARRAY) > 0) {$saved_message	=  L::view_saved;}

			# delete media-files
			if (isset($delete_ratings_reject)) {
				foreach($EDIT_ARRAY as $key=>$val) {
					$key	= intval($key);

					$statement		= $db->prepare("SELECT File_Name, Directory FROM EXIF_DATA WHERE ID=" . $key . ";");
					$IMAGES			= $statement->execute();
					if ($IMAGE = $IMAGES->fetchArray(SQLITE3_ASSOC)) {
						$DELETE_FILE	= $STORAGE_PATH . '/' . $IMAGE['Directory'] . '/' . $IMAGE['File_Name'];
						$DELETE_TIMS	= $STORAGE_PATH . '/' . $IMAGE['Directory'] . '/tims/' . $IMAGE['File_Name'] . '.JPG';
						$DELETE_XMP		= $STORAGE_PATH . '/' . $IMAGE['Directory'] . '/' . pathinfo($IMAGE['File_Name'], PATHINFO_FILENAME) . '.xmp';
						shell_exec ("sudo rm '" . $DELETE_FILE . "'");
						shell_exec ("sudo rm '" . $DELETE_TIMS . "'");
						shell_exec ("sudo rm '" . $DELETE_XMP . "'");
						$db->exec("delete from EXIF_DATA where ID=" . $key . " and LbbRating=-1;");
					}

				}
			}

			# database-queries
			$statement			= $db->prepare("SELECT ID FROM EXIF_DATA " . $WHERE['images'] . " order by " . $order_by . " " . $order_dir . ";");
			$IMAGES_ALL			= $statement->execute();

			#get first and last ID, define offset
			$n = 0;
			while ($FETCH_IMAGE = $IMAGES_ALL->fetchArray(SQLITE3_ASSOC)) {
				$n += 1;
				if ($n == 1) {
					$IMAGE_ID_FIRST = $FETCH_IMAGE['ID'];
				}
				if ($FETCH_IMAGE['ID'] == $ID) {
					$select_offset	= intdiv($n-1, $filter_images_per_page) * $filter_images_per_page - 1;
					$select_offset	= $select_offset >= 0 ? $select_offset : 0; # no negative select_offset
				}
				$IMAGE_ID_LAST = $FETCH_IMAGE['ID'];
			}

			# generate select_limit
			$select_limit	=  $select_offset > 0 ? $filter_images_per_page + 2 : $filter_images_per_page + 1;

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

			$statement				= $db->prepare("WITH RECURSIVE bits(n, mask) AS (
														SELECT 0, 1
														UNION ALL
														SELECT n + 1, mask << 1 FROM bits WHERE n < " . (count($social_services) - 1) . "
													)
													SELECT
														n AS bit,
														SUM( (social_publish & mask) != 0 ) AS set_count
													FROM (
														SELECT social_publish
														FROM EXIF_DATA
														" . ($WHERE['social_publish'] != '' ? $WHERE['social_publish'] . ' AND' : 'WHERE') . "
														(social_publish > 0)
														) AS rows
													CROSS JOIN bits
													GROUP BY n
													HAVING SUM( (social_publish & mask) != 0 ) > 0
													ORDER BY n;");
			$SOCIAL_PUBLISHS		= $statement->execute();

			$statement				= $db->prepare("WITH RECURSIVE bits(n, mask) AS (
														SELECT 0, 1
														UNION ALL
														SELECT n + 1, mask << 1 FROM bits WHERE n < " . (count($social_services) - 1) . "
													)
													SELECT
														n AS bit,
														SUM( (social_published & mask) != 0 ) AS set_count
													FROM (
														SELECT social_published
														FROM EXIF_DATA
														" . ($WHERE['social_published'] != '' ? $WHERE['social_published'] . ' AND' : 'WHERE') . "
														(social_published > 0)
														) AS rows
													CROSS JOIN bits
													GROUP BY n
													HAVING SUM( (social_published & mask) != 0 ) > 0
													ORDER BY n;");
			$SOCIAL_PUBLISHEDS		= $statement->execute();

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
					if (($n == 1) and ($select_offset > 0)) { /*get predecessor ID from first element*/
						$IMAGE_ID_PRE = $IMAGE['ID'];
					} elseif ($n == ($select_offset <= 0 ? 1 : 2)) {
						$IMAGE_ID = $IMAGE['ID'];
					} elseif ($n == $filter_images_per_page + ($select_offset <= 0 ? 1 : 2)) { /*get successor form last element visible on the page*/
						$IMAGE_ID_POST = $IMAGE['ID'];
					}

					// Form selected images exclude first and last: The first and last items serve as technical predecessor/successor nodes.
					if (
						(
							($n > 1) or ($n == 1 and $select_offset <= 0) /* not first selected image () */
						) and
						($n <= $filter_images_per_page + ($select_offset <= 0 ? 0 : 1))) /* not last selected image */
					{
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
	$GET_PARAMETER	.= "&gridcolumns=$gridcolumns";
	if ($filter_directory != "all") {$GET_PARAMETER	.= "&filter_directory=" . str_replace("=","+",base64_encode($filter_directory));}
	if ($filter_date != "all") {$GET_PARAMETER	.= "&filter_date=" . $filter_date;}
	if ($filter_rating != "all") {$GET_PARAMETER	.= "&filter_rating=$filter_rating";}
	if ($filter_file_type_extension != "all") {$GET_PARAMETER	.= "&filter_file_type_extension=$filter_file_type_extension";}
	if ($filter_camera_model_name != "all") {$GET_PARAMETER	.= "&filter_camera_model_name=$filter_camera_model_name";}
	if ($filter_social_publish != "all") {$GET_PARAMETER	.= "&filter_social_publish=$filter_social_publish";}
	if ($filter_social_published != "all") {$GET_PARAMETER	.= "&filter_social_published=$filter_social_published";}
	if ($filter_variable_field != "") {$GET_PARAMETER	.= "&filter_variable_field=$filter_variable_field";}
	if ($filter_variable_value != "") {$GET_PARAMETER	.= "&filter_variable_value=" . str_replace("=","+",base64_encode($filter_variable_value));}

	# define hidden HIDDEN_INPUTS
	$HIDDEN_INPUTS	="<input type=\"hidden\" name=\"filter_medium\" value=\"" . $filter_medium . "\">";
	$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"view_mode\" value=\"" . $view_mode . "\">";
	$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"order_by\" value=\"" . $order_by . "\">";
	$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"order_dir\" value=\"" . $order_dir . "\">";
	$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_images_per_page\" value=\"" . $filter_images_per_page . "\">";
	$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"gridcolumns\" value=\"" . $gridcolumns . "\">";
	$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"ID\" value=\"" . $ID . "\">";
	if ($filter_directory != "all") {$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_directory\" value=\"" . str_replace("=","+",base64_encode($filter_directory)) . "\">";}
	if ($filter_date != "all") {$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_date\" value=\"" . $filter_date . "\">";}
	if ($filter_rating != "all") {$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_rating\" value=\"" . $filter_rating . "\">";}
	if ($filter_file_type_extension != "all") {$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_file_type_extension\" value=\"" . $filter_file_type_extension . "\">";}
	if ($filter_camera_model_name != "all") {$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_camera_model_name\" value=\"" . $filter_camera_model_name . "\">";}
	if ($filter_social_publish != "all") {$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_social_publish\" value=\"" . $filter_social_publish . "\">";}
	if ($filter_social_published != "all") {$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_social_published\" value=\"" . $filter_social_published . "\">";}
	if ($filter_variable_field != "") {$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_variable_field\" value=\"" . $filter_variable_field . "\">";}
	if ($filter_variable_value != "") {$HIDDEN_INPUTS	.="<input type=\"hidden\" name=\"filter_variable_value\" value=\"" . str_replace("=","+",base64_encode($filter_variable_value)) . "\">";}
?>

<html lang="<?php echo $config["conf_LANGUAGE"]; ?>" data-theme="<?php echo $theme; ?>">

<head>
	<?php
		include "${WORKING_DIR}/sub-standards-header-loader.php";
	?>
	<link rel="stylesheet" href="css/slideshow.css">
	<link rel="stylesheet" href="css/mglass.css">
	<script type="text/javascript" src="js/mglass.js"></script>
	<script type="text/javascript" src="js/slideshow.js"></script>
	<script type="text/javascript" src="js/display.js"></script>

</head>

<body <?php echo $background; ?> onload="refreshDisplay(); slideshow_init();">
	<?php include "${WORKING_DIR}/sub-standards-body-loader.php"; ?>
	<?php include "${WORKING_DIR}/sub-menu.php"; ?>
	<?php
		include "${WORKING_DIR}/sub-display.php";
		display();
		if (isset($saved_message)) {print ($saved_message);}

	include("${WORKING_DIR}/sub-view-icons.php")

	?>
<!-- FILTER -->
	<div class="card" style="margin-top: 2em">
		<details>
			<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::view_filter_section; ?></summary>
			<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
				<input type="hidden" name="order_by" value="<?php echo $order_by; ?>">
				<input type="hidden" name="order_dir" value="<?php echo $order_dir; ?>">
				<input type="hidden" name="gridcolumns" value="<?php echo $gridcolumns; ?>">

				<div style="display: flow-root">
					<div style="float:left;padding: 5px;">
						<label for="filter_medium"><?php echo L::view_filter_medium; ?></label><br>

							<select name="filter_medium" id="filter_medium" onchange="this.form.submit()">
								<option value="target_usb" <?php echo ($filter_medium == "target_usb"?" selected":""); ?>><?php echo L::view_filter_medium_target_usb; ?></option>
								<option value="source_usb" <?php echo ($filter_medium == "source_usb"?" selected":""); ?>><?php echo L::view_filter_medium_source_usb; ?></option>
								<?php
									if ($NVMe_available) {
										?>
											<option value="target_nvme" <?php echo ($filter_medium == "target_nvme"?" selected":""); ?>><?php echo L::view_filter_medium_target_nvme; ?></option>
											<option value="source_nvme" <?php echo ($filter_medium == "source_nvme"?" selected":""); ?>><?php echo L::view_filter_medium_source_nvme; ?></option>
										<?php
									}
								?>
								<option value="internal" <?php echo ($filter_medium == "internal"?" selected":""); ?>><?php echo L::view_filter_medium_internal; ?></option>
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

				<?php if ($DATABASE_CONNECTED) { ?>
					<div style="display: flow-root">

						<div style="float:left;padding: 5px;">
							<label for="filter_date"><?php echo L::view_filter_date; ?></label><br>
								<select name="filter_date" id="filter_date" onchange="this.form.submit()">
									<option value="all" <?php echo ($filter_date == "all"?" selected":""); ?>>-</option>
									<?php
										while ($DATE = $DATES->fetchArray(SQLITE3_ASSOC)) {
											echo "<option value=\"" . $DATE['Create_Day'] . "\" " . ($filter_date == $DATE['Create_Day']?" selected":"") . ">" . $DATE['Create_Day'] . " (" . $DATE['FILECOUNT'] . ")</option>";
										}
									?>
								</select>
						</div>

						<div style="float:right;padding: 5px;">
							<label for="filter_rating"><?php echo L::view_filter_rating; ?></label><br>
								<select name="filter_rating" id="filter_rating" onchange="this.form.submit()">
									<option value="all" <?php echo ($filter_rating == "all"?" selected":""); ?>><?php echo L::view_filter_rating_all; ?></option>
									<?php
										while ($RATING = $RATINGS->fetchArray(SQLITE3_ASSOC)) {
											echo "<option value=\"" . $RATING['LbbRating'] . "\" " . ($filter_rating == $RATING['LbbRating']?" selected":"") . ">" . ($RATING['LbbRating'] == '-1' ? 'X' : $RATING['LbbRating'] . " " . L::view_filter_rating_stars) . " (" . $RATING['FILECOUNT'] . ")</option>";
										}
									?>
								</select>
						</div>
					</div>

					<div style="display: flow-root">
							<div style="float:left;padding: 5px;">
								<label for="filter_file_type"><?php echo L::view_filter_file_type; ?></label><br>
									<select name="filter_file_type" id="filter_file_type" onchange="this.form.submit()">
										<option value="all" <?php echo ($filter_file_type == "all"?" selected":""); ?>>-</option>
										<?php
											while ($FILE_TYPE = $FILE_TYPES->fetchArray(SQLITE3_ASSOC)) {
												echo "<option value=\"" . $FILE_TYPE['File_Type'] . "\" " . ($filter_file_type == $FILE_TYPE['File_Type']?" selected":"") . ">" . $FILE_TYPE['File_Type'] . " (" . $FILE_TYPE['FILECOUNT'] . ")</option>";
											}
										?>
									</select>
							</div>

							<div style="float:left;padding: 5px;">
								<label for="filter_file_type_extension"><?php echo L::view_filter_file_type_extension; ?></label><br>
									<select name="filter_file_type_extension" id="filter_file_type_extension" onchange="this.form.submit()">
										<option value="all" <?php echo ($filter_file_type_extension == "all"?" selected":""); ?>>-</option>
										<?php
											while ($FILE_TYPE_EXTENSION = $FILE_TYPE_EXTENSIONS->fetchArray(SQLITE3_ASSOC)) {
												echo "<option value=\"" . $FILE_TYPE_EXTENSION['File_Type_Extension'] . "\" " . ($filter_file_type_extension == $FILE_TYPE_EXTENSION['File_Type_Extension']?" selected":"") . ">" . $FILE_TYPE_EXTENSION['File_Type_Extension'] . " (" . $FILE_TYPE_EXTENSION['FILECOUNT'] . ")</option>";
											}
										?>
									</select>
							</div>

							<div style="float:right;padding: 5px;">
								<label for="filter_camera_model_name"><?php echo L::view_filter_camera_model_name; ?></label><br>
									<select name="filter_camera_model_name" id="filter_camera_model_name" onchange="this.form.submit()">
										<option value="all" <?php echo ($filter_camera_model_name == "all"?" selected":""); ?>>-</option>
										<?php
											while ($CAMERA_MODEL_NAME = $CAMERA_MODEL_NAMES->fetchArray(SQLITE3_ASSOC)) {
												$camera = $CAMERA_MODEL_NAME['Camera_Model_Name'] == '' ? '-' : $CAMERA_MODEL_NAME['Camera_Model_Name'];
												echo "<option value=\"" . $CAMERA_MODEL_NAME['Camera_Model_Name'] . "\" " . ($filter_camera_model_name == $CAMERA_MODEL_NAME['Camera_Model_Name']?" selected":"") . ">" . $camera . " (" . $CAMERA_MODEL_NAME['FILECOUNT'] . ")</option>";
											}
										?>
									</select>
							</div>

					</div>

					<div style="display: flow-root">
							<div style="float:left;padding: 5px;">
								<label for="filter_directory"><?php echo L::view_filter_directory; ?></label><br>
									<select name="filter_directory" id="filter_directory" onchange="this.form.submit()">
										<option value="<?php echo str_replace("=","+",base64_encode('all')); ?>" <?php echo ($filter_directory == "all"?" selected":""); ?>>/</option>
										<?php
											while ($DIRECTORY = $DIRECTORIES->fetchArray(SQLITE3_ASSOC)) {
												echo "<option value=\"" . str_replace("=","+",base64_encode($DIRECTORY['Directory'])) . "\" " . ($filter_directory == $DIRECTORY['Directory']?" selected":"") . ">" . str_replace($STORAGE_PATH,"",$DIRECTORY['Directory']) . " (" . $DIRECTORY['FILECOUNT'] . ")</option>";
											}
										?>
									</select>
							</div>
					</div>

					<div style="display: flow-root">
							<div style="float:left;padding: 5px;">
								<label for="filter_social_publish"><?php echo L::view_filter_social_publish; ?></label><br>
									<select name="filter_social_publish" id="filter_social_publish" onchange="this.form.submit()">
										<option value="all" <?php echo ($filter_social_publish == "all"?" selected":""); ?>>-</option>
										<?php
											while ($SOCIAL_PUBLISH = $SOCIAL_PUBLISHS->fetchArray(SQLITE3_ASSOC)) {
												$SocialServiceName	= isset($social_services[$SOCIAL_PUBLISH['bit']]) ? $social_services[$SOCIAL_PUBLISH['bit']] : '?';
												echo "<option value=\"" . $SOCIAL_PUBLISH['bit'] . "\" " . ($filter_social_publish == $SOCIAL_PUBLISH['bit']?" selected":"") . ">" . $SocialServiceName . " (" . $SOCIAL_PUBLISH['set_count'] . ")</option>";
											}
										?>
									</select>
							</div>

							<div style="float:right;padding: 5px;">
								<label for="filter_social_published"><?php echo L::view_filter_social_published; ?></label><br>
									<select name="filter_social_published" id="filter_social_published" onchange="this.form.submit()">
										<option value="all" <?php echo ($filter_social_published == "all"?" selected":""); ?>>-</option>
										<?php
											while ($SOCIAL_PUBLISHED = $SOCIAL_PUBLISHEDS->fetchArray(SQLITE3_ASSOC)) {
												$SocialServiceName	= isset($social_services[$SOCIAL_PUBLISHED['bit']]) ? $social_services[$SOCIAL_PUBLISHED['bit']] : '?';
												echo "<option value=\"" . $SOCIAL_PUBLISHED['bit'] . "\" " . ($filter_social_published == $SOCIAL_PUBLISHED['bit']?" selected":"") . ">" . $SocialServiceName . " (" . $SOCIAL_PUBLISHED['set_count'] . ")</option>";
											}
										?>
									</select>
							</div>
					</div>

					<div style="display: flow-root">
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
					</div>

				<?php } ?>

			</form>
		</details>
	</div>

<!-- IMAGES -->
	<?php if ($DATABASE_CONNECTED) { ?>
	<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">

		<?php
			echo $HIDDEN_INPUTS;
			navigator(
				$view_mode,
				$imagecount,
				$gridcolumns,
				$filter_images_per_page,
				$select_offset,
				$filter_rating,
				$IMAGE_ID_PRE,
				$IMAGE_ID,
				$IMAGE_ID_POST,
				$IMAGE_ID_FIRST,
				$IMAGE_ID_LAST,
				$GET_PARAMETER,
				$order_by,
				$order_dir,
				L::view_filter_order_by_filename,
				L::view_filter_order_by_creationdate,
				L::view_filter_order_by_id,
				true,
				$slideshow_timer
			);
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
								if (count($DateExplode) >= 6) {
									$interpretedDate	= date_create($DateExplode[0] . '-' . $DateExplode[1] . '-' . $DateExplode[2] . ' ' . $DateExplode[3] . ':' .  $DateExplode[4] . ':' . $DateExplode[5]);
								} else {
									$interpretedDate	= Null;
								}
							}

							if (! is_null($interpretedDate)) {
								$IMAGE_DATE		= date_format($interpretedDate,L::view_date_format);
							} else {
								$IMAGE_DATE	= $IMAGE['Create_Date'];
							}

							$Directory				= $STORAGE_PATH . '/' . $IMAGE['Directory'];
							$IMAGE_ID				= $IMAGE['ID'];
							$IMAGE_FILENAME_TIMS	= $Directory . '/tims/' . $IMAGE['File_Name'] . '.JPG';
							?>


								<div style="width: <?php echo 100/$gridcolumns-1; ?>%;margin-bottom: 3em;" class="floating">

									<div style="width: 100%" title="<?php echo $IMAGE['File_Name']; ?>">
										<?php
											if (($order_by == 'Create_Date') and ($LAST_DATE !== $IMAGE_DATE)) {
												echo '<b>' . $IMAGE_DATE . '</b><br>';
												$LAST_DATE	= $IMAGE_DATE;
											}
										?>
										<a href="<?php echo $GET_PARAMETER . '&view_mode=single&ID=' . ($IMAGE['ID']); ?>">
											<img style="max-width: 100%; border-radius: 5px;" class="rating<?php echo $IMAGE['LbbRating']; ?>" <?php echo empty($IMAGE['Comment'])?"title='" . $IMAGE['File_Name'] . "'":"title='" . $IMAGE['File_Name'] . PHP_EOL . $IMAGE['Comment'] . "'"; ?> src="<?php echo urlencode_keep_slashes($IMAGE_FILENAME_TIMS); ?>" onerror="this.src='/img/unknown.JPG';">
										</a>
									</div>

									<div class="wrap-row d-flex align-items-start gap-2 mt-1">

										<?php echo rating_radio($IMAGE['ID'],$IMAGE['LbbRating']); ?>

										<?php
											if (!empty($IMAGE['Comment'])) {
												?>
													<div class="meta meta-sep" aria-hidden="true">
														<svg width="16" height="16" class="flex-shrink-0">
															<use href="#icon-comment"></use>
														</svg>
													</div>
												<?php
											}

											echo social_pannel($IMAGE['ID'], $IMAGE['social_publish'], $IMAGE['social_published']);
										?>

									</div>

									<div style="float:right;padding: 2px;font-size:0.8em;" class="hidden-mobile">
										<a href="<?php echo $GET_PARAMETER . '&view_mode=single&ID=' . $IMAGE_ID; ?>" style="white-space: normal; overflow-wrap: anywhere; word-break: break-word; word-wrap: break-word;">
											<?php
											echo $IMAGE['File_Name'];
											?>
										</a>
									</div>
									<div style="float:right;padding: 2px;font-size:0.8em;" class="hidden-desktop">
										<a href="<?php echo $GET_PARAMETER . '&view_mode=single&ID=' . $IMAGE_ID; ?>" style="white-space: normal; overflow-wrap: anywhere; word-break: break-word; word-wrap: break-word;">
											<?php
											echo pathinfo($IMAGE['File_Name'], PATHINFO_EXTENSION);
											?>
										</a>
									</div>

								</div>
							<?php
						}
					}
					elseif ($view_mode == "single") {
// SINGLE
						$IMAGE = $IMAGES_ARRAY[0];
						$Directory				= $STORAGE_PATH . '/' . $IMAGE['Directory'];
						$IMAGE_ID				= $IMAGE['ID'];
						$IMAGE_FILENAME			= $Directory . '/' . $IMAGE['File_Name'];
						$IMAGE_FILENAME_TIMS	= $Directory . '/tims/' . $IMAGE['File_Name'] . '.JPG';
						$IMAGE_FILENAME_PARTS	= pathinfo($IMAGE_FILENAME);
						$IMAGE_TYPE				= $IMAGE['File_Type'] !== ""?strtolower($IMAGE['File_Type']):strtolower($IMAGE_FILENAME_PARTS['extension']);
						?>

						<div style="float:left;width: 100%;padding: 5px;">
							<?php
								if (strpos(";" . $constants['const_FILE_EXTENSIONS_LIST_WEB_IMAGES'] . ";" . $constants['const_FILE_EXTENSIONS_LIST_HEIC'] . ";" . $constants['const_FILE_EXTENSIONS_LIST_RAW'] . ";" . $constants['const_FILE_EXTENSIONS_LIST_TIF'] . ";",";" . strtolower($IMAGE_FILENAME_PARTS['extension']) . ";") !== false ) {
// 		image-file

									if (strpos(";" . $constants['const_FILE_EXTENSIONS_LIST_WEB_IMAGES'] . ";",";" . strtolower($IMAGE_FILENAME_PARTS['extension']) . ";") !== false ) {
										$FILENAME_DISPLAY	= $IMAGE_FILENAME;
									} else {
										$FILENAME_DISPLAY	= $IMAGE_FILENAME_TIMS;
									}
							?>
									<div style="width: 100%;text-align:center;" title="<?php echo $IMAGE['File_Name']; ?>">

										<div id="slideshowContent" class="slideshow">
											<span class="slideshowClose" onclick="slideshow_stop(event)" role="button">&times;</span>
											<img class="slideshow-content" src="<?php echo urlencode_keep_slashes($FILENAME_DISPLAY); ?>">
										</div>

										<div class="img-magnifier-container">
											<img id="fullsizeimage" onClick="magnify('fullsizeimage', <?php echo $constants['const_VIEW_MAGNIFYING_GLASS_ZOOM']; ?>)" style="max-width: 100%;border-radius: 5px;" class="rating<?php echo $IMAGE['LbbRating']; ?>" src="<?php echo urlencode_keep_slashes($FILENAME_DISPLAY); ?>">
										</div>

									</div>

<!-- 									RAW-image or TIF image -->
									<?php
										if (strpos(";" . $constants['const_FILE_EXTENSIONS_LIST_RAW'] . ";" . $constants['const_FILE_EXTENSIONS_LIST_TIF'] . ";",";" . strtolower($IMAGE_FILENAME_PARTS['extension']) . ";") !== false ) {
											echo "<div style=\"width: 100%\">";
												echo "<p style=\"text-align: center;font-weight: bold;\">" . L::view_images_preview_low_resolution_image . "</p>";
											echo "</div>";
										}
									?>

								<?php media_functions('image', $IMAGE, $IMAGE_FILENAME); ?>

							<?php
								} elseif (strpos(";" . $constants['const_FILE_EXTENSIONS_LIST_VIDEO'] . ";",";" . strtolower($IMAGE_FILENAME_PARTS['extension']) . ";") !== false ) {
// 		video-file
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

											<div id="slideshowContent" class="slideshow">
												<span class="slideshowClose" onclick="slideshow_stop(event)" role="button">&times;</span>
												<video class="slideshow-content" width="100%" class="rating<?php echo $IMAGE['LbbRating']; ?>" controls autoplay>
													<source src="<?php echo urlencode_keep_slashes($IMAGE_FILENAME_PREVIEW); ?>" type="video/<?php echo $IMAGE_TYPE; ?>"></source>
												</video>
											</div>

											<video width="100%" class="rating<?php echo $IMAGE['LbbRating']; ?>" controls <?php if ($slideshow_timer == '-') {echo "autoplay";}?>>
												<source src="<?php echo urlencode_keep_slashes($IMAGE_FILENAME_PREVIEW); ?>" type="video/<?php echo $IMAGE_TYPE; ?>"></source>
											</video>

											<?php
											if ($LOW_RES) {
// 		low resolution video
												echo "<p style=\"text-align: center;font-weight: bold;\">" . L::view_images_preview_low_resolution_video . "</p>";
											}
											?>
										</div>
<!-- Video common -->
										<?php media_functions('video', $IMAGE, $IMAGE_FILENAME); ?>

									<?php
								} elseif (strpos(";" . $constants['const_FILE_EXTENSIONS_LIST_AUDIO'] . ";",";" . strtolower($IMAGE_FILENAME_PARTS['extension']) . ";") !== false ) {
// 		audio-file
									?>
										<div style="width: 100%;text-align:center;" title="<?php echo $IMAGE['File_Name']; ?>">

											<div id="slideshowContent" class="slideshow">
												<span class="slideshowClose" onclick="slideshow_stop(event)" role="button">&times;</span>
												<audio class="slideshow-content" width="100%" class="rating<?php echo $IMAGE['LbbRating']; ?>" controls autoplay>
													<source src="<?php echo urlencode_keep_slashes($IMAGE_FILENAME); ?>" type="audio/<?php echo $IMAGE_FILENAME_PARTS['extension']; ?>">">
												</audio>
											</div>

											<img style="max-width: 100%;border-radius: 5px;" class="rating<?php echo $IMAGE['LbbRating']; ?>" src="<?php echo urlencode_keep_slashes($IMAGE_FILENAME_TIMS); ?>">

											<audio width="100%" class="rating<?php echo $IMAGE['LbbRating']; ?>" controls <?php if ($slideshow_timer == '-') {echo "autoplay";}?>>
												<source src="<?php echo urlencode_keep_slashes($IMAGE_FILENAME); ?>" type="audio/<?php echo $IMAGE_FILENAME_PARTS['extension']; ?>">">
											</audio>
										</div>

										<?php media_functions('audio', $IMAGE, $IMAGE_FILENAME); ?>
									<?php
								} elseif (strpos(";" . $constants['const_FILE_EXTENSIONS_LIST_TEXT'] . ";",";" . strtolower($IMAGE_FILENAME_PARTS['extension']) . ";") !== false ) {
// 		text-file
									?>
										<div style="width: 100%;text-align:center;" title="<?php echo $IMAGE['File_Name']; ?>">

											<div id="slideshowContent" class="slideshow">
												<span class="slideshowClose" onclick="slideshow_stop(event)" role="button">&times;</span>
												<img class="slideshow-content" src="<?php echo urlencode_keep_slashes($FILENAME_DISPLAY); ?>">
											</div>

											<img style="max-width: 100%;border-radius: 5px;" class="rating<?php echo $IMAGE['LbbRating']; ?>" src="<?php echo urlencode_keep_slashes($IMAGE_FILENAME_TIMS); ?>">

										</div>

										<?php media_functions('text', $IMAGE, $IMAGE_FILENAME); ?>

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

						if ($slideshow_timer !== '-') {echo '<script type="text/javascript">slideshow_display();</script>';}
					}
				} else {
// 					imagecount=0
					?>
						<?php echo L::view_images_empty; ?>
					<?php
				}
			?>

		</div>

		<?php navigator(
			$view_mode,
			$imagecount,
			$gridcolumns,
			$filter_images_per_page,
			$select_offset,
			$filter_rating,
			$IMAGE_ID_PRE,
			$IMAGE_ID,
			$IMAGE_ID_POST,
			$IMAGE_ID_FIRST,
			$IMAGE_ID_LAST,
			$GET_PARAMETER,
			$order_by,
			$order_dir,
			L::view_filter_order_by_filename,
			L::view_filter_order_by_creationdate,
			L::view_filter_order_by_id,
			false,
			$slideshow_timer
		); ?>

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
