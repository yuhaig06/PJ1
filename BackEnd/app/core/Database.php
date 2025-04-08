<?php
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;

    private $dbh;
    private $stmt;
    private $error;
    private static $instance = null;
    private $queryCount = 0;
    private $queryLog = [];

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';charset=utf8mb4';
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        );

        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
            $this->setConnectionOptions();
        } catch(PDOException $e) {
            $this->logError($e->getMessage());
            throw new Exception('Database connection failed');
        }
    }

    // Singleton pattern
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Prepare statement with query logging
    public function query($sql) {
        $this->queryCount++;
        $startTime = microtime(true);
        
        try {
            $this->stmt = $this->dbh->prepare($sql);
            
            // Log query
            $this->queryLog[] = [
                'sql' => $sql,
                'time' => microtime(true) - $startTime,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            $this->logError($this->error);
            throw new Exception('Query preparation failed: ' . $this->error);
        }
    }

    // Bind values with type checking
    public function bind($param, $value, $type = null) {
        if(is_null($type)) {
            switch(true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        
        try {
            $this->stmt->bindValue($param, $value, $type);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            $this->logError($this->error);
            throw new Exception('Parameter binding failed: ' . $this->error);
        }
    }

    // Execute with error handling
    public function execute() {
        try {
            $result = $this->stmt->execute();
            if (!$result) {
                $this->error = $this->stmt->errorInfo()[2];
                $this->logError($this->error);
            }
            return $result;
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            $this->logError($this->error);
            throw new Exception('Query execution failed: ' . $this->error);
        }
    }

    // Get result set as array of objects
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }

    // Get single record as object
    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }

    // Get row count
    public function rowCount() {
        return $this->stmt->rowCount();
    }

    // Get last inserted ID
    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }

    // Begin transaction with error handling
    public function beginTransaction() {
        try {
            return $this->dbh->beginTransaction();
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            $this->logError($this->error);
            throw new Exception('Transaction start failed: ' . $this->error);
        }
    }

    // Commit transaction with error handling
    public function commit() {
        try {
            return $this->dbh->commit();
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            $this->logError($this->error);
            throw new Exception('Transaction commit failed: ' . $this->error);
        }
    }

    // Rollback transaction with error handling
    public function rollBack() {
        try {
            return $this->dbh->rollBack();
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            $this->logError($this->error);
            throw new Exception('Transaction rollback failed: ' . $this->error);
        }
    }

    // Get query count
    public function getQueryCount() {
        return $this->queryCount;
    }

    // Get query log
    public function getQueryLog() {
        return $this->queryLog;
    }

    // Clear query log
    public function clearQueryLog() {
        $this->queryLog = [];
    }

    // Log error to file
    private function logError($error) {
        $logMessage = date('Y-m-d H:i:s') . ' - ' . $error . "\n";
        error_log($logMessage, 3, dirname(__DIR__) . '/logs/database.log');
    }

    // Get last error
    public function getError() {
        return $this->error;
    }

    // Check if there was an error
    public function hasError() {
        return !empty($this->error);
    }

    // Clear error
    public function clearError() {
        $this->error = null;
    }

    private function setConnectionOptions() {
        $this->dbh->setAttribute(PDO::ATTR_TIMEOUT, 5);
        $this->dbh->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    private function handleQueryError($e, $query) {
        $errorMsg = sprintf(
            "Query failed: %s\nError: %s\nFile: %s\nLine: %d",
            $query,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );
        $this->logError($errorMsg);
        throw new \Exception('Database query failed: ' . $e->getMessage());
    }
}