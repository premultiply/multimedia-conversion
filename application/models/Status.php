<?php

/*
 * klasa odpowiedzialna za wysyłanie statusu pracy do klienta
 */
class Status {
	public function respond($url, $content) {
		try{
			$client = new Zend_Http_Client($url);
			$client->setParameterPost('status', $content);
			$valid = Zend_Uri::check($url);
			$client->request('POST');
		} catch (Zend_Http_Exception $e) {
			echo "Caught exception: " . get_class($e) . "\n";
    		echo "Message: " . $e->getMessage() . "\n";
		}
	}
}
