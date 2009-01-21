<?php

class IndexController extends Zend_Controller_Action {
	
	public function indexAction() {		
		$app = new Rest();
		$server = new Zend_Rest_Server();
		$server->setClass($app);
		$server->handle();
	}
}