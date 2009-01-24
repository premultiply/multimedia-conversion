<?php

/*
 * klasa odpowiedzialna za wysyłanie statusu pracy do klienta
 */
class Status {
	public function respond($url, $content) {
		$client = new Zend_Http_Client($url);
		$client->setParameterPost('status', $content);
		$client->request('POST');
	}
}
