<?php


namespace pushwoosh;


class Config {

	
	public $group = __NAMESPACE__;


	public $page = array(
		'name' => __NAMESPACE__,
		'title' => 'Pushwoosh',
		'intro_text' => 'Configuration options for Pushwoosh, you must have a <a href="http://www.pushwoosh.com/accounts-comparison/">Premium account</a> with Pushwoosh to use this plugin',
		'menu_title' => 'Pushwoosh'
	);


	public $sections = array(
		'application_access' => array(
			'title' => 'Settings',
			'description' => 'Please configure the following settings below.',
			'fields' => array(
				'application_code' => array(
					'label' => 'Application code',
					'description' => 'Your Pushwoosh Application Code',
				),
				'api_token' => array(
					'label' => 'API token',
					'description' => 'Your Pushwoosh Api Access Token',
				)
			)
		),
	);
}


class SectionHelper {


	protected $_sections;


	public function __construct($sections) {
		$this->_sections = $sections;
	}

	public function section_legend($value) {
		echo sprintf("%s",$this->_sections[$value['id']]['description']);
	}

	public function input_text($value) {
		$options = get_option($value['name']);
		$default = (isset($value['default'])) ? $value['default'] : null;
		echo sprintf(
			'<input id="%s" type="text" name="%1$s[text_string]" value="%2$s" size="40" /> %3$s%4$s',
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


class Settings {


	protected $_config;


	public function __construct() {
		$this->_config = get_class_vars(sprintf('\%s\config',__NAMESPACE__));
		$this->_section = new SectionHelper($this->_config['sections']);
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
		$settings_link = sprintf(
			'<a href="options-general.php?page=%s">%s</a>',
			$this->_config['page']['name'],
			__('Settings')
		);
		array_unshift($links, $settings_link);
		return $links;		
	}

	public function admin_init() {
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
		echo sprintf(
			'<h2>%s</h2><p>%s</p>',
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

new \pushwoosh\Settings();