<?php
/**
* Class that represents a profile
*/
class Profile extends Record {
	/**
	* Defines the database table that this object is stored in
	*/
	protected $table = 'profile';

	/**
	* Magic getter method
	* @param string $field to retrieve
	* @return mixed data stored in field
	*/
	public function &__get($field) {
		switch($field) {
		case 'certificate':
			$certificate = new Certificate($this->data['certificate_id']);
			return $certificate;
		default:
			return parent::__get($field);
		}
	}

	/**
	* List all log events for this profile.
	* @return array of Event objects
	*/
	public function get_log() {
		global $event_dir;
		if(is_null($this->id)) throw new BadMethodCallException('Profile must be in directory before log entries can be listed');
		return $event_dir->list_events(array(), array("type" => "Profile", "object_id" => $this->id));
	}

	/**
	* Add the specified server to this profile.
	* @param Server $server to add
	*/
	public function add_server(Server $server) {
		global $event_dir;
		if(is_null($this->id)) throw new BadMethodCallException('Profile must be in directory before servers can be added');
		if(is_null($server->id)) throw new InvalidArgumentException('Server must be in directory before it can be added to profile');
		try {
			$stmt = $this->database->prepare("INSERT INTO server_profile SET profile_id = ?, server_id = ?");
			$stmt->bind_param('dd', $this->id, $server->id);
			$stmt->execute();
			$stmt->close();
			$event_dir->add_log($this, array('action' => 'Server rel add', 'name' => $server->hostname));
			$server->request_sync();
		} catch(mysqli_sql_exception $e) {
			if($e->getCode() == 1062) {
				// Duplicate entry - ignore
			} else {
				throw $e;
			}
		}
	}

	/**
	* List all servers bond to this profile.
	* @param array $include list of extra data to include in response - currently unused
	* @param array $filter list of field/value pairs to filter results on
	* @return array of Server objects
	*/
	public function list_servers($include = array(), $filter = array()) {
		// WARNING: The search query is not parameterized - be sure to properly escape all input
		$fields = array("server.*");
		$joins = array("INNER JOIN server_profile ON server.id = server_profile.server_id");
		$where = array("server_profile.profile_id = ?");
		$bind = array("d", $this->id);
		foreach($filter as $field => $value) {
			if($value) {
				switch($field) {
				case 'name':
					$where[] = "server.hostname REGEXP ?";
					$bind[0] = $bind[0] . "s";
					$bind[] = $this->database->escape_string($value);
					break;
				}
			}
		}
		try {
			$stmt = $this->database->prepare("
				SELECT ".implode(", ", $fields)."
				FROM server ".implode(" ", $joins)."
				".(count($where) == 0 ? "" : "WHERE (".implode(") AND (", $where).")")."
				GROUP BY server.id
				ORDER BY server.hostname
			");
			if(count($bind) > 1) {
				$stmt->bind_param(...$bind);
			}
			$stmt->execute();
			$result = $stmt->get_result();
			$profiles = array();
			while($row = $result->fetch_assoc()) {
				$profiles[] = new Server($row['id'], $row);
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

	/**
	* Delete the specified server from this profile.
	* @param Server $server to remove
	*/
	public function delete_server(Server $server) {
		global $event_dir;
		if(is_null($this->id)) throw new BadMethodCallException('Profile must be in directory before servers can be removed');
		if(is_null($server->id)) throw new InvalidArgumentException('Server must be in directory before it can be removed from profile');
		$server_id = $server->id;
		$stmt = $this->database->prepare("DELETE FROM server_profile WHERE profile_id = ? AND server_id = ?");
		$stmt->bind_param('dd', $this->id, $server_id);
		$stmt->execute();
		$stmt->close();
		$event_dir->add_log($this, array('action' => 'Server rel del', 'name' => $server->hostname));
		$server->request_sync();
	}

	/**
	* Add the specified service to this profile.
	* @param Service $service to add
	*/
	public function add_service(Service $service) {
		global $event_dir;
		if(is_null($this->id)) throw new BadMethodCallException('Profile must be in directory before services can be added');
		if(is_null($service->id)) throw new InvalidArgumentException('Service must be in directory before it can be added to profile');
		$service_id = $service->id;
		try {
			$stmt = $this->database->prepare("INSERT INTO service_profile SET profile_id = ?, service_id = ?");
			$stmt->bind_param('dd', $this->id, $service_id);
			$stmt->execute();
			$stmt->close();
			$event_dir->add_log($this, array('action' => 'Service rel add', 'name' => $service->name));
			$this->request_sync();
		} catch(mysqli_sql_exception $e) {
			if($e->getCode() == 1062) {
				// Duplicate entry - ignore
			} else {
				throw $e;
			}
		}
	}

	/**
	* List all services bond to this profile.
	* @param array $include list of extra data to include in response - currently unused
	* @param array $filter list of field/value pairs to filter results on
	* @return array of Service objects
	*/
	public function list_services($include = array(), $filter = array()) {
		// WARNING: The search query is not parameterized - be sure to properly escape all input
		$fields = array("service.*");
		$joins = array("INNER JOIN service_profile ON service.id = service_profile.service_id");
		$where = array("service_profile.profile_id = ?");
		$bind = array("d", $this->id);
		foreach($filter as $field => $value) {
			if($value) {
				switch($field) {
				case 'name':
					$where[] = "service.name REGEXP ?";
					$bind[0] = $bind[0] . "s";
					$bind[] = $this->database->escape_string($value);
					break;
				}
			}
		}
		try {
			$stmt = $this->database->prepare("
				SELECT ".implode(", ", $fields)."
				FROM service ".implode(" ", $joins)."
				".(count($where) == 0 ? "" : "WHERE (".implode(") AND (", $where).")")."
				GROUP BY service.id
				ORDER BY service.name
			");
			if(count($bind) > 1) {
				$stmt->bind_param(...$bind);
			}
			$stmt->execute();
			$result = $stmt->get_result();
			$profiles = array();
			while($row = $result->fetch_assoc()) {
				$profiles[] = new Service($row['id'], $row);
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

	/**
	* Delete the specified service from this profile.
	* @param Service $service to delete
	*/
	public function delete_service(Service $service) {
		global $event_dir;
		if(is_null($this->id)) throw new BadMethodCallException('Profile must be in directory before services can be removed');
		if(is_null($service->id)) throw new InvalidArgumentException('Service must be in directory before it can be removed from profile');
		$service_id = $service->id;
		$stmt = $this->database->prepare("DELETE FROM service_profile WHERE profile_id = ? AND service_id = ?");
		$stmt->bind_param('dd', $this->id, $service_id);
		$stmt->execute();
		$stmt->close();
		$event_dir->add_log($service, array('action' => 'Service rel del', 'name' => $service->name));
		$this->request_sync();
	}

	/**
	* Write property changes to database and log the changes.
	*/
	public function update() {
		global $event_dir;
		$changes = parent::update();
		foreach($changes as $change) {
			switch($change->field) {
				case 'certificate_id':
					$cert_old = new Certificate($change->old_value);
					$cert_new = new Certificate($change->new_value);
					$event_dir->add_log($this, array('action' => 'Setting update', 'value' => $cert_new->name, 'oldvalue' => $cert_old->name, 'field' => ucfirst(str_replace('_', ' ', $change->field))), LOG_WARNING);
					continue 2;
			}
			$event_dir->add_log($this, array('action' => 'Setting update', 'value' => $change->new_value, 'oldvalue' => $change->old_value, 'field' => ucfirst(str_replace('_', ' ', $change->field))));
		}
		if(!empty($changes)) {
			$this->request_sync();
		}
	}

	/**
	* Delete the given profile.
	*/
	public function delete() {
		global $event_dir;
		if(is_null($this->id)) throw new BadMethodCallException('Profile must be in directory before it can be removed');
		$stmt = $this->database->prepare("DELETE FROM profile WHERE id = ?");
		$stmt->bind_param('d', $this->id);
		$stmt->execute();
		$stmt->close();
		$event_dir->add_log($this, array('action' => 'Profile del', 'name' => $this->name), LOG_WARNING);
		$this->request_sync();
	}

	/**
	* Trigger a sync for all servers associated with this profile.
	*/
	public function request_sync() {
		$servers = $this->list_servers();
		foreach($servers as $server) {
			$server->request_sync();
		}
	}
}
