<?php defined('SYSPATH') or die('No direct script access.');
/**
 * KML helper
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author	   Ushahidi Team <team@ushahidi.com> 
 * @author     Hiroshi Miura <miurahr@osmf.jp>
 * @package    Ushahidi - http://source.ushahididev.com
 * @module	   KML Controller	
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
* 
*/
class kml_Core {

	//=== function to get domain name from URL string
	function get_domain($url) {
		// parse host out of URL
		$host = parse_url($url, PHP_URL_HOST);
		// remove first subdomain if it matches one in the list (with ".")
		$sub_domains_remove = array("www.", "www2.", "blog.", "blogs.");
		$domain = str_replace($sub_domains_remove, "", $host);
		// remove additional subdomains if more than 2 "."s (dots)
		while (substr_count($domain, ".") > 2) {
			$remove_upto = strpos(".", $domain) + 1;
			$domain = substr($domain, $remove_upto);
		}
		return $domain;
	}

	function get_category_folder_head_snippet($category) {
		// check if category description is same as category title, or is blank
		if ($category->category_title == $category->category_description || $category->category_description == "") {
			// if so, make snippet blank
			$category_snippet = "<snippet maxLines='0'></snippet>";
		}
		else {
			// if not, make snippet contain category description
			$category_snippet = "<snippet maxLines='1'>" . $category->category_description . "</snippet>";
		}
		return $category_snippet;
	}

	//=== function to generate media string for inclusion in placemark balloon ==
	function get_item_media($item) {
		$urlbase = url::base();
		$item_media = array();
		$item_media_string = "";
		$close_table = false;
		foreach ($item->media as $media) {
			if (strlen($media->media_link) != 0) {
				// if this is the first one with data, then write table header
				if ($item_media_string == "") $item_media_string .= "<table cellpadding='2' cellspacing='0'><tr>"; 
				$close_table = true;
				switch ($media->media_type) {
					case 1:  
						$item_media["image"] = $media->media_link;
						$item_media["image_medium"] = $media->media_medium;
						$item_media["image_thumb"] = $media->media_thumb;
						$item_media_string .= "<td align='center'><a href='" . $urlbase . Kohana::config("upload.relative_directory") . "/" .  $media->media_link . "'><img src='" . $urlbase . Kohana::config("upload.relative_directory") . "/" . $media->media_thumb . "' /></a>";
						$item_media_string .= "<br /><a style='font-size:0.8em; ' href='" . $urlbase . Kohana::config("upload.relative_directory") . "/" . $media->media_link . "'>full size</a></td>";
						break;
					case 2:
						$item_media["video"] = $media->media_link;
						$item_media_string .= "<td><a href='" . $media->media_link . "'>Video</a></td>";
						break;
					case 3:
						$item_media["audio"] = $media->media_link;
						$item_media_string .= "<td><a href='" . $media->media_link . "'>Audio</a></td>";
						break;
					case 4:
						$link = $media->media_link;
						$domain = kml::get_domain($link);
						$item_media["news"] = $link;
						if (strlen($domain) >= 5) {
							$item_media_string .= "<td><a href='" . $link . "'>" . $domain . "</a></td>";
						}
						else {
							$item_media_string .= "<td><a href='" . $link . "'>Source link</a></td>";
						}
						break;
					case 5:
						$item_media["podcast"] = $media->media_link;
						$item_media_string .= "<td><a href='" . $media->media_link . "'>Podcast</a></td>";
						break;
				}
			}
		}
		if ($close_table) $item_media_string .= "</tr></table>";
		$item_media["media_string"] = $item_media_string;
		return $item_media;
	}

	//=== function to generate Categories String for inclusion in placemark balloon ==
	static function generate_categories_string($item, $catID_data, $catID_icons, $options) {
		$urlbase = url::base();
		$categories_string = "";
		$cat_icon_size = $options["cat_icon_size"];

		// Count visible categories
		$catCount = 0;
		foreach ($item->category as $item_category) {
			if($item_category->category_visible == 1) {
				$catCount++;
			}
		}

		// look at category type and select appropriate string writer
		switch ($options["cats_in_balloons"]) {
			case "icons":
				//$categories_string = "testing ICONS";
				// Check if there's no category defined
				if ($catCount == 0) {
					$categories_string = "No Category Selected";
				}
				elseif ($catCount == 1) {
					// Iterate through categories 
					foreach ($item->category as $item_category) {
						// Check that it's a visible category
						if ($item_category->category_visible == 1) {
							// If it's a top level category, write simple categories string
							if ($item_category->parent_id == 0) {
								$categories_string = "<strong style='vertical-align:middle;'>Category:</strong> &nbsp;&nbsp;";
								$categories_string .= "<a href='" . $urlbase . "reports/?c=" . $item_category->id . "'>";
								$categories_string .= "<img src='" . $catID_icons[$item_category->id]["cat_string"] . "' width='" . $cat_icon_size . "' height='" . $cat_icon_size . "' style='vertical-align:middle;' />";
								$categories_string .= "</a>";
							}
							// If it's a sub category, write categories string with parent and sub
							else {
								$categories_string = "<strong style='vertical-align:middle;'>Category:</strong> &nbsp;&nbsp;";
								$categories_string .= "<a href='" . $urlbase . "reports/?c=" . $item_category->id . "'>";
								if($options["cat_parents"]) $categories_string .= "<img src='" . $catID_icons[$item_category->parent_id]["cat_string"] . "' width='" . $cat_icon_size . "' height='" . $cat_icon_size . "' style='vertical-align:middle;' /> ";
								$categories_string .= "<img src='" . $catID_icons[$item_category->id]["cat_string"] . "' width='" . $cat_icon_size . "' height='" . $cat_icon_size . "' style='vertical-align:middle;' />";
								$categories_string .= "</a>";
							}
						}
					}
				}	
				else {
					$categories_string = "<strong style='vertical-align:middle;'>Categories (" . $catCount . "):</strong> &nbsp;&nbsp;";
					// Iterate through categories 
					foreach ($item->category as $item_category) {
						// Check that it's a visible category
						if ($item_category->category_visible == 1) {
							// If it's a top level category, write simple categories string
							if ($item_category->parent_id == 0) {
								$categories_string .= "<a href='" . $urlbase . "reports/?c=" . $item_category->id . "'>";
								$categories_string .= "<img src='" . $catID_icons[$item_category->id]["cat_string"] . "' width='" . $cat_icon_size . "' height='" . $cat_icon_size . "' style='vertical-align:middle;' />";
								$categories_string .= "</a> &nbsp;&nbsp;";
							}
							// If it's a sub category, write categories string with parent and sub
							else {
								$categories_string .= "<a href='" . $urlbase . "reports/?c=" . $item_category->id . "'>";
								if($options["cat_parents"]) $categories_string .= "<img src='" . $catID_icons[$item_category->parent_id]["cat_string"] . "' width='" . $cat_icon_size . "' height='" . $cat_icon_size . "' style='vertical-align:middle;' /> ";
								$categories_string .= "<img src='" . $catID_icons[$item_category->id]["cat_string"] . "' width='" . $cat_icon_size . "' height='" . $cat_icon_size . "' style='vertical-align:middle;' />";
								$categories_string .= "</a> &nbsp;&nbsp;";
							}
						}
					}
				}
				break;
			case "tree":
				//$categories_string = "testing TREE";
				// Check if there's no category defined
				if ($catCount == 0) {
					$categories_string = "No Category Selected";
				}
				// check if there's only one category
				elseif ($catCount == 1) {
					// Iterate through categories 
					foreach ($item->category as $item_category) {
						// Check that it's a visible category
						if ($item_category->category_visible == 1) {
							// If it's a top level category, write simple categories string
							if ($item_category->parent_id == 0) {
								$categories_string = "<strong>Category:</strong> ";
								$categories_string .= "<img src='" . $catID_icons[$item_category->id]["cat_string"] . "' width='" . $cat_icon_size . "' height='" . $cat_icon_size . "' /> " . $item_category->category_title;
								}
							// If it's a sub category, write categories string with parent and sub
							else {
								$categories_string = "<strong>Category:</strong>";
								if($options["cat_parents"]) $categories_string .= "<br /><img src='" . $catID_icons[$item_category->parent_id]["cat_string"] . "' width='" . $cat_icon_size . "' height='" . $cat_icon_size . "' /> " . $catID_data[$item_category->parent_id]->category_title;
								$categories_string .= "<br />&nbsp;&nbsp;&nbsp;<img src='" . $catID_icons[$item_category->id]["cat_string"] . "' width='" . $cat_icon_size . "' height='" . $cat_icon_size . "' /> " . $item_category->category_title;
							}
						}
					}
				}
				// If more than one category, then iterate to write parent and sub categories as needed
				else {
					$categories_string = "<strong>Categories (" . $catCount . "):</strong> ";
					$previous_parentcat_id = -1;
					foreach ($item->category as $item_category) {
						// Check that it's a visible category
						if ($item_category->category_visible == 1) {
							
							if ($item_category->parent_id == 0) {
								$categories_string .= "<br /> <img src='" . $catID_icons[$item_category->id]["cat_string"] . "' width='" . $cat_icon_size . "' height='" . $cat_icon_size . "' /> " . $item_category->category_title;
							}
							else {
								// If it's the first sub with this parent category, write parent and sub categories
								if ($previous_parentcat_id != $item_category->parent_id) {
									if($options["cat_parents"]) $categories_string .= "<br /> <img src='" . $catID_icons[$item_category->parent_id]["cat_string"] . "' width='" . $cat_icon_size . "' height='" . $cat_icon_size . "' /> " . $catID_data[$item_category->parent_id]->category_title;
									$categories_string .= "<br />&nbsp;&nbsp;&nbsp;<img src='" . $catID_icons[$item_category->id]["cat_string"] . "' width='" . $cat_icon_size . "' height='" . $cat_icon_size . "' /> " . $item_category->category_title;
								}
								// otherwise, only write new sub category
								else {
									$categories_string .= "<br />&nbsp;&nbsp;&nbsp;<img src='" . $catID_icons[$item_category->id]["cat_string"] . "' width='" . $cat_icon_size . "' height='" . $cat_icon_size . "' /> " . $item_category->category_title;
								}
								// update previous parent id to track for next category
								$previous_parentcat_id = $item_category->parent_id;	
							}
						}
					}
				}
				break;
			default:
				//$categories_string = "testing DEFAULT";
				$categories_string .= " KML plugin error: invalid type (cats_in_balloons)";
		}
		return $categories_string;
	}	

	function get_verified_string($verified, $options){
    	// Populate verified string (if option is set and item is verified)
    	$verified_string = "";
    	if ($options["verified_in_balloons"] && $verified == 1) {
       		$verified_string = "" .
        	"<tr><td style='color:" . $options["verified_text_color"] . "; '><strong>Verified</strong></td></tr>" . PHP_EOL;
    	}
		return $verified_string;
	}

	function get_media_string($item_media, $options) {
	    // Populate media link strings (if option is set)
   		$media_string = "";
    	if($options["media_in_balloons"] && strlen($item_media["media_string"]) != 0) {
        	$media_string = "" .
        	"<tr><td><div style='max-width:" . $options["placemark_balloon_width"] . "px; overflow:auto; '>" . $item_media["media_string"] . "</div></td></tr>" . PHP_EOL;
		}
		return $media_string;
	}

	function get_categories_string($item, $catID_data, $catID_icons, $options){
		$categories_string = "";
        $categories_type = $options["cats_in_balloons"];
        if($categories_type != "none") {
			$categories_string = kml::generate_categories_string($item, $catID_data, $catID_icons, $options);
		}
		return $categories_string;
	}

	function get_categories_html($item, $catID_data, $catID_icons, $options) {
	    // Populate categories strings (if option is set)
    	$categories_html = "";
    	$categories_type = $options["cats_in_balloons"];
    	if($categories_type != "none") {
        	$categories_string = kml::generate_categories_string($item, $catID_data, $catID_icons, $options);
        	$categories_html = "" .
        	"<tr><td style='color:" . $options["categories_text_color"] . "; '>" . $categories_string . "</td></tr>" . PHP_EOL;
    	}
		return $categories_html;
	}

	function get_location_string($location_name, $options) {
	    // Populate location string (if option is set)
   		$location_string = "";
    	if ($options["location_in_balloons"]) {
        	$location_string = "" .
        	"<tr><td style='color:" . $options["location_text_color"] . "; '><strong>Location:</strong> " . htmlspecialchars($location_name) . "</td></tr>" . PHP_EOL;
		}
		return $location_string;
	}

	//=== Function to Process Categories ==
	//    ...(to build icons array, generate kml styles, and build data arrays for categories and subcat mapping)
	function process_categories($categories, &$catID_icons, &$kml_styles, &$catID_data, &$cat_to_subcats, $options) {

	$urlbase = url::base();
	// Iterate through categories...
	foreach ($categories as $cat) {
		// Write array of catIDs to icons
		$cat_icons = array();
		// if category image is set, then construct icon URLs using image and add to relevant array
		if(isset($cat->category_image)) {
			// if so, use it for the icon
			$cat_icons["placemark"] = htmlspecialchars($urlbase.Kohana::config("upload.relative_directory") . "/" .$cat->category_image);
			$cat_icons["folder"] = htmlspecialchars($urlbase.Kohana::config("upload.relative_directory")."/".$cat->category_image);
			$cat_icons["cat_string"] = htmlspecialchars($urlbase.Kohana::config("upload.relative_directory")."/".$cat->category_image_thumb);
		}
		// otherwise, construct icons using swatch generator
		else {
			// otherwise, use a color swatch
			$cat_icons["placemark"] = htmlspecialchars($urlbase.'swatch/?t=cir&c='.$cat->category_color.'&b=000000&w=32&h=32');
			$cat_icons["folder"] = htmlspecialchars($urlbase.'swatch/?t=rec&c='.$cat->category_color.'&b=000000&w=32&h=32');
			$cat_icons["cat_string"] = htmlspecialchars($urlbase.'swatch/?t=rec&c='.$cat->category_color.'&b=000000&w=10&h=10');
		}
		$catID_icons[$cat->id] = $cat_icons;
		
		// Write KML styles for category's placemarks and folder
		$kml_styles .= kml_style($cat, $catID_icons, $options);
		
		// Generate/Fill category data array (for easy cat data retrieval by cat id)
		$catID_data[$cat->id] = $cat;
		
		//== Generate & Fill cat to subcat mapping array (index = top-level cat id, value array contains subcats)
		// check if top-level category
		if($cat->parent_id == 0) {
			// if first time with this top-level category
			if(!isset($cat_to_subcats[$cat->id])) {
				// initialize mapping: write blank array for top-level category
				$cat_to_subcats[$cat->id] = array();
			}
		}
		// if not top-level category
		else {
			// if first time with this cat's parent category
			if(!isset($cat_to_subcats[$cat->parent_id])) {
				// initialize mapping: write blank array for subcategory
				$cat_to_subcats[$cat->parent_id] = array();
			}
			// this is a sub-category, map it to its parent category
			array_push($cat_to_subcats[$cat->parent_id], $cat);
		}
	}
	return true;
	}

	function process_incidents($items, &$catID_to_incidents) {
	//=== Iterate through incidents (build arrays of incidents in each category)
	foreach($items as $incident) {
		// for each category (and subcategory) that this incident belongs to (they are all in one array):
		foreach($incident->category as $cat) {
			// Check that it's a visible category
			if ($cat->category_visible == '1') {

				// Check if it's the first time we've seen this category
				if(!isset($catID_to_incidents[$cat->id])) {
					// if so, initialize mapping: write blank array for category
					$catID_to_incidents[$cat->id] = array();
				}
				// add incident to array of incidents for the category ID
				array_push($catID_to_incidents[$cat->id], $incident);
			}
		} 
	}
	return true;
	}

}
