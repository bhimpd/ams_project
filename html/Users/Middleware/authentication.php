<?php
namespace Middleware;

use Model\User;
use Configg\Session;
use \Firebase\JWT\JWT;
use \Firebase\JWT\key;
use Config\Token;

/** 
 * checks if the user is present in the database
 * developer can add multiple tokenization /verification
 *  techniques
 */
abstract class Authentication
{
  private $userModel;

  public function __construct(User $userModel)
  {
    $this->userModel = $userModel;

  }
  /**
   * @return_ true on verified and array on 
   * exception
   */
  public function authenticate($username, $password): bool
  {
    try {
      $result = $this->userModel->get(null, $username);
      if (!$result) {
        throw new \Exception("Unable to get from database on given username!!");
      }
      if (!password_verify($password, $result["password"])) {
        throw new \Exception("Unable to verify for given password provided!!");
      }
      return true;

    } catch (\Exception $e) {
      error_log($e->getMessage());
      return false;
    }
  }

  abstract public static function createToken(array $payload, int $exp);
  // abstract public static function verifyToken(string $token);


}

class JWTTokenHandlerAndAuthentication extends Authentication
{

  // static $token = [];
  static $tokenBlackList = [];
  static $secret = "intuji_sanchay";
  // static $secretForNormalUser = "PINKUJI_SECRET KEY";
  static $alg = 'HS256';

  /**
   * can create token with static call
   * @param array payload  || int exp
   * @ return token||bool
   */
  public static function createToken(array $payload, int $exp = 3600)
  {
    try {
      // self::$token = [
      //   "iat" => time(),
      //   "exp" => time() + $exp,
      //   "data" => $payload
      // ];
      $token = Token::encode($payload, self::$secret, time() + $exp );

      return $token;

    } catch (\Exception $e) {
      error_log($e->getMessage());
      return false;
    }


  }
  /**
   * gets user_type from token provided
   * @param string||array
   * @return array
   * 
   */
  public static function getSpecificValueFromToken($authToken, $key): array
  {
    try {

      $payload = JWT::decode($authToken, new key(self::$secret, self::$alg));

      $user_type = $payload->data->user_type;

      return [
        "status" => true,
        "user_type" => $user_type,
        "message" => "Users user_type has been found!!"
      ];

    } catch (\Firebase\JWT\ExpiredException $e) {
      // echo "Token Expired";
      return [
        "status" => false,
        "user_type" => "",
        "message" => "Token Expired"
      ];

    } catch (\Firebase\JWT\SignatureInvalidException $e) {
      // echo "Invalid token provided";
      return [
        "status" => false,
        "user_type" => "",
        "message" => "Invalid token provided"
      ];

    } catch (\Exception $e) {
      // echo '' . $e->getMessage();
      return [
        "status" => false,
        "user_type" => "",
        "message" => $e->getMessage()
      ];
    }
  }

  /**
   * verifies token and destroys the session assisting in logout
   * @return array
   */
  public static function expireToken(): array
  {
    try {
      $response = Authorization::verifyToken();
      if (!$response["status"]) {
        throw new \Exception($response["message"]);
      }
      Session::destroy();
      return [
        "status" => true,
        "message" => "Logged out by token expiraiton."
      ];
    } catch (\Exception $e) {
      return [
        "status" => false,
        "message" => $e->getMessage()
      ];
    }
  }
}
