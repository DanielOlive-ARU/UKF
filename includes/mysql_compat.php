<?php
/**
 * Compatibility shims for legacy mysql_* calls when running on PHP versions
 * where the original extension no longer exists. Wraps mysqli under the hood
 * so existing pages keep working while we migrate to PDO.
 */
if (!function_exists('mysql_connect')) {
    $GLOBALS['__ukf_mysql_link'] = null;

    function _ukf_mysql_resolve_link($link = null)
    {
        if ($link) {
            return $link;
        }
        return isset($GLOBALS['__ukf_mysql_link']) ? $GLOBALS['__ukf_mysql_link'] : null;
    }

    function mysql_connect($host = '', $user = '', $password = '')
    {
        $link = mysqli_connect($host, $user, $password);
        if (!$link) {
            trigger_error('mysql_connect(): ' . mysqli_connect_error(), E_USER_WARNING);
            return false;
        }
        $GLOBALS['__ukf_mysql_link'] = $link;
        return $link;
    }

    function mysql_select_db($dbname, $link = null)
    {
        $link = _ukf_mysql_resolve_link($link);
        if (!$link) {
            trigger_error('mysql_select_db(): no connection', E_USER_WARNING);
            return false;
        }
        return mysqli_select_db($link, $dbname);
    }

    function mysql_set_charset($charset, $link = null)
    {
        $link = _ukf_mysql_resolve_link($link);
        if (!$link) {
            trigger_error('mysql_set_charset(): no connection', E_USER_WARNING);
            return false;
        }
        return mysqli_set_charset($link, $charset);
    }

    function mysql_query($query, $link = null)
    {
        $link = _ukf_mysql_resolve_link($link);
        if (!$link) {
            trigger_error('mysql_query(): no connection', E_USER_WARNING);
            return false;
        }
        return mysqli_query($link, $query);
    }

    function mysql_fetch_assoc($result)
    {
        if (!$result) {
            return false;
        }
        return mysqli_fetch_assoc($result);
    }

    function mysql_num_rows($result)
    {
        if (!$result) {
            return 0;
        }
        return mysqli_num_rows($result);
    }

    function mysql_insert_id($link = null)
    {
        $link = _ukf_mysql_resolve_link($link);
        if (!$link) {
            trigger_error('mysql_insert_id(): no connection', E_USER_WARNING);
            return 0;
        }
        return mysqli_insert_id($link);
    }

    function mysql_real_escape_string($string, $link = null)
    {
        $link = _ukf_mysql_resolve_link($link);
        if (!$link) {
            trigger_error('mysql_real_escape_string(): no connection', E_USER_WARNING);
            return addslashes($string);
        }
        return mysqli_real_escape_string($link, $string);
    }

    function mysql_error($link = null)
    {
        $link = _ukf_mysql_resolve_link($link);
        if (!$link) {
            return mysqli_connect_error();
        }
        return mysqli_error($link);
    }
}
