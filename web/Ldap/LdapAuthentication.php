<?php

namespace PrintApp\Ldap;

/**
 * LDAP Authentication Utility class
 */
class LdapAuthentication {
    private $app;
    private $validLdaps = NULL;
    
    public function __construct($app) {
        $this->app = $app;
    }
    
    /**
     * Attempts to authenticate the user based on the passed Domain, Username, and Password
     * This function will only authenticate to domains listed in the configuration
     * file. If no matching domain is found it will return FALSE - which should be
     * interpreted by the caller as no authentication was attempted.
     * 
     * An exception is thrown if a username or password does not match.
     * 
     * @param string $Domain
     * @param string $Username
     * @param string $Password
     * @return boolean Returns TRUE if authentication is successful
     * @throws \Exception Throws if authentication is attempted but fails
     */
    public function TryToAuthenticate($Domain, $Username, $Password) {
        if ($this->validLdaps === NULL) {
            $this->validLdaps = $this->ParseOutLdapConfig();
        }

        $ldap = $this->getLdapForDomain($Domain);

        if (!empty($ldap)) {
            $ldap_connection = NULL;
            $hosts = $ldap['hosts'];
            for ($i = 0; $i < count($hosts) && !$ldap_connection; $hosts++) {
                if ($ldap['secure']) {
                    $ldap_connection = ldap_connect("ldaps://" . $hosts[$i]);
                }
                else {
                    $ldap_connection = ldap_connect("ldap://". $hosts[i]);
                }
            }
            if (!$ldap_connection) {
                throw new \Exception("Could not connect to any ldap hosts");
            }

            ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, '3');
            ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);

            $ldap_bind = ldap_bind($ldap_connection, $Username .'@'. $ldap['dn'], $Password);

            if (!$ldap_bind) {
                throw new \Exception("Bad username or password");
            }

            $ldap_search = ldap_search($ldap_connection, $ldap['base_ou'], '(&(objectClass=user)(objectCategory=person)(!(userAccountControl:1.2.840.113556.1.4.803:=2))(SAMAccountNAme='. \PrintApp\Ldap\Utils::EscapeLdap($Username) .'))', [ 'SAMAccountName' ]);

            if (!$ldap_search) {
                throw new \Exception("Could not perform search");
            }

            $ldap_entries = ldap_get_entries($ldap_connection, $ldap_search);

            if (!$ldap_entries || !$ldap_entries[0]) {
                throw new \Exception("Invalid user");
            }
            
            return TRUE;
        }
        return FALSE;
    }
    
    /**
     * Returns the default domain according to the
     * configuration file.
     * 
     * @return string
     */
    public function GetDefaultDomain() {
        if ($this->validLdaps === NULL) {
            $this->validLdaps = $this->ParseOutLdapConfig();
        }
        if (!empty($this->validLdaps) && count($this->validLdaps) > 0) {
            reset($this->validLdaps);
            return key($this->validLdaps);
        }
        return '';
    }
    
    /**
     * Returns the configuration associated with the domain, or the default
     * domain configuration if the domain does not explicitly match a
     * configuration.
     * 
     * @param string $Domain
     * @return array An associate array of configuration entries for the domain
     */
    private function GetLdapForDomain($Domain) {
        if (array_key_exists(strtolower($Domain), $this->validLdaps)) {
            return $this->validLdaps[strtolower($Domain)];
        }
        
        return reset($this->validLdaps);
    }
    
    /**
     * Reads the application configuration and returns all configured LDAP
     * servers. At the moment only Active Directory is supported so
     * other LDAP types are ignored.
     * 
     * @return array An associative array of all LDAP configurations
     * @throws \Exception Throws if no LDAP domains are configured
     */
    private function ParseOutLdapConfig() {
        $ldapConfig = NULL;
        
        if (!empty($this->app) && !empty($this->app['config']) && !empty($this->app['config']['ldap'])) {
            $ldapConfig = $this->app['config']['ldap'];
        }
        
        if (empty($ldapConfig) || count($ldapConfig) === 0) {
            throw new \Exception("No ldap servers are configured.");
        }
        
        $validLdaps = [];
        foreach ($ldapConfig as $domain => $config) {
            if (!empty($config['active_directory']) && $config['active_directory']) {
                $validLdaps[strtolower($domain)] = $config;
            }
        }
        
        if (count($validLdaps) === 0) {
            throw new \Exception("No ldap servers are configured.");
        }
        
        return $validLdaps;
    }
}

