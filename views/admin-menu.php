<div id="biscuit-admin-menu">
	<button id="biscuit-admin-menu-activator" title="Administration Menu"><?php echo __("Admin Menu") ?></button>
	<div id="biscuit-admin-menu-top-level">
		<?php
		foreach ($menu_items as $menu_label => $items) {
			?><h4><a href="#" title="<?php echo __($menu_label).' '.__("Administration") ?>"><?php echo __($menu_label) ?></a></h4>
			<div class="biscuit-admin-sub-menu">
				<ul class="biscuit-admin-menu-items">	
				<?php
				foreach ($items as $label => $item_data) {
					$classname = '';
					if (is_string($item_data)) {
						$url = $item_data;
						$item_data = (array)$item_data;
					} else {
						$url = $item_data['url'];
						if (!empty($item_data['classname'])) {
							$classname = $item_data['classname'];
						}
					}
					if (!empty($item_data['ui-icon'])) {
						$classname .= ' ui-button-text-icon-primary';
					}
					$target = '';
					if (!empty($item_data['target'])) {
						$target = ' target="'.$item_data['target'].'"';
					}
					?><li><a class="<?php echo $classname; ?>" href="<?php echo $url ?>"<?php echo $target; ?>><?php
					if (!empty($item_data['ui-icon'])) {
						?><span class="ui-button-icon-primary ui-icon <?php echo $item_data['ui-icon']; ?>"></span><?php
					}
					echo __($label);
					?></a></li><?php
				}
				?>
				</ul>
			</div><?php
		}
		?>
	</div>
</div>
