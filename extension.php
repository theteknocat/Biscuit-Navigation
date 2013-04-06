<?php
/**
 * A collection of rendering functions that draw various navigation widgets
 *
 * @package Extensions
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 **/
class Navigation extends AbstractExtension {
	/**
	 * Constant to use when calling the render_pages_hierarchically method and you want children included instead of just "true"
	 */
	const WITH_CHILDREN = true;
	/**
	 * Constant to use when calling the render_pages_hierarchically method and you do not want children included instead of just "false"
	 */
	const WITHOUT_CHILDREN = false;
	/**
	 * Place to track the current tiger stripe state (odd or even) for any give list being striped
	 *
	 * @var array
	 */
	private $tiger_stripe_states = array();
	/**
	 * Place to cache all the pages so we only need to fetch them all once
	 *
	 * @var string
	 */
	private $_pages;
	/**
	 * Place to store extra breadcrumbs that other modules may want to provide during breadcrumb building
	 *
	 * @var array
	 */
	private $_extra_breadcrumbs = array();
	/**
	 * Place to cache ids of top level parents so it only has to run the logic once for each page
	 *
	 * @var array
	 */
	private $_top_level_parents = array();
	/**
	 * Place to cache list of other top-level menus so it only needs to fetch them once
	 *
	 * @var array
	 */
	private $_other_menus = null;
	/**
	 * Whether or not to exclude items marked for exclusion from the navigation when rendering list menus
	 *
	 * @var bool
	 */
	private $_menu_exclude_items = true;
	/**
	 * Array of items for the administration menu
	 *
	 * @var array
	 */
	private $_admin_menu_items = array();
	/**
	 * Reference to menu factory object
	 *
	 * @var string
	 */
	private $_menu_factory;
	/**
	 * Ensure that extension gets instantiated
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function run() {
		// Noop. Force instantiation
	}
	
	/**
	 * Set whether or not to exclude items from menus that are set to be excluded in the page_index table. This method is for use by
	 * other modules/extensions that call render_pages_hierarchically() directly rather than using the menu methods provided by this extension.
	 *
	 * @param bool $value 
	 * @return void
	 * @author Peter Epp
	 */
	public function set_page_exclusion($value) {
		if (is_bool($value)) {
			$this->_menu_exclude_items = $value;
		}
	}
	/**
	 * Render a hierarchical list of pages using the sorting and hierarchical rendering methods.
	 *
	 * @param $menu_name_or_id string|int Optional. Either the parent menu ID or the menu variable name per the "menus" DB table
	 * @param $exclude_items bool Optional. Whether or not to exclude items marked for exclusion from menus in the page_index table. Defaults to true
	 * @return string HTML code
	 * @author Peter Epp
	 */
	public function render_list_menu($menu_name_or_id = 0, $exclude_items = true) {
		$this->_menu_exclude_items = $exclude_items;
		$pages = $this->all_pages();
		$sorted_pages = $this->sort_pages($pages);
		return $this->render_pages_hierarchically($sorted_pages,$menu_name_or_id);
	}

	/**
	 * Render all top-level pages as a string of text links using a view file
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function render_text_mainmenu() {
		$pages = $this->Biscuit->page_factory->find_all_by('parent',0,array("sort_order" => "ASC"),'`exclude_from_nav` = 0');
		if (!empty($pages)) {
			$view_vars = array(
				"pages" => $pages,
				"Biscuit" => $this->Biscuit,
				"Page" => $this->Biscuit->Page,
				"Navigation" => $this
			);
			return Crumbs::capture_include("navigation/views/text_mainmenu.php", $view_vars);
		}
		return '';
	}

	/**
	 * Render all sub-pages of the current page (if any exist) as text links using a view file, either with a default or specified filename
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function render_text_submenu($menu_name_or_id = "", $view_file = "text_submenu.php") {
		if (empty($menu_name_or_id)) {
			$menu_name_or_id = $this->Biscuit->Page->id();
		}
		if (is_string($menu_name_or_id)) {
			$menu = $this->_menu_factory()->find_by('var_name',$menu_name_or_id);
			if ($menu) {
				$parent_id = $menu->id();
			}
		} else if (is_int($menu_name_or_id)) {
			$parent_id = $menu_name_or_id;
		}
		if (!empty($parent_id)) {
			$pages = $this->Biscuit->page_factory->find_all_by('parent',$parent_id,array("sort_order" => "ASC"),'`exclude_from_nav` = 0');
			if ($pages) {
				$menu = Crumbs::capture_include("navigation/views/".$view_file, array(
					"pages" => $pages,
					"Biscuit" => $this->Biscuit,
					"Page" => $this->Biscuit->Page,
					"Navigation" => $this
				));
				return $menu;
			}
		}
		return '';
	}
	/**
	 * Render breadcrumbs from the home page to the current page.
	 *
	 * @param string $separator 
	 * @return void
	 * @author Peter Epp
	 */
	function render_bread_crumbs($home_label = null,$separator = "&raquo;") {
		if ($this->Biscuit->Page->slug() == 'index') {
			return '';
		}
		if (empty($home_label)) {
			$home_label = HOME_TITLE;
		}
		$curr_page = $this->Biscuit->Page;
		$other_menu_ids = $this->other_menu_ids();
		$pages = $this->all_pages();
		$crumbs = array();
		$crumb_tags = array();
		// Build an array of page models from the current page back to it's parent:
		$curr_id = $curr_page->id();
		while ($curr_id != 0 && $curr_id != NORMAL_ORPHAN_PAGE && $curr_id != HIDDEN_ORPHAN_PAGE && !in_array($curr_id,$other_menu_ids)) {
			foreach ($pages as $page) {
				if ($page->id() == $curr_id) {
					$crumbs[] = $page;
					$curr_id = $page->parent();
					break;
				}
			}
		}
		if (!empty($crumbs)) {
			$crumbs = array_reverse($crumbs);
			$crumbs = array_values($crumbs);
			// Now build array of links from the array of page models:
			if ($curr_page->slug() != 'index') {
				$crumb_tags[] = '<a href="/" class="crumb">'.__($home_label).'</a>';
			}
			Event::fire('build_breadcrumbs',$this);
			$last_breadcrumb_is_secure = false;
			foreach ($crumbs as $crumb) {
				$crumb_title = $crumb->navigation_title();
				if ($crumb->id() != $curr_page->id() || !empty($this->_extra_breadcrumbs)) {
					if ($crumb->force_secure()) {
						$url = SECURE_URL.'/'.$crumb->slug();
					} else {
						$url = STANDARD_URL.'/'.$crumb->slug();
					}
					$crumb_tags[] = '<a href="'.$url.'" class="crumb">'.__($crumb_title).'</a>';
				} else {
					$crumb_tags[] = __($crumb_title);
				}
				$last_breadcrumb_is_secure = $crumb->force_secure();
			}
			if (!empty($this->_extra_breadcrumbs)) {
				foreach ($this->_extra_breadcrumbs as $index => $crumb) {
					if ($index+1 != count($this->_extra_breadcrumbs)) {
						$crumb_tags[] = '<a href="'.$crumb['url'].'" class="crumb">'.__($crumb['label']).'</a>';
					} else {
						$crumb_tags[] = __($crumb['label']);
					}
				}
			}
		}
		return implode(' '.$separator.' ',$crumb_tags);
	}
	/**
	 * Add a breadcrumbs URL and label to the list. This is a method meant to be called by other modules responding to the build_breadcrumbs event.
	 *
	 * @param string $url 
	 * @param string $label 
	 * @return void
	 * @author Peter Epp
	 */
	public function add_breadcrumb($url,$label) {
		$this->_extra_breadcrumbs[] = array(
			'url'   => $url,
			'label' => $label
		);
	}
	/**
	 * Trace the top-level parent ID of a given page or the current one if none provided
	 *
	 * @param Page|null $curr_page Either an instance of a Page model or empty to use the current page 
	 * @return void
	 * @author Peter Epp
	 */
	public function trace_top_level_parent($curr_page = null) {
		if (empty($curr_page)) {
			$curr_page = $this->Biscuit->Page;
		}
		if (empty($this->_top_level_parents[$curr_page->id()])) {
			$pages = $this->all_pages();
			$other_menu_ids = $this->other_menu_ids();
			$curr_id = $curr_page->id();
			$page_id = 0;
			while ($curr_id != 0 && $curr_id != NORMAL_ORPHAN_PAGE && $curr_id != HIDDEN_ORPHAN_PAGE && !in_array($curr_id,$other_menu_ids)) {
				foreach ($pages as $page) {
					if ($page->id() == $curr_id) {
						$curr_id = $page->parent();
						$page_id = $page->id();
						break;
					}
				}
			}
			$this->_top_level_parents[$curr_page->id()] = $page_id;
		}
		return $this->_top_level_parents[$curr_page->id()];
	}
	/**
	 * Sort an array of page models into a new array grouped by parent ID with each subset sorted by DB sort order. This can then be used to render a list of pages in the
	 * correct hierarchy using the render_pages_hierarchically() method. The resulting array might look something like this:
	 * 
	 * array(
	 *     0 => array(
	 *         1 => [page],
	 *         2 => [page],
	 *         3 => [page]
	 *     ),
	 *     5 => array(
	 *         5 => [page],
	 *         10 => [page],
	 *         15 => [page]
	 *     ),
	 *     999999 => array(
	 *         1 => [page],
	 *         2 => [page],
	 *         3 => [page]
	 *     )
	 * )
	 *
	 * @param array $pages References to Page models
	 * @return void
	 * @author Peter Epp
	 */
	public function sort_pages($pages) {
		// Build the arrays needed for array_multisort:
		$sort_order = array();		// Contains the sort orders
		$title = array();			// Contains the page titles
		$pages_by_parent = array();	// An array of the page's pertinent data (id, sort_order and title) that array_multisort can sort using the above 2 arrays
		$pages_by_id = array();		// Place to reorganize the pages into in order to build the sorted array out of the sorted results of array_multisort
		foreach ($pages as $index => $page) {
			$pages_by_id[$page->id()] = $page;
			$sort_order[$page->parent()][] = $page->sort_order();
			$title[$page->parent()][] = $page->title();
			$pages_by_parent[$page->parent()][] = array('id' => $page->id(), 'sort_order' => $page->sort_order(), 'title' => $page->title());
		}
		foreach ($pages_by_parent as $parent_id => $pages_array) {
			$sorted_pages_array = $this->do_sort($sort_order[$parent_id],$title[$parent_id],$pages_array);
			foreach ($sorted_pages_array as $pages_sorted) {
				$sorted_pages[$parent_id][] = $pages_by_id[$pages_sorted['id']];
			}
		}
		return $sorted_pages;
	}
	/**
	 * Use array multisort to sort an array of page data first by sort order then by title
	 *
	 * @param array $sort_order 
	 * @param array $title 
	 * @param array $pages_array 
	 * @return array
	 * @author Peter Epp
	 */
	private function do_sort($sort_order,$title,$pages_array) {
		array_multisort($sort_order, SORT_ASC, $title, SORT_ASC,$pages_array);
		return $pages_array;
	}
	/**
	 * Render pages in a hierarchical fashion from a view file. The pages must be sorted using the sort_pages method prior to passing to this method.
	 *
	 * @param array $pages Array of page models sorted by PageContent::sort_pages() method.
	 * @param int $current_parent_id ID of the current page parent
	 * @param bool $with_children Whether or not to render child pages within the hierarchy
	 * @param string $view_file Name of the view file to use for rendering (without the .php extension). Defaults to 'extensions/navigation/views/list_menu.php'.
	 * @return string HTML code for the list.
	 * @author Peter Epp
	 */
	public function render_pages_hierarchically($pages, $current_parent_name_or_id = 0, $with_children = true, $view_file = 'navigation/views/list_menu.php',$extra_view_vars = array()) {
		if (is_string($current_parent_name_or_id) && preg_match('/([a-z_]+)/',$current_parent_name_or_id)) {
			$menu = $this->_menu_factory()->find_by('var_name',$current_parent_name_or_id);
			if (empty($menu)) {
				throw new ExtensionException("Invalid parent menu ID for rendering menu hierarchically: ".$current_parent_name_or_id);
			}
			$menu_parent_id = $menu->id();
		} else {
			$menu_parent_id = $current_parent_name_or_id;
		}
		// Provide all modules and extensions to the view file:
		$view_vars = array(
			'pages' => $pages,
			'current_parent_id' => $menu_parent_id,
			'with_children' => $with_children,
			'Biscuit' => $this->Biscuit,
			'view_file' => $view_file,
			'exclude_items' => $this->_menu_exclude_items
		);
		if (!empty($extra_view_vars)) {
			$view_vars = array_merge($view_vars, $extra_view_vars);
		}
		$extensions = $this->Biscuit->Extensions();
		foreach ($extensions as $extension_name => $extension) {
			if (is_object($extension)) {
				$view_vars[$extension_name] = $extension;
			}
		}
		$modules = $this->Biscuit->Modules();
		foreach ($modules as $module_name => $module) {
			$module_classname = Crumbs::module_classname($module_name);
			if (substr($module_classname,0,6) == 'Custom') {
				$var_name = substr($module_classname,6);
			} else {
				$var_name = $module_classname;
			}
			$view_vars[$var_name] = $module;
		}
		return Crumbs::capture_include($view_file, $view_vars);
	}
	/**
	 * Return a class name for tiger striping a dynamically rendered list. The method keeps track of whether it's currently odd or even so it will always output the right class
	 * regardless of whether you are rendering a list with a recursive function or using different functions or blocks of code to render different parts of the same
	 * list. Example usage:
	 *
	 * echo '<ul>';
	 * foreach ($some_array as $value) {
	 *     echo '<li class="'.$Navigation->tiger_stripe('some-array-list').'">'.$value.'</li>';
	 * }
	 * echo '</ul>';
	 *
	 * Be sure to pass a unique name to this method for each list you render dynamically.
	 *
	 * @param string $list_name Name of the list you are adding a stripe class name to
	 * @return string Class name to add to your list item ("stripe-odd" or "stripe-even")
	 * @author Peter Epp
	 */
	public function tiger_stripe($list_name) {
		if (empty($this->tiger_stripe_states[$list_name])) {
			$this->tiger_stripe_states[$list_name] = 'odd';
		} else {
			if ($this->tiger_stripe_states[$list_name] == 'odd') {
				$this->tiger_stripe_states[$list_name] = 'even';
			} else {
				$this->tiger_stripe_states[$list_name] = 'odd';
			}
		}
		return 'stripe-'.$this->tiger_stripe_states[$list_name];
	}
	/**
	 * Return a reference to menu factory object, instantiating if empty
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function _menu_factory() {
		if (empty($this->_menu_factory)) {
			$this->_menu_factory = new ModelFactory('Menu');
		}
		return $this->_menu_factory;
	}
	/**
	 * Render an administration control bar, for actions like 'new' at the top of an index page, for example
	 *
	 * @param object $module A reference to the module object
	 * @return string HTML code
	 * @author Peter Epp
	 */
	public function render_admin_bar($module,$model,$options = array()) {
		$model_name = null;
		if (!empty($model) && is_object($model)) {
			$parent_model = get_parent_class($model);
			if ($parent_model != 'AbstractModel') {
				$model_name = $parent_model;
			} else {
				$model_name = get_class($model);
			}
		}
		$view_vars['bar_title'] = 'Administration';
		$view_vars['module'] = $module;
		$view_vars['model']  = $model;
		$view_vars['model_name'] = $model_name;
		$view_vars['has_new_button'] = false;
		$view_vars['new_button_class'] = '';
		$view_vars['new_button_id'] = '';
		$view_vars['new_button_label'] = 'New';
		$view_vars['has_edit_button'] = false;
		$view_vars['edit_button_class'] = '';
		$view_vars['edit_button_id'] = '';
		$view_vars['edit_button_label'] = 'Edit';
		$view_vars['has_del_button'] = false;
		$view_vars['del_button_class'] = ' class="delete-button"';
		$view_vars['del_button_rel'] = '';
		$view_vars['del_button_id'] = '';
		$view_vars['del_button_label'] = 'Delete';
		if (isset($options['bar_title'])) {
			$view_vars['bar_title'] = $options['bar_title'];
		}
		if (isset($options['has_new_button']) && is_bool($options['has_new_button'])) {
			$view_vars['has_new_button'] = $options['has_new_button'];
			if (isset($options['new_button_class'])) {
				$view_vars['new_button_class'] = ' class="'.$options['new_button_class'].'"';
			}
			if (isset($options['new_button_id'])) {
				$view_vars['new_button_id'] = ' id="'.$options['new_button_id'].'"';
			}
			if (isset($options['new_button_label'])) {
				$view_vars['new_button_label'] = $options['new_button_label'];
			}
		}
		if (isset($options['has_edit_button']) && is_bool($options['has_edit_button'])) {
			$view_vars['has_edit_button'] = $options['has_edit_button'];
			if (isset($options['edit_button_class'])) {
				$view_vars['edit_button_class'] = ' class="'.$options['edit_button_class'].'"';
			}
			if (isset($options['edit_button_id'])) {
				$view_vars['edit_button_id'] = ' id="'.$options['edit_button_id'].'"';
			}
			if (isset($options['edit_button_label'])) {
				$view_vars['edit_button_label'] = $options['edit_button_label'];
			}
		}
		if (isset($options['has_del_button']) && is_bool($options['has_del_button'])) {
			$view_vars['has_del_button'] = $options['has_del_button'];
			if (isset($options['del_button_class'])) {
				$view_vars['del_button_class'] = ' class="delete-button '.$options['del_button_class'].'"';
			}
			if (isset($options['del_button_id'])) {
				$view_vars['del_button_id'] = ' id="'.$options['del_button_id'].'"';
			}
			if (isset($options['del_button_label'])) {
				$view_vars['del_button_label'] = $options['del_button_label'];
			}
			if (isset($options['del_button_rel'])) {
				$view_vars['del_button_rel'] = ' rel="'.$options['del_button_rel'].'"';
			}
		}
		if (!empty($options['custom_buttons']) && is_array($options['custom_buttons'])) {
			$view_vars['custom_buttons'] = $options['custom_buttons'];
		}
		return Crumbs::capture_include('navigation/views/admin_bar.php',$view_vars);
	}
	/**
	 * Render a locale switcher widget from a view
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function render_locale_switcher() {
		$locale_factory = new LocaleFactory();
		$all_locales = $locale_factory->find_all(array('friendly_name' => 'ASC'));
		return Crumbs::capture_include('navigation/views/locale_switcher.php',array('all_locales' => $all_locales));
	}
	/**
	 * Return the HTML markup for a link to either login or go to the user's home page, depending on whether or not a user is currently logged in.
	 *
	 * @param $css_class string Optional - a CSS class name (or names separated by spaces) to apply to the anchor tag
	 * @param $inline_style string Optional - inline styles to apply to the anchor tag
	 * @return string Anchor tag markup
	 */
	public function login_link($separator = " | ", $css_class = "") {
		if ($this->Biscuit->ModuleAuthenticator()->user_is_logged_in()) {
			$url = $this->Biscuit->ModuleAuthenticator()->user_home_url();
			$link_id = 'nav-user-home-link';
		}
		else {
			$url = $this->Biscuit->ModuleAuthenticator()->login_url();
			$link_id = 'nav-user-login-link';
		}
		$links = '';
		$extras = '';
		if (!empty($url)) {
			$biscuit_page_slug = substr($url,1);		// Everything after the slash
			// Use the Page model to get the page link name:
			$page = $this->Biscuit->page_factory->find_by('slug', $biscuit_page_slug);
			$extras = ' id="'.$link_id.'"';
			if (!empty($css_class)) {
				$extras .= ' class="'.$css_class.'"';
			}
			if (!empty($inline_style)) {
				$extras .= ' style="'.$inline_style.'"';
			}
			$links .= '<a href="'.(($page->force_secure()) ? SECURE_URL : STANDARD_URL).$url.'"'.$extras.'>'.__($page->title()).'</a>';
		}
		if ($this->Biscuit->ModuleAuthenticator()->user_is_logged_in()) {
			if (!empty($links)) {
				$links .= $separator;
			}
			$links .= '<a id="nav-user-logout-link" href="'.$this->Biscuit->Page->logout_url().'"'.$extras.'>'.__('Logout').'</a>';
		}
		return $links;
	}
	/**
	 * Return the fully qualified URL for any given page
	 *
	 * @param string $page_slug_or_id Either the canonical ID or slug for the page
	 * @param bool $with_logout Optional. Whether or not to make it a logout URL that will redirect back to the page after logging out
	 * @return string
	**/
	public function url($page_slug_or_id, $with_logout = false) {
		if (empty($page_slug_or_id)) {
			return STANDARD_URL.'/';
		}
		$external = false;
		$page_factory = new ModelFactory('Page');
		if (is_int($page_slug_or_id)) {
			$page = $page_factory->find_by('id',$page_slug_or_id);
		} else if (is_string($page_slug_or_id)) {
			$page = $page_factory->find_by('slug',$page_slug_or_id);
		}
		if ($page->ext_link()) {
			$external = true;
			$page_url = $page->ext_link();
		} else {
			if ($page->slug() == 'index') {
				$page_url = '';
			} else {
				$page_url = '/'.$page->slug();
			}
		}
		if (!$external) {
			if ($with_logout) {
				$page_url .= "/logout";
			}
			$page_url = (($page->force_secure()) ? SECURE_URL : STANDARD_URL).$page_url;
		}
		return $page_url;
	}
	/**
	 * Return a canonical page URL relative to the site root for a given page ID
	 *
	 * @param int $page_id Canonical ID of the page to be linked
	 * @return string
	 * @author Peter Epp
	 */
	public function canonical_url($page_id) {
		return '/canonical-page-link/'.$page_id.'/';
	}
	/**
	 * Fetch and cache all pages on the object and return them
	 *
	 * @return array
	 * @author Peter Epp
	 */
	public function all_pages() {
		if (empty($this->_pages)) {
			$page_factory = new ModelFactory('Page');
			$this->_pages = $page_factory->find_all();
		}
		return $this->_pages;
	}
	/**
	 * Find and cache the list of other top-level menu IDs
	 *
	 * @return array
	 * @author Peter Epp
	 */
	public function other_menus() {
		if ($this->_other_menus == null) {
			$other_menus = $this->_menu_factory()->find_all(array('name' => 'ASC'));
			if (empty($other_menus)) {
				$this->_other_menus = array();
			} else {
				$this->_other_menus = $other_menus;
			}
		}
		return $this->_other_menus;
	}
	/**
	 * Render common administration menu for logged in users
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function render_admin_menu() {
		if ($this->Biscuit->ModuleAuthenticator()->user_is_logged_in() && $this->Biscuit->Page->slug() != 'system-admin') {
			Event::fire('build_admin_menu',$this);
			if (!empty($this->_admin_menu_items)) {
				return Crumbs::capture_include('navigation/views/admin-menu.php',array('menu_items' => $this->_admin_menu_items));
			}
		}
		return '';
	}
	/**
	 * Add a set of menu items to the admin menu item list
	 *
	 * @param string $menu_name 
	 * @param string $items 
	 * @return void
	 * @author Peter Epp
	 */
	public function add_admin_menu_items($menu_name,$items) {
		$this->_admin_menu_items[$menu_name] = $items;
	}
	/**
	 * Compile a list of the ids of other menus
	 *
	 * @return array
	 * @author Peter Epp
	 */
	public function other_menu_ids() {
		$other_menu_ids = array();
		$other_menus = $this->other_menus();
		if (!empty($other_menus)) {
			foreach ($other_menus as $menu) {
				$other_menu_ids[] = $menu->id();
			}
		}
		return $other_menu_ids;
	}
	/**
	 * On request dispatch, register admin menu CSS and JS if user is logged in. We can't do this on run because Authenticator isn't available at that point
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_dispatch_request() {
		if ($this->Biscuit->ModuleAuthenticator()->user_is_logged_in() && $this->Biscuit->Page->slug() != 'system-admin') {
			$this->register_css(array('filename' => 'admin-menu.css', 'media' => 'screen'));
			$this->register_js('footer','admin-menu.js');
		}
	}
	/**
	 * Render admin menu. Will only render if user is logged in
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_compile_footer() {
		$this->Biscuit->append_view_var('footer',$this->render_admin_menu());
	}
}
?>