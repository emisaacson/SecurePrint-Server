<?php

namespace PrintApp\UserManagement;

use Symfony\Component\HttpFoundation\Request;

class User {
    private $app;
    private $user_id;
    
    public $Username;
    public $Domain;
    public $DeviceID;
    public $Token;
    
    public function __construct($app, $user_id = NULL) {
        $this->app = $app;
        $this->user_id = $user_id;
    }
    
    public function Load() {
        if (!empty($this->user_id)) {
            $User = $this->app['db']->fetchAssoc("SELECT * FROM user_devices WHERE id = ? LIMIT 1", [ $this->user_id ]);
            $this->Username = $User['username'];
            $this->Domain = $User['domain'];
            $this->Token = $User['token'];
            $this->DeviceID = $User['device_id'];
        }
    }
    
    public function Delete() {
        if (!empty($this->user_id)) {
            //$result = $this->app['db']->executeUpdate("UPDATE user_devices SET is_deleted = true WHERE id = ?",
            $result = $this->app['db']->executeUpdate("DELETE FROM user_devices WHERE id = ?",
                [ $this->user_id ]);
            
            if (!$result) {
                throw new \Exception("Failed to delete user");
            }
        }
    }
    
    public function Save() {
        if (empty($this->user_id)) {
            $result = $this->app['db']->executeUpdate("INSERT INTO user_devices (device_id, username, domain, token) VALUES (?, ?, ?, ?)",
                [ $this->DeviceID, $this->Username, $this->Domain, $this->Token ]);

            $this->user_id = $this->app['db']->lastInsertId();
            if (!$result) {
                throw new \Exception("Failed to insert user");
            }
        }
        else {
            $result = $this->app['db']->executeUpdate("UPDATE user_devices SET is_deleted = false, device_id = ?, username = ?, domain = ?, token = ? WHERE id = ?",
                [ $this->DeviceID, $this->Username, $this->Domain, $this->Token, $this->user_id ]);
            
            if (!$result) {
                throw new \Exception("Failed to update user");
            }
        }
    }
    
    public function ResetToken() {
        $this->Token = User::GetRandomToken();
    }
    
    public static function TryGetUserByUsername($Username, $Domain, $app) {
        $User = $app['db']->fetchAssoc("SELECT * FROM user_devices WHERE username = ? AND domain = ? LIMIT 1", [ $Username, $Domain ]);
        if (!empty($User) && array_key_exists('id', $User) && is_int($User['id'])) {
            return new User($app, $User['id']);
        }
        else {
            return NULL;
        }
    }
    
    public static function GetDomainAndUsernameFromRequest(Request $request) {
        return $request->query->get('domain') ."\\". $request->query->get('username');
    }
    
    public static function GetUsernameFromRequest(Request $request) {
        $username = $request->query->get('username');

        return $username;
    }
    
    public static function GetDomainAndUsernameFromInput($Input, $app) {
        $Domain = "";
        $Username = $Input;
        $UsernameSplit = NULL;

        if (strpos($Input, "\\") !== FALSE) {
            $UsernameSplit = explode("\\", $Input);
        }
        else if (strpos($Input, "/") !== FALSE) {
            $UsernameSplit = explode("/", $Input);
        }

        if ($UsernameSplit !== NULL) {
            $Username = $UsernameSplit[1];
            $Domain = $UsernameSplit[0];
        }
        
        if ($Domain === "") {
            $Ldap = new \PrintApp\Ldap\LdapAuthentication($app);
            $Domain = $Ldap->GetDefaultDomain();
        }
        
        return [
            'Username' => $Username,
            'Domain' => $Domain,
        ];
    }
    
    public static function GetRandomToken() {
        return sha1((string)rand());
    }
}

