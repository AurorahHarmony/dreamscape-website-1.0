<?php

require_once(__DIR__ . '/Database.php');
require_once(__DIR__ . '/../classes/FormHandler.php');

class UserService
{

  /**
   * @param string $username The user's username
   * @param string $email The user's email
   * @param string $password blep
   * @param string $password_confirm
   * @return bool True if the user creation was successful
   */
  static function create(Form $form)
  {
    //$username = null, $email = null, $dob = null, $password = null, $password_confirm = null, $privacy_policy = false, $eula = false
    $username = trim($form->get_value('username'));
    $email = trim($form->get_value('email'));
    $dob = trim($form->get_value('dob'));

    $password = $form->get_value('password');
    $confirm_password = $form->get_value('confirm_password');

    $privacy_policy = (bool) $form->get_value('privacy_policy');
    $eula = (bool) $form->get_value('eula');

    //Username Validation
    if (empty($username) || strlen($username) < 4) { //Username must be longer than 4 characters
      $form->add_error('username', 'Username must be atleast 4 characters long.');
    }

    if (preg_match("/[^a-zA-Z0-9._-]/", $username)) { //Restrict usernames to characters, numbers, and some symbols
      $form->add_error('username', 'Usernames can only contain the characters letters, numbers, hyphens, underscores and periods.');
    }

    $db = new Database;
    $users = $db->select('SELECT * FROM users WHERE username = :username', ['username' => $username]);
    if (!empty($users)) { //Check if a user with this username already exists
      $form->add_error('username', 'A User with this Username already exists.');
    }

    //Email Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $form->add_error('email', 'Email Address is invalid.');
    }

    /**
     * 
     * CHECK IF THE EMAIL ALREADY EXISTS
     * 
     */

    //Date of Birth Validation
    if (empty($dob)) {
      $form->add_error('dob', 'Date of birth is required.');
    } else {

      $today = new DateTime();
      $dob_obj = DateTime::createFromFormat('Y-m-d', $dob);
      $age = $today->diff($dob_obj);

      if ($age->y < 13) { //Users must be older than 13 to register
        $form->general_error = 'You are not elegible for this service.';
        $form->add_error('dob', '');
      }
    }


    //Password Validation
    if (strlen($password) < 6) { //Password must be longer than 6 chars
      $form->add_error('password', 'Password must be at least 6 Characters long.');
    }

    $lowercase = preg_match("/[a-z]/", $password);
    $other_text = preg_match("/[^a-z]/", $password);
    if (!$lowercase || !$other_text) { //Password must have one number
      $form->add_error('password', 'Password must contain one lower case and an uppercase, number or symbol character.');
    }

    if (preg_match("/(.)\\1{2}/", $password)) { //Dissallow repeating characters
      $form->add_error('password', 'You cannot repeat the same character more than twice.');
    }

    if ($password != $confirm_password) { //Passwords must match
      $form->add_error('password', 'Passwords do not match.');
      $form->add_error('confirm_password', 'Passwords do not match.');
    }

    //Privacy Policy Validation
    if (!$privacy_policy) {
      $form->add_error('privacy_policy', 'Please read and agree to the Privacy Policy.');
    }
    //Eula Validation
    if (!$eula) {
      $form->add_error('eula', 'Please read and agree to the End User Licensing Agreement.');
    }

    if ($form->has_errors()) {
      return false;
    }

    //Create User
    /**
     * STUFF
     */
  }

  /**
   * @param Form $form A form class with a username and password value
   * @return boolean Returns true if validated
   */
  static function validate(Form $form)
  {
    $username = $form->get_value('username');
    $password = $form->get_value('password');

    $error_text = 'Invalid Credentials';

    //If a field is empty, return an error
    if (empty($username) || empty($password)) {
      $form->general_error = $error_text;
      return false;
    }

    $db = new Database;
    $users = $db->select('SELECT * FROM users WHERE username = :username', ['username' => $username]);

    //If no username match was found, return an error
    if (empty($users)) {
      $form->general_error = $error_text;
      return false;
    }

    //If the found user does not have a matching password, return an error
    $the_user = $users[0];
    if ($the_user['password'] != $password) {
      $form->general_error = $error_text;
      return false;
    }

    try {
      require_once(__DIR__ . '/../handlers/SessionHandler.php');
      $session = new Session;
      $session->login((int) $the_user['id'], $the_user['username']);
      return true;
    } catch (\Throwable $th) {
      $form->general_error = 'Internal Server Error ';
      return false;
    }
  }
}
