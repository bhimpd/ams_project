<?php
namespace Configg;

class Session
{

  public static function create()
  {
    try {
      session_start();
      return true;
    } catch (\Exception $e) {
      return false;
    }

  }
  public static function update($key, $value)
  {
    try {
      $_SESSION[$key] = $value;
      session_write_close();
      return true;

    } catch (\Exception $e) {
      echo $e->getMessage();
      return false;
    }

  }

  public static function destroy()
  {
    try {
      setcookie(session_name(), "", time() - 60, "/");
      session_destroy();
      return true;
    } catch (\Exception $e) {
      echo $e->getMessage();
      return false;
    }

  }

  public static function get(?string $value)
  {
    try {
      return isset($_SESSION[$value]) ? $_SESSION[$value] : $_SESSION;
    } catch (\Exception $e) {
      echo $e->getMessage();
      return false;
    }


  }

}

