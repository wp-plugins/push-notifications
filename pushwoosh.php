<?php

    /**
     * @package Pushwoosh
     * @version 2.3
     */

    /**
    * Plugin Name: Pushwoosh
    * Plugin URI: http://wordpress.org/plugins/push-notifications/
    * Description: Push notifications plugin for wordpress by Pushwoosh
    * Author: Arello Mobile
    * Author URI: http://www.arello-mobile.com/
    * Version: 2.3
    *
    * Copyright 2013 Arello Mobile (email: support@arello-mobile.com)
    * This program is free software; you can redistribute it and/or modify
    * it under the terms of the GNU General Public License as published by
    * the Free Software Foundation; either version 2 of the License, or
    * (at your option) any later version.
    *
    * This program is distributed in the hope that it will be useful,
    * but WITHOUT ANY WARRANTY; without even the implied warranty of
    * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    * GNU General Public License for more details.
    *
    * You should have received a copy of the GNU General Public License
    * along with this program; if not, write to the Free Software
    * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
    *
    */

	error_reporting(E_ALL);
	ini_set('display_errors', 0);

	include_once('settings.php');
	require_once('lib/pushwoosh.php');



	function pushwoosh_add_meta_box() {
		wp_enqueue_style('pushwoosh_css');
		wp_enqueue_script('pushwoosh_js');
		add_meta_box(
			'pushwoosh_section_id',
			__('Pushwoosh notification', 'pushwoosh'),
			'pushwoosh_message_box',
			'post',
			'side',
			'high'
		);
	}

	add_action('admin_init', 'pushwoosh_add_meta_box');
	add_action('save_post', 'pushwoosh_save_post');

	function pushwoosh_message_box($post) {

		$action = null;
		if (!empty($_GET['action'])) {
			$action = htmlentities($_GET['action']);
		}

		wp_nonce_field(plugin_basename( __FILE__ ), 'pushwoosh_post_nonce');
		$post_type = $post->post_type;
		$checkbox_label = sprintf('Send a push notification when the %s is published', htmlentities($post_type));
		$textarea_placeholder = 'Input text of the push here, otherwise, the post title will be used.';
		$safari_title_placeholder = 'Safari title';
		$checkbox_checked = 'checked="checked"';
		$message_content = '';
		$safari_title = '';

		if ($action == 'edit') {
			$checkbox_checked = '';
			$checkbox_label = sprintf('Send a push notification when the %s is updated', htmlentities($post_type));
			$message_content = get_post_meta($post->ID, 'pushwoosh_message_content', true);
			$safari_title = get_post_meta($post->ID, 'safari_title', true);
		}
		$plugin_content = file_get_contents(plugins_url('/html/pushwoosh.html', __FILE__));
		echo sprintf($plugin_content,
			$safari_title,
			$safari_title_placeholder,
			__($textarea_placeholder, 'pushwoosh'),
			$message_content,
			$checkbox_checked,
			__($checkbox_label, 'pushwoosh')
		);
	}

	function pushwoosh_send_push_by_post($post_id, $message_content, $options=array()) {

		$application_code = get_option('pushwoosh_application_code', array('text_string' => null));
		$api_token = get_option('pushwoosh_api_token', array('text_string' => null));
		$safari_action = get_option('pushwoosh_safari_action', array('text_string' => null));
		$options['safari_action'] = $safari_action['text_string'];
		if ($options['safari_title'] == '') {
			$safari_title = get_option('pushwoosh_safari_title', array('text_string' => null));
			if (empty($safari_title['text_string'])) {
				$site_url = get_option('siteurl');
				$options['safari_title'] = $site_url;
			} else {
				$options['safari_title'] = $safari_title['text_string'];
			}
		}

		$pushwoosh  = new PushWoosh(array('auth' => $api_token['text_string']));

		try {
			$pushwoosh->createTargetedMessage($application_code['text_string'],
				array('send_date' => 'now', 'content' => $message_content), $options);
			$status = 'Success';
		} catch (Exception $e) {
			$status = 'Failed: ' . $e->getMessage();
		}

		update_post_meta($post_id, 'pushwoosh_api_request', $status);
	}

 	function pushwoosh_save_post($post_id) {

		/*
		 * if update many posts, don't send push
		 */
		if (array_key_exists('post_status', $_GET) && $_GET['post_status']=='all') {
			return;
		}

		$message_content = null;
		$url = wp_get_shortlink();

		if (!empty($url)) {
			$url_array = explode('?', $url);
			if(is_array($url_array) && array_key_exists(1, $url_array)) {
				$post_url = '?' . $url_array[1];
			} else {
				$post_url = '';
			}
		}else {
			$post_url = '';
		}

		$options['safari_url_args'] = array($post_url);
		
		$post = null;

		if (!empty($_POST)) {
			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
				return;
			}

			if (!isset($_POST['pushwoosh_post_nonce'])) {
				return;
			}
			if (!wp_verify_nonce($_POST['pushwoosh_post_nonce'], plugin_basename( __FILE__ ))) {
				return;
			}
			if (empty($_POST['pushwoosh_send_push'])) {
				return;
			}
			if (array_key_exists('safari_title', $_POST)) {
				$options['safari_title'] = $_POST['safari_title'];
			} else {
				$options['safari_title'] = '';
			}
			if (array_key_exists('pushwoosh_message_content', $_POST)) {
				$message_content = $_POST['pushwoosh_message_content'];
			}
			update_post_meta($post_id, 'pushwoosh_message_content', $message_content);
			update_post_meta($post_id, 'safari_title', $options['safari_title']);
		} else {
			$message_content = get_post_meta($post_id, 'pushwoosh_message_content', true);
			$options['safari_title'] = get_post_meta($post_id, 'safari_title', true);
		}
		$options['safari_title'] = stripslashes($options['safari_title']);
		$message_content = stripslashes($message_content);
		$post = get_post($post_id);
		if (empty($message_content)) {
			$message_content = $post->post_title;
		}
		if ($post->post_status != 'publish') {
			return;
		}
		pushwoosh_send_push_by_post($post->ID, $message_content, $options);
	}
