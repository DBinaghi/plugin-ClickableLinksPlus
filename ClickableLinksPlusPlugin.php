<?php

/**
 * Clickable Links Plus plugin.
 *
 * @package Omeka\Plugins\ClickableLinksPlus
 */
class ClickableLinksPlusPlugin extends Omeka_Plugin_AbstractPlugin {
	
	protected $_hooks = array(
		'install',
		'uninstall',
		'initialize',
		'config',
		'config_form',
		'public_head'
	);

	public function hookInstall()
	{
		set_option('clickable_links_plus_title', '');
		set_option('clickable_links_plus_label_length', '');
		set_option('clickable_links_plus_wellformatted',0);
		set_option('clickable_links_plus_collections',	0);
		set_option('clickable_links_plus_externallinkicon',	0);
		set_option('clickable_links_plus_elements', serialize(array()));
	}

	public function hookUninstall()
	{
		delete_option('clickable_links_plus_title');
		delete_option('clickable_links_plus_label_length');
		delete_option('clickable_links_plus_wellformatted');
		delete_option('clickable_links_plus_collections');
		delete_option('clickable_links_plus_externallinkicon');
		delete_option('clickable_links_plus_elements');
	}

	/**
	 * Connect display filter
	 */
	public function hookInitialize() 
	{
		add_translation_source(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'languages');

		$db = get_db();

		$selectedElements = unserialize(get_option('clickable_links_plus_elements'));
		if (!empty($selectedElements)) {
			$includeCollections = get_option('clickable_links_plus_collections');

			// Add pseudo code filter to all selected elements
			$sql = "
				SELECT es.name AS e_set, e.name AS e_name
				FROM `$db->Elements` e
				LEFT JOIN `$db->ElementSets` es ON e.element_set_id = es.id
			";
			$elements = $db->fetchAll($sql);
			foreach ($elements as $element) {
				if (isset($selectedElements[$element["e_set"]])) {
					if (in_array($element["e_name"], $selectedElements[$element["e_set"]])) {
						add_filter(
							array("Display", "Item", $element["e_set"], $element["e_name"]),
							array($this, "filterDisplay")
						);
						if ($includeCollections) {
							add_filter(
								array("Display", "Collection", $element["e_set"], $element["e_name"]),
								array($this, "filterDisplay")
							);
						}
					}
				}
			}
		}
	}

	public function hookConfig($args)
	{   
		$post = $args['post'];
		$elements = array();
		$elTable = get_db()->getTable('Element');
		foreach ($post['element_sets'] as $elId) {
			$element = $elTable->find($elId);
			$elSet = $element->getElementSet();
			if (!array_key_exists($elSet->name, $elements)) {
				$elements[$elSet->name] = array();
			}
			$elements[$elSet->name][] = $element->name;
		}
		set_option('clickable_links_plus_elements', 		serialize($elements));
		set_option('clickable_links_plus_title', 			$post['clickable_links_plus_title']);
		set_option('clickable_links_plus_label_length',		$post['clickable_links_plus_label_length']);
		set_option('clickable_links_plus_wellformatted',	$post['clickable_links_plus_wellformatted']);
		set_option('clickable_links_plus_collections',		$post['clickable_links_plus_collections']);
		set_option('clickable_links_plus_externallinkicon',	$post['clickable_links_plus_externallinkicon']);
	}

	public function hookConfigForm()
	{
		include('config_form.php');
	}

    /**
	 * Adds reference to js Linkify files when needed
	 */
	public function hookPublicHead()
	{
		$selectedElements = unserialize(get_option('clickable_links_plus_elements'));
		if (!empty($selectedElements)) {
			queue_js_file('linkifyjs/linkify-polyfill');
			queue_js_file('linkifyjs/linkify');
			queue_js_file('linkifyjs/linkify-html');
			queue_css_file('clickable_links_plus');
		}
	}

	/**
	 * Filters element text via Linkify JS library and adds <a href="..." target="_blank">...</a> tags
	 */
	public function filterDisplay($text, $args) 
	{
		$content = $text;
		
		if (is_admin_theme()) {
			return $content;
		}

		// make sure to leave html fields add_translation_source
		$isHtml = $args["element_text"]["html"];

		if (!$isHtml) {
			// checks if label shortening has been required
			$label_length = intval(get_option('clickable_links_plus_label_length'));
			if ($label_length < 1) $label_length = 0;
			
			// checks if any default tooltip title has been provided
			$tooltip = str_replace(array('\'', '"'), '', get_option('clickable_links_plus_title'));
			if ($tooltip == '') $tooltip = __('click to open');
			
			// checks if only well-formatted URL can be turned into links
			$validation = (get_option('clickable_links_plus_wellformatted') ? '/^(http|ftp)s?:\/\//.test(value)' : 'value');
			
			// checks if icon has to be added to link
			if (get_option('clickable_links_plus_externallinkicon')) {
				$url_data = $this->parseURL($text);
				$class = $url_data['class'];
			} else {
				$class = '';
			}

			$content = "<script>
				var options = {
					attributes: {
						rel: 'nofollow', 
						title: '" . $tooltip . "',
					},
					className: '" . $class . "',
					format: {
						url: function (value) {
							" . ($label_length == 0 ? "return value" : "return value.length > " . $label_length . " ? value.slice(0, " . $label_length . ") + '…' : value") . "
						}
					},
					ignoreTags: ['a'],
					validate: {
						url: function (value) {
							return " . $validation . ";
						}
					}
				};
				var str = '" . addslashes($text) . "';
				document.write(linkifyHtml(str, options));
			</script>";
		}

		return $content;
	}
	
	/**
	 * Parses URL to see whether it is an internal or external one
	 * Returns class and target too
	 * 
	 * Source: https://stackoverflow.com/questions/25090563/php-determine-if-a-url-is-an-internal-or-external-url
	 */
	private function parseURL($url = '', $internal_class = 'internal-link', $external_class = 'external-link')
	{
		// Abort if parameter URL is empty
		if( empty($url) ) {
			return null;
		}

		// Parse home URL and parameter URL
		$link_url = parse_url($url);
		$home_url = parse_url($_SERVER['HTTP_HOST']);

		// Decide on target
		if (empty($link_url['host']) || empty($home_url['host'])) {
			// Is an internal link
			$target = '_self';
			$class = $internal_class;
		} elseif ($link_url['host'] == $home_url['host'] || $link_url['host'] == $home_url['path']) {
			// Is an internal link
			$target = '_self';
			$class = $internal_class;
		} else {
			// Is an external link
			$target = '_blank';
			$class = $external_class;
		}

		// Return array
		$output = array(
			'class'     => $class,
			'target'    => $target,
			'url'       => $url
		);
		return $output;
	}
}
?>
