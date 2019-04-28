<?php
/**
* Class that represents a service
*/
class Service extends Record {
	/**
	* Defines the database table that this object is stored in
	*/
	protected $table = 'service';

	/**
	* List all log events for this service.
	* @return array of Event objects
	*/
	public function get_log() {
		global $event_dir;
		if(is_null($this->id)) throw new BadMethodCallException('Service must be in directory before log entries can be listed');
		return $event_dir->list_events(array(), array("type" => "Service", "object_id" => $this->id));
	}

	/**
	* Magic getter method
	* @param string $field to retrieve
	* @return mixed data stored in field
	*/
	public function &__get($field) {
		switch($field) {
		case 'restart_script':
			$script = new Script($this->data['restart_script_id']);
			return $script;
		case 'status_script':
			$script = new Script($this->data['status_script_id']);
			return $script;
		case 'check_script':
			$script = new Script($this->data['check_script_id']);
			return $script;
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
				case 'restart_script_id':
				case 'status_script_id':
				case 'check_script_id':
					$script_old = new Script($change->old_value);
					$script_new = new Script($change->new_value);
					$event_dir->add_log($this, array('action' => 'Setting update', 'value' => $script_new->name, 'oldvalue' => $script_old->name, 'field' => ucfirst(str_replace('_', ' ', $change->field))));
					continue 2;
			}
			$event_dir->add_log($this, array('action' => 'Setting update', 'value' => $change->new_value, 'oldvalue' => $change->old_value, 'field' => ucfirst(str_replace('_', ' ', $change->field))));
		}
	}

	/**
	* Delete the given service.
	*/
	public function delete() {
		global $event_dir;
		if(is_null($this->id)) throw new BadMethodCallException('Service must be in directory before it can be removed');
		$stmt = $this->database->prepare("DELETE FROM service WHERE id = ?");
		$stmt->bind_param('d', $this->id);
		$stmt->execute();
		$stmt->close();
		$event_dir->add_log($this, array('action' => 'Service del', 'name' => $this->name), LOG_WARNING);
	}

	/**
	* Create the new variable for this service.
	* @param ServiceVariable $variable object to add
	*/
	public function add_variable(ServiceVariable $variable) {
		global $event_dir;
		try {
			$stmt = $this->database->prepare("INSERT INTO variable SET name = ?, service_id = ?, description = ?, value = ?");
			$stmt->bind_param('sdss', $variable->name, $this->id, $variable->description, $variable->value);
			$stmt->execute();
			$variable->id = $stmt->insert_id;
			$stmt->close();
			$event_dir->add_log($variable, array('action' => 'Variable add', 'service' => $this->name));
		} catch(mysqli_sql_exception $e) {
			if($e->getCode() == 1062) {
				// Duplicate entrys
				throw new VariableAlreadyExistsException("Variable {$variable->name} already exists");
			} else {
				throw $e;
			}
		}
	}

	/**
	* Get a variable from the database by its name.
	* @param string $name of variable
	* @return ServiceVariable with specified name
	* @throws ServiceNotFoundException if no service with that name exists
	*/
	public function get_variable_by_name($name) {
		$stmt = $this->database->prepare("SELECT * FROM variable WHERE name = ? AND service_id = ?");
		$stmt->bind_param('sd', $name, $this->id);
		$stmt->execute();
		$result = $stmt->get_result();
		if($row = $result->fetch_assoc()) {
			$service = new ServiceVariable($row['id'], $row);
		} else {
			throw new VariableNotFoundException('Variable does not exist.');
		}
		$stmt->close();
		return $service;
	}

	/**
	* List all variables in the database.
	* @param array $include list of extra data to include in response - currently unused
	* @param array $filter list of field/value pairs to filter results on
	* @return array of ServiceVariable objects
	*/
	public function list_variables($include = array(), $filter = array()) {
		// WARNING: The search query is not parameterized - be sure to properly escape all input
		$fields = array("variable.*");
		$joins = array();
		$where = array("variable.service_id = ?");
		$bind = array("d", $this->id);
		foreach($filter as $field => $value) {
			if($value) {
				switch($field) {
				case 'name':
					$where[] = "variable.name REGEXP ?";
					$bind[0] = $bind[0] . "s";
					$bind[] = $this->database->escape_string($value);
					break;
				}
			}
		}
		try {
			$stmt = $this->database->prepare("
				SELECT ".implode(", ", $fields)."
				FROM variable ".implode(" ", $joins)."
				".(count($where) == 0 ? "" : "WHERE (".implode(") AND (", $where).")")."
				GROUP BY variable.id
				ORDER BY variable.name
			");
			if(count($bind) > 1) {
				$stmt->bind_param(...$bind);
			}
			$stmt->execute();
			$result = $stmt->get_result();
			$services = array();
			while($row = $result->fetch_assoc()) {
				$services[] = new ServiceVariable($row['id'], $row);
			}
			$stmt->close();
			return $services;
		} catch(mysqli_sql_exception $e) {
			if($e->getCode() == 1139) {
				throw new InvalidRegexpException;
			} else {
				throw $e;
			}
		}
	}

	/**
	* List all profiles using this service.
	* @param array $include list of extra data to include in response - currently unused
	* @param array $filter list of field/value pairs to filter results on
	* @return array of Profile objects
	*/
	public function list_dependent_profiles($include = array(), $filter = array()) {
		// WARNING: The search query is not parameterized - be sure to properly escape all input
		$fields = array("profile.*");
		$joins = array("INNER JOIN service_profile ON profile.id = service_profile.profile_id");
		$where = array("service_profile.service_id = ?");
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
}
class VariableAlreadyExistsException extends Exception {}
class VariableNotFoundException extends Exception {}
	