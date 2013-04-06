<?php
$page_links = array();
$index = 1;
foreach ($pages as $page) {
	if ($page->user_can_access()) {
		$classes = '';
		if ($index == count($pages)) {
			$classes .= 'last ';
		}
		if ($page->id() == $Biscuit->Page->id()) {
			$classes .= 'current';
		}
		if (!empty($classes)) {
			$classes = ' class="'.$classes.'"';
		}
		$page_links[] = '<a href="'.$page->url().'" id="link_'.$page->hyphenized_slug().'"'.$classes.'>'.__($page->navigation_title()).'</a>';
	}
	$index++;
}
echo implode("",$page_links);
