<?php
/**
 * Database Class - PDO Wrapper with Security
 * 
 * Secure database abstraction layer with prepared statements
 * 
 * @package CMSv2\Core
 */

declare(strict_types=1);

namespace CMS;

use PDO;
use PDOException;
use CMS\Contracts\DatabaseInterface;

if (!defined('ABSPATH')) {
    exit;
}

/** @implements DatabaseInterface */
class Database implements DatabaseInterface
{
    private static ?self $instance = null;
    private ?PDO $pdo = null;
    public string $prefix = 'cms_';
    public string $last_error = '';
    private ?\PDOStatement $lastStatement = null;
    
    /**
     * Singleton instance
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Alias for instance() – WordPress-style naming used by plugins
     */
    public static function get_instance(): self
    {
        return self::instance();
    }
    
    /**
     * Private constructor
     */
    private function __construct()
    {
        $this->connect();
        
        // H-10: Schema-Erstellung via SchemaManager (ausgelagert aus Database-God-Klasse)
        try {
            (new SchemaManager($this))->createTables();
        } catch (\Exception $e) {
            error_log('Database: SchemaManager::createTables() warning: ' . $e->getMessage());
            // Nicht werfen – App soll weiterlaufen
        }
    }

    /**
     * Connect to database
     */
    private function connect(): void
    {
        // Präfix aus Konfigurationskonstante übernehmen (muss VOR SchemaManager gesetzt sein)
        $this->prefix = defined('DB_PREFIX') ? DB_PREFIX : 'cms_';

        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                // H-11: Native Prepared Statements (sicherer, kein PHP-seitiges Quoting)
                // bindParams() stellt sicher, dass LIMIT/OFFSET als PARAM_INT gebunden werden,
                // sodass MariaDB diese korrekt akzeptiert (keine String-Quotierung wie bei Emulation).
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            if (CMS_DEBUG) {
                error_log('Database connected successfully');
            }
            
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            $this->pdo = null;
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Create required tables (Public wrapper for repair)
     * H-10: Delegiert an MigrationManager
     */
    public function repairTables(): void
    {
        (new MigrationManager($this))->repairTables();
    }

    /**
     * @deprecated Verwende MigrationManager::run() direkt.
     *             Besteht nur noch für eventuelle externe Aufrufe.
     */
    private function migrateColumns(): void
    {
        (new MigrationManager($this))->run();
    }

    /**
     * @deprecated Verwende SchemaManager::createTables() direkt.
     *             Besteht nur noch für eventuelle externe Aufrufe.
     */
    private function createTables(): void
    {
        (new SchemaManager($this))->createTables();
    }

    /**
     * @internal Wird von SchemaManager aufgerufen; nicht mehr direkt in Database.
     * @deprecated Kein direkter Aufruf mehr – via SchemaManager.
     */
    private function createDefaultAdmin(): void
    {
        // Wird von SchemaManager::createTables() übernommen
    }
    
    /**
     * Prepare statement
     */
    public function prepare(string $sql): \PDOStatement|false
    {
        if ($this->pdo === null) {
            error_log('Database::prepare() - CRITICAL: PDO connection is null!');
            error_log('SQL attempted: ' . $sql);
            error_log('DB Config - Host: ' . DB_HOST . ', Name: ' . DB_NAME . ', User: ' . DB_USER);
            throw new \RuntimeException('Database connection is not available. PDO is null.');
        }
        
        try {
            $stmt = $this->pdo->prepare($sql);
            if ($stmt === false) {
                error_log('Database::prepare() - prepare() returned false: ' . $sql);
                $errorInfo = $this->pdo->errorInfo();
                error_log('PDO Error: ' . json_encode($errorInfo));
                throw new \RuntimeException('PDO prepare failed: ' . ($errorInfo[2] ?? 'Unknown error'));
            }
            return $stmt;
        } catch (\PDOException $e) {
            error_log('Database::prepare() Exception: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw new \RuntimeException('Database prepare error: ' . $e->getMessage());
        }
    }
    
    /**
     * Execute query (ungeparametrisiert – nur für vertrauenswürdige SQL-Strings!)
     */
    public function query(string $sql): \PDOStatement
    {
        if ($this->pdo === null) {
            throw new \RuntimeException('Database connection is not available. PDO is null.');
        }
        return $this->pdo->query($sql);
    }

    /**
     * Bind parameters to a prepared statement with correct PDO types.
     * Mit ATTR_EMULATE_PREPARES => false (native Prepared Statements) ist explizites
     * Type-Binding für LIMIT/OFFSET-Parameter erforderlich, damit PARAM_INT korrekt
     * an MySQL/MariaDB übergeben wird (kein String-Quoting durch PHP-Emulation).
     */
    private function bindParams(\PDOStatement $stmt, array $params): void
    {
        foreach (array_values($params) as $i => $value) {
            if (is_int($value)) {
                $stmt->bindValue($i + 1, $value, PDO::PARAM_INT);
            } elseif (is_bool($value)) {
                $stmt->bindValue($i + 1, $value, PDO::PARAM_BOOL);
            } elseif ($value === null) {
                $stmt->bindValue($i + 1, $value, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue($i + 1, (string) $value, PDO::PARAM_STR);
            }
        }
    }

    /**
     * Execute query with parameters (prepared statement)
     */
    public function execute(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->prepare($sql);
        $this->bindParams($stmt, $params);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Validiert einen Tabellen- oder Spaltennamen gegen SQL-Injection.
     *
     * @throws \InvalidArgumentException Bei ungültigem Bezeichner
     */
    private function validateIdentifier(string $name): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException(
                'Ungültiger SQL-Bezeichner: ' . substr($name, 0, 50)
            );
        }
        return '`' . $name . '`';
    }

    /**
     * Insert data (mit Error-Tracking)
     */
    public function insert(string $table, array $data): int|bool
    {
        try {
            $table = $this->prefix . $table;
            $keys = array_keys($data);
            $escapedFields = array_map([$this, 'validateIdentifier'], $keys);
            $fields = implode(', ', $escapedFields);
            $placeholders = implode(', ', array_fill(0, count($keys), '?'));

            $sql = "INSERT INTO `{$table}` ({$fields}) VALUES ({$placeholders})";
            $this->lastStatement = $this->prepare($sql);
            $this->bindParams($this->lastStatement, array_values($data));
            $result = $this->lastStatement->execute();

            if (!$result) {
                $error = $this->lastStatement->errorInfo();
                $this->last_error = $error[2] ?? 'Unknown error';
                return false;
            }

            return (int) $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            $this->last_error = $e->getMessage();
            return false;
        }
    }

    /**
     * Update data (mit Error-Tracking)
     */
    public function update(string $table, array $data, array $where): bool
    {
        try {
            $table = $this->prefix . $table;
            $set = [];
            $values = [];

            foreach ($data as $key => $value) {
                $set[] = $this->validateIdentifier($key) . " = ?";
                $values[] = $value;
            }

            $whereClauses = [];
            foreach ($where as $key => $value) {
                $whereClauses[] = $this->validateIdentifier($key) . " = ?";
                $values[] = $value;
            }

            $sql = "UPDATE `{$table}` SET " . implode(', ', $set) . " WHERE " . implode(' AND ', $whereClauses);
            $this->lastStatement = $this->prepare($sql);
            $this->bindParams($this->lastStatement, $values);
            $result = $this->lastStatement->execute();

            if (!$result) {
                $error = $this->lastStatement->errorInfo();
                $this->last_error = $error[2] ?? 'Unknown error';
                return false;
            }

            return true;
        } catch (\PDOException $e) {
            $this->last_error = $e->getMessage();
            return false;
        }
    }

    /**
     * Delete data
     */
    public function delete(string $table, array $where): bool
    {
        $table = $this->prefix . $table;
        $whereClauses = [];
        $values = [];

        foreach ($where as $key => $value) {
            $whereClauses[] = $this->validateIdentifier($key) . " = ?";
            $values[] = $value;
        }

        $sql  = "DELETE FROM `{$table}` WHERE " . implode(' AND ', $whereClauses);
        $stmt = $this->prepare($sql);
        $this->bindParams($stmt, $values);
        return $stmt->execute();
    }

    /**
     * WordPress-compatible: Get single row as object
     */
    public function get_row(string $query, array $params = []): ?object
    {
        $stmt = $this->prepare($query);
        $this->bindParams($stmt, $params);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result ?: null;
    }

    /**
     * WordPress-compatible: Get single variable (column value)
     */
    public function get_var(string $query, array $params = []): mixed
    {
        $stmt = $this->prepare($query);
        $this->bindParams($stmt, $params);
        $stmt->execute();
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : null;
    }

    /**
     * WordPress-compatible: Get multiple rows as objects
     */
    public function get_results(string $query, array $params = []): array
    {
        $stmt = $this->prepare($query);
        $this->bindParams($stmt, $params);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * WordPress-compatible: Get column values as array
     */
    public function get_col(string $query, array $params = []): array
    {
        $stmt = $this->prepare($query);
        $this->bindParams($stmt, $params);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get affected rows from last operation
     */
    public function affected_rows(): int
    {
        if ($this->lastStatement) {
            return $this->lastStatement->rowCount();
        }
        return 0;
    }
    
    /**
     * Get last insert ID
     */
    public function insert_id(): int
    {
        return (int) $this->pdo->lastInsertId();
    }
    
    /**
     * Get table prefix
     *
     * @deprecated Use getPrefix() instead
     * Accepts an optional table name: prefix('users') returns 'cms_users'.
     */
    public function prefix(string $table = ''): string
    {
        return $table !== '' ? $this->prefix . $table : $this->prefix;
    }

    /**
     * Get PDO instance
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }
    
    /**
     * Get connection (alias for getPdo for compatibility)
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }
    
    /**
     * Get table prefix
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Fetch all rows as an associative array
     */
    public static function fetchAll(string $query, array $params = []): array
    {
        $db = self::instance();
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch single row as an associative array
     */
    public static function fetchOne(string $query, array $params = []): ?array
    {
        $db = self::instance();
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Execute a query with parameters (Static wrapper)
     */
    public static function exec(string $query, array $params = []): bool
    {
        $db = self::instance();
        if (empty($params)) {
            return (bool) $db->query($query);
        }
        $stmt = $db->prepare($query);
        return $stmt->execute($params);
    }
}
