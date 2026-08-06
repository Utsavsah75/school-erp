<?php

namespace App\Core;

use PDO;

/**
 * Base Model. Every concrete Model sets $table, $primaryKey and $fillable,
 * and inherits full CRUD + search + pagination for free. Concrete Models
 * can always drop down to $this->db->query() for anything bespoke.
 */
abstract class Model
{
    protected Database $db;
    protected PDO $pdo;

    /** @var string Table name — must match the schema exactly. */
    protected string $table;

    /** @var string Primary key column name. */
    protected string $primaryKey = 'id';

    /** @var string[] Columns that may be mass-assigned via insert()/update(). */
    protected array $fillable = [];

    /** @var string[] Columns that a search() call should LIKE-match against. */
    protected array $searchable = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * Find a single row by primary key.
     */
    public function find(int|string $id): array|false
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id LIMIT 1";
        $stmt = $this->db->query($sql, ['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Find a single row by an arbitrary column.
     */
    public function findBy(string $column, mixed $value): array|false
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$column}` = :value LIMIT 1";
        $stmt = $this->db->query($sql, ['value' => $value]);
        return $stmt->fetch();
    }

    /**
     * Return every row, optionally ordered.
     * @return array<int,array>
     */
    public function all(string $orderBy = '', string $direction = 'ASC'): array
    {
        $sql = "SELECT * FROM `{$this->table}`";
        if ($orderBy !== '') {
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= " ORDER BY `{$orderBy}` {$direction}";
        }
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Return rows matching a simple where-equals map.
     * @param array<string,mixed> $conditions
     * @return array<int,array>
     */
    public function where(array $conditions, string $orderBy = '', string $direction = 'ASC'): array
    {
        [$whereSql, $params] = $this->buildWhere($conditions);
        $sql = "SELECT * FROM `{$this->table}`" . $whereSql;
        if ($orderBy !== '') {
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= " ORDER BY `{$orderBy}` {$direction}";
        }
        return $this->db->query($sql, $params)->fetchAll();
    }

    /**
     * Same as where() but returns just the first match (or false).
     */
    public function firstWhere(array $conditions): array|false
    {
        $rows = $this->where($conditions);
        return $rows[0] ?? false;
    }

    public function count(array $conditions = []): int
    {
        [$whereSql, $params] = $this->buildWhere($conditions);
        $sql = "SELECT COUNT(*) AS c FROM `{$this->table}`" . $whereSql;
        $row = $this->db->query($sql, $params)->fetch();
        return (int) ($row['c'] ?? 0);
    }

    /**
     * Insert a row (mass-assignment limited to $fillable) and return the new id.
     */
    public function insert(array $data): int
    {
        $data = $this->filterFillable($data);
        if (empty($data)) {
            throw new \InvalidArgumentException('No fillable data supplied to insert().');
        }

        $columns = array_keys($data);
        $placeholders = array_map(fn($c) => ':' . $c, $columns);

        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $this->table,
            '`' . implode('`, `', $columns) . '`',
            implode(', ', $placeholders)
        );

        $this->db->query($sql, $data);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update a row by primary key (mass-assignment limited to $fillable).
     */
    public function update(int|string $id, array $data): bool
    {
        $data = $this->filterFillable($data);
        if (empty($data)) {
            return false;
        }

        $setSql = implode(', ', array_map(fn($c) => "`{$c}` = :{$c}", array_keys($data)));
        $sql = "UPDATE `{$this->table}` SET {$setSql} WHERE `{$this->primaryKey}` = :__id";

        $data['__id'] = $id;
        $stmt = $this->db->query($sql, $data);
        return $stmt->rowCount() > 0;
    }

    public function delete(int|string $id): bool
    {
        $sql = "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id";
        $stmt = $this->db->query($sql, ['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * LIKE-search across $searchable columns.
     * @return array<int,array>
     */
    public function search(string $term, string $orderBy = '', string $direction = 'ASC'): array
    {
        if (empty($this->searchable) || trim($term) === '') {
            return $this->all($orderBy, $direction);
        }

        $clauses = [];
        $params = [];
        foreach ($this->searchable as $i => $col) {
            $clauses[] = "`{$col}` LIKE :term{$i}";
            $params["term{$i}"] = '%' . $term . '%';
        }

        $sql = "SELECT * FROM `{$this->table}` WHERE (" . implode(' OR ', $clauses) . ')';
        if ($orderBy !== '') {
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= " ORDER BY `{$orderBy}` {$direction}";
        }

        return $this->db->query($sql, $params)->fetchAll();
    }

    /**
     * Paginate results with optional where-conditions and a search term.
     *
     * @return array{data: array, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginate(int $page = 1, int $perPage = 20, array $conditions = [], string $search = '', string $orderBy = '', string $direction = 'DESC'): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        [$whereSql, $params] = $this->buildWhere($conditions);

        if (trim($search) !== '' && !empty($this->searchable)) {
            $clauses = [];
            foreach ($this->searchable as $i => $col) {
                $clauses[] = "`{$col}` LIKE :search{$i}";
                $params["search{$i}"] = '%' . $search . '%';
            }
            $searchSql = '(' . implode(' OR ', $clauses) . ')';
            $whereSql = $whereSql === '' ? " WHERE {$searchSql}" : $whereSql . " AND {$searchSql}";
        }

        $countSql = "SELECT COUNT(*) AS c FROM `{$this->table}`" . $whereSql;
        $total = (int) ($this->db->query($countSql, $params)->fetch()['c'] ?? 0);

        $sql = "SELECT * FROM `{$this->table}`" . $whereSql;
        if ($orderBy !== '') {
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= " ORDER BY `{$orderBy}` {$direction}";
        }
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";

        $data = $this->db->query($sql, $params)->fetchAll();

        return [
            'data'      => $data,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    /**
     * Escape hatch for joins / aggregates / anything the generic helpers
     * above don't cover. Still fully parameterised.
     */
    public function raw(string $sql, array $params = []): array
    {
        return $this->db->query($sql, $params)->fetchAll();
    }

    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }
        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * @return array{0:string,1:array} [whereSql, params]
     */
    protected function buildWhere(array $conditions): array
    {
        if (empty($conditions)) {
            return ['', []];
        }
        $clauses = [];
        $params = [];
        foreach ($conditions as $col => $val) {
            if ($val === null) {
                $clauses[] = "`{$col}` IS NULL";
                continue;
            }
            $key = 'w_' . preg_replace('/[^a-zA-Z0-9_]/', '', $col);
            $clauses[] = "`{$col}` = :{$key}";
            $params[$key] = $val;
        }
        return [' WHERE ' . implode(' AND ', $clauses), $params];
    }
}
