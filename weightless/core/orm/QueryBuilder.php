<?php

namespace Weightless\Core\ORM;

class QueryBuilder
{
    private string $table;
    private array $where = [];
    private array $params = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public static function table(string $table): self
    {
        return new self($table);
    }

    public function where(string $column, string $operator, $value): self
    {
        $this->where[] = "$column $operator ?";
        $this->params[] = $value;
        return $this;
    }

    public function get(): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if (!empty($this->where)) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
        }
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($this->params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
