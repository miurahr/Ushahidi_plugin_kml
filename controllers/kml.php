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
 * @package    Ushahidi - http://source.ushahididev.com
 * @module	   Feed Controller	
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
* 
*/

class Kml_Controller extends Controller
{
	public function index()
	{
		// Get Incidents...
		// See if limit parameter "l" is set
		if (isset($_GET['l']) AND !empty($_GET['l']))
		{
			$limit = (int) $_GET['l'];
			// If so, Get all incidents upto limit
			$incidents = ORM::factory('incident')
			->where('incident_active', '1')
			->orderby('incident_date', 'desc')
			->limit($limit)
			->find_all();
		}
		else
		{
			// Otherwise, Get all incidents (no limit)
			$incidents = ORM::factory('incident')
			->where('incident_active', '1')
			->orderby('incident_date', 'desc')
			->find_all();
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
		$view->items = $incidents;
		$view->categories = $categories;
		$view->render(TRUE);
	}
}