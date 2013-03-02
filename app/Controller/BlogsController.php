<?php
App::uses('AppController', 'Controller');
/**
 * Blogs Controller
 *
 * @property Blog $Blog
 */
class BlogsController extends AppController {

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->Blog->recursive = 0;
		$this->set('blogs', $this->paginate());
	}

/**
 * view method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function view($id = null) {
		if (!$this->Blog->exists($id)) {
			throw new NotFoundException(__('Invalid blog'));
		}
		$options = array('conditions' => array('Blog.' . $this->Blog->primaryKey => $id));
		$this->set('blog', $this->Blog->find('first', $options));
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		if ($this->request->is('post')) {
			$this->Blog->create();
			if ($this->Blog->save($this->request->data)) {
				$this->flash(__('Blog saved.'), array('action' => 'index'));
			} else {
			}
		}
		$users = $this->Blog->User->find('list');
		$this->set(compact('users'));
	}

/**
 * edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function edit($id = null) {
		if (!$this->Blog->exists($id)) {
			throw new NotFoundException(__('Invalid blog'));
		}
		if ($this->request->is('post') || $this->request->is('put')) {
			if ($this->Blog->save($this->request->data)) {
				$this->flash(__('The blog has been saved.'), array('action' => 'index'));
			} else {
			}
		} else {
			$options = array('conditions' => array('Blog.' . $this->Blog->primaryKey => $id));
			$this->request->data = $this->Blog->find('first', $options);
		}
		$users = $this->Blog->User->find('list');
		$this->set(compact('users'));
	}

/**
 * delete method
 *
 * @throws NotFoundException
 * @throws MethodNotAllowedException
 * @param string $id
 * @return void
 */
	public function delete($id = null) {
		$this->Blog->id = $id;
		if (!$this->Blog->exists()) {
			throw new NotFoundException(__('Invalid blog'));
		}
		$this->request->onlyAllow('post', 'delete');
		if ($this->Blog->delete()) {
			$this->flash(__('Blog deleted'), array('action' => 'index'));
		}
		$this->flash(__('Blog was not deleted'), array('action' => 'index'));
		$this->redirect(array('action' => 'index'));
	}
}
