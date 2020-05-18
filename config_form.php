<?php
	$clickable_links_plus_title 		= get_option('clickable_links_plus_title');
	$clickable_links_plus_label_len		= get_option('clickable_links_plus_label_length');
	$clickable_links_plus_wellformatted	= get_option('clickable_links_plus_wellformatted');

	$db = get_db();
	$sql = "
		SELECT es.name AS es_name, e.id AS e_id, e.name AS e_name
		FROM `$db->Elements` e
		LEFT JOIN `$db->ElementSets` es ON e.element_set_id = es.id
		ORDER BY es.id, e.id
	";
	$elements = $db->fetchAll($sql);
	$selectedElements = unserialize(get_option('clickable_links_plus_elements'));
	$view = get_view();
?>

<h2><?php echo __('General settings'); ?></h2>

 <div class="field">
	<div class="two columns alpha">
		<?php echo $view->formLabel('clickable_links_plus_title', __('Link tooltip')); ?>
	</div>
	<div class="inputs five columns omega">
		<p class="explanation">
			<?php echo __('The text to be used as a tooltip for all clickable links. If blank, no tooltip will be visible.'); ?>
		</p>
		<?php echo $view->formText('clickable_links_plus_title', $clickable_links_plus_title); ?>
	</div>
</div>

 <div class="field">
	<div class="two columns alpha">
		<?php echo $view->formLabel('clickable_links_plus_label_len', __('Link label length')); ?>
	</div>
	<div class="inputs five columns omega">
		<p class="explanation">
			<?php echo __('Maximum number of URL\'s characters shown as the link\'s label (a minimum of 15 characters is suggested). If blank, the whole URL will be shown.'); ?>
		</p>
		<?php echo $view->formText('clickable_links_plus_label_len', $clickable_links_plus_label_len); ?>
	</div>
</div>

 <div class="field">
	<div class="two columns alpha">
		<?php echo $view->formLabel('clickable_links_plus_wellformatted', __('URL format restriction')); ?>
	</div>
	<div class="inputs five columns omega">
		<p class="explanation">
			<?php echo __('If checked, plugin functionality will be limited only to well-formatted URLs (e.g.: "http://example.com" will be turned into a link, but "www.example.com" will not).'); ?>
		</p>
		<?php echo $view->formCheckbox('clickable_links_plus_wellformatted', $clickable_links_plus_wellformatted, null, array('1', '0')); ?>
	</div>
</div>

<h2><?php echo __('Scope'); ?></h2>
<p><?php echo __('Select the elements whose URLs will be turned into clickable links (only Public side).'); ?></p>

<table id="hide-elements-table">
	<thead>
		<tr>
			<th style="text-align: center; font-weight: bold"><?php echo __('Element'); ?></th>
			<th style="text-align: center; font-weight: bold"><?php echo __('Active'); ?></th>
		</tr>
	</thead>
	<tbody>
<?php
	$es_oldname = '';
	foreach ($elements as $element):
		$value = 0;
		$e_name = $element['e_name'];
		$es_name = $element['es_name'];
		$e_id = $element['e_id'];
		if (!is_null($selectedElements[$es_name])) {
			$value = (in_array($e_name, $selectedElements[$es_name]) ? $e_id : 0);
		}
		
		if ($es_name != $es_oldname):
			$es_oldname = $es_name;
?>
		<tr>
			<th colspan="2" style="font-weight: bold"><?php echo Inflector::humanize(__($es_name), 'all'); ?></th>
		</tr>
<?php 	endif; ?>
		<tr>
			<td><?php echo __($e_name); ?></td>
			<td style="text-align: center"><?php echo $view->formCheckbox('element_sets[]', $value, array('id'=>'element_sets-' . $e_id), array($e_id, '0')); ?></td>
		</tr>
<?php endforeach; ?>
	</tbody>
</table>