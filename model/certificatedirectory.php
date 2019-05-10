<?php
/**
* Class for reading/writing to the list of Certiticate objects in the database.
*/
class CertificateDirectory extends DBDirectory {
	/**
	* Create a new certificate in the database.
	* @param Certificate $certificate object to add
	*/
	public function add_certificate(Certificate $certificate) {
		global $event_dir;
		if(!$certificate->csr) {
			$certificate->get_openssl_info();
		}
		try {
			$stmt = $this->database->prepare("INSERT INTO certificate SET name = ?, private = ?, cert = ?, fullchain = ?, serial = ?, expiration = ?, owner_id = ?, signing_request = ?, csr = ?");
			$stmt->bind_param('ssssssdds', $certificate->name, $certificate->private, $certificate->cert, $certificate->fullchain, $certificate->serial, $certificate->expiration, $certificate->owner_id, $certificate->signing_request, $certificate->csr);
			$stmt->execute();
			$certificate->id = $stmt->insert_id;
			$stmt->close();
			$event_dir->add_log($certificate, array('action' => 'Certificate add'));
		} catch(mysqli_sql_exception $e) {
			if($e->getCode() == 1062) {
				// Duplicate entrys
				throw new CertificateAlreadyExistsException("Certificate {$certificate->name} already exists");
			} else {
				throw $e;
			}
		}
	}

	/**
	* Get a certificate from the database by its ID.
	* @param int $id of certificate
	* @return Certificate with specified ID
	* @throws CertificateNotFoundException if no certificate with that ID exists
	*/
	public function get_certificate_by_id($id) {
		$stmt = $this->database->prepare("SELECT * FROM certificate WHERE id = ?");
		$stmt->bind_param('d', $id);
		$stmt->execute();
		$result = $stmt->get_result();
		if($row = $result->fetch_assoc()) {
			$certificate = new Certificate($row['id'], $row);
		} else {
			throw new CertificateNotFoundException('Certificate does not exist.');
		}
		$stmt->close();
		return $certificate;
	}

	/**
	* Get a certificate from the database by its name.
	* @param string $name of certificate
	* @return Certificate with specified name
	* @throws CertificateNotFoundException if no certificate with that name exists
	*/
	public function get_certificate_by_name($name) {
		$stmt = $this->database->prepare("SELECT * FROM certificate WHERE name = ?");
		$stmt->bind_param('s', $name);
		$stmt->execute();
		$result = $stmt->get_result();
		if($row = $result->fetch_assoc()) {
			$certificate = new Certificate($row['id'], $row);
		} else {
			throw new CertificateNotFoundException('Certificate does not exist.');
		}
		$stmt->close();
		return $certificate;
	}

	/**
	* List all certificates in the database.
	* @param array $include list of extra data to include in response - currently unused
	* @param array $filter list of field/value pairs to filter results on
	* @return array of User objects
	*/
	public function list_certificates($include = array(), $filter = array()) {
		// WARNING: The search query is not parameterized - be sure to properly escape all input
		$fields = array("certificate.*");
		$joins = array();
		$where = array();
		$bind = array("");
		foreach($filter as $field => $value) {
			if($value || $value == 0) {
				switch($field) {
				case 'name':
					$where[] = "certificate.name REGEXP ?";
					$bind[0] = $bind[0] . "s";
					$bind[] = $this->database->escape_string($value);
					break;
				case 'serial':
					$where[] = "certificate.serial REGEXP ?";
					$bind[0] = $bind[0] . "s";
					$bind[] = $this->database->escape_string($value);
					break;
				case 'owner_id':
					$where[] = "certificate.owner_id = ?";
					$bind[0] = $bind[0] . "d";
					$bind[] = $value;
					break;
				case 'signing_request':
					$where[] = "certificate.signing_request = ?";
					$bind[0] = $bind[0] . "d";
					$bind[] = $value;
					break;
				}
			}
		}
		try {
			$stmt = $this->database->prepare("
				SELECT ".implode(", ", $fields)."
				FROM certificate ".implode(" ", $joins)."
				".(count($where) == 0 ? "" : "WHERE (".implode(") AND (", $where).")")."
				GROUP BY certificate.id
				ORDER BY certificate.expiration
			");
			if(count($bind) > 1) {
				$stmt->bind_param(...$bind);
			}
			$stmt->execute();
			$result = $stmt->get_result();
			$certificates = array();
			while($row = $result->fetch_assoc()) {
				$certificates[] = new Certificate($row['id'], $row);
			}
			$stmt->close();
			return $certificates;
		} catch(mysqli_sql_exception $e) {
			if($e->getCode() == 1139) {
				throw new InvalidRegexpException;
			} else {
				throw $e;
			}
		}
	}
}

class CertificateNotFoundException extends Exception {}
class CertificateAlreadyExistsException extends Exception {}
