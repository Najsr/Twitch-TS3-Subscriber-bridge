<?php
namespace Database;

class Database extends \SQLite3
{
    function __construct()
    {
        $this->open('clients.db');
    }

    function __destruct()
    {
        $this->close();
    }

    function init()
    {
        $this->query('CREATE TABLE IF NOT EXISTS `USERS`
        (`key` integer PRIMARY KEY autoincrement,
        `id` varchar(255) NOT NULL default 0,
        `uid` varchar(255) NOT NULL default 0)
        ');
    }

    function add($cid, $cuid)
    {
        $stmt = $this->prepare("INSERT INTO `USERS` (id, uid) VALUES (:id, :uid)");
        $stmt->bindValue(':id', $cid, SQLITE3_TEXT);
        $stmt->bindValue(':uid', $cuid, SQLITE3_TEXT);
        $stmt->execute();
        return $this->changes() >= 1 ? true : false;
    }

    function get($id)
    {
        $stmt = $result = null;
        if (!is_numeric($id)) {
            $stmt = $this->prepare("SELECT * FROM `USERS` WHERE uid = :id");
        } else {
            $stmt = $this->prepare("SELECT * FROM `USERS` WHERE id = :id");
        }
        $stmt->bindValue(':id', $id);
        return $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    }

    function getAll()
    {
        $stmt = $this->prepare("SELECT * FROM `USERS`");
        $result = $stmt->execute();
        $rows = [];
        while($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[$row['id']] = $row;
        }
        return $rows;
    }

    function delete($id)
    {
        $stmt;
        if (!is_numeric($id)) {
            $stmt = $this->prepare("DELETE FROM `USERS` WHERE uid = :id");
        } else {
            $stmt = $this->prepare("DELETE FROM `USERS` WHERE id = :id");
        }
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $this->changes() >= 1 ? true : false;
    }
}
