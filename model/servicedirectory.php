<?php
/**
* Class for reading/writing to the list of Service objects in the database.
*/
class ServiceDirectory extends DBDirectory {
	/**
	* Create the new service in the database.
	* @param Service $service object to add
	*/
	public function add_service(Service $service) {
		global $event_dir;
		try {
			$stmt = $this->database->prepare("INSERT INTO service SET name = ?, restart_script_id = ?, status_script_id = ?, check_script_id = ?");
			$stmt->bind_param('sddd', $service->name, $service->restart_script_id, $service->status_script_id, $service->check_script_id);
			$stmt->execute();
			$service->id = $stmt->insert_id;
			$stmt->close();
			$event_dir->add_log($service, array('action' => 'Service add'));
		} catch(mysqli_sql_exception $e) {
			if($e->getCode() == 1062) {
				// Duplicate entrys
				throw new ServiceAlreadyExistsException("Service {$service->name} already exists");
			} else {
				throw $e;
			}
		}
	}

	/**
	* Get a service from the database by its ID.
	* @param int $id of service
	* @return Service with specified ID
	* @throws ServiceNotFoundException if no service with that ID exists
	*/
	public function get_service_by_id($id) {
		$stmt = $this->database->prepare("SELECT * FROM service WHERE id = ?");
		$stmt->bind_param('d', $id);
		$stmt->execute();
		$result = $stmt->get_result();
		if($row = $result->fetch_assoc()) {
			$service = new Service($row['id'], $row);
		} else {
			throw new ServiceNotFoundException('Service does not exist.');
		}
		$stmt->close();
		return $service;
	}

	/**
	* Get a service from the database by its name.
	* @param string $name of service
	* @return Service with specified name
	* @throws ServiceNotFoundException if no service with that name exists
	*/
	public function get_service_by_name($name) {
		$stmt = $this->database->prepare("SELECT * FROM service WHERE name = ?");
		$stmt->bind_param('s', $name);
		$stmt->execute();
		$result = $stmt->get_result();
		if($row = $result->fetch_assoc()) {
			$service = new Service($row['id'], $row);
		} else {
			throw new ServiceNotFoundException('Service does not exist.');
		}
		$stmt->close();
		return $service;
	}

	/**
	* List all services in the database.
	* @param array $include list of extra data to include in response - currently unused
	* @param array $filter list of field/value pairs to filter results on
	* @return array of User objects
	*/
	public function list_services($include = array(), $filter = array()) {
		// WARNING: The search query is not parameterized - be sure to properly escape all input
		$fields = array("service.*");
		$joins = array();
		$where = array();
		$bind = array("");
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

class ServiceNotFoundException extends Exception {}
class ServiceAlreadyExistsException extends Exception {}
