<?php
/**
* Class that represents a note associated with a server
*/
class ServerNote extends Record {
	/**
	* Defines the database table that this object is stored in
	*/
	protected $table = 'server_note';

	public function __construct($id = null, $preload_data = array()) {
		parent::__construct($id, $preload_data);
		global $active_user;
		if(is_null($id)) $this->id = $active_user->id;
	}

	/**
	* Magic getter method
	* @param string $field to retrieve
	* @return mixed data stored in field
	*/
	public function &__get($field) {
		global $user_dir;
		switch($field) {
		case 'user':
			$user = new User($this->id);
			return $user;
		case 'server':
			$server = new Server($this->server_id);
			return $server;
		default:
			return parent::__get($field);
		}
	}

	/**
	* Write property changes to database and log the changes.
	*/
	public function update() {
		global $event_dir;
		$changes = parent::update();
		foreach($changes as $change) {
			switch($change->field) {
				case 'note':
					$event_dir->add_log($this->server, array('action' => 'Note modified'));
					break;
			}
		}
	}

	/**
	* Delete the specified note.
	*/
	public function delete() {
		global $event_dir;
		$stmt = $this->database->prepare("DELETE FROM server_note WHERE id = ?");
		$stmt->bind_param('d', $this->id);
		$stmt->execute();
		$stmt->close();
		$event_dir->add_log($this->server, array('action' => 'Note del'));
	}
}
