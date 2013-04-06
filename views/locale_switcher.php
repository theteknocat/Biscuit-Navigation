<?php
if (!empty($all_locales) && count($all_locales) > 1) {
	$switcher_items = array();
	foreach ($all_locales as $_locale) {
		$img = '';
		$icon_path = $_locale->icon_path();
		if (!empty($icon_path)) {
			$img = '<img src="'.$icon_path.'" alt="'.$_locale->short_name().'">&nbsp;';
		}
		if ($_locale->is_active()) {
			$switcher_items[] = $img.$_locale->short_name();
		} else {
			$switcher_items[] = '<a href="'.$_locale->url().'">'.$img.$_locale->short_name().'</a>';
		}
	}
	echo implode(" | ",$switcher_items);
}
