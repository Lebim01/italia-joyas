<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Sales_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('movements_model');
    }

    public function addPayment($data = [])
    {
        if ($this->db->insert('payments', $data)) {
            if ($data['paid_by'] == 'gift_card') {
                $gc = $this->site->getGiftCard($data['gc_no']);
                $this->db->update('gift_cards', ['balance' => ($gc->balance - $data['amount'])], ['card_no' => $data['gc_no']]);
            }
            $this->syncSalePayments($data['sale_id']);
            return true;
        }
        return false;
    }

    public function deleteInvoice($id)
    {
        $osale  = $this->getSaleByID($id);
        $oitems = $this->getAllSaleItems($id);
        foreach ($oitems as $oitem) {
            $product = $this->site->getProductByID($oitem->product_id, $osale->store_id);
            if ($product->type == 'standard') {
                $this->movements_model->removeMovement($oitem->product_id, $id, 'sale');
                $this->db->update('product_store_qty', ['quantity' => ($product->quantity + $oitem->quantity)], ['product_id' => $product->id, 'store_id' => $osale->store_id]);
            } elseif ($product->type == 'combo') {
                $combo_items = $this->getComboItemsByPID($product->id);
                foreach ($combo_items as $combo_item) {
                    $cpr = $this->site->getProductByID($combo_item->id, $osale->store_id);
                    if ($cpr->type == 'standard') {
                        $qty = $combo_item->qty * $oitem->quantity;
                        $this->db->update('product_store_qty', ['quantity' => ($cpr->quantity + $qty)], ['product_id' => $cpr->id, 'store_id' => $osale->store_id]);
                    }
                }
            }
        }
        if ($this->db->delete('sale_items', ['sale_id' => $id]) && $this->db->delete('sales', ['id' => $id]) && $this->db->delete('payments', ['sale_id' => $id])) {
            return true;
        }
        return false;
    }

    public function deleteOpenedSale($id)
    {
        if ($this->db->delete('suspended_items', ['suspend_id' => $id]) && $this->db->delete('suspended_sales', ['id' => $id])) {
            return true;
        }
        return false;
    }

    public function deletePayment($id)
    {
        $payment = $this->getPaymentByID($id);
        if ($payment->paid_by == 'gift_card') {
            $gc = $this->site->getGiftCard($payment->gc_no);
            $this->db->update('gift_cards', ['balance' => ($gc->balance + $payment->amount)], ['card_no' => $payment->gc_no]);
        }
        if ($this->db->delete('payments', ['id' => $id])) {
            $this->syncSalePayments($payment->sale_id);
            return true;
        }
        return false;
    }

    public function getAllSaleItems($sale_id)
    {
        $j = "(SELECT id, code, name, tax_method from {$this->db->dbprefix('products')}) P";
        $this->db->select("sale_items.*,
            (CASE WHEN {$this->db->dbprefix('sale_items')}.product_code IS NULL THEN {$this->db->dbprefix('products')}.code ELSE {$this->db->dbprefix('sale_items')}.product_code END) as product_code,
            (CASE WHEN {$this->db->dbprefix('sale_items')}.product_name IS NULL THEN {$this->db->dbprefix('products')}.name ELSE {$this->db->dbprefix('sale_items')}.product_name END) as product_name,
            {$this->db->dbprefix('products')}.tax_method as tax_method", false)
            ->join('products', 'products.id=sale_items.product_id', 'left outer')
            ->order_by('sale_items.id');
        $q = $this->db->get_where('sale_items', ['sale_id' => $sale_id]);
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getAllSalePayments($sale_id)
    {
        $q = $this->db->get_where('payments', ['sale_id' => $sale_id]);
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getComboItemsByPID($product_id)
    {
        $this->db->select($this->db->dbprefix('products') . '.id as id, ' . $this->db->dbprefix('products') . '.code as code, ' . $this->db->dbprefix('combo_items') . '.quantity as qty, ' . $this->db->dbprefix('products') . '.name as name, ' . $this->db->dbprefix('products') . '.quantity as quantity')
            ->join('products', 'products.code=combo_items.item_code', 'left')
            ->group_by('combo_items.id');
        $q = $this->db->get_where('combo_items', ['product_id' => $product_id]);
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getCustomerByID($id)
    {
        $q = $this->db->get_where('customers', ['id' => $id], 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getPaymentByID($id)
    {
        $q = $this->db->get_where('payments', ['id' => $id], 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getSaleByID($id)
    {
        $q = $this->db->get_where('sales', ['id' => $id], 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getSalePayments($sale_id)
    {
        $this->db->order_by('id', 'asc');
        $q = $this->db->get_where('payments', ['sale_id' => $sale_id]);
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
    }

    public function syncSalePayments($id)
    {
        $sale     = $this->getSaleByID($id);
        $payments = $this->getSalePayments($id);
        $paid     = 0;
        if ($payments) {
            foreach ($payments as $payment) {
                $paid += $payment->amount;
            }
        }
        $status = $paid <= 0 ? 'due' : ($sale->grand_total <= $paid ? 'paid' : 'partial');
        if ($this->db->update('sales', ['paid' => $paid, 'status' => $status], ['id' => $id])) {
            return true;
        }

        return false;
    }

    public function updatePayment($id, $data = [])
    {
        $payment = $this->getPaymentByID($id);
        if ($payment->paid_by == 'gift_card') {
            $gc = $this->site->getGiftCard($payment->gc_no);
            $this->db->update('gift_cards', ['balance' => ($gc->balance + $payment->amount)], ['card_no' => $payment->gc_no]);
        }
        if ($this->db->update('payments', $data, ['id' => $id])) {
            if ($data['paid_by'] == 'gift_card') {
                $gc = $this->site->getGiftCard($data['gc_no']);
                $this->db->update('gift_cards', ['balance' => ($gc->balance - $data['amount'])], ['card_no' => $data['gc_no']]);
            }
            $this->syncSalePayments($data['sale_id']);
            return true;
        }
        return false;
    }

    public function updateStatus($id, $status)
    {
        if ($this->db->update('sales', ['status' => $status], ['id' => $id])) {
            return true;
        }
        return false;
    }

    public function getallSales($fechas)
    {
        $data = $this->db->query("SELECT 
                                    tec_sales.id,
                                    tec_sales.date,
                                    tec_sales.grand_total,
                                    SUM(tec_sale_items.discount) AS discount 
                                    FROM
                                    tec_sales 
                                    LEFT JOIN tec_sale_items 
                                        ON tec_sales.id = tec_sale_items.sale_id 
                                        WHERE tec_sales.date >= '" . $fechas[1] . " 00:00:00' AND tec_sales.date <= '" . $fechas[2] . " 23:59:59'
                                    GROUP BY tec_sales.id 
                                    ORDER BY tec_sales.date ASC 
                                    ")->result();
        return $data;
    }

    public function getallItemSales($fechas)
    {
        $data = $this->db->query("SELECT 
                                    product_code,
                                    product_name,
                                    unit_price,
                                    discount,
                                    SUM(quantity) AS quantity,
                                    SUM(subtotal) AS subtotal 
                                FROM
                                    tec_sale_items 
                                    LEFT JOIN tec_sales 
                                    ON tec_sale_items.sale_id = tec_sales.id
                                    WHERE tec_sales.date >= '" . $fechas[1] . " 00:00:00' AND tec_sales.date <= '" . $fechas[2] . " 23:59:59'
                                    GROUP BY tec_sale_items.product_id
                                    ORDER BY tec_sales.date ASC
                                    ")->result();
        return $data;
    }

    public function getAllPartialSales(){
        $sql = "SELECT *
                FROM tec_sales
                WHERE status = 'partial'";
        $data = $this->db->query($sql)->result();

        foreach($data as $row){
            $sql_customer = "SELECT * FROM tec_customers WHERE id = {$row->customer_id}";
            $row->customer = $this->db->query($sql_customer)->row();
        }

        return $data;
    }
}
