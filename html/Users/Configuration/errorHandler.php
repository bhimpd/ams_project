<?php

function customErrorHandler($errno ,$errstr, $errfile, $errline){
  $logMessage = "[" . date("Y-m-d H:i:s") . "] Error: [$errno] $errstr in $errfile on line $errline\n";

  $logPath = "/html/User/error.log";

  // file_put_contents($logPath ,$logMessage , FILE_APPEND);

  return true;
}
// set_error_handler("customErrorHandler");