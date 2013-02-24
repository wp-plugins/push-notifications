<?php
 	/**
 	 *	Plugin Name: Pushwoosh
 	 *	Plugin URI: http://pushwoosh.com
 	 *	Description: Pushwoosh API lib for wordpress
 	 *	Version: 1.0.0
 	 *	Author: Arello Mobile
 	 *	Author URI: http://www.arello-mobile.com/
 	 */

class PushwooshInternalErrorException extends Exception {
}

class PushwooshBadRequestException extends Exception {
}

class PushWoosh {
	
	
	protected $settings = array(
		'server' => 'https://cp.pushwoosh.com/json/1.3/',
		'auth' => '',
	);
	
	
	public function __construct($settings = array()) {
		$this->settings = array_merge($this->settings, $settings);
	}
	
	public function request($method, $data = array()) {
		$params = array(
			'http' => array(
				'header' => array('Content-Type: application/json'),
				'method' => 'POST',
				'content' => json_encode(array('request' => $data))
			)
		);
		$url = $this->settings['server'] . $method;
		$ctx = stream_context_create($params);
		if (!($fp = @fopen($url, 'rb', false, $ctx))) {
			throw new PushwooshInternalErrorException('Connection to PushWoosh failed');
		}
		$response = @stream_get_contents($fp);
		if (!$response) {
			throw new PushwooshInternalErrorException('stream_get_contents() failed');
		}
		$response = json_decode($response, true);		
		if (!is_array($response)) {
			throw new PushwooshBadRequestException('Failed to parse response from PushWoosh');
		}
		if (empty($response['status_code']) || empty($response['status_message'])) {
			throw new PushwooshBadRequestException('Bad response format');
			
		}		
		if ($response['status_code'] != 200 || $response['status_message'] != 'OK') {
			throw new PushwooshBadRequestException(sprintf('PushWoosh responded with error: %s with code: %s', $response['status_message'], $response['status_code']) );
		}
		return $response;		
	}

	public function createMessage($applicationCode, $options = array()) {
		$body = array(
			'application' => $applicationCode,
			'auth' => $this->settings['auth'],
			'notifications' => array(
			)			
		);
		$messageDefault = array(
			'send_date' => 'now',
			'content' => array(
				'en' => '',
			)
		);
		if (!isset($options[0])) {
			$options = array($options);
		}
		foreach ($options as $message) {
			$body['notifications'][] = $message + $messageDefault;
		}
		return $this->request('createMessage', $body);		
	}

	public function removeMessage($id) {
		$body = array(
			'auth' => $this->settings['auth'],
			'message' => $id			
		);
		return $this->request('deleteMessage', $body);
	}
}