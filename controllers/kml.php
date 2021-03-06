<?php defined('SYSPATH') or die('No direct script access.');
/**
 * KML Controller
 * Generates KML with PlaceMarkers and Category Styles
 * organized by categories and subcategories
 * includes high quality balloon styling
 * includes icons from Ushahidi swatch generator, which work well in both Google Earth and Maps
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author	   Ushahidi Team <team@ushahidi.com> 
 * @author     Hiroshi Miura <miurahr@osmf.jp>
 * @package    Ushahidi - http://source.ushahididev.com
 * @module	   Feed Controller	
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
* 
*/

class Kml_Controller extends Controller
{
	private function get_params()
	{
		// ?l=<n> means limit number of items <= n
		// ?l=0 means don't limit
		// without limit, default limit will be applied.
		if (isset($_GET['l']) AND !empty($_GET['l']))
		{
			$limit = $this->input->xss_clean($_GET['l']);
			$limit = (isset($limit) AND intval($limit) >0)?intval($limit):0;
		} else {
			$limit = -1;
		}

		if (isset($_GET['cat']) AND !empty($_GET['cat'])) {
			$category_id = $this->input->xss_clean($_GET['cat']);
			$category_id = (isset($category_id) AND intval($category_id) > 0)?
							intval($category_id):0;
		} else {
			$category_id = 0;
		}

		// cron on?
		if (isset($_GET['cron']) AND !empty($_GET['cron']))
		{
			$cron_flag = true;
		} else {
			$cron_flag = false;
		}
		return array($limit, $category_id, $cron_flag);
	}

	private function set_filename($limit, $category_id, $cron_flag)
	{
		$kml_filename = "latest.kml";  // filename for exported KML file
		$kmz_filename = "latest.kmz";  // filename for exported KMZ file

		if ($category_id == 0)
		{
			$cat_name = "all_";
		}
		 elseif ($category_id > 0)
		{
			$cat_name = "cat".strval($category_id)."_";
		}
		else 
		{
			$cat_name = "";
		}

		if ($limit == -1 && $category_id == 0 && $cron_flag == true)
		{ 
			// cron request without parameter 
			// generate default kml file that is distributed through CDN
		}
		elseif ($limit == 0)
		{
			$kml_filename = $cat_name."full_".$kml_filename;
			$kmz_filename = $cat_name."full_".$kmz_filename;
		}
		elseif ($limit == -1) 
		{
			$kml_filename = $cat_name.$kml_filename;
			$kmz_filename = $cat_name.$kmz_filename;
		}
		else
		{
			$kml_filename = $cat_name.strval($limit)."_".$kml_filename;
			$kmz_filename = $cat_name.strval($limit)."_".$kmz_filename;
		}
		return array($kml_filename, $kmz_filename);
	}

	//=== function to zip KML into KMZ file
	private function create_kmz($kmlFileName, $kmzFileName){

		kohana::log('info', "generating kmz file");
		$zip = new ZipArchive();

		if ($zip->open("$kmzFileName", ZIPARCHIVE::CREATE|ZIPARCHIVE::OVERWRITE)!==TRUE) {
			kohana::log('error', "cannot open kmz file");
			echo("cannot open <". $kmzFileName .">\n");
		}

		kohana::log('info', "adding kml to kmz file");
		$zip->addFile($kmlFileName, "doc.kml");
		//$zip->addFile("plugins/kml2/views/circle_border.png", "files/circle_border.png");
		$zip->close();
		kohana::log('info', "closed kmz file");
		return $zip;
	}

	public function index()
	{
		// 0. define default limitation for google maps
		// 1. get limit
		// 2. set filename
		// 3. ditect cache
		// 4.1. has cache -> return file
		// 4.2. no cache -> get data from sql, and write in view

		// 1.
		list($limit, $category_id, $cron_flag) = $this::get_params();

		// use cdn when normal request
		if ($limit == -1 && $cron_flag == false && $category_id == 0) { 
			url::redirect(Kohana::config("kml.cdn_kml_url"));
		}

		// 2.
		list($kml_filename, $kmz_filename) = 
					$this::set_filename($limit, $category_id, $cron_flag);

		if ($limit == -1)
		{
			$default_limit = Kohana::config('kml.default_limit');
			$limit = isset($default_limit)?$default_limit:1000;
		}

  		// internal path to KML/KMZ file in uploads directory
		$kmlFileName = Kohana::config('upload.directory', TRUE) . $kml_filename;
		$kmzFileName = Kohana::config('upload.directory', TRUE) . $kmz_filename;

		// 3.
		//=== Caching Options ==
		// TOD: Add something to check if new incidents have come in and re-create files only if needed?
		$cache_secs = Kohana::config('kml.cache_secs',TRUE);
		$cache_on = Kohana::config('kml.cache_on',TRUE);
		if ($cache_on && file_exists($kmzFileName)
			&& (time() - filemtime($kmzFileName) < $cache_secs)) {
			$use_cache = true;
			// 4.1
			$incident_items = NULL;
			$categories = NULL;
		}
		else
		{
			$use_cache = false;
			// 4.2
			$incidents = ORM::factory('incident')
					->orderby('incident_date', 'desc');
			if ($category_id == 0)
			{
					$incidents = $incidents->where('incident_active', '1');
			}
			else 
			{
					$incidents = $incidents->join('incident_category', 
						 		'incident_category.incident_id','incident.id',
						   		'INNER')
							->where(array('incident_category.category_id'
						        => strval($category_id),
						      'incident_active' => '1'));
			}
			$incident_items = ($limit != 0) ?$incidents->find_all($limit,0)
											:$incidents->find_all();
		}
		// Get all Categories...
		$categories = ORM::factory('category')
						->where('category_visible', '1')
						->find_all();

		//header("Content-Type: application/vnd.google-earth.kml+xml");
		header("Content-Type: application/vnd.google-earth.kmz");
		//header("Content-Disposition: attachment; filename=".time().".kml");
		//header("Content-Disposition: attachment; filename=ushahidi_link.kml");
		header("Content-Disposition: attachment; filename=".time().".kmz");
		header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
		header("Cache-Control: cache, must-revalidate");
		header("Pragma: public");

		$view = new View("kml");
		$view->kml_name = htmlspecialchars(Kohana::config('settings.site_name'));
		$view->kml_tagline = htmlspecialchars(Kohana::config('settings.site_tagline'));
		$view->kml_filename = $kml_filename;
	//	$view->kmz_filename = $kmz_filename;
		$view->kmlFileName = $kmlFileName;
	//	$view->kmzFileName = $kmzFileName;

		$view->items = $incident_items;
		$view->categories = $categories;

		if (!$use_cache)
		{
			kohana::log('info', "generating new kml file");
			$kmlFile = fopen($kmlFileName, "w");
			if (flock($kmlFile, LOCK_EX)) { // do an exclusive lock
				kohana::log('info', "Got lock on $kmlFileName");
				$view->kmlFile = $kmlFile;

				$view->render(FALSE);

				flock($kmlFile, LOCK_UN); // release the lock
				fclose($kmlFile);
				kohana::log('info', " ...locked and closed $kmlFileName");
			} else {
				kohana::log('error', "Couldn't lock $kmlFileName");
   			}
		}

		if ( ! $cron_flag )
		{
		    if (Kohana::config('kml.compress'))
			{
				$this::create_kmz($kmlFileName, $kmzFileName);
				echo readfile($kmzFileName);
			}
			else
			{
				echo readfile($kmlFileName);
			}
		}
	}
}
