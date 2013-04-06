<div id="biscuit-admin-menu">
	<button id="biscuit-admin-menu-activator" title="Administration Menu"><?php echo __("Admin Menu") ?></button>
	<div id="biscuit-admin-menu-top-level">
		<?php
		foreach ($menu_items as $menu_label => $items) {
			?><h4><a href="#" title="<?php echo __($menu_label).' '.__("Administration") ?>"><?php echo __($menu_label) ?></a></h4>
			<div class="biscuit-admin-sub-menu">
				<ul class="biscuit-admin-menu-items">	
				<?php
				foreach ($items as $label => $url) {
					?><li><a href="<?php echo $url ?>"><?php echo __($label) ?></a></li><?php
				}
				?>
				</ul>
			</div><?php
		}
		?>
	</div>
</div>
