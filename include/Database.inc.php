<?php
require_once('MDB2.php');

abstract class Database {
	protected static $instance;
	private $mdb, $dbName;

	public function __construct($dsn, $options) {
		$this->mdb =& MDB2::singleton($dsn, $options);
		$this->checkError($this->mdb, "MDB2 creation error");
		$this->mdb->setFetchMode(MDB2_FETCHMODE_ASSOC);
	}

	public function __destruct() {
		$this->mdb->disconnect();
	}
	
	abstract public static function singleton($dsn, $options);

	//DATABASE UTILITY FUNCTIONS
	private function query($sql, $params, $prepareType, $returnType) {
		if ($prepareType === -1) {
			$result = $this->mdb->query($sql);
			$this->checkError($result, "Direct SQL Query");
			return $result;
		}
		$stmt = $this->mdb->prepare($sql, NULL, $prepareType);
		$this->checkError($stmt, "Prepared statement creation error");
		if ($params === false) $result = $stmt->execute();
		else $result = $stmt->execute($params);
		$this->checkError($result, "Prepared statment execution error");
		$stmt->free();
		if ($prepareType === MDB2_PREPARE_MANIP) return $result;
		$return = $result->$returnType();
		while ($result->nextResult()); //This line is to fix a bug in MDB2/MySQL
		$result->free();
		return $return;
	}

	protected function exec($sql, $params = false) { 
		$this->query($sql, $params, MDB2_PREPARE_MANIP, false);
		return $this->mdb->lastInsertID();
	}
	protected function fetchOne($sql, $params = false) { return $this->query($sql, $params, MDB2_PREPARE_RESULT, 'fetchOne'); }
	protected function fetchRow($sql, $params = false) { return $this->query($sql, $params, MDB2_PREPARE_RESULT, 'fetchRow'); }
	protected function fetchAll($sql, $params = false) { return $this->query($sql, $params, MDB2_PREPARE_RESULT, 'fetchAll'); }
	protected function direct($sql) { return $this->query($sql, false, -1, false); }
	protected function insert($table, $vals) {
		if (count($vals) < 1) {
			trigger_error('Must submit more than one value!', E_USER_ERROR);
			die;
		}
		$sql = "INSERT INTO $table (";
		$cols = array_keys($vals);
		$vals = array_values($vals);
		$delim = '';
		foreach ($cols as $col) {
			$sql .= "$delim$col";
			$delim = ', ';
		}
		$sql .= ') VALUES (?';
		$delim = '';
		$sql .= str_repeat(', ?', count($vals)-1) . ')';
		return $this->exec($sql, $vals);
	}

	protected function checkError($object, $message = false) {
		if ($message) $message .= ' ';
		if (PEAR::isError($object)) {
			trigger_error($message . $object->getMessage() . ' ' . $object->getDebugInfo(), E_USER_ERROR);
			die;
		}
	}

}