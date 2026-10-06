<?php
declare(strict_types=1);

/**
 * PDO-backed fallback for hosts that ship pdo_mysql but not mysqli.
 *
 * Defines mysqli, mysqli_stmt and mysqli_result over PDO so the application
 * can keep using its existing query code unchanged. Loaded only when the
 * mysqli extension is genuinely absent, so hosts with native mysqli are
 * completely unaffected.
 */

if (extension_loaded('mysqli')) {
    return;
}

if (!defined('MYSQLI_ASSOC')) {
    define('MYSQLI_ASSOC', 1);
}
if (!defined('MYSQLI_NUM')) {
    define('MYSQLI_NUM', 2);
}
if (!defined('MYSQLI_BOTH')) {
    define('MYSQLI_BOTH', 3);
}

if (!class_exists('mysqli_result', false)) {
    class mysqli_result
    {
        public $num_rows = 0;
        public $affected_rows = 0;
        public $insert_id = 0;

        private array $rows = [];
        private array $fields = [];
        private int $position = 0;
        private bool $freed = false;

        public function __construct(array $rows, array $fields = [])
        {
            $this->rows = $rows;
            $this->num_rows = count($rows);
            $this->fields = $fields;
            $this->position = 0;
        }

        public function fetch_assoc()
        {
            if ($this->freed || $this->position >= count($this->rows)) {
                return null;
            }
            return $this->rows[$this->position++];
        }

        public function fetch_array($mode = MYSQLI_BOTH)
        {
            $row = $this->fetch_assoc();
            if ($row === null) {
                return null;
            }
            if ($mode === MYSQLI_NUM) {
                return array_values($row);
            }
            return $row;
        }

        public function fetch_row()
        {
            $row = $this->fetch_assoc();
            return $row === null ? null : array_values($row);
        }

        public function fetch_all($mode = MYSQLI_ASSOC): array
        {
            $out = [];
            if ($this->freed) {
                return $out;
            }
            if ($mode === MYSQLI_ASSOC || $mode === MYSQLI_BOTH) {
                for ($i = $this->position, $n = count($this->rows); $i < $n; $i++) {
                    $out[] = $this->rows[$i];
                }
            } else {
                for ($i = $this->position, $n = count($this->rows); $i < $n; $i++) {
                    $out[] = array_values($this->rows[$i]);
                }
            }
            $this->position = count($this->rows);
            return $out;
        }

        public function fetch_fields(): array
        {
            if ($this->fields !== []) {
                return $this->fields;
            }

            $fields = [];
            $index = 0;
            foreach ($this->rows[0] ?? [] as $name => $value) {
                $fields[] = (object)[
                    'name'     => (string)$name,
                    'orgname'  => (string)$name,
                    'table'    => '',
                    'orgtable' => '',
                    'db'       => '',
                    'catalog'  => 'def',
                    'max_length' => is_string($value) ? strlen($value) : 0,
                    'flags'    => 0,
                    'type'     => 254,
                    'decimals' => 0,
                    'charsetnr' => 45,
                ];
                $index++;
            }

            return $fields;
        }

        public function fetch_object(?string $class = null, ?array $args = null)
        {
            $row = $this->fetch_assoc();
            if ($row === null) {
                return null;
            }
            if ($class !== null) {
                return new $class(...($args ?? []), ...$row);
            }
            return (object)$row;
        }

        public function free(): bool
        {
            $this->rows = [];
            $this->num_rows = 0;
            $this->position = 0;
            $this->freed = true;
            return true;
        }

        public function free_result(): bool
        {
            return $this->free();
        }

        public function fetch_col($column = 0): array
        {
            $out = [];
            foreach ($this->rows as $row) {
                $values = array_values($row);
                $out[] = $values[$column] ?? null;
            }
            return $out;
        }
    }
}

if (!class_exists('mysqli_stmt', false)) {
    class mysqli_stmt
    {
        public $num_rows = 0;
        public $affected_rows = 0;
        public $insert_id = 0;
        public $error = '';
        public $errno = 0;
        public $error2 = '';

        private $pdoStmt = null;
        private ?object $connection = null;
        private string $types = '';
        private array $params = [];
        private array $bound = [];
        private array $rows = [];
        private array $fields = [];
        private int $position = 0;
        private bool $hasResultSet = false;

        public function __construct($pdoStmt, ?object $connection = null, string $sql = '')
        {
            $this->pdoStmt = $pdoStmt;
            $this->connection = $connection;
            $this->sql = $sql;
        }

        private string $sql = '';

        public function bind_param(string $types, &...$vars): bool
        {
            $this->types = $types;
            $this->params = [];
            foreach ($vars as $index => $value) {
                $this->params[$index] = &$vars[$index];
            }
            return true;
        }

        public function bind_result(&...$vars): bool
        {
            $this->bound = [];
            foreach ($vars as $index => $value) {
                $this->bound[$index] = &$vars[$index];
            }
            return true;
        }

        public function execute($params = null): bool
        {
            $this->error = '';
            $this->errno = 0;
            $this->rows = [];
            $this->position = 0;
            $this->bound = $this->bound;
            $this->hasResultSet = false;

            if ($this->pdoStmt === null) {
                $this->error = 'Statement is closed.';
                $this->errno = 1;
                return false;
            }

            $bind = $params ?? $this->params;

            if ($bind !== []) {
                $types = $this->types;
                foreach ($bind as $offset => $value) {
                    $char = substr($types, $offset, 1);
                    if ($char === 'i') {
                        $pdoType = PDO::PARAM_INT;
                    } elseif ($char === 'b') {
                        $pdoType = PDO::PARAM_LOB;
                    } else {
                        $pdoType = PDO::PARAM_STR;
                    }
                    if (!$this->pdoStmt->bindValue($offset + 1, $value, $pdoType)) {
                        list($state, $driverCode, $message) = $this->pdoStmt->errorInfo();
                        $this->error = $message ?? $state ?? 'bind failed';
                        $this->errno = (int)($driverCode ?? 1);
                        return false;
                    }
                }
            }

            try {
                $ok = $this->pdoStmt->execute();
            } catch (Throwable $e) {
                $this->error = $e->getMessage();
                $this->errno = (int)($e->getCode() ?: 1);
                return false;
            }

            if ($ok === false) {
                list($state, $driverCode, $message) = $this->pdoStmt->errorInfo();
                $this->error = $message ?? $state ?? 'execute failed';
                $this->errno = (int)($driverCode ?? 1);
                if ($this->connection !== null) {
                    $this->connection->recordError($this->error);
                }
                return false;
            }

            if ($this->pdoStmt->columnCount() > 0) {
                $this->hasResultSet = true;
                try {
                    $this->rows = $this->pdoStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } catch (Throwable $e) {
                    $this->error = $e->getMessage();
                    $this->errno = (int)($e->getCode() ?: 1);
                    return false;
                }
                $this->fields = $this->readFieldNames();
                $this->num_rows = count($this->rows);
                $this->pdoStmt->closeCursor();
            } else {
                $this->hasResultSet = false;
                $this->num_rows = 0;
            }

            $this->affected_rows = (int)$this->pdoStmt->rowCount();
            $this->insert_id = (int)($this->connection !== null ? $this->connection->lastInsertId() : 0);

            return true;
        }

        private function readFieldNames(): array
        {
            $fields = [];

            for ($i = 0, $count = $this->pdoStmt->columnCount(); $i < $count; $i++) {
                $meta = $this->pdoStmt->getColumnMeta($i);
                if (!is_array($meta) || !isset($meta['name'])) {
                    $fields = [];
                    break;
                }
                $name = (string)$meta['name'];
                $fields[] = (object)[
                    'name'       => $name,
                    'orgname'    => (string)($meta['table'] ?? '') !== '' ? (string)($meta['native_type'] ?? $name) : $name,
                    'table'      => (string)($meta['table'] ?? ''),
                    'orgtable'   => (string)($meta['table'] ?? ''),
                    'db'         => '',
                    'catalog'    => 'def',
                    'max_length' => 0,
                    'flags'      => 0,
                    'type'       => 254,
                    'decimals'   => 0,
                    'charsetnr'  => 45,
                ];
            }

            return $fields;
        }

        public function fetch(): bool
        {
            if (!$this->hasResultSet || $this->position >= count($this->rows)) {
                return false;
            }

            $row = $this->rows[$this->position++];
            $position = 0;
            foreach ($this->bound as $index => $unused) {
                $field = $this->fields[$position] ?? null;
                $name = $field !== null ? $field->name : null;
                $value = null;
                if ($name !== null && array_key_exists($name, $row)) {
                    $value = $row[$name];
                } else {
                    $values = array_values($row);
                    $value = $values[$position] ?? null;
                }
                $this->bound[$index] = $value;
                $position++;
            }

            return true;
        }

        public function get_result()
        {
            if (!$this->hasResultSet) {
                return false;
            }
            return new mysqli_result($this->rows, $this->fields);
        }

        public function store_result(): bool
        {
            return $this->hasResultSet;
        }

        public function result_metadata()
        {
            if (!$this->hasResultSet) {
                return false;
            }
            return new mysqli_result([], $this->fields);
        }

        public function more_results(): bool
        {
            return false;
        }

        public function next_result(): bool
        {
            return false;
        }

        public function affected_rows(): int
        {
            return (int)$this->affected_rows;
        }

        public function insert_id(): int
        {
            return (int)$this->insert_id;
        }

        public function rowCount(): int
        {
            return (int)$this->affected_rows;
        }

        public function fetchAll($mode = null, $className = null, $ctorArgs = null): array
        {
            $rows = $this->rows;
            $this->position = count($rows);
            if ($mode === null || $mode === MYSQLI_ASSOC || $mode === PDO::FETCH_ASSOC) {
                return $rows;
            }
            $out = [];
            foreach ($rows as $row) {
                $out[] = array_values($row);
            }
            return $out;
        }

        public function fetchColumn($column = 0)
        {
            if ($this->position >= count($this->rows)) {
                return false;
            }
            $row = $this->rows[$this->position++];
            if (is_int($column) || (is_string($column) && ctype_digit($column))) {
                $values = array_values($row);
                return $values[(int)$column] ?? null;
            }
            return $row[$column] ?? null;
        }

        public function bindValue($param, $value, $type = PDO::PARAM_STR): bool
        {
            $index = is_string($param) && strpos($param, ':') === 0 ? (int)substr($param, 1) : (int)$param;
            $this->params[$index - 1] = $value;
            if ($this->pdoStmt === null) {
                return false;
            }
            return $this->pdoStmt->bindValue($index, $value, $type);
        }

        public function bindParam($param, &$variable, $type = PDO::PARAM_STR): bool
        {
            $index = is_string($param) && strpos($param, ':') === 0 ? (int)substr($param, 1) : (int)$param;
            $this->params[$index - 1] = $variable;
            if ($this->pdoStmt === null) {
                return false;
            }
            return $this->pdoStmt->bindParam($index, $variable, $type);
        }

        public function close(): bool
        {
            if ($this->pdoStmt !== null) {
                try {
                    $this->pdoStmt->closeCursor();
                } catch (Throwable $e) {
                    // A closed cursor is not a failure we need to surface.
                }
                $this->pdoStmt = null;
            }
            $this->rows = [];
            $this->bound = [];
            $this->params = [];
            $this->num_rows = 0;
            return true;
        }

        public function free_result(): bool
        {
            $this->rows = [];
            $this->num_rows = 0;
            $this->position = 0;
            return true;
        }
    }
}

if (!class_exists('mysqli', false)) {
    class mysqli
    {
        public $connect_errno = 0;
        public $connect_error = '';
        public $error = '';
        public $errno = 0;
        public $insert_id = 0;
        public $affected_rows = 0;
        public $host_info = '';
        public $server_info = '';
        public $client_info = '';
        public $host = '';

        private ?PDO $pdo = null;

        public function __construct(
            $host = null,
            $user = null,
            $password = null,
            $database = null,
            $port = null,
            $socket = null
        ) {
            $this->host = (string)$host;
            $dsn = 'mysql:host=' . (string)$host;
            if ($database !== null && $database !== '') {
                $dsn .= ';dbname=' . $database;
            }
            $dsn .= ';charset=utf8mb4';
            if ($port !== null && $port !== '' && $host !== null && (string)$host !== 'localhost') {
                $dsn = 'mysql:host=' . (string)$host . ';port=' . (int)$port;
                if ($database !== null && $database !== '') {
                    $dsn .= ';dbname=' . $database;
                }
                $dsn .= ';charset=utf8mb4';
            }

            try {
                $this->pdo = new PDO($dsn, (string)$user, (string)$password, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_SILENT,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_STRINGIFY_FETCHES  => false,
                ]);
            } catch (Throwable $e) {
                $this->connect_errno = 1;
                $this->connect_error = $e->getMessage();
                $this->pdo = null;
                return;
            }

            $this->host_info  = 'localhost via TCP/IP';
            $this->server_info = (string)$this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            $this->client_info = 'mysqlnd';
        }

        public function recordError(string $message): void
        {
            $this->error = $message;
        }

        public function lastInsertId(): int
        {
            if ($this->pdo === null) {
                return 0;
            }
            $id = $this->pdo->lastInsertId();
            return is_string($id) && ctype_digit($id) ? (int)$id : 0;
        }

        public function set_charset(string $charset): bool
        {
            if ($this->pdo === null) {
                return false;
            }
            if (!preg_match('/^[A-Za-z0-9_]+$/', $charset)) {
                return false;
            }
            $ok = $this->pdo->exec("SET NAMES '" . $charset . "'");
            if ($ok === false) {
                list($state, $driverCode, $message) = $this->pdo->errorInfo();
                $this->error = $message ?? $state ?? 'set_charset failed';
                $this->errno = (int)($driverCode ?? 1);
                return false;
            }
            return true;
        }

        public function query(string $query)
        {
            if ($this->pdo === null) {
                $this->error = 'No connection.';
                $this->errno = 2006;
                return false;
            }

            try {
                $stmt = $this->pdo->query($query);
            } catch (Throwable $e) {
                $this->error = $e->getMessage();
                $this->errno = (int)($e->getCode() ?: 1);
                return false;
            }

            if ($stmt === false) {
                list($state, $driverCode, $message) = $this->pdo->errorInfo();
                $this->error = $message ?? $state ?? 'query failed';
                $this->errno = (int)($driverCode ?? 1);
                return false;
            }

            $this->error = '';
            $this->errno = 0;

            if ($stmt->columnCount() === 0) {
                $this->affected_rows = (int)$stmt->rowCount();
                $this->insert_id = $this->lastInsertId();
                $stmt->closeCursor();
                return new mysqli_result([], []);
            }

            try {
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e) {
                $this->error = $e->getMessage();
                $this->errno = (int)($e->getCode() ?: 1);
                return false;
            }
            $stmt->closeCursor();

            $fields = [];
            $count = $stmt->columnCount();
            for ($i = 0; $i < $count; $i++) {
                $meta = $stmt->getColumnMeta($i);
                if (is_array($meta) && isset($meta['name'])) {
                    $fields[] = (object)[
                        'name'       => (string)$meta['name'],
                        'orgname'    => (string)$meta['name'],
                        'table'      => (string)($meta['table'] ?? ''),
                        'orgtable'   => (string)($meta['table'] ?? ''),
                        'db'         => '',
                        'catalog'    => 'def',
                        'max_length' => 0,
                        'flags'      => 0,
                        'type'       => 254,
                        'decimals'   => 0,
                        'charsetnr'  => 45,
                    ];
                }
            }

            $this->affected_rows = count($rows);
            return new mysqli_result($rows, $fields);
        }

        public function prepare(string $query)
        {
            if ($this->pdo === null) {
                $this->error = 'No connection.';
                $this->errno = 2006;
                return false;
            }

            try {
                $stmt = $this->pdo->prepare($query);
            } catch (Throwable $e) {
                $this->error = $e->getMessage();
                $this->errno = (int)($e->getCode() ?: 1);
                return false;
            }

            if ($stmt === false) {
                list($state, $driverCode, $message) = $this->pdo->errorInfo();
                $this->error = $message ?? $state ?? 'prepare failed';
                $this->errno = (int)($driverCode ?? 1);
                return false;
            }

            $this->error = '';
            $this->errno = 0;

            return new mysqli_stmt($stmt, $this, $query);
        }

        public function real_escape_string(string $value): string
        {
            if ($this->pdo === null) {
                return addslashes($value);
            }
            $quoted = $this->pdo->quote($value);
            if ($quoted === false) {
                return addslashes($value);
            }
            return substr($quoted, 1, -1);
        }

        public function escape_string(string $value): string
        {
            return $this->real_escape_string($value);
        }

        public function begin_transaction(): bool
        {
            if ($this->pdo === null) {
                return false;
            }
            try {
                return $this->pdo->beginTransaction();
            } catch (Throwable $e) {
                $this->error = $e->getMessage();
                return false;
            }
        }

        public function commit(): bool
        {
            if ($this->pdo === null) {
                return false;
            }
            try {
                return $this->pdo->commit();
            } catch (Throwable $e) {
                $this->error = $e->getMessage();
                return false;
            }
        }

        public function rollback(): bool
        {
            if ($this->pdo === null) {
                return false;
            }
            try {
                return $this->pdo->rollBack();
            } catch (Throwable $e) {
                $this->error = $e->getMessage();
                return false;
            }
        }

        public function autocommit(bool $mode): bool
        {
            if ($this->pdo === null) {
                return false;
            }
            return $this->set_attribute(PDO::ATTR_AUTOCOMMIT, $mode);
        }

        public function set_attribute(int $attribute, $value): bool
        {
            if ($this->pdo === null) {
                return false;
            }
            return $this->pdo->setAttribute($attribute, $value);
        }

        public function get_pdo(): ?PDO
        {
            return $this->pdo;
        }

        public function close(): bool
        {
            $this->pdo = null;
            return true;
        }

        public function __destruct()
        {
            $this->pdo = null;
        }
    }
}
