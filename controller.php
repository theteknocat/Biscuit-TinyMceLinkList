<?php
/**
 * Generate the Javascript code for Tiny MCE containing a list of all links in the site. Other plugins can add their own items to the list by responding
 * to the "build_mce_link_list" event.  They must accept a reference to this object as a second parameter, and call the "add_to_list" method of this
 * object passing it an array of associative arrays, each of which contains a title and a URL.
 *
 * @package Modules
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 */
class TinyMceLinkList extends AbstractModuleController {
	/**
	 * Build the link list and render it
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function action_index() {
		$this->link_list = $this->get_page_links_as_js_array();

		// In case any modules need to add additional items to the link list:
		Event::fire("build_mce_link_list");

		$this->set_view_var("link_list",$this->link_list);
		Response::content_type("text/javascript");
		$this->Biscuit->render_with_template(false);
		$this->render();
	}
	/**
	 * Compile a list of links for pages in the site belonging to a given parent page. Starting default parent is 0 (top-level).
	 *
	 * @param int $parent_id The ID of the parent page to find links within
	 * @param int $max_user_level Maximum allowed user level for the pages included in the list
	 * @param int $indent The indent level
	 * @return void
	 * @author Peter Epp
	 */
	private function get_page_links_as_js_array() {
		$pages = $this->Biscuit->ExtensionNavigation()->all_pages();
		$curr_user = $this->Biscuit->ModuleAuthenticator()->active_user();
		$curr_user_level = $curr_user->user_level();
		$sorted_pages = $this->Biscuit->ExtensionNavigation()->sort_pages($pages);
		if ($pages) {
			$js_array = '["--- Main Menu ---",""],'.$this->Biscuit->ExtensionNavigation()->render_pages_hierarchically($sorted_pages, 0, Navigation::WITH_CHILDREN, 'modules/tiny_mce_link_list/views/js_array_list.php',array('curr_user_level' => $curr_user_level));
			$other_menus = $this->Biscuit->ExtensionNavigation()->other_menus();
			if (!empty($other_menus)) {
				foreach ($other_menus as $menu) {
					$other_menu_js_array = $this->Biscuit->ExtensionNavigation()->render_pages_hierarchically($sorted_pages, $menu->id(), Navigation::WITH_CHILDREN, 'modules/tiny_mce_link_list/views/js_array_list.php',array('curr_user_level' => $curr_user_level));
					if (!empty($other_menu_js_array)) {
						$js_array .= ',["--- '.$menu->name().' ---",""],'.$other_menu_js_array;
					}
				}
			}
			$orphans_js_array = $this->Biscuit->ExtensionNavigation()->render_pages_hierarchically($sorted_pages, NORMAL_ORPHAN_PAGE, Navigation::WITH_CHILDREN, 'modules/tiny_mce_link_list/views/js_array_list.php',array('curr_user_level' => $curr_user_level));
			if (!empty($orphans_js_array)) {
				$js_array .= ',["--- Orphan Pages ---",""],'.$orphans_js_array;
			}
			return $js_array;
		}
		return null;	// Houston, we have a problem. If no pages exist then the site won't work, but you never know.
	}
	/**
	 * Add links to the list
	 *
	 * @param array $list_items An indexed array of associative arrays in the format: array(0 => array("title" => "My Title", "url" => "http://mydomain.com/my_page"))
	 * @param string $section_title Optional - title for the section of links in the list. If not provided, it will be separated by a row of dashes.
	 * @return void
	 * @author Peter Epp
	 */
	public function add_to_list($list_items,$section_title = null) {
		if (empty($list_items)) {
			return;
		}
		if (!empty($section_title)) {
			$this->link_list .= ',
	["--- '.$section_title.' ---",""],';
		}
		else {
			$this->link_list .= ',
	["-------------------------",""],';
		}
		$list_content = array();
		foreach ($list_items as $item) {
			$list_content[] = '
	["'.$item['title'].'","'.$item['url'].'"]';
		}
		$this->link_list .= implode(",",$list_content);
	}
	/**
	 * Run migrations required for module to be installed properly
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function install_migration() {
		$link_list_page = DB::fetch_one("SELECT `id` FROM `page_index` WHERE `slug` = 'tiny_mce_link_list'");
		if (!$link_list_page) {
			// Add tiny_mce_link_list page:
			DB::insert("INSERT INTO `page_index` SET `parent` = 9999999, `slug` = 'tiny_mce_link_list', `title` = 'Tiny MCE Link List'");
			// Get module row ID:
			$module_id = DB::fetch_one("SELECT `id` FROM `modules` WHERE `name` = 'TinyMceLinkList'");
			// Remove TinyMceLinkList from module pages first to ensure clean install:
			DB::query("DELETE FROM `module_pages` WHERE `module_id` = {$module_id} AND `page_name` = 'tiny_mce_link_list'");
			// Add TinyMceLinkList to tiny_mce_link_list page:
			DB::insert("INSERT INTO `module_pages` SET `module_id` = {$module_id}, `page_name` = 'tiny_mce_link_list', `is_primary` = 1");
		}
	}
	/**
	 * Run migrations to properly uninstall the module
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function uninstall_migration() {
		$module_id = DB::fetch_one("SELECT `id` FROM `modules` WHERE `name` = 'TinyMceLinkList'");
		DB::query("DELETE FROM `page_index` WHERE `slug` = 'tiny_mce_link_list'");
		DB::query("DELETE FROM `module_pages` WHERE `module_id` = ".$module_id);
	}
}
?>