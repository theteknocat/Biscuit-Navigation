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
		if (Permissions::can_access((int)$page->access_level())) {
			$page_links[] = '<a href="'.$page->url().'" id="link_'.$page->hyphenized_slug().'"'.$classes.'>'.$page->title().'</a>';
		}
	}
	$index++;
}
echo implode(" &bull; ",$page_links);
?> &bull; <?php
if ($Biscuit->ModuleAuthenticator()->user_is_logged_in()) {
	?><a href="<?php echo $Page->logout_url() ?>">Logout</a><?php
}
else {
	?><a href="/login?ref_page=/<?php echo $Page->slug(); ?>" id="login">Login</a><?php
}
?>
