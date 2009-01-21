<?php

/*
 * glowny kontroler obslugujacy zapytania rest, niezbyt skomplikowany, cala reszta jest w modelu Rest
 */

class IndexController extends Zend_Controller_Action {
	
	public function indexAction() {		
		$app = new Rest();
		$server = new Zend_Rest_Server();
		$server->setClass($app);
		$server->handle();
	}
}