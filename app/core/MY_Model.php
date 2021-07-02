<?php (defined('BASEPATH')) or exit('No direct script access allowed');

class MY_Model extends CI_Model
{

  function __construct()
  {
    parent::__construct();
  }

  function enum_select($table, $field)
  {
    $query = "SHOW COLUMNS FROM `$table` LIKE '$field'";
    $row = $this->db->query($query)->row()->Type;
    $regex = "/'(.*?)'/";
    preg_match_all($regex, $row, $enum_array);
    $enum_fields = $enum_array[1];
    return ($enum_fields);
  }
}
