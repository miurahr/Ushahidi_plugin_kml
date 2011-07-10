<?php 

// NOTE: SWATCH Generator required!
//  This code relies on operation of your Ushahidi instance's SWATCH image generator (http://<siteurl>/swatch/?...).  

//--- load options array --
$options = Kohana::config("kml.options");
$options["kml_filename"]=$kml_filename;
//$options["kmz_filename"]=$kmz_filename;
$options["kmlFileName"]=$kmlFileName;
//$options["kmzFileName"]=$kmzFileName;

//=== Shared Data Variables and Arrays ==
$kml_styles = ""; 	// KML styles string for all categories
$catID_data = array();  // contains all categories, indexed by their category id
$catID_icons = array(); // contains array of icons for each category, indexed by cat ID and icon type
$cat_to_subcats = array();  // 
$catID_to_incidents = array();  // array with all category IDs, each with array of related incidents

//process_categories($kmlFile, $categories, $catID_icons, $kml_styles, $catID_data, $cat_to_subcats, $options);

//=============================================================================================
// Variables above, Action Functions below
//=============================================================================================

//=== function to write KML header ==
function kml_head($kml_name, $kml_tagline, $options) {
	$urlbase = url::base();
	$logo = Kohana::config("kml.logo");
	$kml_head =	"" . 
	"<?xml version='1.0' encoding='UTF-8'?>" . PHP_EOL .
	"<kml xmlns='http://www.opengis.net/kml/2.2' xmlns:gx='http://www.google.com/kml/ext/2.2' xmlns:kml='http://www.opengis.net/kml/2.2' xmlns:atom='http://www.w3.org/2005/Atom'>" . PHP_EOL .
	"	<Document>" . PHP_EOL . 
	"		<name><![CDATA[" . $kml_name . "]]></name>" . PHP_EOL . 
	"		<snippet maxLines='1'>Updated: " . date("Y-m-d H:i:s") . "</snippet>" . PHP_EOL .
	"		<open>1</open>" . PHP_EOL .
	"		<description>" . PHP_EOL .
	"			<![CDATA[<table width='" . $options["document_balloon_width"] . "' cellpadding='0' cellspacing='0'><tr><td>" . PHP_EOL .
	"			<p><strong>" . $kml_tagline . "</strong></p>" . PHP_EOL .
	"			<a href='" . $urlbase . "'>" . $urlbase . "</a><br />" . PHP_EOL .
	"			<p>Note: Reports are represented by multiple placemarks if they are in multiple categories.</p>" . PHP_EOL .
	"			<p style='color:" . $options["date_text_color"] . "; '><strong>This kml last updated</strong>: " . gmdate("D, d M Y H:i:s") . " GMT</p>" . PHP_EOL .
	//"			<p>Static KML file for offline use: <a href='" . $urlbase . Kohana::config("upload.relative_directory") . "/" . $options["kmz_filename"] . "'>" . $options["kmz_filename"] . "</a></p>" . PHP_EOL .
	"			<hr />" . PHP_EOL .
	"			<table width='100%' cellpadding='0' cellspacing='0'><tr><td align='left'>" . PHP_EOL .
	"				<img src='" . $urlbase . $logo["path"]. $logo["filename"]."' width='".$logo["width"]."' height='".$logo["height"]."' />" . PHP_EOL .
	"			</td><td align='right'>" . PHP_EOL .
	"				<a href='" . $urlbase . "'><strong>" . $urlbase . "</strong></a><br />" . PHP_EOL .
	"				<a href='" . $urlbase . "reports/submit/'>Submit a new report</a>" . PHP_EOL .
	"			</td></tr></table>" . PHP_EOL .
	"			</td></tr></table>]]>" . PHP_EOL .								
	"		</description>" . PHP_EOL .
	"		<styleUrl>#style_top_document</styleUrl>" . PHP_EOL .
	"		<Style id='style_top_document'>" . PHP_EOL .
	"			<ListStyle>" . PHP_EOL .
	"				<ItemIcon>" . PHP_EOL .
	"					<href>" . htmlspecialchars($urlbase . $logo["path"]. $logo["filename"]) . "</href>" . PHP_EOL .
    "				</ItemIcon>" . PHP_EOL .
	"				<maxSnippetLines>1</maxSnippetLines>" . PHP_EOL .
	"			</ListStyle>" . PHP_EOL .
	"			<BalloonStyle>" . PHP_EOL .
	"				<text><![CDATA[<html><body><h2 style='color:" . $options["title_text_color"] . "; '>$[name]</h2>$[description]</body></html>]]></text>" . PHP_EOL .
	"			</BalloonStyle>" . PHP_EOL .
	"		</Style>" . PHP_EOL;	
	return $kml_head;
}

//=== function to generate StyleMap and Styles for one category's placemarks and folders ==
function kml_style($category, $catID_icons, $options) {
	$urlbase = url::base();
	$kml_style = "" .
	"		<StyleMap id='stylemap_categoryID_" . $category->id . "'>" . PHP_EOL .
	"			<Pair>" . PHP_EOL .
	"				<key>normal</key>" . PHP_EOL .
	"				<styleUrl>#style_categoryID_" . $category->id . "_n</styleUrl>" . PHP_EOL .
	"			</Pair>" . PHP_EOL .
	"			<Pair>" . PHP_EOL .
	"				<key>highlight</key>" . PHP_EOL .
	"				<styleUrl>#style_categoryID_" . $category->id . "_h</styleUrl>" . PHP_EOL .
	"			</Pair>" . PHP_EOL .
	"		</StyleMap>" . PHP_EOL .
	"		<Style id='style_categoryID_" . $category->id . "_n'>" . PHP_EOL .
	"			<IconStyle>" . PHP_EOL .
	"				<scale>" . $options['normal_icon_scale'] . "</scale>" . PHP_EOL .
	"				<Icon>" . PHP_EOL .
	"					<href>" . $catID_icons[$category->id]["placemark"] . "</href>" . PHP_EOL .
	"				</Icon>" . PHP_EOL .
	"				<hotSpot x='0.5' y='0.5' xunits='fraction' yunits='fraction' />" . PHP_EOL .
	"			</IconStyle>" . PHP_EOL .
	"			<LabelStyle>" . PHP_EOL .
	"				<scale>" . $options['normal_label_scale'] . "</scale>" . PHP_EOL .
	"			</LabelStyle>" . PHP_EOL .
	"			<BalloonStyle>" . PHP_EOL .
	"				<text><![CDATA[<html><body><h2 style='color:" . $options["title_text_color"] . "; '>$[name]</h2>$[description]</body></html>]]></text>" . PHP_EOL .
	"			</BalloonStyle>" . PHP_EOL .
	"		</Style>" . PHP_EOL .
	"		<Style id='style_categoryID_" . $category->id . "_h'>" . PHP_EOL .
	"			<IconStyle>" . PHP_EOL .
	"				<scale>" . $options['highlight_icon_scale'] . "</scale>" . PHP_EOL .		
	"				<Icon>" . PHP_EOL .
	"					<href>" . $catID_icons[$category->id]["placemark"] . "</href>" . PHP_EOL .
	"				</Icon>" . PHP_EOL .
	"				<hotSpot x='0.5' y='0.5' xunits='fraction' yunits='fraction' />" . PHP_EOL .
	"			</IconStyle>" . PHP_EOL .
	"			<LabelStyle>" . PHP_EOL .
	"				<scale>" . $options['highlight_label_scale'] . "</scale>" . PHP_EOL .
	"			</LabelStyle>" . PHP_EOL .
	"			<BalloonStyle>" . PHP_EOL .
	"				<text><![CDATA[<html><body><h2 style='color:" . $options["title_text_color"] . "; '>$[name]</h2>$[description]</body></html>]]></text>" . PHP_EOL .
	"			</BalloonStyle>" . PHP_EOL .
	"		</Style>" . PHP_EOL;

	$kml_style .= "" .
	"		<Style id='style_categoryID_" . $category->id . "_folder'>" . PHP_EOL .
	"			<ListStyle>" . PHP_EOL .
	"				<ItemIcon>" . PHP_EOL .
	"					<href>" . $catID_icons[$category->id]["folder"] . "</href>" . PHP_EOL .
	"				</ItemIcon>" . PHP_EOL .
	"				<maxSnippetLines>" . $options["folder_snippets"] . "</maxSnippetLines>" . PHP_EOL .
	"			</ListStyle>" . PHP_EOL .
	"		</Style>" . PHP_EOL;
	return $kml_style;
}

//=== function to write Folder header for one category ==
function folder_head($category, $options) {
	$category_snippet = kml::get_category_folder_head_snippet($category);
	$kml_folder_head = "" . 
	"		<Folder id='folder_categoryID_" . $category->id . "'>" . PHP_EOL .
	"			<name><![CDATA[" . $category->category_title . "]]></name>" . PHP_EOL .
	"			" . $category_snippet . PHP_EOL .
	"			<visibility>" . $options["visibility"] . "</visibility>" . PHP_EOL .
	"			<open>0</open>" . PHP_EOL .
	"			<styleUrl>#style_categoryID_" . $category->id . "_folder</styleUrl>" . PHP_EOL;
	return $kml_folder_head;
}

//=== function to write placemark for one item ==
function placemark($item, $cat_id, $catID_data, $catID_icons, $options) {
	$urlbase = url::base();
	$logo = Kohana::config("kml.logo");

	$kml_placemark = "" .
	"			<Placemark id='placemark_report_" . $item->id . "_cat_" . $cat_id . "'>" . PHP_EOL .
	"				<name><![CDATA[" . htmlspecialchars($item->incident_title) . "]]></name>" . PHP_EOL .
	"				<TimeStamp><when>" . preg_replace('/^(\d{2,4}-\d{1,2}-\d{1,2})\s(\d{1,2}:\d{1,2}:\d{1,2})$/',"$1T$2+09:00", $item->incident_date) . "</when></TimeStamp>" . PHP_EOL .
	"				<snippet maxLines='0'></snippet>" . PHP_EOL .
	"				<visibility>" . $options["visibility"] . "</visibility>" . PHP_EOL .
	"				<styleUrl>#stylemap_categoryID_" . $cat_id . "</styleUrl>" . PHP_EOL .
	"				<description>" . PHP_EOL .
	"					<![CDATA[<table width='" . $options["placemark_balloon_width"] . "' cellpadding='0' cellspacing='2'>" . PHP_EOL .
							kml::get_verified_string($item->incident_verified, $options) .
	"						<tr><td><div style='max-height:100px; overflow:auto; color:" . $options["description_text_color"] . ";'>" . htmlspecialchars($item->incident_description) . "</div></td></tr>" . PHP_EOL .
							kml::get_media_string(kml::get_item_media($item), $options) .
							kml::get_categories_html($item, $catID_data, $catID_icons, $options) .
							kml::get_location_string($item->location->location_name, $options) .
	"						<tr><td>" . PHP_EOL .
	"							<table width='100%' cellpadding='0' cellspacing='0'><tr><td align='left' style='color:" . $options["date_text_color"] . "; '>" . PHP_EOL .
	"								<strong>Submitted:</strong> " . $item->incident_date . PHP_EOL .
	"							</td><td align='right'>" . PHP_EOL .
	"								<a href='" . $urlbase.'reports/view/'.$item->id . "'>More Information</a>" . PHP_EOL .
	"							</td></tr></table>" . PHP_EOL .
	"						</td></tr>" . PHP_EOL .
	"						<tr><td><hr /></td></tr>" . PHP_EOL .
	"						<tr><td>" . PHP_EOL .
	"							<table width='100%' cellpadding='0' cellspacing='0'><tr><td align='left' width='" . $logo["width"] . "px'>" . PHP_EOL .
	"								<img src='" . $urlbase . $logo["path"] . $logo["filename"] . "' width='" . $logo["width"] . "' height='" . $logo["height"] . "' />" . PHP_EOL .
	"							</td><td colspan='2' align='right'>" . PHP_EOL .
	"								<a href='" . $urlbase . "'><strong>" . $urlbase . "</strong></a><br />" . PHP_EOL .
	"								<a href='" . $urlbase . "reports/submit/'>Submit a new report</a>" . PHP_EOL .
	"							</td></tr></table>" . PHP_EOL .
	"						</td></tr>" . PHP_EOL .
	"					</table>]]>" . PHP_EOL .
	"				</description>" . PHP_EOL .
					$categories_string = kml::get_categories_string($item, $catID_data, $catID_icons, $options);
					generate_extended_data($item, $cat_id, $categories_string, $options) .
	"				<Point>" . PHP_EOL .
	"					<coordinates>" . $item->location->longitude . "," . $item->location->latitude . "</coordinates>" . PHP_EOL .
	"				</Point>" . PHP_EOL .
	"			</Placemark>" . PHP_EOL;
	return $kml_placemark;
}

//=== function to generate Extended Data section (if enabled in options) ==
function generate_extended_data($item, $cat_id, $categories_string, $options) {
	$urlbase = url::base();
	$kml_extended_data = "";
	if ($options["extended_data"]) {
		$kml_extended_data = "" .
		"				<ExtendedData>" . PHP_EOL .
		"					<Data name='report_id'><value><![CDATA[" . $item->id . "]]></value></Data>" . PHP_EOL .
		"					<Data name='report_title'><value><![CDATA[" . htmlspecialchars($item->incident_title) . "]]></value></Data>" . PHP_EOL .
		"					<Data name='report_description'><value><![CDATA[" . htmlspecialchars($item->incident_description) . "]]></value></Data>" . PHP_EOL .
		"					<Data name='report_date'><value><![CDATA[" . $item->incident_date . "]]></value></Data>" . PHP_EOL .
		"					<Data name='report_url'><value><![CDATA[" . $urlbase . "reports/view/" . $item->id . "]]></value></Data>" . PHP_EOL .
		"					<Data name='report_category'><value><![CDATA[" . $cat_id . "]]></value></Data>" . PHP_EOL .
		"					<Data name='report_categories_string'><value><![CDATA[" . $categories_string . "]]></value></Data>" . PHP_EOL .
		"					<Data name='report_location_string'><value><![CDATA[" . $item->location->location_name . "]]></value></Data>" . PHP_EOL .
		"					<Data name='report_latitude'><value><![CDATA[" . $item->location->latitude . "]]></value></Data>" . PHP_EOL .
		"					<Data name='report_longitude'><value><![CDATA[" . $item->location->longitude . "]]></value></Data>" . PHP_EOL .
		"				</ExtendedData>" . PHP_EOL;
	}
	return $kml_extended_data;
}

//=== function to write Folder footer for one category ==
function folder_foot() {
	$kml_folder_foot = "" . 
	"		</Folder>" . PHP_EOL;
	return $kml_folder_foot;
}

//=== function to write KML footer ==
function kml_foot() {
	$kml_foot = "" .
	"	</Document>" . PHP_EOL .
	"</kml>" . PHP_EOL;
	return $kml_foot;
}


//=============================================================================================
// Action Functions above, Logic Functions below
//=============================================================================================
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


//=============================================================================================
// Generate and Write KML 
//=============================================================================================

	// we need to compress data after views generation.
	// we write it file where controller demand once.
	process_categories($categories, $catID_icons, $kml_styles, $catID_data, $cat_to_subcats, $options);

	fwrite($kmlFile, kml_head($kml_name, $kml_tagline, $options));
	fwrite($kmlFile, $kml_styles);

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
	
	//=== Iterate through top-level categories  (make array of subcategories)
	foreach ($cat_to_subcats as $cat_id => $subcats) {

		// For each top-level category, write folder header
		fwrite($kmlFile, folder_head($catID_data[$cat_id], $options));
		// Iterate through subcategories (if any) for that top-level category
		foreach ($subcats as $subcat) {
			// For each subcategory, write folder header
			fwrite($kmlFile, folder_head($subcat, $options));

			// If this subcategory has one or more incidents tagged with it...
			if(isset($catID_to_incidents[$subcat->id])) {
				// then iterate through incidents (if any) attached to that cat ID
				foreach ($catID_to_incidents[$subcat->id] as $item) {
					// write incident/item's placemark
					fwrite($kmlFile, placemark($item, $subcat->id, $catID_data, $catID_icons, $options));
				} 
			}
			// Write folder footer for the sub category
			fwrite($kmlFile, folder_foot());
		}
		// If the parent category has one or more incidents tagged with it...
		if(isset($catID_to_incidents[$cat_id])) {
			// then iterate through incidents attached to that cat ID
			foreach($catID_to_incidents[$cat_id] as $item) {
				// write incident/item's placemark
				fwrite($kmlFile, placemark($item, $cat_id, $catID_data, $catID_icons, $options));
			} 
		}
		// Write folder footer for top-level category
		fwrite($kmlFile, folder_foot());
	}
	fwrite($kmlFile, kml_foot());

?>
