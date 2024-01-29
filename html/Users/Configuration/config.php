<?php

require_once __DIR__ . "/../vendor/autoload.php";
foreach (glob(__DIR__ . '/../Routes/*.php') as $file) {

  require_once $file;
}
foreach (glob(__DIR__ . '/../Routes/*/*.php') as $file) {

  require_once $file;
}
foreach (glob(__DIR__ . '/../Routes/Category/*.php') as $file) {

  require_once $file;
}
foreach (glob(__DIR__ . '/../Routes/Location/*.php') as $file) {

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
foreach (glob(__DIR__ . '/../RequestHandlers/*.php') as $file) {
  require_once $file;
}
foreach (glob(__DIR__ . '/../Validate/*.php') as $file) {
  require_once $file;

}

