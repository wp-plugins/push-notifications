<?php

class PushwooshInternalErrorException extends Exception {

}

class PushwooshBadRequestException extends Exception {

}

class PushWoosh {

	protected $settings = array(
		'server' => 'https://cp.pushwoosh.com/json/1.3/',
		'auth' => ''
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
		if (!($response = file_get_contents($url, false, $ctx))) {
			throw new PushwooshInternalErrorException('Connection to PushWoosh failed');
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
		if (!is_array($options) || !isset($options[0])) {
			$options = array($options);
		}
		foreach ($options as $message) {
			$body['notifications'][] = $message + $messageDefault;
		}
		return $this->request('createMessage', $body);		
	}
	public function compileDevicesFilter($applicationCode, $tags = array()) {
		$conditions = array('A("' . $applicationCode . '")');
		if (!count($tags)) {
			return $conditions[0];
		}
		foreach ($tags as $condition) {
			$value = $condition['value'];
			if (empty($condition['type'])) {
				continue;
			}
			switch ($condition['type']) {
			case 1:
				if (is_array($value)) {
					$value = array_map('intval', $value);
				} else {
					$value = intval($value);
				}
				break;
			case 2:
			case 3:
				if ($condition['operator'] == 'IN') {
					$value = explode(',', $value);
				}
				break;
			}
			$tmp = array('"' . $condition['name'] . '"', $condition['operator'], json_encode($value));
			$conditions[] = 'T(' . implode(', ', $tmp) . ')';
		}
		return implode(' * ', $conditions);
	}

	public function createTargetedMessage($applicationCode, $message = array(), $options = array()) {
		$body = array(
			'auth' => $this->settings['auth'],
			'send_date' => 'now',
			'content' => array(
				'en' => ''
				)
			);
        $body = array_merge($body, $options);
		$body = array_merge($body, $message);
        $body['devices_filter'] = $this->compileDevicesFilter($applicationCode, array());
		return $this->request('createTargetedMessage', $body);
	}

	public function removeMessage($id) {
		$body = array(
			'auth' => $this->settings['auth'],
			'message' => $id			
		);
		return $this->request('deleteMessage', $body);
	}

}
