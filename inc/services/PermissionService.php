<?php
require_once(__DIR__ . '/Database.php');
class PermissionService
{

  /**
   * @param int $user_id The ID of the user you want to get permissions of
   * @return array An array of the user's permissions
   */
  public static function get_perms(int $user_id)
  {
    $db = new Database;
    $permissions = $db->select(
      'SELECT permissions.perm_name FROM users 
	      JOIN roles ON roles.role_id = users.role_id
        JOIN roles_permissions ON roles_permissions.role_id = roles.role_id
        JOIN permissions ON permissions.perm_id = roles_permissions.perm_id
        WHERE users.id = :user_id
    ',
      ['user_id' => $user_id]
    );

    $perm_array = [];
    foreach ($permissions as $permission) {
      array_push($perm_array, $permission['perm_name']);
    }
    return $perm_array;
  }

  /**
   * Gets a list of the Permissions a role has
   * @param int $role_id The ID of the role you are looking for
   * @return array An array of the permissions the role has
   */
  public static function get_role_perm_array($role_id)
  {
    $db = new Database;
    $perms = $db->select('SELECT permissions.perm_id FROM roles_permissions JOIN permissions ON roles_permissions.perm_id = permissions.perm_id WHERE role_id = :role_id', ['role_id' => $role_id]);

    $perms_arr = [];

    foreach ($perms as $perm) {
      array_push($perms_arr, $perm['perm_id']);
    }
    return $perms_arr;
  }

  /**
   * @return array Array of all permissions available
   */
  public static function get_all_permissions()
  {
    $db = new Database;
    $all_perms = $db->select('SELECT * FROM permissions');
    return $all_perms;
  }

  /**
   * @param int $user_id The ID of the user you want to check the permission for
   * @param string $perm_name The permission you want to check against
   * @param bool $get_fresh Should permissions be pulled freshly from the database?
   * @return array|bool False if the user does not have the permission. Otherwise, an array of their permissions
   */
  public static function has_perm($perm_name, int $user_id, $get_fresh = false)
  {
    $user_perms = null;
    if ($get_fresh) {
      $user_perms = self::get_perms($user_id);
      $_SESSION['perms'] = $user_perms;
    } else {

      if (!isset($_SESSION)) {
        session_start();
      }

      $user_perms = $_SESSION['perms'];
    }

    if (array_search($perm_name, $user_perms) === false) {
      return false;
    } else {
      return $user_perms;
    }
  }

  /**
   * @param int $user_id The ID of the user you want to get the role name of
   * @return string The user's role name
   */
  public static function get_role(int $user_id)
  {
    $db = new Database;
    $roles = $db->select(
      'SELECT roles.role_name FROM users 
	      JOIN roles ON roles.role_id = users.role_id
        WHERE users.id = :user_id
    ',
      ['user_id' => $user_id]
    );
    return $roles[0]['role_name'];
  }

  public static function get_available_roles()
  {
    $db = new Database;
    $roles = $db->select('SELECT * FROM roles');
    return $roles;
  }

  /**
   * Echos out a formatted error message stating the missing permissions
   */
  public static function echo_insufficient_perm()
  {
    echo '<div class="alert alert-danger text-center" role="alert">
            <h1 class="text-danger">Unable to complete action. Missing permissions.</h1>
            <button class="btn btn-info" onclick="window.history.back()">&lt; Back</button>
          </div>';
  }

  /**
   * @param int $role_id The ID of the role to be updated
   * @param array $new_perm_values an array of the permission name with its new value ('PERM_ID' => true|false) 
   */

  public static function update_perms($role_id, $new_perm_values = [], $callback = null)
  {
    $to_insert = [];
    $to_delete = [];

    foreach ($new_perm_values as $perm_id => $new_value_bool) {
      if ($new_value_bool == true) {
        array_push($to_insert, $perm_id);
      } else {
        array_push($to_delete, $perm_id);
      }
    }

    $db = new Database;

    foreach ($to_delete as $rem_perm_id) {
      $db->delete('roles_permissions', 'role_id = :role_id AND perm_id = :perm_id', ['role_id' => $role_id, 'perm_id' => $rem_perm_id]);
    }
    foreach ($to_insert as $add_perm_id) {
      $db->insert('roles_permissions', ['role_id' => $role_id, 'perm_id' => $add_perm_id], true);
    }
  }
}