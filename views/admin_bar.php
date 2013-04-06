<?php
$buttons = '';
$action_suffix = '';
if (!empty($model) && ($has_new_button || $has_edit_button || $has_del_button)) {
	$delete_action = 'delete';
	if ($model_name != $module->primary_model()) {
		$action_suffix = '_'.AkInflector::underscore($model_name);
	}
}
if ($has_del_button && !empty($model)) {
	$buttons .= '<a'.$del_button_id.$del_button_class.$del_button_rel.' style="float: right; margin: 0 0 0 5px" href="'.$module->url('delete'.$action_suffix, $model->id()).'">'.__($del_button_label).'</a>';
}
if ($has_new_button) {
	$buttons .= '<a'.$new_button_id.$new_button_class.' style="float: right; margin: 0 0 0 5px" href="'.$module->url('new'.$action_suffix).'">'.__($new_button_label).'</a>';
}
if ($has_edit_button && !empty($model)) {
	$buttons .= '<a'.$edit_button_id.$edit_button_class.' style="float: right; margin: 0 0 0 5px" href="'.$module->url('edit'.$action_suffix, $model->id()).'">'.__($edit_button_label).'</a>';
}
if (!empty($custom_buttons)) {
	foreach ($custom_buttons as $button_data) {
		$button_class = '';
		$button_id = '';
		if (!empty($button_data['classname'])) {
			$button_class = ' class="'.$button_data['classname'].'"';
		}
		if (!empty($button_data['id'])) {
			$button_id = ' id="'.$button_data['id'].'"';
		}
		$buttons .= '<a'.$button_id.$button_class.' style="float: right; margin: 0 0 0 5px" href="'.$button_data['href'].'">'.__($button_data['label']).'</a>';
	}
}
if (!empty($buttons)) {
	?>
<div class="controls">
	<span class="admin"><?php echo __($bar_title) ?></span><?php echo $buttons ?>
</div>
	<?php
}
?>
