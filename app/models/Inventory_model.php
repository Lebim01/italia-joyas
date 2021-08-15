<?php
 if (!defined('BASEPATH')) {
     exit('No direct script access allowed');
 }

class Inventory_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('movements_model');
    }


    public function adjustProduct($store_id, $product_code, $newQty, $created_by){
      if($product_code){
        $product = $this->products_model->getProductByCode($product_code);
        $stock = $this->products_model->getStoreQuantity($product->id, $store_id);

        if((float) $stock->quantity != $newQty){
          $this->movements_model->addMovement(
            $store_id,
            $product->id,
            $newQty,
            'inventory-adjust',
            null,
            'Producto inventario ajustado',
            $created_by
          );

          $dataUpdate = [
            'quantity' => $newQty
          ];

          /**
           * Significa que hay un apartado que ya no esta? lo prevenimos
           */
          if($newQty < (float) $stock->apart){
            $dataUpdate['apart'] = $newQty;
          }

          $this->db->where('store_id', $store_id);
          $this->db->where('product_id', $product->id);
          $this->db->update('product_store_qty', $dataUpdate);
        }
      }
    }
}
