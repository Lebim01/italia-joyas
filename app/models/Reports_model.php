<?php
 if (!defined('BASEPATH')) {
     exit('No direct script access allowed');
 }

class Reports_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getAllCustomers()
    {
        $q = $this->db->get('customers');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getAllProducts()
    {
        $q = $this->db->get('products');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getAllStaff()
    {
        $q = $this->db->get('users');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getDailySales($year, $month)
    {
        if ($this->db->dbdriver == 'sqlite3') {
            $this->db->select("strftime('%d', date) AS date, COALESCE(sum(product_tax), 0) as product_tax, COALESCE(sum(order_tax), 0) as order_tax, COALESCE(sum(total), 0) as total, COALESCE(sum(grand_total), 0) as grand_total, COALESCE(sum(total_tax), 0) as total_tax, COALESCE(sum(rounding), 0) as rounding, COALESCE(sum(total_discount), 0) as discount, COALESCE(sum(paid), 0) as paid", false)->group_by("strftime('%d', date)");
        } else {
            $this->db->select("DATE_FORMAT(date,  '%d') AS date, COALESCE(sum(product_tax), 0) as product_tax, COALESCE(sum(order_tax), 0) as order_tax, COALESCE(sum(total), 0) as total, COALESCE(sum(grand_total), 0) as grand_total, COALESCE(sum(total_tax), 0) as total_tax, COALESCE(sum(rounding), 0) as rounding, COALESCE(sum(total_discount), 0) as discount, COALESCE(sum(paid), 0) as paid", false)->group_by("DATE_FORMAT(date, '%d')");
        }
        $this->db->like('date', "{$year}-{$month}", 'after');
        if ($this->session->userdata('store_id')) {
            $this->db->where('store_id', $this->session->userdata('store_id'));
        }
        $q = $this->db->get('sales');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getMonthlySales($year)
    {
        if ($this->db->dbdriver == 'sqlite3') {
            $this->db->select("strftime('%m', date) AS date, COALESCE(sum(product_tax), 0) as product_tax, COALESCE(sum(order_tax), 0) as order_tax, COALESCE(sum(total), 0) as total, COALESCE(sum(grand_total), 0) as grand_total, COALESCE(sum(total_tax), 0) as tax, COALESCE(sum(total_discount), 0) as discount, COALESCE(sum(paid), 0) as paid", false)
            ->group_by("strftime('%m', date)")
            ->order_by("strftime('%m', date) ASC");
        } else {
            $this->db->select("DATE_FORMAT( date,  '%m' ) AS date, COALESCE(sum(product_tax), 0) as product_tax, COALESCE(sum(order_tax), 0) as order_tax, COALESCE(sum(total), 0) as total, COALESCE(sum(grand_total), 0) as grand_total, COALESCE(sum(total_tax), 0) as tax, COALESCE(sum(total_discount), 0) as discount, COALESCE(sum(paid), 0) as paid", false)
            ->group_by("DATE_FORMAT(date, '%m')")
            ->order_by("DATE_FORMAT(date, '%m') ASC");
        }

        $this->db->like('date', "{$year}", 'after');
        if ($this->session->userdata('store_id')) {
            $this->db->where('store_id', $this->session->userdata('store_id'));
        }
        $q = $this->db->get('sales');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getTotalCustomerSales($customer_id, $user = null, $start_date = null, $end_date = null)
    {
        $this->db->select('COUNT(id) as number, sum(grand_total) as amount, sum(paid) as paid');
        if ($start_date && $end_date) {
            $this->db->where('date >=', $start_date);
            $this->db->where('date <=', $end_date);
        }
        if ($user) {
            $this->db->where('created_by', $user);
        }
        if ($this->session->userdata('store_id')) {
            $this->db->where('store_id', $this->session->userdata('store_id'));
        }
        $q = $this->db->get_where('sales', ['customer_id' => $customer_id]);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getTotalExpenses($start, $end)
    {
        $this->db->select('count(id) as total, sum(COALESCE(amount, 0)) as total_amount', false)
            ->where("date >= '{$start}' and date <= '{$end}'", null, false);
        if ($this->session->userdata('store_id')) {
            $this->db->where('store_id', $this->session->userdata('store_id'));
        }
        $q = $this->db->get('expenses');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getTotalPurchases($start, $end)
    {
        $this->db->select('count(id) as total, sum(COALESCE(total, 0)) as total_amount', false)
            ->where("date >= '{$start}' and date <= '{$end}'", null, false);
        if ($this->session->userdata('store_id')) {
            $this->db->where('store_id', $this->session->userdata('store_id'));
        }
        $q = $this->db->get('purchases');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getTotalSales($start, $end)
    {
        $this->db->select('count(id) as total, sum(COALESCE(grand_total, 0)) as total_amount, SUM(COALESCE(paid, 0)) as paid, SUM(COALESCE(total_tax, 0)) as tax', false)
            ->where("date >= '{$start}' and date <= '{$end}'", null, false);
        if ($this->session->userdata('store_id')) {
            $this->db->where('store_id', $this->session->userdata('store_id'));
        }
        $q = $this->db->get('sales');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getTotalSalesforCustomer($customer_id, $user = null, $start_date = null, $end_date = null)
    {
        if ($start_date && $end_date) {
            $this->db->where('date >=', $start_date);
            $this->db->where('date <=', $end_date);
        }
        if ($user) {
            $this->db->where('created_by', $user);
        }
        if ($this->session->userdata('store_id')) {
            $this->db->where('store_id', $this->session->userdata('store_id'));
        }
        $q = $this->db->get_where('sales', ['customer_id' => $customer_id]);
        return $q->num_rows();
    }

    public function getTotalSalesValueforCustomer($customer_id, $user = null, $start_date = null, $end_date = null)
    {
        $this->db->select('sum(grand_total) as total');
        if ($start_date && $end_date) {
            $this->db->where('date >=', $start_date);
            $this->db->where('date <=', $end_date);
        }
        if ($user) {
            $this->db->where('created_by', $user);
        }
        if ($this->session->userdata('store_id')) {
            $this->db->where('store_id', $this->session->userdata('store_id'));
        }
        $q = $this->db->get_where('sales', ['customer_id' => $customer_id]);
        if ($q->num_rows() > 0) {
            $s = $q->row();
            return $s->total;
        }
        return false;
    }

    public function topProducts()
    {
        $m = date('Y-m');
        $this->db->select($this->db->dbprefix('products') . '.code as product_code, ' . $this->db->dbprefix('products') . '.name as product_name, sum(' . $this->db->dbprefix('sale_items') . '.quantity) as quantity')
        ->join('products', 'products.id=sale_items.product_id', 'left')
        ->join('sales', 'sales.id=sale_items.sale_id', 'left')
        ->order_by('sum(' . $this->db->dbprefix('sale_items') . '.quantity)', 'desc')
        ->group_by('sale_items.product_id')
        ->limit(10)
        ->like($this->db->dbprefix('sales') . '.date', $m, 'both');
        if ($this->session->userdata('store_id')) {
            $this->db->where('store_id', $this->session->userdata('store_id'));
        }
        $q = $this->db->get('sale_items');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function topProducts1()
    {
        $m = date('Y-m', strtotime('first day of last month'));
        $this->db->select($this->db->dbprefix('products') . '.code as product_code, ' . $this->db->dbprefix('products') . '.name as product_name, sum(' . $this->db->dbprefix('sale_items') . '.quantity) as quantity')
        ->join('products', 'products.id=sale_items.product_id', 'left')
        ->join('sales', 'sales.id=sale_items.sale_id', 'left')
        ->order_by('sum(' . $this->db->dbprefix('sale_items') . '.quantity)', 'desc')
        ->group_by('sale_items.product_id')
        ->limit(10)
        ->like($this->db->dbprefix('sales') . '.date', $m, 'both');
        if ($this->session->userdata('store_id')) {
            $this->db->where('store_id', $this->session->userdata('store_id'));
        }
        $q = $this->db->get('sale_items');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function topProducts12()
    {
        $this->db->select($this->db->dbprefix('products') . '.code as product_code, ' . $this->db->dbprefix('products') . '.name as product_name, sum(' . $this->db->dbprefix('sale_items') . '.quantity) as quantity')
        ->join('products', 'products.id=sale_items.product_id', 'left')
        ->join('sales', 'sales.id=sale_items.sale_id', 'left')
        ->order_by('sum(' . $this->db->dbprefix('sale_items') . '.quantity)', 'desc')
        ->group_by('sale_items.product_id')
        ->limit(10);
        if ($this->db->dbdriver == 'sqlite3') {
            // ->where("date >= datetime('now','-6 month')", NULL, FALSE)
            $this->db->where("{$this->db->dbprefix('sales')}.date >= datetime(date('now','start of month','+1 month','-1 day'), '-12 month')", null, false);
        } else {
            $this->db->where($this->db->dbprefix('sales') . '.date >= last_day(now()) + interval 1 day - interval 12 month', null, false);
        }

        if ($this->session->userdata('store_id')) {
            $this->db->where('store_id', $this->session->userdata('store_id'));
        }
        $q = $this->db->get('sale_items');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
    }

    public function topProducts3()
    {
        $this->db->select($this->db->dbprefix('products') . '.code as product_code, ' . $this->db->dbprefix('products') . '.name as product_name, sum(' . $this->db->dbprefix('sale_items') . '.quantity) as quantity')
        ->join('products', 'products.id=sale_items.product_id', 'left')
        ->join('sales', 'sales.id=sale_items.sale_id', 'left')
        ->order_by('sum(' . $this->db->dbprefix('sale_items') . '.quantity)', 'desc')
        ->group_by('sale_items.product_id')
        ->limit(10);
        if ($this->db->dbdriver == 'sqlite3') {
            // ->where("date >= datetime('now','-6 month')", NULL, FALSE)
            $this->db->where("{$this->db->dbprefix('sales')}.date >= datetime(date('now','start of month','+1 month','-1 day'), '-3 month')", null, false);
        } else {
            $this->db->where($this->db->dbprefix('sales') . '.date >= last_day(now()) + interval 1 day - interval 3 month', null, false);
        }
        if ($this->session->userdata('store_id')) {
            $this->db->where('store_id', $this->session->userdata('store_id'));
        }
        $q = $this->db->get('sale_items');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getProducts($filtros)
    {
        $where = "";

        if($filtros[1] && $filtros[2]){
            $where = 'WHERE SUBSTRING(tec_products.code, 1, 1) = "'.$filtros[1].'" AND SUBSTRING(tec_products.code, 2, 3) = "'.$filtros[2].'" ';
        } 
        if($filtros[1]  && !$filtros[2] ){
            $where = "WHERE SUBSTRING(tec_products.code, 1, 1) = '".$filtros[1]."'";
        } 
        if(!$filtros[1] && $filtros[2]){
            $where = "WHERE SUBSTRING(tec_products.code, 2, 3) = '".$filtros[2]."'";
        }


        $data = $this->db->query("SELECT 
                                    tec_products.*,
                                    tec_product_store_qty.quantity AS cantidad,
                                    tec_product_store_qty.quantity * tec_products.price AS importe,
                                    tec_product_store_qty.apart
                                FROM
                                    tec_products 
                                    INNER JOIN tec_product_store_qty 
                                    ON tec_products.id = tec_product_store_qty.product_id 
                                    ".$where." AND tec_product_store_qty.store_id = ".$filtros[3]."
                                    ORDER BY tec_products.name ASC
                                    ")->result();
        return  $data;
        
    }

    public function getProductsExistencia($filtros)
    {

        $data = $this->db->query("SELECT 
                                    tec_products.*,
                                    tec_product_store_qty.quantity AS cantidad,
                                    tec_product_store_qty.quantity * tec_products.price AS importe,
                                    tec_product_store_qty.apart
                                FROM
                                    tec_products 
                                    INNER JOIN tec_product_store_qty 
                                    ON tec_products.id = tec_product_store_qty.product_id 
                                    WHERE tec_product_store_qty.quantity > 0 AND tec_product_store_qty.store_id = ".$filtros[1]."
                                    ORDER BY tec_products.name ASC
                                    ")->result();
        return  $data;
        
    }

    public function getComisiones($filtros)
    {

        $data = $this->db->query("SELECT 
                                tec_users.first_name,
                                tec_users.last_name,
                                SUM(tec_sales.grand_total),
                                SUM(
                                    CASE
                                    WHEN tec_sales.transaction_type = 'credit' 
                                    THEN tec_sales.grand_total * .01 
                                    ELSE 0 
                                    END
                                ) AS tarjeta,
                                SUM(
                                    CASE
                                    WHEN tec_sales.transaction_type = 'liquidate' 
                                    THEN tec_sales.grand_total * .015 
                                    ELSE 0 
                                    END
                                ) AS contado 
                                FROM
                                tec_sales 
                                LEFT JOIN tec_users 
                                    ON tec_sales.created_by = tec_users.id 
                                WHERE tec_sales.date >= '".$filtros[1]."  00:00:00' 
                                AND tec_sales.date <= '".$filtros[2]."  23:59:59'
                                GROUP BY tec_sales.created_by
                                ")->result();
                                //echo $this->db->last_query();exit;
        return  $data;
        
    }

    public function getallSales($fechas)
    {
        $where = "";
        if($fechas[4] == "cash"){
            $where = "AND tec_sales.transaction_type = 'liquidate'";
        }

        if($fechas[4] == "CC"){
            $where = "AND tec_sales.transaction_type = 'credit'";
        }
        $data = $this->db->query("SELECT 
                                    tec_sales.id,
                                    tec_sales.date,
                                    tec_sales.invoice,
                                    tec_sales.grand_total,
                                    tec_sales.order_discount_id,
                                        tec_sales.extra_discount,
                                    SUM(tec_sale_items.discount) AS discount ,
                                    tec_sales.customer_name as customer_name,
                                    tec_users.first_name,
                                    tec_users.last_name,
                                    CASE
                                        WHEN tec_payments.paid_by = 'cash' THEN 'Efectivo'
                                        WHEN tec_payments.paid_by = 'CC' THEN 'Pago con tarjeta'
                                        ELSE 'NA'
                                        END AS tipopago
                                    FROM
                                    tec_sales 
                                    LEFT JOIN tec_sale_items 
                                        ON tec_sales.id = tec_sale_items.sale_id 
                                    LEFT JOIN tec_users
                                        ON tec_sales.created_by = tec_users.id
                                    LEFT JOIN tec_payments 
                                        ON tec_sales.id = tec_payments.sale_id 
                                    WHERE tec_sales.store_id = ".$fechas[3]." AND  tec_sales.date >= '" . $fechas[1] . " 00:00:00' AND tec_sales.date <= '" . $fechas[2] . " 23:59:59'  ".$where."
                                    GROUP BY tec_sales.id 
                                    ORDER BY tec_sales.date ASC 
                                    ")->result();
        return $data;
    }

    public function getallPurchases($fechas)
    {

        $data = $this->db->query("SELECT 
                                        tec_products.name,
                                        tec_products.code,
                                        tec_purchase_items.quantity,
                                        tec_purchase_items.cost,
                                        tec_purchase_items.subtotal,
                                        tec_purchases.date,
                                        tec_users.first_name,
                                        tec_users.last_name,
                                        tec_suppliers.name AS supplier 
                                    FROM
                                        tec_purchase_items 
                                        LEFT JOIN tec_purchases 
                                        ON tec_purchase_items.purchase_id = tec_purchases.id 
                                        LEFT JOIN tec_products 
                                        ON tec_purchase_items.product_id = tec_products.id 
                                        LEFT JOIN tec_users 
                                        ON tec_purchases.created_by = tec_users.id 
                                        LEFT JOIN tec_suppliers 
                                        ON tec_purchases.supplier_id = tec_suppliers.id 
                                    WHERE tec_purchases.store_id = ".$fechas[3]." 
                                        AND tec_purchases.date >= '" . $fechas[1] . " 00:00:00' 
                                        AND tec_purchases.date <= '" . $fechas[2] . " 23:59:59' 
                                    ORDER BY tec_purchases.date ASC 
                                    ")->result();
        return $data;
    }


    /* public function getCustomers()
    {

        $data = $this->db->query("SELECT 
                                        *
                                    FROM
                                        tec_customers
                                    ")->result();
        return $data;
    } */

    public function getCustomers($term, $limit = 10)
    {
        if ($this->db->dbdriver == 'sqlite3') {
            $this->db->where("name LIKE '%" . $term . "%'");
        } else {
            $this->db->where("name LIKE '%" . $term . "%'");
        }
        $this->db->limit($limit);
        $q = $this->db->get('customers');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getallSalesFiscal($fechas)
    {
        $data = $this->db->query("SELECT 
                                    tec_sales.id,
                                    tec_sales.date,
                                    tec_sales.invoice,
                                    tec_sales.grand_total,
                                    SUM(tec_sale_items.discount) AS discount ,
                                    tec_users.first_name,
                                    tec_users.last_name
                                    FROM
                                    tec_sales 
                                    LEFT JOIN tec_sale_items 
                                        ON tec_sales.id = tec_sale_items.sale_id 
                                    LEFT JOIN tec_users
                                        ON tec_sales.created_by = tec_users.id
                                    WHERE tec_sales.store_id = ".$fechas[3]."  AND tec_sales.invoice IS NOT NULL AND  tec_sales.date >= '" . $fechas[1] . " 00:00:00' AND tec_sales.date <= '" . $fechas[2] . " 23:59:59'
                                    GROUP BY tec_sales.id 
                                    ORDER BY tec_sales.date ASC 
                                    ")->result();
        return $data;
    }

    public function getallItemSales($fechas)
    {   
        $where = "";
        if($fechas[4] != "todos"){
            $where = "AND tec_payments.paid_by = '".$fechas[4]."'";
        }

        $data = $this->db->query("SELECT 
                                        product_code,
                                        product_name,
                                        unit_price,
                                        discount,
                                        tec_sales.order_discount_id,
                                        tec_sales.extra_discount,
                                        tec_sales.date,
                                        tec_sales.created_by,
                                        quantity,
                                        subtotal,
                                        tec_users.first_name,
                                        tec_users.last_name,
                                        CASE
                                        WHEN tec_payments.paid_by = 'cash' THEN 'Efectivo'
                                        WHEN tec_payments.paid_by = 'CC' THEN 'Pago con tarjeta'
                                        ELSE 'Credito'
                                        END AS tipopago
                                    FROM
                                    tec_sale_items 
                                    LEFT JOIN tec_sales 
                                    ON tec_sale_items.sale_id = tec_sales.id 
                                    LEFT JOIN tec_users
                                    ON tec_sales.created_by = tec_users.id
                                    LEFT JOIN tec_payments 
                                    ON tec_sales.id = tec_payments.sale_id 
                                    WHERE tec_sales.store_id = ".$fechas[3]." AND  tec_sales.date >= '" . $fechas[1] . " 00:00:00'  AND tec_sales.date <= '" . $fechas[2] . " 23:59:59' ".$where."
                                ORDER BY product_code ASC 
                                ")->result();
        return $data;
    }

    public function movementsProducts($filtros)
    {
        $id = $this->db->query("SELECT * FROM tec_products WHERE code =  ".$filtros[1]." ")->result();
        //var_dump($id[0]->code); exit;
        if(isset($id[0]->code)){
                $data = $this->db->query("SELECT 
                tec_stores.name as store,
                tec_stores.id AS storeid,
                tec_products.name AS productname,
                tec_products.details,
                tec_products.price * tec_products_movements.quantity AS price,
                tec_products.code AS codeproducts,
                tec_products_movements.code AS codemovement,
                tec_products_movements.quantity,
                tec_products_movements.date,
                tec_products_movements.description 
            FROM
                tec_products_movements 
                LEFT JOIN tec_products 
                ON tec_products_movements.product_id = tec_products.id 
                LEFT JOIN tec_stores 
                ON tec_products_movements.store_id = tec_stores.id 
                WHERE   tec_products.id = ".$id[0]->id."
                AND tec_products_movements.store_id = ".$filtros[4]." 
                AND tec_products_movements.date BETWEEN '" . $filtros[2] . " 00:00:00' 
                AND '" . $filtros[3] . " 23:59:59' 
            ")->result();
            return $data;
        } else {
            return "NA";
        }
        
        
    }

    public function getStores()
    {
        $data = $this->db->query("SELECT * FROM tec_stores ")->result();
        return $data;
    }

    public function getStatusAccount($phone)
    {   
        $payments = [];
        $statusAccount = [];
        $customer = $this->db->query("SELECT * FROM tec_customers WHERE phone =".$phone[1]."")->result();

        $data = $this->db->query("SELECT 
                                    ts.id,
                                    tec_customers.name,
                                    ts.date,
                                    ts.grand_total,
                                    ts.paid,
                                    ts.transaction_type,
                                    ts.status,
                                    ts.id,
                                    tec_stores.name AS store,
                                    (SELECT 
                                    GROUP_CONCAT(' ',tp.code) 
                                    FROM
                                    tec_sale_items tsi 
                                    LEFT JOIN tec_products tp ON tp.id = tsi.product_id
                                    WHERE tsi.sale_id = ts.id) AS products_sale
                                FROM
                                    tec_sales ts 
                                    LEFT JOIN tec_customers 
                                    ON tec_customers.id = ts.customer_id 
                                    LEFT JOIN tec_stores 
                                    ON ts.store_id = tec_stores.id 
                                WHERE (ts.status = 'partial') 
                                    AND (ts.transaction_type = 'credit') 
                                    AND tec_customers.phone = ".$phone[1]." 
                                ")->result();

        foreach ($data as $key => $row) {
            $payments[$key] = $this->db->query("SELECT 
                                                    tec_payments.*,
                                                    tec_stores.name 
                                                FROM
                                                    tec_payments 
                                                    LEFT JOIN tec_stores 
                                                    ON tec_payments.store_id = tec_stores.id 
                                                WHERE sale_id = ".$row->id."")->result();
            $statusAccount [$key] = [
                                        'compra' => $row,
                                        'pagos'  => $payments[$key]
                                    ];       
        }
        
        return $statusAccount;
        
    }


    public function getCustomerByPhone($phone)
    {   
        $customer = $this->db->query("SELECT * FROM tec_customers WHERE phone =".$phone[1]."")->result();
        
        return $customer;
        
    }

    public function getPaymentsStreet($filtros)
    {   
        $payments = $this->db->query("SELECT 
                                            tec_payments.*,
                                            tec_customers.name 
                                        FROM
                                            tec_payments 
                                            LEFT JOIN tec_customers 
                                            ON tec_payments.customer_id = tec_customers.id 
                                        WHERE date >= '" . $filtros[1] . " 00:00:00' 
                                            AND date <= '" . $filtros[2] . " 23:59:59' 
                                            AND tec_payments.store_id = 99
                                    ")->result();
        
        return $payments;
        
    }

    public function getPaymentsReport($start, $end)
    {   
        $sql = "SELECT 
            tec_payments.amount,
            tec_payments.date,
            tec_stores.name AS store,
            tec_users.first_name,
            tec_customers.name AS customer,
            tec_users.last_name,
            tec_payments.paid_by,
            CASE
                WHEN tec_payments.paid_by = 'cash' THEN 'Efectivo'
                WHEN tec_payments.paid_by = 'CC' THEN 'Tarjeta'
                WHEN tec_payments.paid_by = 'transfer' THEN 'Transferencia'
                ELSE 'NA'
            END AS tipopago
        FROM
            tec_payments 
            LEFT JOIN tec_customers 
            ON tec_payments.customer_id = tec_customers.id 
            LEFT JOIN tec_users 
            ON tec_payments.created_by = tec_users.id 
            LEFT JOIN tec_stores 
            ON tec_stores.id = tec_payments.store_id
            WHERE tec_payments.date BETWEEN '{$start}' AND '{$end} 23:59:59'
        ";

        return $this->db->query($sql)->result();
    }

    public function getCurentTakeInventory($store_id){
        $sql = "SELECT *
                FROM tec_take_inventory
                WHERE status = 'open' AND store_id = {$store_id}";
        return $this->db->query($sql)->row();
    }

    public function getTakeInventory($id, $created_by){
        $sql = "SELECT tec_take_inventory_items.*, tec_products.name, tec_products.code, tec_products.image
                FROM tec_take_inventory_items
                INNER JOIN tec_products ON tec_products.id = tec_take_inventory_items.product_id
                WHERE tec_take_inventory_items.id_take_inventory = {$id} AND tec_take_inventory_items.created_by = {$created_by}";
        return $this->db->query($sql)->result();
    }

    public function up_take_inventory($created_by, $store_id){
        $sql = "INSERT INTO tec_take_inventory SET store_id = {$store_id}, created_by = {$created_by}";
        $this->db->query($sql);
    }

    public function add_product_take_inventory($id_take_inventory, $created_by, $product_id, $quantity, $description = ""){
        $sql = "INSERT INTO tec_take_inventory_items SET 
            id_take_inventory = {$id_take_inventory}, 
            created_by = {$created_by},
            product_id = {$product_id},
            quantity = {$quantity},
            description = '$description'";
        $this->db->query($sql);
    }

    public function remove_product_take_inventory($id){
        $sql = "DELETE FROM tec_take_inventory_items WHERE id = {$id}";
        $this->db->query($sql);
    }

    public function cancel_take_inventory($id){
        $sql = "UPDATE tec_take_inventory SET status = 'cancel' WHERE id = {$id}";
        $this->db->query($sql);
    }

    public function getProductsTakeInventoryReport($id, $store_id){
        $sql = "SELECT
                    tbl.product_id,
                    SUM(IF(source = 'inventory', tbl.quantity, 0)) AS inventory,
                    SUM(IF(source = 'take_inventory', tbl.quantity, 0)) AS take_inventory,
                    GROUP_CONCAT(DISTINCT description) as description,
                    tec_products.code,
                    tec_products.name
                FROM (
                    SELECT product_id, SUM(quantity) AS quantity, 'inventory' AS source, '' as description
                    FROM tec_inventory
                    WHERE store_id = {$store_id} AND quantity > 0
                    GROUP BY product_id
                
                    UNION ALL
                
                    SELECT product_id, SUM(tec_take_inventory_items.quantity) AS quantity, 'take_inventory' AS source, description
                    FROM `tec_take_inventory`
                    INNER JOIN `tec_take_inventory_items` ON tec_take_inventory_items.`id_take_inventory` = tec_take_inventory.id
                    WHERE tec_take_inventory.id = {$id}
                    GROUP BY tec_take_inventory_items.`product_id`
                ) AS tbl
                INNER JOIN tec_products ON tec_products.id = tbl.product_id
                GROUP BY tbl.product_id";
        return $this->db->query($sql)->result();
    }
}
