<?php
// Note for IE6: there must be no whitespace between UL and LI tags or IE6 will render gaps. If you make a custom copy of this view in your site be sure
// not to add any whitespace to avoid this IE6 quirk.
if (!empty($pages[$current_parent_id])) {
	?><ul><?php
	foreach ($pages[$current_parent_id] as $page) {
		if ($page->user_can_access() && (!$exclude_items || ($exclude_items && !$page->exclude_from_nav()))) {
			$link_class = '';
			if ($page->id() == $Biscuit->Page->id()) {
				$link_class = ' class="current"';
			}
			?><li><a href="<?php echo $page->url() ?>"<?php echo $link_class ?>><?php echo __($page->navigation_title()) ?></a><?php
			if (!empty($pages[$page->id()]) && $with_children) {
				echo $Navigation->render_pages_hierarchically($pages, $page->id());
			}
			?></li><?php
		}
	}
	?></ul><?php
}
