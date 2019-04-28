<?php
/**
* Class for reading/writing to the list of Script objects in the database.
*/
class ScriptDirectory extends DBDirectory {
	/**
	* Create a new script in the database.
	* @param Script $script object to add
	*/
	public function add_script(Script $script) {
		global $event_dir;
		try {
			$stmt = $this->database->prepare("INSERT INTO script SET name = ?, content = ?, type = ?");
			$stmt->bind_param('sss', $script->name, $script->content, $script->type);
			$stmt->execute();
			$script->id = $stmt->insert_id;
			$stmt->close();
			$event_dir->add_log($script, array('action' => 'Script add'));
		} catch(mysqli_sql_exception $e) {
			if($e->getCode() == 1062) {
				// Duplicate entrys
				throw new ScriptAlreadyExistsException("Script {$script->name} already exists");
			} else {
				throw $e;
			}
		}
	}

	/**
	* Get a script from the database by its ID.
	* @param int $id of script
	* @return Script with specified ID
	* @throws ScriptNotFoundException if no script with that ID exists
	*/
	public function get_script_by_id($id) {
		$stmt = $this->database->prepare("SELECT * FROM script WHERE id = ?");
		$stmt->bind_param('d', $id);
		$stmt->execute();
		$result = $stmt->get_result();
		if($row = $result->fetch_assoc()) {
			$script = new Script($row['id'], $row);
		} else {
			throw new ScriptNotFoundException('Script does not exist.');
		}
		$stmt->close();
		return $script;
	}

	/**
	* Get a script from the database by its name.
	* @param string $name of script
	* @return Script with specified name
	* @throws ScriptNotFoundException if no script with that name exists
	*/
	public function get_script_by_name($name) {
		$stmt = $this->database->prepare("SELECT * FROM script WHERE name = ?");
		$stmt->bind_param('s', $name);
		$stmt->execute();
		$result = $stmt->get_result();
		if($row = $result->fetch_assoc()) {
			$script = new Script($row['id'], $row);
		} else {
			throw new ScriptNotFoundException('Script does not exist.');
		}
		$stmt->close();
		return $script;
	}

	/**
	* List all scripts in the database.
	* @param array $include list of extra data to include in response - currently unused
	* @param array $filter list of field/value pairs to filter results on
	* @return array of User objects
	*/
	public function list_scripts($include = array(), $filter = array()) {
		// WARNING: The search query is not parameterized - be sure to properly escape all input
		$fields = array("script.*");
		$joins = array();
		$where = array();
		$bind = array("");
		foreach($filter as $field => $value) {
			if($value) {
				switch($field) {
				case 'name':
					$where[] = "script.name REGEXP ?";
					$bind[0] = $bind[0] . "s";
					$bind[] = $this->database->escape_string($value);
					break;
				case 'type':
					$clause = implode(',', array_fill(0, count($value), '?'));;
					$bind[0] = $bind[0] . implode('', array_fill(0, count($value), 's'));;
					foreach($value as $entry) {
						$bind[] = $entry;
					}
					$where[] = "script.type IN (". $clause . ")";
					break;
				}
			}
		}
		try {
			$stmt = $this->database->prepare("
				SELECT ".implode(", ", $fields)."
				FROM script ".implode(" ", $joins)."
				".(count($where) == 0 ? "" : "WHERE (".implode(") AND (", $where).")")."
				GROUP BY script.id
				ORDER BY script.name
			");
			if(count($bind) > 1) {
				$stmt->bind_param(...$bind);
			}
			$stmt->execute();
			$result = $stmt->get_result();
			$scripts = array();
			while($row = $result->fetch_assoc()) {
				$scripts[] = new Script($row['id'], $row);
			}
			$stmt->close();
			return $scripts;
		} catch(mysqli_sql_exception $e) {
			if($e->getCode() == 1139) {
				throw new InvalidRegexpException;
			} else {
				throw $e;
			}
		}
	}
}

class ScriptNotFoundException extends Exception {}
class ScriptAlreadyExistsException extends Exception {}
