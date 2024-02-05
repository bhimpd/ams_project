<?php

header("Access-Control-Allow-Origin: *");
// Allow specific HTTP methods
header("Access-Control-Allow-Methods: POST, GET,PUT,DELETE, PATCH , OPTIONS");

// Allow specific headers
header("Access-Control-Allow-Headers: *");

// Set the age to 1 day to improve speed/caching.
// header('Access-Control-Max-Age: 86400');
// Allow credentials (if needed)
header("Access-Control-Allow-Credentials: true");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit();
}