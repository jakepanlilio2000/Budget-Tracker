<?php
namespace models;
use core\Model;

class User extends Model {
    protected string $table = 'users';

    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function updateProfile(int $id, string $name, string $email): bool {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET name = :name, email = :email WHERE id = :id");
        return $stmt->execute(['name' => $name, 'email' => $email, 'id' => $id]);
    }

    public function updatePassword(int $id, string $hashedPassword): bool {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET password = :password WHERE id = :id");
        return $stmt->execute(['password' => $hashedPassword, 'id' => $id]);
    }
}