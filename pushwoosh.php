<?php
 	/**
 	 *	Plugin Name: Pushwoosh
 	 *	Plugin URI: http://pushwoosh.com
 	 *	Description: Push notifications plugin for wordpress by Pushwoosh
 	 *	Version: 1.1.0
 	 *	Author: Arello Mobile
 	 *	Author URI: http://www.arello-mobile.com/
 	 */


 	include_once('settings.php');
 	require_once('lib/pushwoosh.php');


 	add_action('admin_init', 'pushwoosh_add_meta_box');
 	add_action('save_post', 'pushwoosh_save_post');


 	function pushwoosh_add_meta_box() {
 		add_meta_box(
 			'pushwoosh_section_id',
 			__( 'Pushwoosh notification', 'pushwoosh' ),
 			'pushwoosh_message_box',
 			'post',
 			'side',
 			'high'
 		);
 		add_meta_box(
 			'pushwoosh_section_id',
 			__( 'Pushwoosh notification', 'pushwoosh' ),
 			'pushwoosh_message_box',
 			'page',
 			'side',
 			'high'
 		); 		
 	}


 	function pushwoosh_message_box($post) {
 		$action = null;
 		if (!empty($_GET['action'])) {
 			$action = htmlentities($_GET['action']);
 		}
 		wp_nonce_field( plugin_basename( __FILE__ ), 'pushwoosh_post_nonce' );

 		$type = $post->post_type;

 		$label = sprintf('Send a push notification when the %s is published', $type);
 		$placeholder = 'Text message';
 		$checked = 'checked="checked"';
 		$message_content = '';

 		if ($action == 'edit') {
 			$label = sprintf('Send a push notification when the %s is updated', $type);
 			$checked = '';
 			$message_content = get_post_meta($post->ID, 'pushwoosh_message_content', true);
 		}

 		echo '<textarea name="pushwoosh_message_content" value="" style="width:100%" placeholder="' . __($placeholder, 'pushwoosh') . '"/>' . $message_content . '</textarea><br/>';
 		echo '<input type="checkbox" name="pushwoosh_send_push" id="pushwoosh_checkbox_send_push" ' . $checked . ' value="1"> <label for="pushwoosh_checkbox_send_push">' . __($label, 'pushwoosh') . '</label>';		
 	}


 	function pushwoosh_send_push_by_post($post_id, $message_content) {
 		$application_code = get_option('pushwoosh_application_code', array('text_string' => null));
 		$api_token = get_option('pushwoosh_api_token', array('text_string' => null));

 		$pushwoosh  = new PushWoosh(array('auth' => $api_token['text_string']));

 		try {
	 		$pushwoosh->createMessage(
	 			$application_code['text_string'],
	 			array(
	 				'send_date' => 'now',
	 				'content' => $message_content
	 			)
	 		);
	 		$status = 'Success';
 		} catch (Exception $e) {
 			$status = 'Failed: ' . $e->getMessage(); 			
 		}

 		update_post_meta($post_id, 'pushwoosh_api_request', $status); 				
 	}



 	function pushwoosh_save_post($post_id) {

 		$message_content = null;
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

	 		if (empty($_POST['pushwoosh_message_content']) || empty($_POST['pushwoosh_send_push'])) {
	 			return;
	 		}
	 		$message_content = $_POST['pushwoosh_message_content'];
	 		update_post_meta($post_id, 'pushwoosh_message_content', $message_content);	 				 			
 		} else {
 			$message_content = get_post_meta($post_id, 'pushwoosh_message_content', true);
 			if (empty($message_content)) {
 				return;
 			}
 		}

 		$post = get_post($post_id);

 		if ($post->post_status !== 'publish') {
 			return;
 		}

 		pushwoosh_send_push_by_post($post->ID, $message_content);
 	}