<?php
/**
 * Useful functions to use
 *
 * PHP version 7.0
 *
 * @package  Stationer\Graphite
 * @author   Tyler Uebele
 * @license  MIT https://github.com/stationer/Graphite/blob/master/LICENSE
 * @link     https://github.com/stationer/Graphite
 */

/**
 * Fetch raw HTTP request headers
 * Cache the result for successive calls to avoid manifest issues with successive calls.
 *
 * @return string Full representation of HTTP request headers
 */
function php_getRawInputHeader() {
    static $output = '';
    if ('' == $output && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        foreach ($headers as $k => $v) {
            $output .= $k . ': ' . $v . "\n";
        }
    }

    return $output;
}

/**
 * Fetch raw HTTP request body
 * Cache the result for successive calls to avoid manifest issues with successive calls.
 *
 * @return string Full representation of HTTP request body
 */
function php_getRawInputBody() {
    static $output = '';
    if ('' == $output) {
        $output = file_get_contents('php://input', null, null, 0);
    }

    return $output;
}

/**
 * Fetch raw HTTP request
 *
 * @return string Full representation of HTTP request headers and body
 */
function php_getRawInput() {
    return php_getRawInputHeader() ."\n". php_getRawInputBody();
}

/**
 * Updates a variable in a url
 *
 * @param string $url      URL to add the variable to
 * @param string $variable Variable in the query string to alter
 * @param mixed  $value    Value to set the query string to
 *
 * @return string
 */
function updateQueryString($url, $variable, $value) {
    $baseUrl = $url;
    $query = array();

    if (strpos($url, '?') !== false) {
        $parts = explode('?', $url);
        $baseUrl = reset($parts);
        $queryString = end($parts);

        parse_str($queryString, $query);
    }

    $query[$variable] = $value;

    return $baseUrl . '?' . http_build_query($query);
}

/**
 * A shorthand for the frequent
 *   `isset($var) ? $var : '';`
 * statements that salt our codebase.  Used as
 *   `ifset($var)`
 *
 * @param mixed $test    Value to test and return if set.
 * @param mixed $default Value to return if $test is empty
 *
 * @return mixed
 */
function ifset(&$test, $default = null) {
    return isset($test) ? $test : $default;
}


/**
 * Compares an array (supports multidimensional) to a other versions and merges differences
 *
 * @param array $base       Base array before changes were made
 * @param array ...$patches Other arrays to compare with
 *
 * @return array $result Array containing the merged differences
 */
function array_patch(array $base, array ...$patches) {
    // Initialize result array to base array
    $result = [] + $base;

    // Loop over patch arrays and patch the result with each
    foreach ($patches as $patch) {
        // Merge things result which are absent from base
        foreach ($patch as $key => $value) {
            if (!isset($base[$key])) {
                $result[$key] = $patch[$key];
            }
        }

        foreach ($base as $key => $value) {
            // remove things from result which are missing from patch
            if (!isset($patch[$key])) {
                unset($result[$key]);
                continue;
            }
            // Skip items which are unchanged
            // soft equals to ignore types and allow unordered array compare
            if ($patch[$key] == $base[$key]) {
                continue;
            }
            // Merge things into result which are different from base in patch
            // If both values are arrays, use recusion
            if (is_array($patch[$key]) && is_array($base[$key])) {
                $result[$key] = array_patch($base[$key], $patch[$key]);
                continue;
            }
            // If either value is scalar, merge the patch value into the result
            $result[$key] = $patch[$key];
        }
    }

    return $result;
}

/**
 * Determine if the specified array contains all the specified keys
 * j
 * @param array $keys   Keys to seek
 * @param array $search Array to seek in
 *
 * @return bool
 */
function array_keys_exist($keys, $search) {
    // If we were passed a single key, use existing function
    if (!is_array($keys)) {
        return array_key_exists($keys, $search);
    }
    // If there are no keys in $search that are not in $keys
    // We have all the keys
    return [] == array_diff($keys, array_keys($search));
}


/**
 * Searches through array to find positive integer values
 *
 * @param array $data Array of data being processed
 *
 * @return array Array of integers
 */
function array_filter_ids(array $data) {
    // Array to be returned
    $newData = [];

    // Validates that each id is an integer
    foreach ($data as $id) {
        // Verifies that $val is an integer or a string representation of one
        if (is_numeric($id) && filter_var($id, FILTER_VALIDATE_INT)) {
            // Validate that the integer is positive
            $id = (int)$id;
            if ($id >= 0) {
                $newData[] = $id;
            }
        }
    }

    return $newData;
}

/**
 * Helper for brevity in templates - echo html escaped string
 *
 * @param string $s String to output
 *
 * @return void
 */
function html($s) {
    echo htmlspecialchars($s, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
}

/**
 * Capture the output of var_dump() and return it as a string
 *
 * @param mixed $s value to dump
 *
 * @return string
 */
function ob_var_dump($s) {
    ob_start();
    // @codingStandardsIgnoreStart
    var_dump($s);
    // @codingStandardsIgnoreEnd

    return ob_get_clean();
}

/**
 * Emit invocation info, and passed value
 *
 * @param mixed $value   value to var_dump
 * @param bool  $die whether to exit when done
 *
 * @return void
 */
function croak($value = null, $die = false) {
    $debug = debug_backtrace();
    echo '<div class="G__croak">'
        .'<pre class="G__croak_info"><b>'.__METHOD__.'()</b> called'
        .(isset($debug[1])
            ? ' in <b>'.(isset($debug[1]['class'])
                ? $debug[1]['class'].$debug[1]['type']
                : ''
            ).$debug[1]['function'].'()</b>'
            : '')
        .' at <b>'.$debug[0]['file'].':'.$debug[0]['line'].'</b></pre>'
        .'<hr><pre class="G__croak_value">';
    // @codingStandardsIgnoreStart
    var_dump($value);
    // @codingStandardsIgnoreEnd
    echo '</pre></div>';
    if ($die) {
        exit;
    }
}
