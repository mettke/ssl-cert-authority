<?php
/**
* Class for reading/writing to the list of Server objects in the database.
*/
class ServerDirectory extends DBDirectory {
	/**
	* Create the new server in the database.
	* @param Server $server object to add
	* @throws ServerAlreadyExistsException if a server with that hostname already exists
	*/
	public function add_server(Server $server) {
		global $event_dir;
		try {
			$stmt = $this->database->prepare("INSERT INTO server SET hostname = ?, port = ?");
			$stmt->bind_param('sd', $server->hostname, $server->port);
			$stmt->execute();
			$server->id = $stmt->insert_id;
			$stmt->close();
			$event_dir->add_log($server, array('action' => 'Server add'));
			$server->trigger_sync();
		} catch(mysqli_sql_exception $e) {
			if($e->getCode() == 1062) {
				// Duplicate entry
				throw new ServerAlreadyExistsException("Server {$server->hostname} already exists");
			} else {
				throw $e;
			}
		}
	}

	/**
	* Get a server from the database by its ID.
	* @param int $id of server
	* @return Server with specified ID
	* @throws ServerNotFoundException if no server with that ID exists
	*/
	public function get_server_by_id($server_id) {
		$stmt = $this->database->prepare("SELECT * FROM server WHERE id = ?");
		$stmt->bind_param('d', $server_id);
		$stmt->execute();
		$result = $stmt->get_result();
		if($row = $result->fetch_assoc()) {
			$server = new Server($row['id'], $row);
		} else {
			throw new ServerNotFoundException('Server does not exist.');
		}
		$stmt->close();
		return $server;
	}

	/**
	* Get a server from the database by its hostname.
	* @param string $hostname of server
	* @return Server with specified hostname
	* @throws ServerNotFoundException if no server with that hostname exists
	*/
	public function get_server_by_hostname($hostname) {
		$stmt = $this->database->prepare("SELECT * FROM server WHERE hostname = ?");
		$stmt->bind_param('s', $hostname);
		$stmt->execute();
		$result = $stmt->get_result();
		if($row = $result->fetch_assoc()) {
			$server = new Server($row['id'], $row);
		} else {
			throw new ServerNotFoundException('Server does not exist');
		}
		$stmt->close();
		return $server;
	}

	/**
	* Get a server from the database by its uuid.
	* @param string $uuid of server
	* @return Server with specified uuid
	* @throws ServerNotFoundException if no server with that uuid exists
	*/
	public function get_server_by_uuid($uuid) {
		$stmt = $this->database->prepare("SELECT * FROM server WHERE uuid = ?");
		$stmt->bind_param('s', $uuid);
		$stmt->execute();
		$result = $stmt->get_result();
		if($row = $result->fetch_assoc()) {
			$server = new Server($row['id'], $row);
		} else {
			throw new ServerNotFoundException('Server does not exist');
		}
		$stmt->close();
		return $server;
	}

	/**
	* List all servers in the database.
	* @param array $include list of extra data to include in response
	* @param array $filter list of field/value pairs to filter results on
	* @return array of Server objects
	*/
	public function list_servers($include = array(), $filter = array()) {
		// WARNING: The search query is not parameterized - be sure to properly escape all input
		$fields = array("server.*");
		$joins = array();
		$where = array();
		$bind = array("");
		foreach($filter as $field => $value) {
			if($value) {
				switch($field) {
				case 'hostname':
					$where[] = "server.hostname REGEXP ?";
					$bind[0] = $bind[0] . "s";
					$bind[] = $this->database->escape_string($value);
					break;
				case 'ip_address':
				case 'rsa_key_fingerprint':
					$where[] = "server.$field = ?";
					$bind[0] = $bind[0] . "s";
					$bind[] = $this->database->escape_string($value);
					break;
				case 'sync_status':
					$clause = implode(',', array_fill(0, count($value), '?'));;
					$bind[0] = $bind[0] . implode('', array_fill(0, count($value), 's'));;
					foreach($value as $entry) {
						$bind[] = $entry;
					}
					$where[] = "server.sync_status IN (". $clause . ")";
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
			$servers = array();
			while($row = $result->fetch_assoc()) {
				$servers[] = new Server($row['id'], $row);
			}
			$stmt->close();
			usort($servers, function($a, $b) {return strnatcasecmp($a->hostname, $b->hostname);});
			# Reverse domain level sort
			#usort($servers, function($a, $b) {return strnatcasecmp(implode('.', array_reverse(explode('.', $a->hostname))), implode('.', array_reverse(explode('.', $b->hostname))));});
			return $servers;
		} catch(mysqli_sql_exception $e) {
			if($e->getCode() == 1139) {
				throw new InvalidRegexpException;
			} else {
				throw $e;
			}
		}
	}
}

class ServerNotFoundException extends Exception {}
class ServerAlreadyExistsException extends Exception {}
