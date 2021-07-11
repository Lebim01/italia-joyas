<?php defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('D')) {
  function D($data)
  {
    echo "<pre>";
    print_r($data);
    echo "</pre>";
  }
}
