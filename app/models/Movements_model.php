<?php
 if (!defined('BASEPATH')) {
     exit('No direct script access allowed');
 }

class Movements_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function addMovement($store_id, $product_id, $quantity, $code, $description, $created_by){
      $this->db->insert('products_movements', [
        'store_id' => $store_id,
        'product_id' => $product_id,
        'quantity' => $quantity,
        'code' => $code,
        'description' => $description,
        'created_by' => $created_by
      ]);
    }
}
