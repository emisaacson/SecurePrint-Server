<?php

namespace PrintApp\Ldap;

/**
 * LDAP utility class
 */
class Utils {

    /**
     * A function to escape metacharaters in an LDAP query string.
     * 
     * @param string $string The input string
     * @return string A safe string to embed in an LDAP query
     */
    static function EscapeLdap($string) {

        $escapeDn = array('\\', '*', '(', ')', "\x00");
        $escape   = array('\\', ',', '=', '+', '<', '>', ';', '"', '#');

        $search = array_merge($escape, $escapeDn);

        $replace = array();
        foreach ($search as $char) {
            $replace[] = sprintf('\\%02x', ord($char));
        }

        return str_replace($search, $replace, $string);
    }

}
