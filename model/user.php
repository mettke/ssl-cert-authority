<?php
/**
* Class that represents a user of this system
*/
class User extends Record {
	/**
	* Defines the database table that this object is stored in
	*/
	protected $table = 'user';
	/**
	* Defines the field that is the primary key of the table
	*/
	protected $idfield = 'id';
	/**
	* LDAP connection object
	*/
	private $ldap;

	public function __construct($id = null, $preload_data = array()) {
		parent::__construct($id, $preload_data);
		global $ldap;
		$this->ldap = $ldap;
	}

	/**
	* List all log events for this certificate.
	* @return array of Event objects
	*/
	public function get_log() {
		global $event_dir;
		if(is_null($this->id)) throw new BadMethodCallException('User must be in directory before log entries can be listed');
		return $event_dir->list_events(array(), array("type" => "User", "object_id" => $this->id));
	}

	/**
	* Write property changes to database and log the changes.
	* Triggers a resync if the user was activated/deactivated.
	*/
	public function update() {
		global $event_dir;
		$changes = parent::update();
		foreach($changes as $change) {
			$loglevel = LOG_INFO;
			switch($change->field) {
			case 'active':
				if($change->new_value == 1) $loglevel = LOG_WARNING;
				break;
			case 'csrf_token':
				continue 2;
			}
			$event_dir->add_log($this, array('action' => 'Setting update', 'value' => $change->new_value, 'oldvalue' => $change->old_value, 'field' => ucfirst(str_replace('_', ' ', $change->field))), $loglevel);
		}
	}

	/**
	* Delete the given user.
	*/
	public function delete() {
		global $event_dir;
		if(is_null($this->id)) throw new BadMethodCallException('User must be in directory before it can be removed');
		$stmt = $this->database->prepare("DELETE FROM user WHERE id = ?");
		$stmt->bind_param('d', $this->id);
		$stmt->execute();
		$stmt->close();
		$event_dir->add_log($this, array('action' => 'User del', 'name' => $this->name), LOG_WARNING);
	}

	/**
	* Add an alert to be displayed to this user on their next normal page load.
	* @param UserAlert $alert to be displayed
	*/
	public function add_alert(UserAlert $alert) {
		if(is_null($this->id)) throw new BadMethodCallException('User must be in directory before alerts can be added');
		$stmt = $this->database->prepare("INSERT INTO user_alert SET user_id = ?, class = ?, content = ?, escaping = ?");
		$stmt->bind_param('dssd', $this->id, $alert->class, $alert->content, $alert->escaping);
		$stmt->execute();
		$alert->id = $stmt->insert_id;
		$stmt->close();
	}

	/**
	* List all alerts for this user *and* delete them.
	* @return array of UserAlert objects
	*/
	public function pop_alerts() {
		if(is_null($this->id)) throw new BadMethodCallException('User must be in directory before alerts can be listed');
		$stmt = $this->database->prepare("SELECT * FROM user_alert WHERE user_id = ?");
		$stmt->bind_param('d', $this->id);
		$stmt->execute();
		$result = $stmt->get_result();
		$alerts = array();
		$alert_ids = array();
		while($row = $result->fetch_assoc()) {
			$alerts[] = new UserAlert($row['id'], $row);
			$alert_ids[] = $row['id'];
		}
		$stmt->close();
		if(count($alert_ids) > 0) {
			$this->database->query("DELETE FROM user_alert WHERE id IN (".implode(", ", $alert_ids).")");
		}
		return $alerts;
	}

	/**
	* Return HTML containing the user's CSRF token for inclusion in a POST form.
	* Also includes a random string of the same length to help guard against http://breachattack.com/
	* @return string HTML
	*/
	public function get_csrf_field() {
		return '<input type="hidden" name="csrf_token" value="'.hesc($this->get_csrf_token()).'"><!-- '.hash("sha512", mt_rand(0, mt_getrandmax())).' -->'."\n";
	}

	/**
	* Return the user's CSRF token. Generate one if they do not yet have one.
	* @return string CSRF token
	*/
	public function get_csrf_token() {
		if(is_null($this->id)) throw new BadMethodCallException('User must be in directory before CSRF token can be generated');
		if(!isset($this->data['csrf_token'])) {
			$this->data['csrf_token'] = hash("sha512", mt_rand(0, mt_getrandmax()));
			$this->update();
		}
		return $this->data['csrf_token'];
	}

	/**
	* Check the given string against the user's CSRF token.
	* @return bool true on string match
	*/
	public function check_csrf_token($token) {
		return $token === $this->get_csrf_token();
	}

	/**
	* Retrieve the user's details from LDAP.
	* @throws UserNotFoundException if the user is not found in LDAP
	*/
	public function get_details_from_ldap() {
		global $config;
		$attributes = array();
		$attributes[] = 'dn';
		$attributes[] = $config['ldap']['user_id'];
		$attributes[] = $config['ldap']['user_name'];
		$attributes[] = $config['ldap']['user_email'];
		$filter = $config['ldap']['filter'];
		if(empty($filter)) {
			$filter = LDAP::escape($config['ldap']['user_id']).'='.LDAP::escape($this->uid);
		} else {
			$filter = "(&(".LDAP::escape($config['ldap']['user_id']).'='.LDAP::escape($this->uid).")$filter)";
		}
		if(isset($config['ldap']['user_active'])) {
			$attributes[] = $config['ldap']['user_active'];
		}
		$ldapusers = $this->ldap->search($config['ldap']['dn_user'], $filter, array_keys(array_flip($attributes)));
		if($ldapuser = reset($ldapusers)) {
			$this->auth_realm = 'LDAP';
			$this->uid = $ldapuser[strtolower($config['ldap']['user_id'])];
			$this->name = $ldapuser[strtolower($config['ldap']['user_name'])];
			$this->email = $ldapuser[strtolower($config['ldap']['user_email'])];
			if(isset($config['ldap']['user_active'])) {
				$this->active = 0;
				if(isset($config['ldap']['user_active_true'])) {
					$this->active = intval($ldapuser[strtolower($config['ldap']['user_active'])] == $config['ldap']['user_active_true']);
				} elseif(isset($config['ldap']['user_active_false'])) {
					$this->active = intval($ldapuser[strtolower($config['ldap']['user_active'])] != $config['ldap']['user_active_false']);
				}
			} else {
				$this->active = 1;
			}
		} else {
			throw new UserNotFoundException('User does not exist.');
		}
	}
}
