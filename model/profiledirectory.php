<?php
/**
* Class for reading/writing to the list of Profile objects in the database.
*/
class ProfileDirectory extends DBDirectory {
	/**
	* Create a new profile in the database.
	* @param Profile $profile object to add
	*/
	public function add_profile(Profile $profile) {
		global $event_dir;
		try {
			$stmt = $this->database->prepare("INSERT INTO profile SET name = ?, certificate_id = ?");
			$stmt->bind_param('sd', $profile->name, $profile->certificate_id);
			$stmt->execute();
			$profile->id = $stmt->insert_id;
			$stmt->close();	
			$event_dir->add_log($profile, array('action' => 'Profile add'));
		} catch(mysqli_sql_exception $e) {
			if($e->getCode() == 1062) {
				// Duplicate entrys
				throw new ProfileAlreadyExistsException("Profile {$profile->name} already exists");
			} else {
				throw $e;
			}
		}
	}

	/**
	* Get a profile from the database by its ID.
	* @param int $id of profile
	* @return Profile with specified ID
	* @throws ProfileNotFoundException if no profile with that ID exists
	*/
	public function get_profile_by_id($id) {
		$stmt = $this->database->prepare("SELECT * FROM profile WHERE id = ?");
		$stmt->bind_param('d', $id);
		$stmt->execute();
		$result = $stmt->get_result();
		if($row = $result->fetch_assoc()) {
			$profile = new Profile($row['id'], $row);
		} else {
			throw new ProfileNotFoundException('Profile does not exist.');
		}
		$stmt->close();
		return $profile;
	}

	/**
	* Get a profile from the database by its name.
	* @param string $name of profile
	* @return Profile with specified name
	* @throws ProfileNotFoundException if no profile with that name exists
	*/
	public function get_profile_by_name($name) {
		$stmt = $this->database->prepare("SELECT * FROM profile WHERE name = ?");
		$stmt->bind_param('s', $name);
		$stmt->execute();
		$result = $stmt->get_result();
		if($row = $result->fetch_assoc()) {
			$profile = new Profile($row['id'], $row);
		} else {
			throw new ProfileNotFoundException('Profile does not exist.');
		}
		$stmt->close();
		return $profile;
	}

	/**
	* List all profiles in the database.
	* @param array $include list of extra data to include in response - currently unused
	* @param array $filter list of field/value pairs to filter results on
	* @return array of User objects
	*/
	public function list_profiles($include = array(), $filter = array()) {
		// WARNING: The search query is not parameterized - be sure to properly escape all input
		$fields = array("profile.*");
		$joins = array();
		$where = array();
		$bind = array("");
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
				".(count($where) == 0 ? "" : "WHERE (".implode(") AND (", $where).")")."
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

class ProfileNotFoundException extends Exception {}
class ProfileAlreadyExistsException extends Exception {}
