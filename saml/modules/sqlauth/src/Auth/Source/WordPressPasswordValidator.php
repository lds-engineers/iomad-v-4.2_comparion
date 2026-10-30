<?php


namespace SimpleSAML\Module\sqlauth\Auth\Source;

class WordPressPasswordValidator
{
    /**
     * Validates the user's password against the stored hash.
     *
     * @param string $inputPassword The password input from the user.
     * @param string $storedHash The stored password hash from the database.
     * @return bool True if the password matches the stored hash, false otherwise.
     * @throws \Exception If the WordPress password hashing class cannot be included.
     */
    public static function validatePassword($inputPassword, $storedHash)
    {
        print_r(array($password, $user_pass));
        die;
        // Path to WordPress's password hashing class
        // Update this path according to your WordPress installation
        $wpPath = '/home/customer/www/staging31.pdhstar.com/public_html/wp-includes/class-phpass.php';

        // Check if the class file exists
        if (!file_exists($wpPath)) {
            throw new \Exception("WordPress password hashing class file not found: {$wpPath}");
        }

        // Include the WordPress password hashing class
        require_once $wpPath;

        // Create a new instance of the PasswordHash class
        // The '8' refers to the number of iterations and 'true' means to use portable hashes
        $wpHasher = new \PasswordHash(8, true);

        // Check if the input password matches the stored hash
        return $wpHasher->CheckPassword($inputPassword, $storedHash);
    }
}
