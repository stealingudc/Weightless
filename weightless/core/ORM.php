<?php

namespace Weightless\Core;

use Weightless\Core\ORM\AutoIncrement;
use Weightless\Core\ORM\Column;
use Weightless\Core\ORM\Database;
use Weightless\Core\ORM\ID;
use Weightless\Core\ORM\Relationships\ManyToOne;
use Weightless\Core\ORM\Relationships\OneToMany;
use Weightless\Core\ORM\Table;
use Weightless\Core\ORM\Type;
use Weightless\Core\ORM\Validator;

class ORM
{
  // TODO: Transactions

  protected static function getTableName(string $className): string
  {
    $reflection = new \ReflectionClass($className);
    $tableAttr = $reflection->getAttributes(Table::class)[0] ?? null;
    if (!$tableAttr) {
      throw new \Exception("Class $className does not have a Table attribute.");
    }
    return $tableAttr->newInstance()->name;
  }

  /**
   * @return array<string, string> 
   */
  protected static function getColumnMappings(object $entity): array
  {
    $reflection = new \ReflectionClass($entity);
    $columns = [];
    foreach ($reflection->getProperties() as $property) {
      $columnAttr = $property->getAttributes(Column::class)[0] ?? null;
      if ($columnAttr) {
        $columns[$property->getName()] = $columnAttr->newInstance()->name;
      }
    }
    return $columns;
  }

  public static function save(object $entity): bool
  {
    // Ensure the table exists
    self::createTableIfNotExists($entity);

    // Validate entity data
    self::validateEntity($entity);

    $tableName = self::getTableName(get_class($entity));
    $columns = self::getColumnMappings($entity);

    $fields = [];
    $values = [];
    $placeholders = [];
    $idProperty = null;
    $autoIncrement = null;

    foreach ($columns as $property => $column) {
      $reflectionProperty = new \ReflectionProperty($entity, $property);
      $value = $reflectionProperty->getValue($entity);

      // Check if this property is the ID and if it should auto-increment
      if ($reflectionProperty->getAttributes(Id::class)) {
        $idProperty = $reflectionProperty;
        $autoIncrement = $reflectionProperty->getAttributes(AutoIncrement::class)[0] ?? null;

        if ($autoIncrement && $autoIncrement->newInstance()->enabled && $value === null) {
          continue; // Skip adding the ID column if it's auto-incrementing and not set
        }
      }

      $fields[] = $column;
      $placeholders[] = '?';
      $values[] = $value;
    }

    $sql = "INSERT INTO $tableName (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = Database::getConnection()->prepare($sql);
    $result = $stmt->execute($values);

    // If the entity has an auto-incrementing ID, set it after insertion
    if ($idProperty && $autoIncrement && $autoIncrement->newInstance()->enabled) {
      $lastInsertId = Database::getConnection()->lastInsertId();
      $idProperty->setValue($entity, (int)$lastInsertId);
    }

    return $result;
  }

  public static function update(object $entity): void
  {
    self::validateEntity($entity);

    $tableName = self::getTableName(get_class($entity));
    $columns = self::getColumnMappings($entity);

    $fields = [];
    $values = [];

    $idColumn = null;
    $idValue = null;

    foreach ($columns as $property => $column) {
      $value = (new \ReflectionProperty($entity, $property))->getValue($entity);

      if ((new \ReflectionProperty($entity, $property))->getAttributes(Id::class)) {
        $idColumn = $column;
        $idValue = $value;
      } else {
        $fields[] = "$column = ?";
        $values[] = $value;
      }
    }

    if (!$idColumn || !$idValue) {
      throw new \Exception("Entity does not have a valid ID.");
    }

    $values[] = $idValue;
    $sql = "UPDATE $tableName SET " . implode(', ', $fields) . " WHERE $idColumn = ?";
    $stmt = Database::getConnection()->prepare($sql);
    $stmt->execute($values);
  }

  public static function delete(object $entity): void
  {
    $tableName = self::getTableName(get_class($entity));
    $columns = self::getColumnMappings($entity);

    $idColumn = null;
    $idValue = null;

    foreach ($columns as $property => $column) {
      if ((new \ReflectionProperty($entity, $property))->getAttributes(Id::class)) {
        $idColumn = $column;
        $idValue = (new \ReflectionProperty($entity, $property))->getValue($entity);
        break;
      }
    }

    if (!$idColumn || !$idValue) {
      throw new \Exception("Entity does not have a valid ID.");
    }

    $sql = "DELETE FROM $tableName WHERE $idColumn = ?";
    $stmt = Database::getConnection()->prepare($sql);
    $stmt->execute([$idValue]);
  }

  /**
   * @return array<mixed>
   * */
  public static function find(string $className, string $column, mixed $value): array
  {
    $tableName = self::getTableName($className);

    // Build the SQL query to find by the given column
    $sql = "SELECT * FROM $tableName WHERE $column = ?";
    $stmt = Database::getConnection()->prepare($sql);
    $stmt->execute([$value]);
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    if (!$rows) {
      return [];
    }

    $entities = [];
    $reflection = new \ReflectionClass($className);

    foreach ($rows as $data) {
      $entity = new $className();

      // Map the data to the entity's properties
      foreach ($data as $column => $value) {
        foreach ($reflection->getProperties() as $property) {
          $columnAttr = $property->getAttributes(Column::class)[0] ?? null;
          if ($columnAttr && $columnAttr->newInstance()->name === $column) {
            $property->setValue($entity, $value);
          }
        }
      }

      // Load related entities
      foreach ($reflection->getProperties() as $property) {
        if ($property->getAttributes(OneToMany::class)) {
          $relation = $property->getAttributes(OneToMany::class)[0]->newInstance();
          $relatedEntities = self::findRelated($relation->targetEntity, $relation->mappedBy, $entity);
          $property->setValue($entity, $relatedEntities);
        }

        if ($property->getAttributes(ManyToOne::class)) {
          $relation = $property->getAttributes(ManyToOne::class)[0]->newInstance();
          $relatedEntity = self::findOne($relation->targetEntity, $property->getAttributes(Column::class)[0]->newInstance()->name, $data[$property->getAttributes(Column::class)[0]->newInstance()->name]);
          $property->setValue($entity, $relatedEntity);
        }
      }

      $entities[] = $entity;
    }

    return $entities;
  }

  public static function findOne(string $className, string $column, mixed $value): ?object
  {
    $results = self::find($className, $column, $value);

    return $results[0] ?? null;
  }

  /**
   * @return array<mixed>
   * */
  public static function findAll(string $className): array
  {
    $tableName = self::getTableName($className);
    $sql = "SELECT * FROM $tableName";
    $stmt = Database::getConnection()->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    $results = [];
    foreach ($rows as $data) {
      $reflection = new \ReflectionClass($className);
      $entity = new $className();
      foreach ($data as $column => $value) {
        foreach ($reflection->getProperties() as $property) {
          $columnAttr = $property->getAttributes(Column::class)[0] ?? null;
          if ($columnAttr && $columnAttr->newInstance()->name === $column) {
            $property->setValue($entity, $value);
          }
        }
      }
      $results[] = $entity;
    }

    return $results;
  }

  /**
   * @return array<mixed>
   * */
  private static function findRelated(string $targetEntity, string $mappedBy, object $entity): array
  {
    $tableName = self::getTableName($targetEntity);
    $mappedByColumn = (new \ReflectionClass($targetEntity))->getProperty($mappedBy)->getAttributes(Column::class)[0]->newInstance()->name;
    $idValue = (new \ReflectionProperty($entity, 'id'))->getValue($entity);

    $sql = "SELECT * FROM $tableName WHERE $mappedByColumn = ?";
    $stmt = Database::getConnection()->prepare($sql);
    $stmt->execute([$idValue]);
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    $relatedEntities = [];
    foreach ($rows as $row) {
      $relatedEntities[] = self::findOne($targetEntity, 'id', $row['id']);
    }

    return $relatedEntities;
  }

  private static function createTableIfNotExists(object $entity): bool
  {
    $tableName = self::getTableName(get_class($entity));
    $columns = self::getColumnMappings($entity);

    $columnDefinitions = [];

    foreach ($columns as $property => $column) {
      $reflectionProperty = new \ReflectionProperty($entity, $property);
      // @phpstan-ignore-next-line (See: https://github.com/phpstan/phpstan/issues/3937)
      $type = $reflectionProperty->getType()->getName();

      // Define column types based on PHP types
      switch ($type) {
        case 'int':
          $sqlType = 'INT';
          break;
        case 'string':
          $sqlType = 'VARCHAR(255)';
          break;
        case 'float':
          $sqlType = 'FLOAT';
          break;
        case 'bool':
          $sqlType = 'BOOLEAN';
          break;
        default:
          throw new \Exception("Unsupported data type: $type");
      }

      // Check if this property is the ID and if it should auto-increment
      if ($reflectionProperty->getAttributes(Id::class)) {
        $sqlType .= ' PRIMARY KEY';
        $autoIncrement = $reflectionProperty->getAttributes(AutoIncrement::class)[0] ?? null;

        if ($autoIncrement && $autoIncrement->newInstance()->enabled) {
          $sqlType .= ' AUTO_INCREMENT';
        }
      }

      $columnDefinitions[] = "$column $sqlType";
    }

    $columnsSql = implode(', ', $columnDefinitions);
    $sql = "CREATE TABLE IF NOT EXISTS $tableName ($columnsSql)";

    $stmt = Database::getConnection()->prepare($sql);
    return $stmt->execute();
  }

  private static function validateEntity(object $entity): void
  {
    $reflection = new \ReflectionClass($entity);
    $validator = new Validator();
    $rules = [];
    $data = [];

    foreach ($reflection->getProperties() as $property) {
      $property->setAccessible(true);
      $value = $property->getValue($entity);

      $validationAttr = $property->getAttributes(Type::class)[0] ?? null;
      if ($validationAttr) {
        $rules[$property->getName()] = $validationAttr->newInstance()->rules;
      }

      $data[$property->getName()] = $value;
    }

    if (!$validator->validate($data, $rules)) {
      $errors = $validator->getErrors();
      throw new \Exception("Validation failed: " . json_encode($errors));
    }
  }
}
