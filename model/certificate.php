<?php
/**
* Class that represents a certificate
*/
class Certificate extends Record {
	/**
	* Defines the database table that this object is stored in
	*/
	protected $table = 'certificate';

	/**
	* List all log events for this certificate.
	* @return array of Event objects
	*/
	public function get_log() {
		global $event_dir;
		if(is_null($this->id)) throw new BadMethodCallException('Certificate must be in directory before log entries can be listed');
		return $event_dir->list_events(array(), array("type" => "Certificate", "object_id" => $this->id));
	}

	/**
	* Write property changes to database and log the changes.
	*/
	public function update() {
		global $event_dir;
		$changes = parent::update();
		foreach($changes as $change) {
			$loglevel = LOG_WARNING;
			switch($change->field) {
				case 'private':
				case 'cert':
				case 'fullchain':
					continue 2;
				case 'name':
					$loglevel = LOG_INFO;
					break;
			}
			$event_dir->add_log($this, array('action' => 'Setting update', 'value' => $change->new_value, 'oldvalue' => $change->old_value, 'field' => ucfirst(str_replace('_', ' ', $change->field))), $loglevel);
		}
	}

	/**
	* Delete the given Certificate.
	*/
	public function delete() {
		global $event_dir;
		if(is_null($this->id)) throw new BadMethodCallException('Certificate must be in directory before it can be removed');
		try {
			$stmt = $this->database->prepare("DELETE FROM certificate WHERE id = ?");
			$stmt->bind_param('d', $this->id);
			$stmt->execute();
			$stmt->close();
			$event_dir->add_log($this, array('action' => 'Certificate del', 'name' => $this->name), LOG_WARNING);	
		} catch(mysqli_sql_exception $e) {
			if($e->getCode() == 1451) {
				// Depending profiles
				throw new CertificateInUseException("Certificate {$this->name} is required in one or more profiles.");
			} else {
				throw $e;
			}
		}
	}

	/**
	* Determine the serial and expiration of a certificate by passing it to the openssl utility.
	*/
	public function get_openssl_info() {
		$cert_filename = tempnam('/tmp', 'cert-test-');
		$cert_file = fopen($cert_filename, 'w');
		fwrite($cert_file, $this->cert);
		fclose($cert_file);
		exec('openssl x509 -noout -serial -in '.escapeshellarg($cert_filename).' 2>/dev/null', $serial);
		exec('openssl x509 -noout -enddate -in '.escapeshellarg($cert_filename).' 2>/dev/null', $enddate);
		exec('openssl x509 -noout -modulus -in '.escapeshellarg($cert_filename).' 2>/dev/null | openssl sha1', $cert_modulus);
		unlink($cert_filename);

		$fullchain_filename = tempnam('/tmp', 'fullchain-test-');
		$fullchain_file = fopen($fullchain_filename, 'w');
		fwrite($fullchain_file, $this->fullchain);
		fclose($fullchain_file);
		exec('openssl x509 -noout -modulus -in '.escapeshellarg($fullchain_filename).' 2>/dev/null | openssl sha1', $fullchain_modulus);		
		unlink($fullchain_filename);

		$private_filename = tempnam('/tmp', 'private-test-');
		$private_file = fopen($private_filename, 'w');
		fwrite($private_file, $this->private);
		fclose($private_file);
		exec('openssl rsa -noout -modulus -in '.escapeshellarg($private_filename).' 2>/dev/null | openssl sha1', $private_modulus);		
		unlink($private_filename);

		if(empty($cert_modulus) || $cert_modulus != $fullchain_modulus || $fullchain_modulus != $private_modulus) {
			throw new InvalidArgumentException("Certificate doesn't look valid");
		} else if(count($serial) == 1 && count($enddate) == 1 && 
				preg_match('|^serial=(.*)$|', $serial[0], $matches_fp) &&
				preg_match('|^notAfter=(.*)$|', $enddate[0], $matches_ed)) {
			$this->serial = $matches_fp[1];
			$date = strtotime($matches_ed[1]);
			$this->expiration = date('Y-m-d H:i:s', $date);
		} else {
			throw new InvalidArgumentException("Certificate doesn't look valid");
		}
	}

	/**
	* List all profiles using this certificate.
	* @param array $include list of extra data to include in response - currently unused
	* @param array $filter list of field/value pairs to filter results on
	* @return array of Profile objects
	*/
	public function list_dependent_profiles($include = array(), $filter = array()) {
		// WARNING: The search query is not parameterized - be sure to properly escape all input
		$fields = array("profile.*");
		$joins = array();
		$where = array("profile.certificate_id = ?");
		$bind = array("d", $this->id);
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
class CertificateInUseException extends Exception {}
