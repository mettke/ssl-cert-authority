<?php
/**
* Class that represents a service variable
*/
class ServiceVariable extends Record {
	/**
	* Defines the database table that this object is stored in
	*/
	protected $table = 'variable';

	/**
	* Magic getter method
	* @param string $field to retrieve
	* @return mixed data stored in field
	*/
	public function &__get($field) {
		global $user_dir;
		switch($field) {
		case 'service':
			$service = new Service($this->service_id);
			return $service;
		default:
			return parent::__get($field);
		}
	}

	/**
	* List all log events for this certificate.
	* @return array of Event objects
	*/
	public function get_log() {
		global $event_dir;
		if(is_null($this->id)) throw new BadMethodCallException('ServiceVariable must be in directory before log entries can be listed');
		return $event_dir->list_events(array(), array("type" => "ServiceVariable", "object_id" => $this->id));
	}

	/**
	* Write property changes to database and log the changes.
	*/
	public function update() {
		global $event_dir;
		$changes = parent::update();
		foreach($changes as $change) {
			switch($change->field) {
				case 'description':
				$event_dir->add_log($this, array('action' => 'Variable updated'));	
					continue 2;
			}
			$event_dir->add_log($this, array('action' => 'Setting update', 'value' => $change->new_value, 'oldvalue' => $change->old_value, 'field' => ucfirst(str_replace('_', ' ', $change->field))));
		}
	}

	/**
	* Delete the given variable.
	*/
	public function delete() {
		global $event_dir;
		if(is_null($this->id)) throw new BadMethodCallException('Variable must be in directory before it can be removed');
		$stmt = $this->database->prepare("DELETE FROM variable WHERE id = ?");
		$stmt->bind_param('d', $this->id);
		$stmt->execute();
		$stmt->close();
		$event_dir->add_log($this, array('action' => 'Variable del', 'name' => $this->name, 'service' => $this->name), LOG_WARNING);
	}
}
