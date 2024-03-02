<?php

require_once __DIR__."/../Configuration/cors.php";


foreach (glob(__DIR__ . '/../Routes/*.php') as $file) {

  require_once $file;
}

foreach (glob(__DIR__ . '/../Routes/*/*.php') as $file) {

  require_once $file;
}
foreach (glob(__DIR__ . '/../Helpers/imageHandler.php') as $file) {

  require_once $file;
}

foreach (glob(__DIR__ . '/../AccessControl/*.php') as $file) {
  require_once $file;
}
foreach (glob(__DIR__ . '/../Configuration/*.php') as $file) {
  require_once $file;
}


foreach (glob(__DIR__ . '/../Middleware/*.php') as $file) {
  require_once $file;
}
foreach (glob(__DIR__ . '/../Model/*.php') as $file) {
  require_once $file;
}
require_once __DIR__."/../Helpers/filterandsortparametersCreator.php";
require_once __DIR__.'/../RequestHandlers/interfacesForRequestHandlers.php';
require_once  __DIR__.'/../RequestHandlers/userRequestHandlers.php';
foreach (glob(__DIR__ . '/../RequestHandlers/*.php') as $file) {
  require_once $file;
}
foreach (glob(__DIR__ . '/../Validate/*.php') as $file) {
  require_once $file;

}

