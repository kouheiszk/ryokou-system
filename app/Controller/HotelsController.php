<?php

App::uses('AppController', 'Controller');

class HotelsController extends AppController {
	public $name = 'Hotels';
	public $uses = array('Hotel');
	public $components = array('RequestHandler');
	
	public function index() {
    }

    public function autocomplete() {
        if(! $this->request->is('ajax')) {
            throw new ForbiddenException(__('このURLにはアクセスできません。'));
        }
        
		$this->autoRender = false;
		$this->RequestHandler->setContent('json');
		$this->RequestHandler->respondAs('application/json');
		
        $conditions = array();
        if(isset($this->request->query['term'])) {
        	$keyword = trim($this->request->query['term']);
			if(! empty($keyword)) {
				$conditions = array(
					'OR' => array(
						'Hotel.name like ?' => '%' . $keyword . '%',
						'Hotel.yomi like ?' => '%' . $keyword . '%',
						'Hotel.roman like ?' => '%' . $keyword . '%',
						'Hotel.location like ?' => '%' . $keyword . '%',
					)
				);
			}
        }
		
		$this->Hotel->recursive = -1;
		$classes = $this->Hotel->find('all', array(
			'conditions' => $conditions,
			'fields' => array(
				'id', 'name'
			),
			'limit' => 10
		));

        echo json_encode($classes);
		exit;
    }
}
