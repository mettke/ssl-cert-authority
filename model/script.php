<?php
/**
* Class that represents a script
*/
class Script extends Record {
	/**
	* Defines the database table that this object is stored in
	*/
	protected $table = 'script';

	/**
	* List all log events for this certificate.
	* @return array of Event objects
	*/
	public function get_log() {
		global $event_dir;
		if(is_null($this->id)) throw new BadMethodCallException('Script must be in directory before log entries can be listed');
		return $event_dir->list_events(array(), array("type" => "Script", "object_id" => $this->id));
	}

	/**
	* Write property changes to database and log the changes.
	*/
	public function update() {
		global $event_dir;
		$changes = parent::update();
		foreach($changes as $change) {
			switch($change->field) {
				case 'content':
					$event_dir->add_log($this, array('action' => 'Script updated'));
					continue 2;
			}
			$event_dir->add_log($this, array('action' => 'Setting update', 'value' => $change->new_value, 'oldvalue' => $change->old_value, 'field' => ucfirst(str_replace('_', ' ', $change->field))));
		}
	}

	/**
	* Delete the given script.
	*/
	public function delete() {
		global $event_dir;
		if(is_null($this->id)) throw new BadMethodCallException('Script must be in directory before it can be removed');
		$stmt = $this->database->prepare("DELETE FROM script WHERE id = ?");
		$stmt->bind_param('d', $this->id);
		$stmt->execute();
		$stmt->close();
		$event_dir->add_log($this, array('action' => 'Script del', 'name' => $this->name), LOG_WARNING);
	}

	/**
	* List all services using this script.
	* @param array $include list of extra data to include in response - currently unused
	* @param array $filter list of field/value pairs to filter results on
	* @return array of Service objects
	*/
	public function list_dependent_services($include = array(), $filter = array()) {
		// WARNING: The search query is not parameterized - be sure to properly escape all input
		$fields = array("service.*");
		$joins = array();
		$where = array();
		$bind = array("ddd", $this->id, $this->id, $this->id);
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
				WHERE ((service.restart_script_id = ?) OR (service.status_script_id = ?) OR (service.check_script_id = ?))
				".(count($where) == 0 ? "" : "AND (".implode(") AND (", $where).")")."
				GROUP BY service.id
				ORDER BY service.name
			");
			if(count($bind) > 1) {
				$stmt->bind_param(...$bind);
			}
			$stmt->execute();
			$result = $stmt->get_result();
			$services = array();
			while($row = $result->fetch_assoc()) {
				$services[] = new Service($row['id'], $row);
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
}
