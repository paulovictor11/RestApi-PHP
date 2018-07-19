<?php

class DbOperations {
    private $con;

    function __construct(){
        require_once dirname(__FILE__) . '/DbConnect.php';

        $db = new DbConnect;
        $this->con = $db->connect();
    }

    public function createUser($email, $password, $name, $school){
        if (!$this->isEmailExists($email)) {
            $stmt = $this->con->prepare("INSERT INTO users (email, password, name, school) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $email, $password, $name, $school);
            
            if($stmt->execute()){
                return USER_CREATED;
            } else {
                return USER_FAILURE;
            }
        }

        return USER_EXISTS;
    }

    public function userLogin($email, $password){
        if ($this->isEmailExists($email)) {
            $hashed_password = $this->getUserPasswordByEmail($email);
            if (password_verify($password, $hashed_password)) {
                return USER_AUTHENTICATED;
            } else {
                return USER_PASS_DONT_MATCH;
            }
        } else {
            return USER_NOT_FOUND;
        }
    }

    private function getUserPasswordByEmail($email){
        $stmt = $this->con->prepare("SELECT password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($password);
        $stmt->fetch();
        
        return $password;
    }

    public function getAllUsers(){
        $stmt = $this->con->prepare("SELECT id, email, name, school FROM users;");
        $stmt->execute();
        $stmt->bind_result($id, $email, $name, $school);
        $users = array();

        while ($stmt->fetch()){
            $user = array();
            $user['id'] = $id;
            $user['email'] = $email;
            $user['name'] = $name;
            $user['school'] = $school;

            array_push($users, $user);
        }

        return $users;
    }

    public function getUserByEmail($email){
        $stmt = $this->con->prepare("SELECT id, email, name, school FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($id, $email, $name, $school);
        $stmt->fetch();
        
        $user = array();
        $user['id'] = $id;
        $user['email'] = $email;
        $user['name'] = $name;
        $user['school'] = $school;

        return $user;

    }

    public function updateUser($email, $name, $school, $id){
        $stmt = $this->con->prepare("UPDATE users SET email = ?, name = ?, school = ? WHERE id = ?");
        $stmt->bind_param("sssi", $email, $name, $school, $id);

        if($stmt->execute()){
            return true; 
        }

        return false; 
    }

    public function updatePassword($currentPassword, $newPassword, $email){
        $hashed_password = $this->getUserPasswordByEmail($email);

        if (password_verify($currentPassword, $hashed_password)) {
            $hash_password = password_hash($newpassword, PASSWORD_DEFAULT);
            $stmt = $this->con->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hash_password, $email);

            if ($stmt->execute()) {
                return PASS_CHANGED;
            }

            return PASS_NOT_CHNAGED;
        } else {
            return PASS_DONT_MATCH;
        }
    }

    public function deleteUser($id){
        $stmt = $this->con->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    private function isEmailExists($email){
        $stmt = $this->con->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        return $stmt->num_rows > 0;
    }
}