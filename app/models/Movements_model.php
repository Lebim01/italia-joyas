<?php
 if (!defined('BASEPATH')) {
     exit('No direct script access allowed');
 }

class Movements_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('products_model');
    }

    public function updateMovement($product_id, $ref_id, $code, $description){
      if($code && $type && $product_id){
        $this->db->where('product_id', $product_id);
        $this->db->where('ref_id', $ref_id);
        $this->db->where('code', $code);
        $this->db->update('products_movements', ['description' => $description]);
      }
    }

    public function returnSale($product_id, $sale_id){
      if($sale_id && $product_id){
        $this->db->where('product_id', $product_id);
        $this->db->where('ref_id', $sale_id);
        $this->db->where('code', 'sale');
        $this->db->update('products_movements', [
          'description' => '', 
          'code' => 'sale-return', 
          'description' => 'Producto devuelto'
        ]);
      }
    }

    public function returnBuySupplier($product_id, $purchase_id){
      if($purchase_id && $product_id){
        $this->db->where('product_id', $product_id);
        $this->db->where('ref_id', $purchase_id);
        $this->db->where('code', 'buy-supplier');
        $this->db->update('products_movements', [
          'description' => '', 
          'code' => 'buy-supplier-return', 
          'description' => 'Producto devuelto al proveedor'
        ]);
      }
    }

    public function removeMovement($product_id, $ref_id, $code = ''){
      if($code && $type && $product_id){
        $this->db->where('product_id', $product_id);
        $this->db->where('ref_id', $ref_id);
        $this->db->where('code', $code);
        $this->db->delete('products_movements');
      }
    }

    public function addMovement($store_id, $product_id, $quantity, $code, $ref_id, $description, $created_by){
      if($this->products_model->exists($product_id)){
        $this->db->insert('products_movements', [
          'store_id' => $store_id,
          'product_id' => $product_id,
          'quantity' => $quantity,
          'code' => $code,
          'description' => $description,
          'created_by' => $created_by,
          'ref_id' => $ref_id
        ]);
      }
    }
}
