<?php
/**
* Class for reading/writing to the list of SyncRequest objects in the database.
*/
class SyncRequestDirectory extends DBDirectory {
	/**
	* Store query as a prepared statement.
	*/
	private $sync_list_stmt;

	/**
	* Create the new sync request in the database.
	* @param SyncRequest $req object to add
	*/
	public function add_sync_request(SyncRequest $req) {
		$stmt = $this->database->prepare("INSERT IGNORE INTO sync_request SET server_id = ?");
		$stmt->bind_param('d', $req->server_id);
		$stmt->execute();
		$req->id = $stmt->insert_id;
		$stmt->close();
	}

	/**
	* Delete the sync request from the database.
	* @param SyncRequest $req object to delete
	*/
	public function delete_sync_request(SyncRequest $req) {
		if(is_null($req->id)) throw new BadMethodCallException('SyncRequest must be in directory before it can be removed');
		$stmt = $this->database->prepare("DELETE FROM sync_request WHERE id = ?");
		$stmt->bind_param('s', $req->id);
		$stmt->execute();
		$stmt->close();
	}

	/**
	* List the sync requests stored in the database that are not being processed yet.
	* @return array of SyncRequest objects
	*/
	public function list_pending_sync_requests() {
		if(!isset($this->sync_list_stmt)) {
			$this->sync_list_stmt = $this->database->prepare("SELECT * FROM sync_request WHERE processing = 0 ORDER BY id");
		}
		$this->sync_list_stmt->execute();
		$result = $this->sync_list_stmt->get_result();
		$reqs = array();
		while($row = $result->fetch_assoc()) {
			$reqs[] = new SyncRequest($row['id'], $row);
		}
		return $reqs;
	}

	/**
	* Delete all pending sync requests for this server.
	*/
	public function delete_all_sync_requests() {
		$stmt = $this->database->prepare("DELETE FROM sync_request");
		$stmt->execute();
	}
}

class SyncRequestNotFoundException extends Exception {}
