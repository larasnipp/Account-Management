<?php
App::uses('AppController', 'Controller');
/**
 * Threads Controller
 *
 * @property Thread $Thread
 */
class ThreadsController extends AppController {

	public function beforeFilter() {
		parent::beforeFilter();
	}

	public function isAuthorized($user) {
		if (parent::isAuthorized($user)) {
			return true;
		}

		// Allow all users to access view and add
		if (in_array($this->action, array('view','add'))) {
			return true;
		}

		return false;
	}

/**
 * view method
 *
 * @param string $id
 * @return void
 */
	public function view($id = null) {
		array_push($this->helpers, 'Time', 'Text', 'Markdown');

		$this->Thread->id = $id;
		if (!$this->Thread->exists()) {
			throw new NotFoundException(__('Invalid thread'));
		}

		$this->Thread->Behaviors->attach('Containable');
		$this->Thread->contain(array('User','Board','Post' => 'User'));

		$thread = $this->Thread->read(null, $id);

		$this->set('thread', $thread);
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		$this->Thread->Board->recursive = 0;

		$board = $this->Thread->Board->findById($this->passedArgs['board']);

		if (empty($board)) {
			throw new NotFoundException(__('Invalid board'));
		}

		if (
			$board['Board']['public'] == 0 &&
			!in_array($this->Auth->user('role'), array('admin', 'auditor')) &&
			!in_array($this->Auth->user('class'), array('supporting', 'regular'))
		) {
			$this->Auth->flash('Access to that board is restricted');
			$this->redirect(array('controler' => 'boards', 'action' => 'index'));
		}

		if ($this->request->is('post')) {
			$this->request->data['Thread']['board_id'] = $board['Board']['id'];
			$this->request->data['Thread']['user_id'] = $this->Auth->user('id');

			$this->request->data['Post'][0]['user_id'] = $this->Auth->user('id');
			$this->request->data['Post'][0]['mailed'] = 0;

			$this->Thread->create();
			if ($this->Thread->saveAssociated($this->request->data)) {
				$this->Session->setFlash(__('The post has been saved'));
				$this->redirect(array('controller' => 'boards', 'action' => 'view', $board['Board']['id']));
			} else {
				$this->Session->setFlash(__('The post could not be saved. Please, try again.'));
			}
		}
	}

/* Admin Role Functions */

/**
 * edit method
 *
 * @param string $id
 * @return void
 */
	public function edit($id = null) {
		$this->Thread->id = $id;
		if (!$this->Thread->exists()) {
			throw new NotFoundException(__('Invalid thread'));
		}
		if ($this->request->is('post') || $this->request->is('put')) {
			if ($this->Thread->save($this->request->data)) {
				$this->Session->setFlash(__('The thread has been saved'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The thread could not be saved. Please, try again.'));
			}
		} else {
			$this->request->data = $this->Thread->read(null, $id);
		}
		$boards = $this->Thread->Board->find('list');
		$users = $this->Thread->User->find('list');
		$this->set(compact('boards', 'users'));
	}

/**
 * delete method
 *
 * @param string $id
 * @return void
 */
	public function delete($id = null) {
		if (!$this->request->is('post')) {
			throw new MethodNotAllowedException();
		}
		$this->Thread->id = $id;
		if (!$this->Thread->exists()) {
			throw new NotFoundException(__('Invalid thread'));
		}
		if ($this->Thread->delete()) {
			$this->Session->setFlash(__('Thread deleted'));
			$this->redirect(array('action' => 'index'));
		}
		$this->Session->setFlash(__('Thread was not deleted'));
		$this->redirect(array('action' => 'index'));
	}
}
