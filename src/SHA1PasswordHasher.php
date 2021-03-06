<?php
/**
 * SHA1PasswordHasher - Simple SHA1 based Password Hashing plugin
 * File : /src/SHA1PasswordHasher.php
 *
 * PHP version 7.0
 *
 * @package  Stationer\Graphite
 * @author   LoneFry <dev@lonefry.com>
 * @license  MIT https://github.com/stationer/Graphite/blob/master/LICENSE
 * @link     https://github.com/stationer/Graphite
 */

namespace Stationer\Graphite;

/**
 * SHA1PasswordHasher class - Simple SHA1 based Password Hashing plugin
 *
 * @package  Stationer\Graphite
 * @author   LoneFry <dev@lonefry.com>
 * @license  MIT https://github.com/stationer/Graphite/blob/master/LICENSE
 * @link     https://github.com/stationer/Graphite
 * @see      /src/PasswordHasher.php
 */
class SHA1PasswordHasher implements IPasswordHasher {
    /**
     * Private constructor to prevent instantiation
     */
    private function __construct() {
    }

    /**
     * Create a hashword using sha1()
     *
     * @param string $password Password to hash
     *
     * @return string hashed Password for storage
     */
    public static function hash_password($password) {
        return sha1($password);
    }

    /**
     * Test a password against a recalled SHA1
     *
     * @param string $password Password to test
     * @param string $hash     SHA1 from database
     *
     * @return bool True if password passes, false if not
     */
    public static function test_password($password, $hash) {
        return sha1($password) == $hash;
    }

    /**
     * Test a hash is SHA1
     *
     * @param string $hash SHA1 from database
     *
     * @return bool True if argument passes as SHA1, false if not
     */
    public static function is_hash($hash) {
        return preg_match('/[0-9a-f]{40}/i', $hash);
    }
}
