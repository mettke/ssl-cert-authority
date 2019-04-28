<?php
/**
* Class that represents a log event that was recorded in relation to a group
*/
class Event extends Record {
	/**
	* Defines the database table that this object is stored in
	*/
	protected $table = 'event';

	/**
	* Magic getter method
	* @param string $field to retrieve
	* @return mixed data stored in field
	*/
	public function &__get($field) {
		switch($field) {
		case 'name':
			$name = null;
			try {
				switch($this->type) {
					case 'Script':
					case 'Service':
					case 'Certificate':
					case 'Profile':
					case 'ServiceVariable':
						$name = $this->__get('object')->name;
						break;
					case 'User':
						$name = $this->__get('object')->uid;
						break;
					case 'Server':
						$name = $this->__get('object')->hostname;
						break;
				}
			} catch(NoResultsFoundException $e) {}
			return $name;
		case 'object':
			$object = null;
			switch($this->type) {
				case 'User':
					$object = new User($this->data['object_id']);
					break;
				case 'Script':
					$object = new Script($this->data['object_id']);
					break;
				case 'Service':
					$object = new Service($this->data['object_id']);
					break;
				case 'Certificate':
					$object = new Certificate($this->data['object_id']);
					break;
				case 'Server':
					$object = new Server($this->data['object_id']);
					break;
				case 'Profile':
					$object = new Profile($this->data['object_id']);
					break;
				case 'ServiceVariable':
					$object = new ServiceVariable($this->data['object_id']);
					break;
			}
			return $object;
		case 'actor':
			$actor = new User($this->data['actor_id']);
			return $actor;
		default:
			return parent::__get($field);
		}
	}
}
