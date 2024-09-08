<?php

namespace Weightless\Core\ORM;

class QueryBuilder
{
  private string $table;
  /** @var array<string> */
  private array $where = [];
  /** @var array<mixed> */
  private array $params = [];

  public function __construct(string $table)
  {
    $this->table = $table;
  }

  public static function table(string $table): self
  {
    return new self($table);
  }

  public function where(string $column, string $operator, mixed $value): self
  {
    $this->where[] = "$column $operator ?";
    $this->params[] = $value;
    return $this;
  }

  /** @return array<mixed> */
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
