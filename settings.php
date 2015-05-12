<?php

class PushwooshConfig {

	public $group = 'pushwoosh';

	public $page = array(
		'name' => 'pushwoosh',
		'title' => 'Pushwoosh Settings',
		'intro_text' => 'Configuration options for Pushwoosh, you must have a <a href="https://www.pushwoosh.com/pricing/">Premium account</a> with Pushwoosh to use this plugin',
		'menu_title' => 'Pushwoosh'
	);

	public $sections = array(
		'application_access' => array(
			'title' => 'Settings',
			'description' => 'Please configure the following settings below.',
			'fields' => array(
				'api_token' => array(
					'label' => 'API token',
					'description' => 'API access token from the Pushwoosh control panel (API access token from the 
					<a href="https://cp.pushwoosh.com/api_access/" target="_blank">Pushwoosh Control Panel</a>).'
					),
				'application_code' => array(
					'label' => 'Application code',
					'description' => 'Your Pushwoosh application ID you send the message to.',
					),
				'chrome_default_icon' => array(
					'label' => 'Chrome Icon',
					'description' => 'Full URL to icon',
				),
				'safari_title' => array(
					'label' => 'Chrome/Safari Title',
					'description' => 'If you do not wish to manually input Chrome/Safari Title every time, you can specify
					the default title here. If you publish a post with a blank Chrome/Safari Title field,
					the URL of your website is used as default title.'
					),
				'safari_action' => array(
					'label' => 'Safari Action Button',
					'description' => 'Your Action Button for Safari push.',
					'default'=>'Click Here'
					),
				)
			)
	);
}


class PushwooshSectionHelper {

	protected $_sections;

	public function __construct($sections) {
		$this->_sections = $sections;
	}

	public function section_legend($value) {
		echo sprintf("%s",$this->_sections[$value['id']]['description']);
	}

	public function input_text($value) {
		$api_class = '';
		if ($value['name'] == 'pushwoosh_api_token') {
			$api_class = 'pushwoosh-api-token-input';
		}
	$options = get_option($value['name']);
	$default = (isset($value['default'])) ? $value['default'] : null;
	echo sprintf('<input class="%s" id="%1$s" type="text" name="%2$s[text_string]" value="%3$s" size="40" /> %4$s%5$s',
			$api_class,
			$value['name'],
			(!empty ($options['text_string'])) ? $options['text_string'] : $default,
			(!empty ($value['suffix'])) ? $value['suffix'] : null,
			(!empty ($value['description'])) ? sprintf("<br /><em>%s</em>", __($value['description'], 'pushwoosh')) : null);
	}

	public function input_submit($value) {
		echo sprintf('<input type="submit" name="Submit" value="%s" />', $value);
	}

	public function form_start($action) {
		echo sprintf('<form method="POST" action="%s">', $action);
	}

	public function form_end() {
		echo '</form>';
	}
}


class PushwooshSettings {

	protected $_config;

	public function __construct() {
		$this->_config = get_class_vars('PushwooshConfig');
		$this->_section = new PushwooshSectionHelper($this->_config['sections']);
		$this->initialize();
	}

	protected function initialize() {

		if (!function_exists('add_action')) {
			return;
		}

		add_action('admin_init', array($this, 'admin_init'));
		add_action('admin_menu', array($this, 'admin_add_page'));

		if (!function_exists('add_filter')) {
			return;
		}

		$filter = 'plugin_action_links_' . basename(__DIR__) . '/pushwoosh.php';
		add_filter($filter, array($this, 'admin_add_links'), 10, 4);
	}

	public function admin_add_links($links, $file) {

		$settings_link = sprintf('<a href="options-general.php?page=%s">%s</a>',
			$this->_config['page']['name'],
			__('Settings')
		);
		array_unshift($links, $settings_link);
		return $links;
	}

	public function admin_init() {

		wp_register_script('pushwoosh_js', plugins_url('/js/pushwoosh.js', __FILE__), array(), '1.0');
		wp_register_style('pushwoosh_css', plugins_url('/css/pushwoosh.css', __FILE__), array(), '1.0');

		foreach ($this->_config['sections'] as $key => $section):
			add_settings_section(
				$key,
				__($section['title'], 'pushwoosh'),
				array($this->_section, 'section_legend'),
				$this->_config['page']['name'],
				$section
			);
			foreach ($section['fields'] as $field_key => $field_value):
      			$function = array($this->_section, 'input_text');
      			$callback = null;
				add_settings_field(
					$this->_config['group'] . '_' . $field_key,
					__($field_value['label'], 'pushwoosh'),
					$function,
					$this->_config['page']['name'], 
					$key,
					array_merge(
						$field_value,
						array('name' => $this->_config['group'] . '_' . $field_key)
					)
				);
				register_setting(
					$this->_config['group'],
					$this->_config['group'] . '_' . $field_key,
					$callback
				);
			endforeach;
		endforeach;
	}

	public function admin_add_page() {

		$args = array(
			__($this->_config['page']['title'], 'pushwoosh'),
			__($this->_config['page']['menu_title'], 'pushwoosh'),
			'manage_options',
			$this->_config['page']['name'],
			array($this, 'options_page')
		);
		call_user_func_array('add_options_page', $args);
	}

	public function options_page() {

		echo sprintf('<h2>%s</h2><p>%s</p>',
			__($this->_config['page']['title'], 'pushwoosh'),
			__($this->_config['page']['intro_text'], 'pushwoosh')
		);
		$this->_section->form_start('options.php');
		settings_fields($this->_config['group']);
		do_settings_sections($this->_config['page']['name']);
		$this->_section->input_submit(__('Save changes', 'pushwoosh'));
		$this->_section->form_end();
	}
}

new PushwooshSettings();
