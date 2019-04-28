<?php
/**
* Class for reading from the list of all Event objects in the database.
*/
class EventDirectory extends DBDirectory {
	/**
	* Create log entry from class.
	* @param Record $object object to add
	*/
	public function add_log(Record $object, $details, $level = LOG_INFO) {
		global $active_user;
		if(is_null($object->id)) throw new BadMethodCallException('Object must be in directory before log entries can be added');
		$json = json_encode($details, JSON_UNESCAPED_UNICODE);
		$class = get_class($object);
		$stmt = $this->database->prepare("INSERT INTO event SET object_id = ?, actor_id = ?, date = UTC_TIMESTAMP(), details = ?, type = ?");
		$stmt->bind_param('ddss', $object->id, $active_user->id, $json, $class);
		$stmt->execute();
		$stmt->close();

		if($class == "Server") {
			$name = $object->hostname;
		} elseif($class == "User") {
			$name = $object->uid;
		} else {
			$name = $object->name;
		}

		$text = "KeysScope=\"$class:{$name}\" KeysRequester=\"{$active_user->uid}\"";
		foreach($details as $key => $value) {
			$text .= ' Keys'.ucfirst($key).'="'.str_replace('"', '', $value).'"';
		}
		openlog('keys', LOG_ODELAY, LOG_AUTH);
		syslog($level, $text);
		closelog();
	}

	/**
	* List events of all types stored in the database ordered from most recent.
	* @param array $include list of extra data to include in response - currently unused
	* @param array $filter list of field/value pairs to filter results on
	* @param int|null $limit max results to return
	* @return array of *Event objects
	*/
	public function list_events($include = array(), $filter = array(), $limit = 100) {
		// WARNING: The search query is not parameterized - be sure to properly escape all input
		$fields = array("event.*");
		$joins = array();
		$where = array();
		$bind = array("");
		foreach($filter as $field => $value) {
			if($value) {
				switch($field) {
				case 'type':
					$where[] = "event.type = ?";
					$bind[0] = $bind[0] . "s";
					$bind[] = $this->database->escape_string($value);
					break;
				case 'object_id':
					$where[] = "event.object_id = ?";
					$bind[0] = $bind[0] . "d";
					$bind[] = $value;
					break;
				}
			}
		}
		$stmt = $this->database->prepare("
			SELECT ".implode(", ", $fields)."
			FROM event ".implode(" ", $joins)."
			".(count($where) == 0 ? "" : "WHERE (".implode(") AND (", $where).")")."
			GROUP BY event.id
			ORDER BY event.id DESC
		");
		if(count($bind) > 1) {
			$stmt->bind_param(...$bind);
		}
		$stmt->execute();
		$result = $stmt->get_result();
		$events = array();
		while($row = $result->fetch_assoc()) {
			$events[] = new Event($row['id'], $row);
		}
		$stmt->close();
		return $events;
	}
}

class EventNotFoundException extends Exception {}
