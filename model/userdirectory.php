<?php
/**
* Class for reading/writing to the list of User objects in the database.
*/
class UserDirectory extends DBDirectory {
	/**
	* LDAP connection object
	*/
	private $ldap;
	/**
	* Avoid making multiple LDAP lookups on the same person by caching their details here
	*/
	private $cache_uid;

	public function __construct() {
		parent::__construct();
		global $ldap;
		$this->ldap = $ldap;
		$this->cache_uid = array();
	}

	/**
	* Create the new user in the database.
	* @param User $user object to add
	*/
	public function add_user(User $user) {
		global $event_dir;
		try {
			$stmt = $this->database->prepare("INSERT INTO user SET uid = ?, name = ?, email = ?, active = ?, auth_realm = ?, admin = ?");
			$stmt->bind_param('sssdsd', $user->uid, $user->name, $user->email, $user->active, $user->auth_realm, $user->admin);
			$stmt->execute();
			$user->id = $stmt->insert_id;
			$stmt->close();
			$event_dir->add_log($user, array('action' => 'User add'));
		} catch(mysqli_sql_exception $e) {
			if($e->getCode() == 1062) {
				// Duplicate entry
				throw new UserAlreadyExistsException("User {$user->uid} already exists");
			} else {
				throw $e;
			}
		}		
	}

	/**
	* Get a user from the database by its ID.
	* @param int $id of user
	* @return User with specified ID
	* @throws UserNotFoundException if no user with that ID exists
	*/
	public function get_user_by_id($id) {
		$stmt = $this->database->prepare("SELECT * FROM user WHERE id = ?");
		$stmt->bind_param('d', $id);
		$stmt->execute();
		$result = $stmt->get_result();
		if($row = $result->fetch_assoc()) {
			$user = new User($row['id'], $row);
		} else {
			throw new UserNotFoundException('User does not exist.');
		}
		$stmt->close();
		return $user;
	}

	/**
	* Get a user from the database by its uid. If it does not exist in the database, retrieve it
	* from LDAP and store in the database.
	* @param string $uid of user
	* @return User with specified uid
	* @throws UserNotFoundException if no user with that uid exists
	*/
	public function get_user_by_uid($uid) {
		global $config, $active_user;
		$ldap_enabled = $config['ldap']['enabled'];
		try {
			$user = $this->_get_user_by_uid($uid);
		} catch(UserNotFoundException $e) {
			if ($ldap_enabled == 1) {
				$active_user = $this->_get_user_by_uid('cert-sync');
				$user = new User;
				$user->uid = $uid;
				$this->cache_uid[$uid] = $user;
				$user->auth_realm = 'LDAP';

				$user->get_details_from_ldap();
				$this->add_user($user);
			} else {
				throw new UserNotFoundException('User does not exist.');
			}
		}
		return $user;
	}

	private function _get_user_by_uid($uid) {
		if(isset($this->cache_uid[$uid])) {
			return $this->cache_uid[$uid];
		}
		$stmt = $this->database->prepare("SELECT * FROM user WHERE uid = ?");
		$stmt->bind_param('s', $uid);
		$stmt->execute();
		$result = $stmt->get_result();
		if($row = $result->fetch_assoc()) {
			$user = new User($row['id'], $row);
			$this->cache_uid[$uid] = $user;
		} else {
			throw new UserNotFoundException('User does not exist.');
		}
		$stmt->close();
		return $user;
	}

	/**
	* List all users in the database.
	* @param array $include list of extra data to include in response - currently unused
	* @param array $filter list of field/value pairs to filter results on
	* @return array of User objects
	*/
	public function list_users($include = array(), $filter = array()) {
		// WARNING: The search query is not parameterized - be sure to properly escape all input
		$fields = array("user.*");
		$joins = array();
		$where = array();
		$bind = array("");
		foreach($filter as $field => $value) {
			if($value) {
				switch($field) {
				case 'uid':
					$where[] = "user.uid REGEXP ?";
					$bind[0] = $bind[0] . "s";
					$bind[] = $this->database->escape_string($value);
					break;
				case 'name':
					$where[] = "user.name REGEXP ?";
					$bind[0] = $bind[0] . "s";
					$bind[] = $this->database->escape_string($value);
					break;
				}
			}
		}
		try {
			$stmt = $this->database->prepare("
				SELECT ".implode(", ", $fields)."
				FROM user ".implode(" ", $joins)."
				".(count($where) == 0 ? "" : "WHERE (".implode(") AND (", $where).")")."
				GROUP BY user.id
				ORDER BY user.uid
			");
			if(count($bind) > 1) {
				$stmt->bind_param(...$bind);
			}
			$stmt->execute();
			$result = $stmt->get_result();
			$users = array();
			while($row = $result->fetch_assoc()) {
				$users[] = new User($row['id'], $row);
			}
			$stmt->close();
			return $users;
		} catch(mysqli_sql_exception $e) {
			if($e->getCode() == 1139) {
				throw new InvalidRegexpException;
			} else {
				throw $e;
			}
		}
	}
}

class UserNotFoundException extends Exception {}
class UserAlreadyExistsException extends Exception {}
