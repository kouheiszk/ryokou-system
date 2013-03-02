<?php

App::uses('Model', 'Model');

class AppModel extends Model {
	public $actsAs = array('Acl' => array('type' => 'requester'));

    public function parentNode() {
        return null;
    }
}
