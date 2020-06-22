<?php

namespace App\Core\Database;

use PDO;

class QueryBuilder
{
    protected $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function selectAll($table)
    {
        $stmt = $this->pdo->prepare("select * from {$table} where Deleted = false");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function selectBy($table, $params, bool $deleted = true, $options = [])
    {
        $paramstr = '';
        foreach ($params as $key => $value) :
            $paramstr = $paramstr . "{$key} = :{$key} and ";
        endforeach;
        // $paramstr = trim($paramstr, ' and ');
        $query = '';
        if (empty($options)) :
            $deleted ? $query = sprintf(
                'select * from %s where Deleted = false and  %s',
                $table,
                $paramstr
            ) : $query = sprintf(
                'select * from %s where %s',
                $table,
                $paramstr
            );
        else :
            $deleted ? $query = sprintf(
                "select %s from %s where Deleted = false and %s",
                implode(', ', array_values($options)),
                $table,
                $paramstr
            ) : $query = sprintf(
                "select %s from %s where %s",
                implode(', ', array_values($options)),
                $table,
                $paramstr
            );
        endif;
        $query = $query . '1=1';
        try {
            $stmt = $this->pdo->prepare($query);
            foreach ($params as $key => $value) :
                $stmt->bindValue(':' . $key, $value);
            endforeach;
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    public function insert($table, $params)
    {
        $query = sprintf(
            'insert into %s (%s) values(%s)',
            $table,
            implode(', ', array_keys($params)),
            ':' . implode(', :', array_keys($params))
        );
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $this->pdo->lastInsertId();
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    public function delete($table, $column, $param)
    {
        $query = sprintf(
            "update %s set Deleted = true where %s = %s",
            $table,
            $column,
            ':' . $column
        );
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':' . $column, $param);
            $stmt->execute();
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    public function modify($table, $params, $whereCol, $whereVal)
    {
        $paramstr = '';
        foreach ($params as $key => $value) :
            $paramstr = $paramstr . "{$key} = :{$key}, ";
        endforeach;
        $paramstr = trim($paramstr, ', ');
        $query = sprintf(
            'update %s set %s where %s = %s',
            $table,
            $paramstr,
            $whereCol,
            $whereVal
        );
        try {
            $stmt = $this->pdo->prepare($query);
            foreach ($params as $key => $value) :
                $stmt->bindValue(':' . $key, $value);
            endforeach;
            $stmt->execute();
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }
}
