<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}


class Pos_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('movements_model');
    }

    public function addSale($data, $items, $payment = [], $did = null)
    {
        if(!$data['customer_id']) unset($data['customer_id']);
        if(!$data['customer_name']) $data['customer_name'] = 'MOSTRADOR';

        // boolean flag
        $discountInvetory = in_array($data['transaction_type'], ['liquidate', 'credit']);
        // add items apart
        $isApart = in_array($data['transaction_type'], ['apart']);
        $paid = 0;
        if (!empty($payment)) {
            foreach ($payment as $item) {
                $paid=$paid + floatval($item["amount"]);
            }
           
        }
        $data["paid"] = $paid;
        //exit;
        if ($this->db->insert('sales', $data)) {
            $sale_id = $this->db->insert_id();
            foreach ($items as $item) {
                $item['sale_id'] = $sale_id;
                if ($this->db->insert('sale_items', $item)) {
                    if ($item['product_id'] > 0 && $product = $this->site->getProductByID($item['product_id'])) {
                        if ($product->type == 'standard') {
                            $dataUpdate = [];
                            if($discountInvetory){
                                $dataUpdate = ['quantity' => ($product->quantity - $item['quantity'])];
                            }
                            if($isApart){
                                $dataUpdate = ['apart' => ($product->apart + $item['quantity'])];
                            }
                            $this->movements_model->addMovement(
                                $data['store_id'],
                                $product->id,
                                $item['quantity'],
                                $isApart ? 'apart' : 'sale',
                                $sale_id,
                                $isApart ? 'Producto apartado' : 'Producto vendido',
                                $data['created_by']
                            );
                            $this->db->update('product_store_qty', $dataUpdate, ['product_id' => $product->id, 'store_id' => $data['store_id']]);
                        } elseif ($product->type == 'combo') {
                            $combo_items = $this->getComboItemsByPID($product->id);
                            foreach ($combo_items as $combo_item) {
                                $cpr = $this->site->getProductByID($combo_item->id);
                                if ($cpr->type == 'standard') {
                                    $qty = $combo_item->qty * $item['quantity'];
                                    $this->movements_model->addMovement(
                                        $data['store_id'],
                                        $cpr->id,
                                        $item['quantity'],
                                        $isApart ? 'apart' : 'sale',
                                        $sale_id,
                                        $isApart ? 'Producto apartado' : 'Producto vendido',
                                        $data['created_by']
                                    );
                                    $this->db->update('product_store_qty', ['quantity' => ($cpr->quantity - $qty)], ['product_id' => $cpr->id, 'store_id' => $data['store_id']]);
                                }
                            }
                        }
                    }
                }
            }

            if ($did) {
                $this->db->delete('suspended_sales', ['id' => $did]);
                $this->db->delete('suspended_items', ['suspend_id' => $did]);
            }
            $msg = [];
            
            if (!empty($payment)) {
                foreach ($payment as $item) {
                    if($item["amount"] != ""){
                        unset($item['cc_cvv2']);
                        $item['sale_id'] = $sale_id;
                        $this->db->insert('payments', $item);
                    }
                }
            }
            return ['sale_id' => $sale_id, 'message' => $msg];
        }
        return false;
    }

    public function closeRegister($rid, $user_id, $data)
    {
        if (!$rid) {
            $rid = $this->session->userdata('register_id');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        if ($data['transfer_opened_bills'] == -1) {
            $this->db->delete('suspended_sales', ['created_by' => $user_id]);
        } elseif ($data['transfer_opened_bills'] != 0) {
            $this->db->update('suspended_sales', ['created_by' => $data['transfer_opened_bills']], ['created_by' => $user_id]);
        }
        if ($this->db->update('registers', $data, ['id' => $rid, 'user_id' => $user_id])) {
            return true;
        }
        return false;
    }

    public function fetch_products($category_id, $limit, $start, $in_stock = false)
    {
        $this->db->limit($limit, $start);
        if ($category_id) {
            $this->db->where('category_id', $category_id);
        }
        if ($in_stock) {
            $this->db->join('inventory', 'inventory.product_id = products.id AND inventory.available > 0');
        }
        $this->db->order_by('products.name', 'asc');
        $query = $this->db->get('products');

        return $query->result();
    }

    public function getAllSaleItems($sale_id)
    {
        $j = "(SELECT id, code, name, tax_method from {$this->db->dbprefix('products')}) P";
        $this->db
            ->select("
                sale_items.*,
                (CASE WHEN {$this->db->dbprefix('sale_items')}.product_code IS NULL THEN {$this->db->dbprefix('products')}.code ELSE {$this->db->dbprefix('sale_items')}.product_code END) as product_code,
                (CASE WHEN {$this->db->dbprefix('sale_items')}.product_name IS NULL THEN {$this->db->dbprefix('products')}.name ELSE {$this->db->dbprefix('sale_items')}.product_name END) as product_name,
                {$this->db->dbprefix('products')}.tax_method as tax_method"
                , false
            )
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

    public function getGiftCardByNO($no)
    {
        $q = $this->db->get_where('gift_cards', ['card_no' => $no], 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getOpenRegisters()
    {
        $this->db->select('date, user_id, cash_in_hand, CONCAT(' . $this->db->dbprefix('users') . ".first_name, ' ', " . $this->db->dbprefix('users') . ".last_name, ' - ', " . $this->db->dbprefix('users') . '.email) as user', false)
            ->join('users', 'users.id=pos_register.user_id', 'left');
        $q = $this->db->get_where('registers', ['status' => 'open']);
        if ($q->num_rows() > 0) {
            foreach ($q->result() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getProductByCode($code)
    {
        $jpsq = "( SELECT product_id, quantity, price from {$this->db->dbprefix('product_store_qty')} WHERE store_id = {$this->session->userdata('store_id')} ) AS PSQ";
        $this->db->select("{$this->db->dbprefix('products')}.*, COALESCE(PSQ.quantity, 0) as quantity, COALESCE(PSQ.price, {$this->db->dbprefix('products')}.price) as store_price", false)
            ->join($jpsq, 'PSQ.product_id=products.id', 'left');
        $q = $this->db->get_where('products', ['code' => $code], 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getProductNames($term, $limit = 10, $strict = false)
    {
        $store_id = $this->session->userdata('store_id');
        $this->db->select("{$this->db->dbprefix('products')}.*, COALESCE(psq.quantity, 0) as quantity, COALESCE(psq.price, 0) as store_price")
            ->join("( SELECT * from {$this->db->dbprefix('product_store_qty')} WHERE store_id = {$store_id}) psq", 'products.id=psq.product_id', 'left');
        if ($strict) {
            $this->db->where('code', $term);
        } else {
            if ($this->db->dbdriver == 'sqlite3') {
                $this->db->where("(name LIKE '%{$term}%' OR code LIKE '%{$term}%' OR  (name || ' (' || code || ')') LIKE '%{$term}%')");
            } else {
                $this->db->where("(name LIKE '%{$term}%' OR code LIKE '%{$term}%' OR  concat(name, ' (', code, ')') LIKE '%{$term}%')");
            }
        }
        $this->db->group_by('products.id')->limit($limit);
        $q = $this->db->get('products');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getRegisterCashRefunds($date = null, $user_id = null)
    {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS returned', false)
            ->join('return_sales', 'return_sales.id=payments.return_id', 'left')
            ->where('type', 'returned')->where('payments.date >', $date)->where("{$this->db->dbprefix('payments')}.paid_by", 'cash');
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterCashLiquidate($date = null, $user_id = null)
    {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', false)
            ->join('sales', 'sales.id=payments.sale_id', 'left')
            ->where('payments.date >', $date)
            ->where("{$this->db->dbprefix('payments')}.paid_by", 'cash')
            ->where('sales.transaction_type', 'liquidate');
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterCashSales($date = null, $user_id = null)
    {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', false)
            ->join('sales', 'sales.id=payments.sale_id', 'left')
            ->where('payments.date >', $date)
            ->where("{$this->db->dbprefix('payments')}.paid_by", 'cash');
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterCashAparts($date = null, $user_id = null)
    {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', false)
            ->join('sales', 'sales.id=payments.sale_id', 'left')
            ->where('sales.transaction_type', 'apart')
            ->where('payments.date >', $date)
            ->where('payments.paid_by ', 'cash');
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterCardAparts($date = null, $user_id = null)
    {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', false)
            ->join('sales', 'sales.id=payments.sale_id', 'left')
            ->where('sales.transaction_type', 'apart')
            ->where('payments.date >', $date)
            ->where('payments.paid_by ', 'CC');
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterCashCredits($date = null, $user_id = null)
    {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', false)
            ->join('sales', 'sales.id=payments.sale_id', 'left')
            ->where('sales.transaction_type', 'credit')
            ->where('payments.date >', $date)
            ->where('payments.paid_by !=', 'transfer')
            ->where('payments.is_abono', true)
            ->where('sales.date <', $date);
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterCCSales($date = null, $user_id = null)
    {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as total_cc_slips, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', false)
            ->join('sales', 'sales.id=payments.sale_id', 'left')
            ->where('payments.date >', $date)
            ->where("{$this->db->dbprefix('payments')}.paid_by", 'CC');
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterCardsPayments($date = null, $user_id = null)
    {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        
        $data = $this->db->query("SELECT 
                                    SUM(amount) as paid,
                                    paid_by,
                                    banks 
                                FROM
                                    tec_payments 
                                WHERE paid_by = 'CC' AND tec_payments.date > '".$date."' AND created_by = ".$user_id."
                                GROUP BY banks      
        ")->result();
        return $data;
    }

    public function getRegisterChSales($date = null, $user_id = null)
    {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as total_cheques, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', false)
            ->join('sales', 'sales.id=payments.sale_id', 'left')
            ->where('payments.date >', $date)
            ->group_start()->where("{$this->db->dbprefix('payments')}.paid_by", 'Cheque')->or_where("{$this->db->dbprefix('payments')}.paid_by", 'cheque')->group_end();
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterExpenses($date = null, $user_id = null)
    {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('SUM( COALESCE( amount, 0 ) ) AS total', false)
            ->where('date >', $date);
        $this->db->where('created_by', $user_id);

        $q = $this->db->get('expenses');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterGCSales($date = null, $user_id = null)
    {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as total_cheques, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', false)
            ->join('sales', 'sales.id=payments.sale_id', 'left')
            ->where('payments.date >', $date)->where("{$this->db->dbprefix('payments')}.paid_by", 'gift_card');
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterOtherSales($date = null, $user_id = null)
    {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as total_cheques, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', false)
            ->join('sales', 'sales.id=payments.sale_id', 'left')
            ->where('payments.date >', $date)->where("{$this->db->dbprefix('payments')}.paid_by", 'other');
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterRefunds($date = null, $user_id = null)
    {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS returned', false)
            ->join('return_sales', 'return_sales.id=payments.return_id', 'left')
            ->where('type', 'returned')->where('payments.date >', $date);
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSales($date = null, $user_id = null)
    {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', false)
            ->join('sales', 'sales.id=payments.sale_id', 'left')
            ->where('sales.date >', $date)
            ->where('payments.date >', $date);
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterCreditSales($date = null, $user_id = null){
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', false)
            ->join('sales', 'sales.id=payments.sale_id', 'left')
            ->where('payments.date >', $date)
            ->where('payments.is_abono', false)
            ->where('sales.transaction_type', 'credit');
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterApartsSales($date = null, $user_id = null){
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', false)
            ->join('sales', 'sales.id=payments.sale_id', 'left')
            ->where('payments.date >', $date)
            ->where('sales.transaction_type', 'apart');
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterAparts($user_id = null, $date = null){
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('id, customer_name, date, COALESCE( grand_total, 0 ) AS total, COALESCE( paid, 0 ) AS paid', false)
            ->where('sales.date >', $date)
            ->where('sales.transaction_type', 'apart');
        $this->db->where('created_by', $user_id);

        return $this->db->get('sales')->result();
    }

    public function getRegisterStripeSales($date = null, $user_id = null)
    {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as total_cheques, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', false)
            ->join('sales', 'sales.id=payments.sale_id', 'left')
            ->where('payments.date >', $date)->where("{$this->db->dbprefix('payments')}.paid_by", 'stripe');
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getSaleByID($sale_id)
    {
        $q = $this->db->get_where('sales', ['id' => $sale_id], 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getSuspendedSaleByID($id)
    {
        $q = $this->db->get_where('suspended_sales', ['id' => $id], 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getSuspendedSaleItems($id)
    {
        $q = $this->db->get_where('suspended_items', ['suspend_id' => $id]);
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getSuspendedSales($user_id = null)
    {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->order_by('date', 'desc');
        $q = $this->db->get_where('suspended_sales', ['created_by' => $user_id]);
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getTodayCashRefunds()
    {
        $date = date('Y-m-d 00:00:00');
        $this->db->select('SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS returned', false)
            ->join('return_sales', 'return_sales.id=payments.return_id', 'left')
            ->where('type', 'returned')->where('payments.date >', $date)->where("{$this->db->dbprefix('payments')}.paid_by", 'cash');

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getTodayCashSales()
    {
        $date = date('Y-m-d 00:00:00');
        $this->db->select('SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', false)
            ->join('sales', 'sales.id=payments.sale_id', 'left')
            ->where('payments.date >', $date)->where("{$this->db->dbprefix('payments')}.paid_by", 'cash');

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getTodayCCSales()
    {
        $date = date('Y-m-d 00:00:00');
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as total_cc_slips, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', false)
            ->join('sales', 'sales.id=payments.sale_id', 'left')
            ->where('payments.date >', $date)->where("{$this->db->dbprefix('payments')}.paid_by", 'CC');

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getTodayChSales()
    {
        $date = date('Y-m-d 00:00:00');
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as total_cheques, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', false)
            ->join('sales', 'sales.id=payments.sale_id', 'left')
            ->where('payments.date >', $date)
            ->group_start()->where("{$this->db->dbprefix('payments')}.paid_by", 'Cheque')->or_where("{$this->db->dbprefix('payments')}.paid_by", 'cheque')->group_end();

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getTodayExpenses()
    {
        $date = date('Y-m-d 00:00:00');
        $this->db->select('SUM( COALESCE( amount, 0 ) ) AS total', false)
            ->where('date >', $date);

        $q = $this->db->get('expenses');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getTodayGCSales()
    {
        $date = date('Y-m-d 00:00:00');
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as total_cheques, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', false)
            ->join('sales', 'sales.id=payments.sale_id', 'left')
            ->where('payments.date >', $date)->where("{$this->db->dbprefix('payments')}.paid_by", 'gift_card');

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getTodayOtherSales()
    {
        $date = date('Y-m-d 00:00:00');
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as total_cheques, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', false)
            ->join('sales', 'sales.id=payments.sale_id', 'left')
            ->where('payments.date >', $date)->where("{$this->db->dbprefix('payments')}.paid_by", 'other');

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getTodayRefunds()
    {
        $date = date('Y-m-d 00:00:00');
        $this->db->select('SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS returned', false)
            ->join('return_sales', 'return_sales.id=payments.return_id', 'left')
            ->where('type', 'returned')->where('payments.date >', $date);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getTodaySales()
    {
        $date = date('Y-m-d 00:00:00');
        $this->db->select('SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', false)
            ->join('sales', 'sales.id=payments.sale_id', 'left')
            ->where('payments.date >', $date);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getTodayStripeSales()
    {
        $date = date('Y-m-d 00:00:00');
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as total_cheques, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', false)
            ->join('sales', 'sales.id=payments.sale_id', 'left')
            ->where('payments.date >', $date)->where("{$this->db->dbprefix('payments')}.paid_by", 'stripe');

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function openRegister($data)
    {
        if ($this->db->insert('registers', $data)) {
            return true;
        }
        return false;
    }

    public function products_count($category_id = false, $in_stock = false)
    {
        if ($category_id > 0) {
            $this->db->where('category_id', $category_id);
        }
        if ($in_stock) {
            $this->db->join('inventory', 'inventory.product_id = products.id AND inventory.available > 0');
        }
        return $this->db->count_all_results('products');
    }

    public function registerData($user_id = null)
    {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $q = $this->db->get_where('registers', ['user_id' => $user_id, 'status' => 'open'], 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function stripe($amount = 0, $card_info = [], $desc = '')
    {
        $this->load->model('stripe_payments');
        // $card_info = array( "number" => "4242424242424242", "exp_month" => 1, "exp_year" => 2016, "cvc" => "314" );
        // $amount = $amount ? $amount*100 : 3000;
        $amount = $amount * 100;
        if ($amount && !empty($card_info)) {
            $token_info = $this->stripe_payments->create_card_token($card_info);
            if (!isset($token_info['error'])) {
                $token = $token_info->id;
                $data  = $this->stripe_payments->insert($token, $desc, $amount, $this->Settings->currency_prefix);
                if (!isset($data['error'])) {
                    $result = [
                        'transaction_id' => $data->id,
                        'created_at'            => date('Y-m-d H:i:s', $data->created),
                        'amount'                => ($data->amount / 100),
                        'currency'              => strtoupper($data->currency),
                    ];
                    return $result;
                }
                return $data;
            }
            return $token_info;
        }
        return false;
    }

    public function suspendSale($data, $items, $did = null)
    {
        if ($did) {
            if ($this->db->update('suspended_sales', $data, ['id' => $did]) && $this->db->delete('suspended_items', ['suspend_id' => $did])) {
                foreach ($items as $item) {
                    unset($item['cost']);
                    $item['suspend_id'] = $did;
                    $this->db->insert('suspended_items', $item);
                }
                return true;
            }
        } else {
            if ($this->db->insert('suspended_sales', $data)) {
                $suspend_id = $this->db->insert_id();
                foreach ($items as $item) {
                    unset($item['cost']);
                    $item['suspend_id'] = $suspend_id;
                    $this->db->insert('suspended_items', $item);
                }
                return $suspend_id;
            }
        }
        return false;
    }

    public function updateSale($id, $data, $items, $devolution = false)
    {
        // boolean flag
        $discountInvetory = in_array($data['transaction_type'], ['liquidate', 'credit']);
        // add items apart
        $isApart = in_array($data['transaction_type'], ['apart']);

        $osale  = $this->getSaleByID($id); // OLD SALE
        $oitems = $this->getAllSaleItems($id); // OLD ITEMS

        foreach ($oitems as $oitem) {
            $product = $this->site->getProductByID($oitem->product_id, $osale->store_id);
            if ($product->type == 'standard') {
                $dataUpdate = [];
                if($discountInvetory){
                    $dataUpdate = ['quantity' => ($product->quantity + $oitem->quantity)];
                }
                if($isApart){
                    $dataUpdate = ['apart' => ($product->apart - $oitem->quantity)];
                }
                // Registrar movimiento de producto devuelto
                $this->movements_model->returnSale($product->id, $id);
                $this->db->update('product_store_qty', $dataUpdate, ['product_id' => $product->id, 'store_id' => $data['store_id']]);
            } elseif ($product->type == 'combo') {
                $combo_items = $this->getComboItemsByPID($product->id);
                foreach ($combo_items as $combo_item) {
                    $cpr = $this->site->getProductByID($combo_item->id, $osale->store_id);
                    if ($cpr->type == 'standard') {
                        $qty = $combo_item->qty * $oitem->quantity;
                        // Registrar movimiento de producto devuelto
                        $this->movements_model->returnSale($cpr->id, $id);
                        $this->db->update('product_store_qty', ['quantity' => ($cpr->quantity + $qty)], ['product_id' => $cpr->id, 'store_id' => $osale->store_id]);
                    }
                }
            }
        }

        $data['status'] = $osale->paid > 0 ? 'partial' : ($data['grand_total'] <= $osale->paid ? 'paid' : 'due');

        if ($this->db->update('sales', $data, ['id' => $id]) && $this->db->delete('sale_items', ['sale_id' => $id])) {
            foreach ($items as $item) {
                $item['sale_id'] = $id;
                if ($this->db->insert('sale_items', $item)) {
                    $product = $this->site->getProductByID($item['product_id'], $osale->store_id);
                    if ($product->type == 'standard') {
                        $dataUpdate = [];
                        if($discountInvetory){
                            $dataUpdate = ['quantity' => ($product->quantity - $item['quantity'])];
                        }
                        if($isApart){
                            $dataUpdate = ['apart' => ($product->apart + $item['quantity'])];
                        }
                        $this->movements_model->addMovement(
                            $osale->store_id,
                            $product->id,
                            $item['quantity'],
                            $isApart ? 'apart' : 'sale',
                            $id,
                            $isApart ? 'Producto apartado' : 'Producto vendido',
                            $data['created_by']
                        );
                        $this->db->update('product_store_qty', $dataUpdate, ['product_id' => $product->id, 'store_id' => $data['store_id']]);
                    } elseif ($product->type == 'combo') {
                        $combo_items = $this->getComboItemsByPID($product->id);
                        foreach ($combo_items as $combo_item) {
                            $cpr = $this->site->getProductByID($combo_item->id, $osale->store_id);
                            if ($cpr->type == 'standard') {
                                $qty = $combo_item->qty * $item['quantity'];
                                $this->movements_model->addMovement(
                                    $osale->store_id,
                                    $cpr->id,
                                    $item['quantity'],
                                    $isApart ? 'apart' : 'sale',
                                    $id,
                                    $isApart ? 'Producto apartado' : 'Producto vendido',
                                    $data['created_by']
                                );
                                $this->db->update('product_store_qty', ['quantity' => ($cpr->quantity - $qty)], ['product_id' => $cpr->id, 'store_id' => $osale->store_id]);
                            }
                        }
                    }
                }
            }

            return true;
        }

        return false;
    }

    public function getAllCashiers(){
        $this->db->where('active', 1);
        return $this->db->get('users')->result();
    }

    public function getRegisterOpen(){
        $sql = "SELECT 
                    reg.*, 
                    CONCAT(users.first_name, ' ', users.last_name) as user, 
                    stores.name as store
                FROM tec_registers reg
                INNER JOIN tec_users users ON users.id = reg.user_id
                INNER JOIN tec_stores stores ON stores.id = reg.store_id
                WHERE status = 'open'";
        return $this->db->query($sql)->result();
    }

    public function getApartsOpened(){
        $this->db->where('status', 'partial');
        $this->db->where('transaction_type', 'apart');
        return $this->db->get('sales')->result();
    }

    public function getLastPaymentSale($sale_id){
        $this->db->where('sale_id', $sale_id);
        $this->db->order_by('date', 'DESC');
        $this->db->limit(1);
        return $this->db->get('payments')->row();
    }

    public function closeSale($sale_id){
        $this->load->model('sales_model');
        $sale = $this->sales_model->getSaleByID($sale_id);
        $items = $this->getAllSaleItems($sale_id);

        foreach($items as $item){
            $this->db->where('product_id', $item->id);
            $this->db->where('store_id', $sale->store_id);

            $this->db->set('apart', 'apart-'.$item->quantity, FALSE);
            $this->db->update('product_store_qty');
        }

        $this->db->where('id', $sale_id);
        $this->db->update('sales', ['status' => 'closed']);
    }

    public function getSaleFromPayment($payment_id){
        $this->db->select('sales.*')
            ->join('sales', 'sales.id=payments.sale_id', 'left')
            ->where('payments.id', $payment_id);
        return $this->db->get('payments')->row();
    }

    public function getLatePayments($phone)
    {
        $data = $this->db->query("SELECT 
                                    tec_customers.name AS customer_name,
                                    tec_payments.date AS last_payment,
                                    tec_sales.transaction_type,
                                    tec_sales.status,
                                    tec_payments.amount 
                                FROM
                                    tec_customers 
                                    INNER JOIN tec_sales 
                                    ON tec_sales.customer_id = tec_customers.id 
                                    AND tec_sales.transaction_type = 'credit' 
                                    AND tec_sales.status = 'partial' 
                                    INNER JOIN tec_payments 
                                    ON tec_payments.sale_id = tec_sales.id 
                                WHERE tec_payments.date < CURRENT_DATE
                                    AND tec_customers.phone = ".$phone." 
                                    AND tec_payments.amount > 0 
                                ORDER BY tec_payments.date DESC 
                                LIMIT 1      
        ")->result();
        return $data;
    }

    public function getProductSale($sale_id, $product_id){
        $sql = "SELECT si.* FROM tec_sales INNER JOIN tec_sale_items si ON si.sale_id = tec_sales.id WHERE tec_sales.id = {$sale_id} AND si.product_id = {$product_id}";
        return $this->db->query($sql)->row();
    }
}
