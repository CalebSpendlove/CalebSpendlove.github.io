<?php
/*

Oxygen Webhelp plugin
Copyright (c) 1998-2015 Syncro Soft SRL, Romania.  All rights reserved.
Licensed under the terms stated in the license file EULA_Webhelp.txt 
available in the base directory of this Oxygen Webhelp plugin.

*/

class Ldap {
    /**
     * Backend name
     *
     * @var string
     */
    const AUTH_NAME = 'LDAP';

    /**
     * LDAP connection
     *
     * @var null | resource LDAP Connection
     */
    private $link = null;

    /**
     * LDAP server hostname
     *
     * @var string
     */
    private $ldap_server = LDAP_SERVER;

    /**
     * LDAP server port
     *
     * @var int
     */
    private $ldap_port = LDAP_PORT;

    /**
     * Set it TRUE to require certificate to be verified for "ldaps://" style URL.
     *
     * @var bool
     */
    private $ldap_ssl_verify = LDAP_SSL_VERIFY;

    /**
     * Set it TRUE to enable LDAP START_TLS support
     *
     * @var bool
     */
    private $ldap_start_tls = LDAP_START_TLS;

    /**
     * LDAP bind type:
     *    "anonymous"
     *    "user" - use the given user/password from the form
     *    "proxy" - a specific user to browse the LDAP directory
     *
     * @var string
     */
    private $ldap_bind_type = LDAP_BIND_TYPE;

    /**
     * LDAP username to connect with.
     * null for anonymous bind.
     *
     * @var string
     */
    private $ldap_username = LDAP_USERNAME;

    /**
     * LDAP password to connect with.
     * null for anonymous bind.
     *
     * @var string
     */
    private $ldap_password = LDAP_PASSWORD;

    /**
     * LDAP account base; i.e. root of all user account
     *
     * @var string
     */
    private $ldap_account_base = LDAP_ACCOUNT_BASE;

    /**
     * LDAP query pattern to use when searching for a user account
     *    e.g. ActiveDirectory: '(&(objectClass=user)(sAMAccountName=%s))'
     *    e.g. OpenLDAP: 'uid=%s'
     *
     * @var string
     */
    private $ldap_user_pattern = LDAP_USER_PATTERN;

    /**
     * Name of an attribute of the user account object which should be used as the full name of the user.
     *
     * @var string
     */
    private $ldap_account_fullname = LDAP_ACCOUNT_FULLNAME;

    /**
     * Name of an attribute of the user account object which should be used as the email of the user.
     *
     * @var string
     */
    private $ldap_account_email = LDAP_ACCOUNT_EMAIL;

    /**
     * Set LDAP server hostname
     *
     * @param string $ldap_server
     */
    public function setLdapServer($ldap_server)
    {
        $this->ldap_server = $ldap_server;
    }

    /**
     * Set LDAP server port
     *
     * @param int $ldap_port
     */
    public function setLdapPort($ldap_port)
    {
        $this->ldap_port = $ldap_port;
    }

    /**
     * Set LDAP_SSL_VERIFY
     *
     * @param boolean $ldap_ssl_verify
     */
    public function setLdapSslVerify($ldap_ssl_verify)
    {
        $this->ldap_ssl_verify = $ldap_ssl_verify;
    }

    /**
     * Set LDAP_START_TLS
     *
     * @param boolean $ldap_start_tls
     */
    public function setLdapStartTls($ldap_start_tls)
    {
        $this->ldap_start_tls = $ldap_start_tls;
    }

    /**
     * Set LDAP bind type
     *
     * @param string $ldap_bind_type
     */
    public function setLdapBindType($ldap_bind_type)
    {
        $this->ldap_bind_type = $ldap_bind_type;
    }

    /**
     * Set username used to connect to LDAP server
     *
     * @param null $ldap_username
     */
    public function setLdapUsername($ldap_username)
    {
        $this->ldap_username = $ldap_username;
    }

    /**
     * Set password used to connect to LDAP server
     *
     * @param null $ldap_password
     */
    public function setLdapPassword($ldap_password)
    {
        $this->ldap_password = $ldap_password;
    }

    /**
     * Set LDAP account base
     *
     * @param string $ldap_account_base
     */
    public function setLdapAccountBase($ldap_account_base)
    {
        $this->ldap_account_base = $ldap_account_base;
    }

    /**
     * Set LDAP query pattern
     *
     * @param string $ldap_user_pattern
     */
    public function setLdapUserPattern($ldap_user_pattern)
    {
        $this->ldap_user_pattern = $ldap_user_pattern;
    }

    /**
     * Set name of attribute used as the full name of the user
     *
     * @param string $ldap_account_fullname
     */
    public function setLdapAccountFullname($ldap_account_fullname)
    {
        $this->ldap_account_fullname = $ldap_account_fullname;
    }

    /**
     * Set name of attribute used as the email of the user
     *
     * @param string $ldap_account_email
     */
    public function setLdapAccountEmail($ldap_account_email)
    {
        $this->ldap_account_email = $ldap_account_email;
    }

    /**
     * Authenticate the user, given a username and a password.
     *
     * @access public
     * @param  string  $username  Username
     * @param  string  $password  Password
     * @return boolean The result of the operation as a boolean value.
     */
    public function authenticate($username, $password)
    {
        $result = $this->findUser($username, $password);

        if (is_array($result)) {
	        return true;
	    }
    
        return false;
    }

    /**
     * Find the user from the LDAP server
     * Interrogates the LDAP server using the given username and password.
     *
     * @access public
     * @param  string  $username  Username
     * @param  string  $password  Password
     * @return boolean|array false if the user was not found, or information about the user.
     */
    public function findUser($username, $password)
    {
        $ldap = $this->connect();

        if (is_resource($ldap) && $this->bind($ldap, $username, $password)) {
            return $this->search($ldap, $username, $password);
        }

        return false;
    }

    /**
     * Create a LDAP connection
     *
     * @access private
     * @return resource    $ldap    LDAP connection
     * @throws Exception
     */
    private function connect()
    {
        if (! function_exists('ldap_connect')) {
            die('The PHP LDAP extension is required');
        }

        // Skip SSL certificate verification
        if (! $this->ldap_ssl_verify) {
            putenv('LDAPTLS_REQCERT=never');
        }

        $ldap = ldap_connect($this->ldap_server, $this->ldap_port);

        if (! is_resource($ldap)) {
            throw new Exception('Can\'t connect to LDAP server: ' . $this->ldap_server);
        }

        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, 1);
        ldap_set_option($ldap, LDAP_OPT_TIMELIMIT, 1);

        if ($this->ldap_start_tls && ! @ldap_start_tls($ldap)) {
            die('Unable to use ldap_start_tls()');
        }

        $this->link = $ldap;

        return $ldap;
    }

    /**
     * Close current LDAP connection
     *
     * @return bool true on success or false on failure.
     */
    public function close()
    {
        return ldap_close($this->link) || false;
    }


    /**
     * LDAP bind
     *
     * @access private
     * @param  resource  $ldap      LDAP connection
     * @param  string    $username  Username
     * @param  string    $password  Password
     * @return boolean   true if success or false otherwise.
     */
    private function bind($ldap, $username, $password)
    {
        if ($this->ldap_bind_type === 'user') {
            $ldap_username = sprintf($this->ldap_username, $username);
            $ldap_password = $password;
        }
        else if ($this->ldap_bind_type === 'proxy') {
            $ldap_username = $this->ldap_username;
            $ldap_password = $this->ldap_password;
        }
        else {
            $ldap_username = null;
            $ldap_password = null;
        }

        if (! @ldap_bind($ldap, $ldap_username, $ldap_password)) {
            return false;
        }

        return true;
    }

    /**
     * LDAP user lookup
     *
     * @access private
     * @param  resource  $ldap      LDAP connection
     * @param  string    $username  Username
     * @param  string    $password  Password
     * @return boolean|array false if no user found or information about the user.
     */
    private function search($ldap, $username, $password)
    {
        $sr = @ldap_search($ldap, $this->ldap_account_base, sprintf($this->ldap_user_pattern, $username), array($this->ldap_account_fullname, $this->ldap_account_email));

        if ($sr === false) {
            return false;
        }

        $info = ldap_get_entries($ldap, $sr);

        // User not found
        if (count($info) == 0 || $info['count'] == 0) {
            return false;
        }

        // We got our user
        if (@ldap_bind($ldap, $info[0]['dn'], $password)) {

            return array(
                'username' => $username,
                'name' => isset($info[0][$this->ldap_account_fullname][0]) ? $info[0][$this->ldap_account_fullname][0] : '',
                'email' => isset($info[0][$this->ldap_account_email][0]) ? $info[0][$this->ldap_account_email][0] : '',
            );
        }

        return false;
    }

    /**
     * LDAP users list
     *
     * @access public
     * @param  array        $info   Required information
     * @param  integer      $limit  Limit result
     * @return array|bool   false if failure or no user found, or information about all users.
     * @throws Exception if can't bind LDAP server using given credentials
     */
    public function listAllUsers( $info, $limit )
    {
        $ldap = $this->connect();
        $result = false;

        if ( $this->bind($ldap, $this->ldap_username, $this->ldap_password) ) {
            $request = @ldap_list($ldap, $this->ldap_account_base, sprintf($this->ldap_user_pattern, "*"), $info, 0, $limit);
            $result = @ldap_get_entries($ldap, $request);
        } else {
            throw new Exception('Can\'t bind LDAP server using provided credentials');
        }

        return $result;
    }

	/**
     * Gather LDAP user information
     *
     * @access public
     * @param string    $username   LDAP Username
     * @param array     $info       Required information
     * @return array|null null if failure or required information about the user.
     * @throws Exception
     */
    public function getUserInfo( $username, $info )
    {
        $ldap = $this->connect();
        $result = null;
        if ( $this->bind($ldap, $this->ldap_username, $this->ldap_password) ) {
            $request = @ldap_list($ldap, $this->ldap_account_base, sprintf($this->ldap_user_pattern, $username), $info);
            $result = @ldap_get_entries($ldap, $request);
        } else {
            throw new Exception('Can\'t bind LDAP server using ' . $this->ldap_username . ' and ' . $this->ldap_password);
        }

        return $result;
    }

    /**
     * Test LDAP connection using specified settings
     *
     * @return bool true if LDAP connection succeed or false if failure.
     * @throws Exception
     */
    public function testLdapConnection() {
        $toReturn = false;

        try {
            $ldap = $this->connect();
            $toReturn = $this->bind($ldap, $this->ldap_username, $this->ldap_password);
        } catch (Exception $e) {
            throw new Exception('Can\'t bind LDAP server using specified settings.');
        }

        return $toReturn;
    }
} 