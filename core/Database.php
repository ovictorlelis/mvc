<?php

namespace core;

use PDO;
use PDOException;

class Database
{
    protected $pdo;
    protected $table;
    protected $fields = '*';
    protected $sql = '';
    protected $wheres = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function table($table)
    {
        $this->table = $table;
        return $this;
    }

    public function select($fields)
    {
        $this->fields = $fields;
        return $this;
    }

    public function insert($values)
    {
        $fields = array_keys($values);
        $binds  = array_pad([], count($fields), '?');

        $this->sql = 'INSERT INTO ' . $this->table . ' (' . implode(',', $fields) . ') VALUES (' . implode(',', $binds) . ')';

        try {
            $stmt = $this->pdo->prepare($this->sql);
            $stmt->execute(array_values($values));
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            // Log the error instead of using die()
            error_log($e->getMessage());
            throw new PDOException("Database Insertion Error: " . $e->getMessage());
        } finally {
            $this->reset();
        }
    }

    public function update($values)
    {
        $fields = array_keys($values);
        $setClause = implode(' = ?, ', $fields) . ' = ?';

        $this->sql = 'UPDATE ' . $this->table . ' SET ' . $setClause;
        $this->appendWheres();

        try {
            $stmt = $this->pdo->prepare($this->sql);
            $bindedValues = array_merge(array_values($values), array_column($this->wheres, 'value'));
            $stmt->execute($bindedValues);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new PDOException("Database Update Error: " . $e->getMessage());
        } finally {
            $this->reset();
        }
    }

    public function delete()
    {
        $this->sql = 'DELETE FROM ' . $this->table;
        $this->appendWheres();

        try {
            $stmt = $this->pdo->prepare($this->sql);
            $bindedValues = array_column($this->wheres, 'value');
            $stmt->execute($bindedValues);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new PDOException("Database Deletion Error: " . $e->getMessage());
        } finally {
            $this->reset();
        }
    }

    public function where($column, $option, $value = '')
    {
        if (empty($value)) {
            $this->wheres[] = [
                'type' => 'AND',
                'column' => $column,
                'operator' => '=',
                'value' => $option,
            ];
        } else {
            $this->wheres[] = [
                'type' => 'AND',
                'column' => $column,
                'operator' => $option,
                'value' => $value,
            ];
        }

        return $this;
    }

    public function orWhere($column, $option, $value = '')
    {
        if (empty($value)) {
            $this->wheres[] = [
                'type' => 'OR',
                'column' => $column,
                'operator' => '=',
                'value' => $option,
            ];
        } else {
            $this->wheres[] = [
                'type' => 'OR',
                'column' => $column,
                'operator' => $option,
                'value' => $value,
            ];
        }

        return $this;
    }

    public function get()
    {
        $this->sql = 'SELECT ' . $this->fields . ' FROM ' . $this->table;
        $this->appendWheres();

        try {
            $stmt = $this->pdo->prepare($this->sql);
            $bindedValues = array_column($this->wheres, 'value');
            $stmt->execute($bindedValues);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new PDOException("Database Fetch Error: " . $e->getMessage());
        } finally {
            $this->reset();
        }
    }

    public function first()
    {
        $this->sql = 'SELECT ' . $this->fields . ' FROM ' . $this->table;
        $this->appendWheres();

        try {
            $stmt = $this->pdo->prepare($this->sql);
            $bindedValues = array_column($this->wheres, 'value');
            $stmt->execute($bindedValues);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new PDOException("Database Fetch Error: " . $e->getMessage());
        } finally {
            $this->reset();
        }
    }

    public function firstOrFail()
    {
        $result = $this->first();
        if ($result) {
            return $result;
        }

        $controller = new \core\Controller();
        die($controller->view('404'));
    }

    public function find($id)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM $this->table WHERE id = ?");
            $stmt->bindValue(1, $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new PDOException("Database Find Error: " . $e->getMessage());
        }
    }

    public function findOrFail($id)
    {
        $result = $this->find($id);
        if ($result) {
            return $result;
        }

        $controller = new \core\Controller();
        die($controller->view('404'));
    }

    private function appendWheres()
    {
        if (!empty($this->wheres)) {
            $this->sql .= ' WHERE ';
            foreach ($this->wheres as $index => $where) {
                if ($index > 0) {
                    $this->sql .= ' ' . $where['type'] . ' ';
                }
                $this->sql .= $where['column'] . ' ' . $where['operator'] . ' ?';
            }
        }
    }


    public function query($query, $params = [])
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    private function reset()
    {
        $this->fields = '*';
        $this->sql = '';
        $this->wheres = [];
    }
}
