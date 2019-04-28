<?php
/**
* Class that represents a server
*/
class Server extends Record {
	/**
	* Defines the database table that this object is stored in
	*/
	protected $table = 'server';

	/**
	* List all log events for this server.
	* @return array of Event objects
	*/
	public function get_log() {
		global $event_dir;
		if(is_null($this->id)) throw new BadMethodCallException('Server must be in directory before log entries can be listed');
		return $event_dir->list_events(array(), array("type" => "Server", "object_id" => $this->id));
	}


	/**
	* Write property changes to database and log the changes.
	*/
	public function update() {
		global $event_dir;
		$changes = parent::update();
		$resync = FALSE;
		foreach($changes as $change) {
			switch($change->field) {
			case 'hostname':
			case 'port':
				$resync = true;
				break;
			case 'rsa_key_fingerprint':
				if(empty($change->new_value)) $resync = true;
				break;
			}
			$event_dir->add_log($this, array('action' => 'Setting update', 'value' => $change->new_value, 'oldvalue' => $change->old_value, 'field' => ucfirst(str_replace('_', ' ', $change->field))));
		}
		if($resync) {
			$this->request_sync();
		}
	}

	/**
	* Delete the given Server.
	*/
	public function delete() {
		global $event_dir;
		if(is_null($this->id)) throw new BadMethodCallException('Server must be in directory before it can be removed');
		$stmt = $this->database->prepare("DELETE FROM server WHERE id = ?");
		$stmt->bind_param('d', $this->id);
		$stmt->execute();
		$stmt->close();
		$event_dir->add_log($this, array('action' => 'Server del', 'name' => $this->hostname), LOG_WARNING);
	}

	/**
	* Add a note to the server. The note is a piece of text with metadata (who added it and when).
	* @param ServerNote $note to be added
	*/
	public function add_note(ServerNote $note) {
		global $event_dir;
		if(is_null($this->id)) throw new BadMethodCallException('Server must be in directory before notes can be added');
		$stmt = $this->database->prepare("INSERT INTO server_note SET server_id = ?, user_id = ?, date = UTC_TIMESTAMP(), note = ?");
		$stmt->bind_param('dds', $this->id, $note->user->id, $note->note);
		$stmt->execute();
		$note->id = $stmt->insert_id;
		$stmt->close();
		$event_dir->add_log($this, array('action' => 'Note add'));
	}

	/**
	* Retrieve a specific note for this server by its ID.
	* @param int $id of note to retrieve
	* @return ServerNote matching the ID
	* @throws ServerNoteNotFoundException if no note exists with that ID
	*/
	public function get_note_by_id($id) {
		if(is_null($this->id)) throw new BadMethodCallException('Server must be in directory before notes can be listed');
		$stmt = $this->database->prepare("SELECT * FROM server_note WHERE server_id = ? AND id = ? ORDER BY id");
		$stmt->bind_param('dd', $this->id, $id);
		$stmt->execute();
		$result = $stmt->get_result();
		if($row = $result->fetch_assoc()) {
			$note = new ServerNote($row['id'], $row);
		} else {
			throw new ServerNoteNotFoundException('Note does not exist.');
		}
		$stmt->close();
		return $note;
	}

	/**
	* List all notes associated with this server.
	* @return array of ServerNote objects
	*/
	public function list_notes($include = array(), $filter = array()) {
		// WARNING: The search query is not parameterized - be sure to properly escape all input
		$fields = array("server_note.*");
		$joins = array();
		$where = array("server_id = ?");
		$bind = array("d", $this->id);
		foreach($filter as $field => $value) {
			// if($value) {
			// 	switch($field) {
			// 	case 'name':
			// 		$where[] = "server_note.name = ?";
			// 		$bind[0] = $bind[0] . "s";
			// 		$bind[] = $this->database->escape_string($value);
			// 		break;
			// 	}
			// }
		}
		$stmt = $this->database->prepare("
			SELECT ".implode(", ", $fields)."
			FROM server_note ".implode(" ", $joins)."
			".(count($where) == 0 ? "" : "WHERE (".implode(") AND (", $where).")")."
			GROUP BY server_note.id
			ORDER BY server_note.id
		");
		if(count($bind) > 1) {
			$stmt->bind_param(...$bind);
		}
		$stmt->execute();
		$result = $stmt->get_result();
		$notes = array();
		while($row = $result->fetch_assoc()) {
			$notes[] = new ServerNote($row['id'], $row);
		}
		$stmt->close();
		return $notes;
	}

	/**
	* List all profiles using this server.
	* @param array $include list of extra data to include in response - currently unused
	* @param array $filter list of field/value pairs to filter results on
	* @return array of Profile objects
	*/
	public function list_dependent_profiles($include = array(), $filter = array()) {
		// WARNING: The search query is not parameterized - be sure to properly escape all input
		$fields = array("profile.*");
		$joins = array("INNER JOIN server_profile ON profile.id = server_profile.profile_id");
		$where = array("server_profile.server_id = ?");
		$bind = array("d", $this->id);
		foreach($filter as $field => $value) {
			if($value) {
				switch($field) {
				case 'name':
					$where[] = "profile.name REGEXP ?";
					$bind[0] = $bind[0] . "s";
					$bind[] = $this->database->escape_string($value);
					break;
				}
			}
		}
		try {
			$stmt = $this->database->prepare("
				SELECT ".implode(", ", $fields)."
				FROM profile ".implode(" ", $joins)."
				".(count($where) == 0 ? "" : "AND (".implode(") AND (", $where).")")."
				GROUP BY profile.id
				ORDER BY profile.name
			");
			if(count($bind) > 1) {
				$stmt->bind_param(...$bind);
			}
			$stmt->execute();
			$result = $stmt->get_result();
			$profiles = array();
			while($row = $result->fetch_assoc()) {
				$profiles[] = new Profile($row['id'], $row);
			}
			$stmt->close();
			return $profiles;
		} catch(mysqli_sql_exception $e) {
			if($e->getCode() == 1139) {
				throw new InvalidRegexpException;
			} else {
				throw $e;
			}
		}
	}

	public function request_sync() {
		global $event_dir;
		if(is_null($this->id)) throw new BadMethodCallException('Server must be in directory before sync reporting can be done');
		if($this->sync_status != 'proposed') {
			$event_dir->add_log($this, array('action' => 'Sync status change', 'value' => 'Configuration changed'));
			$this->sync_status = 'proposed';
			$this->update();
		}
	}

	/**
	* Trigger a sync for this server.
	*/
	public function trigger_sync() {
		if(is_null($this->id)) throw new BadMethodCallException('Server must be in directory before synchronisation can be triggered');
		global $sync_request_dir;
		$sync_request = new SyncRequest;
		$sync_request->server_id = $this->id;
		$sync_request_dir->add_sync_request($sync_request);
	}

	/**
	* Get the more recent log event that recorded a change in sync status.
	* @todo In a future change we may want to move the 'action' parameter into its own database field.
	* @return ServerEvent last sync status change event
	*/
	public function get_last_sync_event() {
		if(is_null($this->id)) throw new BadMethodCallException('Server must be in directory before log entries can be listed');
		$stmt = $this->database->prepare("SELECT * FROM event WHERE object_id = ? AND type = 'Server' AND details LIKE '{\"action\":\"Sync status change\"%' ORDER BY id DESC LIMIT 1");
		$stmt->bind_param('d', $this->id);
		$stmt->execute();
		$result = $stmt->get_result();
		if($row = $result->fetch_assoc()) {
			$event = new Event($row['id'], $row);
		} else {
			$event = null;
		}
		$stmt->close();
		return $event;
	}

	/**
	* List all pending sync requests for this server.
	* @return array of SyncRequest objects
	*/
	public function list_sync_requests() {
		$stmt = $this->database->prepare("SELECT * FROM sync_request WHERE server_id = ?");
		$stmt->bind_param('d', $this->id);
		$stmt->execute();
		$result = $stmt->get_result();
		$reqs = array();
		while($row = $result->fetch_assoc()) {
			$reqs[] = new SyncRequest($row['id'], $row);
		}
		return $reqs;
	}

	/**
	* Update the sync status for the server and write a log message if the status details have changed.
	* @param string $status "sync success", "sync failure" or "sync warning"
	* @param string $logmsg details of the sync attempt's success or failure
	*/
	public function sync_report($status, $logmsg) {
		global $event_dir;
		if(is_null($this->id)) throw new BadMethodCallException('Server must be in directory before sync reporting can be done');
		$prevlogmsg = $this->get_last_sync_event();
		if(is_null($prevlogmsg) || $logmsg != json_decode($prevlogmsg->details)->value) {
			$logmsg = array('action' => 'Sync status change', 'value' => $logmsg);
			$event_dir->add_log($this, $logmsg);
		}
		$this->sync_status = $status;
		$this->update();
	}

	/**
	* Delete all pending sync requests for this server.
	*/
	public function delete_all_sync_requests() {
		$stmt = $this->database->prepare("DELETE FROM sync_request WHERE server_id = ?");
		$stmt->bind_param('d', $this->id);
		$stmt->execute();
	}
}

class ServerNoteNotFoundException extends Exception {}
	