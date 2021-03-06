<?php defined('BASEPATH') or exit('No direct script access allowed');
require "vendor/autoload.php";

use Dompdf\Dompdf;
class Reports extends MY_Controller
{

    function __construct()
    {
        parent::__construct();


        if (!$this->loggedIn) {
            redirect('login');
        }

        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }

        $this->load->model('reports_model');
        $this->load->model('customers_model');
        $this->load->model('sales_model');
        $this->load->library('form_validation');

    }

    function daily_sales($year = NULL, $month = NULL)
    {
        if (!$year) {
            $year = date('Y');
        }
        if (!$month) {
            $month = date('m');
        }
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->lang->load('calendar');
        $config = array(
            'show_next_prev' => TRUE,
            'next_prev_url' => site_url('reports/daily_sales'),
            'month_type' => 'long',
            'day_type' => 'long'
        );
        $config['template'] = '

        {table_open}<table border="0" cellpadding="0" cellspacing="0" class="table table-bordered table-calendar" style="min-width:522px;">{/table_open}

        {heading_row_start}<tr class="active">{/heading_row_start}

        {heading_previous_cell}<th><div class="text-center"><a href="{previous_url}">&lt;&lt;</div></a></th>{/heading_previous_cell}
        {heading_title_cell}<th colspan="{colspan}"><div class="text-center">{heading}</div></th>{/heading_title_cell}
        {heading_next_cell}<th><div class="text-center"><a href="{next_url}">&gt;&gt;</a></div></th>{/heading_next_cell}

        {heading_row_end}</tr>{/heading_row_end}

        {week_row_start}<tr>{/week_row_start}
        {week_day_cell}<td class="cl_equal"><div class="cl_wday">{week_day}</div></td>{/week_day_cell}
        {week_row_end}</tr>{/week_row_end}

        {cal_row_start}<tr>{/cal_row_start}
        {cal_cell_start}<td>{/cal_cell_start}

        {cal_cell_content}{day}<br>{content}{/cal_cell_content}
        {cal_cell_content_today}<div class="highlight">{day}</div>{content}{/cal_cell_content_today}

        {cal_cell_no_content}{day}{/cal_cell_no_content}
        {cal_cell_no_content_today}<div class="highlight">{day}</div>{/cal_cell_no_content_today}

        {cal_cell_blank}&nbsp;{/cal_cell_blank}

        {cal_cell_end}</td>{/cal_cell_end}
        {cal_row_end}</tr>{/cal_row_end}

        {table_close}</table>{/table_close}
        ';

        $this->load->library('calendar', $config);

        $sales = $this->reports_model->getDailySales($year, $month);

        if (!empty($sales)) {
            foreach ($sales as $sale) {
                $sale->date = intval($sale->date);
                $daily_sale[$sale->date] = "<table class='table table-condensed table-striped' style='margin-bottom:0;'><tr><td>" . lang('total') .
                    "</td><td style='text-align:right;'>{$this->tec->formatMoney($sale->total)}</td></tr><tr><td><span style='font-weight:normal;'>" . lang('product_tax') . "<br>" . lang('order_tax') . "</span><br>" . lang('tax') .
                    "</td><td style='text-align:right;'><span style='font-weight:normal;'>{$this->tec->formatMoney($sale->product_tax)}<br>{$this->tec->formatMoney($sale->order_tax)}</span><br>{$this->tec->formatMoney($sale->total_tax)}</td></tr><tr><td class='violet'>" . lang('discount') .
                    "</td><td style='text-align:right;'>{$this->tec->formatMoney($sale->discount)}</td></tr><tr><td class='violet'>" . lang('grand_total') .
                    "</td><td style='text-align:right;' class='violet'>{$this->tec->formatMoney($sale->grand_total)}</td></tr><tr><td class='green'>" . lang('paid') .
                    "</td><td style='text-align:right;' class='green'>{$this->tec->formatMoney($sale->paid)}</td></tr><tr><td class='orange'>" . lang('balance') .
                    "</td><td style='text-align:right;' class='orange'>{$this->tec->formatMoney(($sale->grand_total +$sale->rounding) -$sale->paid)}</td></tr></table>";
            }
        } else {
            $daily_sale = array();
        }

        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['calender'] = $this->calendar->generate($year, $month, $daily_sale);

        $start = $year . '-' . $month . '-01 00:00:00';
        $end = $year . '-' . $month . '-' . days_in_month($month, $year) . ' 23:59:59';
        $this->data['total_purchases'] = $this->reports_model->getTotalPurchases($start, $end);
        $this->data['total_sales'] = $this->reports_model->getTotalSales($start, $end);
        $this->data['total_expenses'] = $this->reports_model->getTotalExpenses($start, $end);

        $this->data['page_title'] = $this->lang->line("daily_sales");
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('daily_sales')));
        $meta = array('page_title' => lang('daily_sales'), 'bc' => $bc);
        $this->page_construct('reports/daily', $this->data, $meta);
    }


    function monthly_sales($year = NULL)
    {
        if (!$year) {
            $year = date('Y');
        }
        $this->load->language('calendar');
        $this->lang->load('calendar');
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $start = $year . '-01-01 00:00:00';
        $end = $year . '-12-31 23:59:59';
        $this->data['total_purchases'] = $this->reports_model->getTotalPurchases($start, $end);
        $this->data['total_sales'] = $this->reports_model->getTotalSales($start, $end);
        $this->data['total_expenses'] = $this->reports_model->getTotalExpenses($start, $end);
        $this->data['year'] = $year;
        $this->data['sales'] = $this->reports_model->getMonthlySales($year);
        $this->data['page_title'] = $this->lang->line("monthly_sales");
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('monthly_sales')));
        $meta = array('page_title' => lang('monthly_sales'), 'bc' => $bc);
        $this->page_construct('reports/monthly', $this->data, $meta);
    }

    function index()
    {
        if ($this->input->post('customer')) {
            $start_date = $this->input->post('start_date') ? $this->input->post('start_date') : NULL;
            $end_date = $this->input->post('end_date') ? $this->input->post('end_date') : NULL;
            $user = $this->input->post('user') ? $this->input->post('user') : NULL;
            $this->data['total_sales'] = $this->reports_model->getTotalCustomerSales($this->input->post('customer'), $user, $start_date, $end_date);
        }
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['customers'] = $this->reports_model->getAllCustomers();
        $this->data['users'] = $this->reports_model->getAllStaff();
        $this->data['page_title'] = $this->lang->line("sales_report");
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('sales_report')));
        $meta = array('page_title' => lang('sales_report'), 'bc' => $bc);
        $this->page_construct('reports/sales', $this->data, $meta);
    }

    function purchase()
    {
        if ($this->input->post('customer')) {
            $start_date = $this->input->post('start_date') ? $this->input->post('start_date') : NULL;
            $end_date = $this->input->post('end_date') ? $this->input->post('end_date') : NULL;
            $user = $this->input->post('user') ? $this->input->post('user') : NULL;
            $this->data['total_sales'] = $this->reports_model->getTotalCustomerSales($this->input->post('customer'), $user, $start_date, $end_date);
        }
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['customers'] = $this->reports_model->getAllCustomers();
        $this->data['users'] = $this->reports_model->getAllStaff();
        $this->data['page_title'] = $this->lang->line("purchase_report");
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('sales_report')));
        $meta = array('page_title' => lang('purchase_report'), 'bc' => $bc);
        $this->page_construct('reports/purchase', $this->data, $meta);
    }

    function customer()
    {
        
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('sales_report')));
        $meta = array('page_title' => lang('customer_report'), 'bc' => $bc);
        $this->page_construct('reports/customer', $this->data, $meta);
    }

    function collecter()
    {
        
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('sales_report')));
        $meta = array('page_title' => lang('collecter_report'), 'bc' => $bc);
        $this->data['creditsClients']  = $this->sales_model->getAllCreditsClients();
        /* var_dump($creditsClients);exit; */
        $this->page_construct('reports/collecter', $this->data, $meta, $creditsClients);
    }

    function collecter_ticket($start, $end)
    {
        $startDate = urldecode($start);
        $endDate = urldecode($end);

        $this->data['payments'] = $this->reports_model->getPaymentsReport($startDate, $endDate);
        $this->data['date'] = date('Y-m-d H:m:i');

        $total = (object)[
            "transfer" => 0,
            "cash" => 0
        ];
        foreach($this->data['payments'] as $payment){
            $total->{$payment->paid_by} += $payment->amount;
        }
        $this->data['total'] = $total;

        $this->load->view($this->theme . 'reports/collecter_ticket', $this->data);
    }

    function get_sales()
    {
        $customer = $this->input->get('customer') ? $this->input->get('customer') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        $user = $this->input->get('user') ? $this->input->get('user') : NULL;

        $this->load->library('datatables');
        $this->datatables
            ->select("id, date, customer_name, total, total_tax, total_discount, grand_total, paid, (grand_total-paid) as balance, status")
            ->from('sales');
        if ($this->session->userdata('store_id')) {
            $this->datatables->where('store_id', $this->session->userdata('store_id'));
        }
        $this->datatables->unset_column('id');
        if ($customer) {
            $this->datatables->where('customer_id', $customer);
        }
        if ($user) {
            $this->datatables->where('created_by', $user);
        }
        if ($start_date) {
            $this->datatables->where('date >=', $start_date);
        }
        if ($end_date) {
            $this->datatables->where('date <=', $end_date);
        }

        echo $this->datatables->generate();
    }

    function products()
    {
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['products'] = $this->reports_model->getAllProducts();
        $this->data['page_title'] = $this->lang->line("products_report");
        $this->data['page_title'] = $this->lang->line("products_report");
        $this->data['stores']     = $this->site->getAllStores();
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('products_report')));
        $meta = array('page_title' => lang('products_report'), 'bc' => $bc);
        $this->page_construct('reports/products', $this->data, $meta);
    }

    function get_products()
    {
        $product = $this->input->get('product') ? $this->input->get('product') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        //COALESCE(sum(".$this->db->dbprefix('sale_items').".quantity)*".$this->db->dbprefix('products').".cost, 0) as cost,
        $this->load->library('datatables');
        $this->datatables
            ->select($this->db->dbprefix('products') . ".id as id, " . $this->db->dbprefix('products') . ".name, " . $this->db->dbprefix('products') . ".code, COALESCE(sum(" . $this->db->dbprefix('sale_items') . ".quantity), 0) as sold, ROUND(COALESCE(((sum(" . $this->db->dbprefix('sale_items') . ".subtotal)*" . $this->db->dbprefix('products') . ".tax)/100), 0), 2) as tax, COALESCE(sum(" . $this->db->dbprefix('sale_items') . ".quantity)*" . $this->db->dbprefix('sale_items') . ".cost, 0) as cost, COALESCE(sum(" . $this->db->dbprefix('sale_items') . ".subtotal), 0) as income, ROUND((COALESCE(sum(" . $this->db->dbprefix('sale_items') . ".subtotal), 0)) - COALESCE(sum(" . $this->db->dbprefix('sale_items') . ".quantity)*" . $this->db->dbprefix('sale_items') . ".cost, 0) -COALESCE(((sum(" . $this->db->dbprefix('sale_items') . ".subtotal)*" . $this->db->dbprefix('products') . ".tax)/100), 0), 2)
            as profit", FALSE)
            ->from('sale_items')
            ->join('products', 'sale_items.product_id=products.id', 'left')
            ->join('sales', 'sale_items.sale_id=sales.id', 'left');
        if ($this->session->userdata('store_id')) {
            $this->datatables->where('sales.store_id', $this->session->userdata('store_id'));
        }
        $this->datatables->group_by('products.id');

        if ($product) {
            $this->datatables->where('products.id', $product);
        }
        if ($start_date) {
            $this->datatables->where('date >=', $start_date);
        }
        if ($end_date) {
            $this->datatables->where('date <=', $end_date);
        }
        echo $this->datatables->generate();
    }

    function profit($income, $cost, $tax)
    {
        return floatval($income) . " - " . floatval($cost) . " - " . floatval($tax);
    }

    function top_products()
    {
        $this->data['topProducts'] = $this->reports_model->topProducts();
        $this->data['topProducts1'] = $this->reports_model->topProducts1();
        $this->data['topProducts3'] = $this->reports_model->topProducts3();
        $this->data['topProducts12'] = $this->reports_model->topProducts12();
        $this->data['page_title'] = $this->lang->line("top_products");
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('top_products')));
        $meta = array('page_title' => lang('top_products'), 'bc' => $bc);
        $this->page_construct('reports/top', $this->data, $meta);
    }

    function registers()
    {
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['users'] = $this->reports_model->getAllStaff();
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('registers_report')));
        $meta = array('page_title' => lang('registers_report'), 'bc' => $bc);
        $this->page_construct('reports/registers', $this->data, $meta);
    }

    function get_register_logs()
    {
        $user = $this->input->get('user') ? $this->input->get('user') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;

        $this->load->library('datatables');
        if ($this->db->dbdriver == 'sqlite3') {
            $this->datatables->select("{$this->db->dbprefix('registers')}.id as id, date, closed_at, ({$this->db->dbprefix('users')}.first_name || ' ' || {$this->db->dbprefix('users')}.last_name || '<br>' || {$this->db->dbprefix('users')}.email) as user, cash_in_hand, (total_cc_slips || ' (' || total_cc_slips_submitted || ')') as cc_slips, (total_cheques || ' (' || total_cheques_submitted || ')') as total_cheques, (total_cash || ' (' || total_cash_submitted || ')') as total_cash, note", FALSE);
        } else {
            $this->datatables->select("{$this->db->dbprefix('registers')}.id as id, date, closed_at, CONCAT(" . $this->db->dbprefix('users') . ".first_name, ' ', " . $this->db->dbprefix('users') . ".last_name, '<br>', " . $this->db->dbprefix('users') . ".email) as user, cash_in_hand, CONCAT(total_cc_slips, ' (', total_cc_slips_submitted, ')') as cc_slips, CONCAT(total_cheques, ' (', total_cheques_submitted, ')') as total_cheques, CONCAT(total_cash, ' (', total_cash_submitted, ')') as total_cash, note", FALSE);
        }
        $this->datatables->from("registers")
            ->join('users', 'users.id=registers.user_id', 'left');

        if ($user) {
            $this->datatables->where('registers.user_id', $user);
        }
        if ($start_date) {
            $this->datatables->where('date  >=', $start_date)->where('date <=', $end_date);
        }
        if ($this->session->userdata('store_id')) {
            $this->datatables->where('registers.store_id', $this->session->userdata('store_id'));
        }

        echo $this->datatables->generate();
    }

    function payments()
    {
        if ($this->input->post('customer')) {
            $start_date = $this->input->post('start_date') ? $this->input->post('start_date') : NULL;
            $end_date = $this->input->post('end_date') ? $this->input->post('end_date') : NULL;
            $user = $this->input->post('user') ? $this->input->post('user') : NULL;
            $this->data['total_sales'] = $this->reports_model->getTotalCustomerSales($this->input->post('customer'), $user, $start_date, $end_date);
        }
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['users'] = $this->reports_model->getAllStaff();
        $this->data['customers'] = $this->reports_model->getAllCustomers();
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('payments_report')));
        $meta = array('page_title' => lang('payments_report'), 'bc' => $bc);
        $this->page_construct('reports/payments', $this->data, $meta);
    }

    function inventory()
    {
        if ($this->input->post('customer')) {
            $start_date = $this->input->post('start_date') ? $this->input->post('start_date') : NULL;
            $end_date = $this->input->post('end_date') ? $this->input->post('end_date') : NULL;
            $user = $this->input->post('user') ? $this->input->post('user') : NULL;
            $this->data['total_sales'] = $this->reports_model->getTotalCustomerSales($this->input->post('customer'), $user, $start_date, $end_date);
        }
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['users'] = $this->reports_model->getAllStaff();
        $this->data['customers'] = $this->reports_model->getAllCustomers();
        $this->data['products'] = $this->reports_model->getAllProducts();

        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('payments_report')));
        $meta = array('page_title' => lang('inventory_report'), 'bc' => $bc);
        $this->page_construct('reports/inventory', $this->data, $meta);
    }

    function get_inventory(){
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;

        $this->load->library('datatables');
        $this->datatables
            ->select("{$this->db->dbprefix('products')}.code as code, {$this->db->dbprefix('products')}.name, {$this->db->dbprefix('inventory')}.available, {$this->db->dbprefix('inventory')}.quantity, {$this->db->dbprefix('inventory')}.apart")
            ->from('inventory')
            ->join('products', 'inventory.product_id=products.id', 'left')
            ->order_by('code');

        $this->datatables->add_column(
            'Fisico',
            "<input class='form-control w-100 physical-inv' type='number' placeholder='$1' />",
            'quantity'
        );

        if ($this->session->userdata('store_id')) {
            $this->datatables->where('inventory.store_id', $this->session->userdata('store_id'));
        }
        if ($start_date) {
            $this->datatables->where("{$this->db->dbprefix('payments')}.date  >=", $start_date)
                ->where("{$this->db->dbprefix('payments')}.date <=", $end_date);
        }

        echo $this->datatables->generate();
    }

    function get_payments()
    {
        $user = $this->input->get('user') ? $this->input->get('user') : NULL;
        $ref = $this->input->get('payment_ref') ? $this->input->get('payment_ref') : NULL;
        $sale_id = $this->input->get('sale_no') ? $this->input->get('sale_no') : NULL;
        $customer = $this->input->get('customer') ? $this->input->get('customer') : NULL;
        $paid_by = $this->input->get('paid_by') ? $this->input->get('paid_by') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;

        $this->load->library('datatables');
        $this->datatables
            ->select("{$this->db->dbprefix('payments')}.id as id, {$this->db->dbprefix('payments')}.date, {$this->db->dbprefix('payments')}.reference as ref, {$this->db->dbprefix('sales')}.id as sale_no, paid_by, amount")
            ->from('payments')
            ->join('sales', 'payments.sale_id=sales.id', 'left')
            ->group_by('payments.id');

        if ($this->session->userdata('store_id')) {
            $this->datatables->where('payments.store_id', $this->session->userdata('store_id'));
        }
        if ($user) {
            $this->datatables->where('payments.created_by', $user);
        }
        if ($ref) {
            $this->datatables->where('payments.reference', $ref);
        }
        if ($paid_by) {
            $this->datatables->where('payments.paid_by', $paid_by);
        }
        if ($sale_id) {
            $this->datatables->where('sales.id', $sale_id);
        }
        if ($customer) {
            $this->datatables->where('sales.customer_id', $customer);
        }
        if ($start_date) {
            $this->datatables->where("{$this->db->dbprefix('payments')}.date  >=", $start_date)
                ->where("{$this->db->dbprefix('payments')}.date <=", $end_date);
        }

        echo $this->datatables->generate();
    }

    function alerts()
    {
        $data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('stock_alert');
        $bc = array(array('link' => '#', 'page' => lang('stock_alert')));
        $meta = array('page_title' => lang('stock_alert'), 'bc' => $bc);
        $this->page_construct('reports/alerts', $this->data, $meta);
    }

    function get_alerts()
    {
        $this->load->library('datatables');
        $this->datatables->select($this->db->dbprefix('products') . ".id as id, " . $this->db->dbprefix('products') . ".image as image, " . $this->db->dbprefix('products') . ".code as code, " . $this->db->dbprefix('products') . ".name as pname, type, " . $this->db->dbprefix('categories') . ".name as cname, (CASE WHEN psq.quantity IS NULL THEN 0 ELSE psq.quantity END) as quantity, alert_quantity, tax, tax_method, cost, (CASE WHEN psq.price > 0 THEN psq.price ELSE {$this->db->dbprefix('products')}.price END) as price", FALSE)
            ->from('products')
            ->join('categories', 'categories.id=products.category_id')
            ->join("( SELECT * from {$this->db->dbprefix('product_store_qty')} WHERE store_id = {$this->session->userdata('store_id')}) psq", 'products.id=psq.product_id', 'left')
            ->where("(CASE WHEN psq.quantity IS NULL THEN 0 ELSE psq.quantity END) < {$this->db->dbprefix('products')}.alert_quantity", NULL, FALSE)
            ->group_by('products.id');
        $this->datatables->add_column("Actions", "<div class='text-center'><a href='#' class='btn btn-xs btn-primary ap tip' data-id='$1' title='" . lang('add_to_purcahse_order') . "'><i class='fa fa-plus'></i></a></div>", "id");
        // $this->datatables->unset_column('id');
        echo $this->datatables->generate();
    }

    function getStores()
    {
        $stores = $this->reports_model->getStores();
        echo json_encode($stores);
    }

    public function reportsproducts()
    {
        $filtros = $_GET['filtros'];
        $arrayfiltros = explode(",", $filtros);
        $productos = [];
        $header = "";
        $table = "";
        $tableBS = "";
        $tableIA = "";
        $tableRC = "";
        $tableRS = "";
        $tableS = "";
        $tableAP = "";
        $rango = "";
        $tablaExis = "";
        $tienda = $this->site->getStoreByID(end($arrayfiltros));
        if ($arrayfiltros[0] == "Reporte de existencia de productos") {
            $productos = $this->reports_model->getProducts($arrayfiltros);
            //echo $productos;exit;
            $header = '
                <tr class="header">
                    <td style="text-align:center">#</td>
                    <td style="text-align:center">Clave</td>
                    <td style="text-align:center">Nombre</td>
                    <td style="text-align:center">Precio P</td>
                    <td style="text-align:center">Unidades</td>
                    <td style="text-align:center">Apartados</td>
                    <td style="text-align:center">Importe</td>
                </tr>
            ';
            for ($i = 0; $i <= count($productos) - 1; $i++) {
                $item = $i + 1;
                $table .= '
                    <tr>
                        <td style="text-align:center;">' . $item . '</td>
                        <td style="text-align:center;">' . $productos[$i]->code . '</td>
                        <td style="text-align:center;">' . $productos[$i]->name . '</td>
                        <td style="text-align:center;">' . $this->tec->formatMoney($productos[$i]->price) . '</td>
                        <td style="text-align:center;">' . $this->tec->formatMoney($productos[$i]->cantidad) . '</td>
                        <td style="text-align:center;">' . $this->tec->formatMoney($productos[$i]->cantidad) . '</td>
                        <td style="text-align:center;">' . $this->tec->formatMoney($productos[$i]->importe) . '</td>
                    </tr>
                ';
            }
        }

        if ($arrayfiltros[0] == "Reporte solo en existencia") {
            $productos = $this->reports_model->getProductsExistencia($arrayfiltros);
            //echo $productos;exit;
            $header = '
                <tr class="header">
                    <td style="text-align:center">#</td>
                    <td style="text-align:center">Clave</td>
                    <td style="text-align:center">Nombre</td>
                    <td style="text-align:center">Precio P</td>
                    <td style="text-align:center">Unidades</td>
                    <td style="text-align:center">Apartados</td>
                    <td style="text-align:center">Importe</td>
                </tr>
            ';
            for ($i = 0; $i <= count($productos) - 1; $i++) {
                $item = $i + 1;
                $table .= '
                    <tr>
                        <td style="text-align:center;">' . $item . '</td>
                        <td style="text-align:center;">' . $productos[$i]->code . '</td>
                        <td style="text-align:center;">' . $productos[$i]->name . '</td>
                        <td style="text-align:center;">' . $this->tec->formatMoney($productos[$i]->price) . '</td>
                        <td style="text-align:center;">' . $this->tec->formatMoney($productos[$i]->cantidad) . '</td>
                        <td style="text-align:center;">' . $this->tec->formatMoney($productos[$i]->apart) . '</td>
                        <td style="text-align:center;">' . $this->tec->formatMoney($productos[$i]->importe) . '</td>
                    </tr>
                ';
            }
        }

        if ($arrayfiltros[0] == " de productos") {
            $productos = $this->reports_model->movementsProducts($arrayfiltros);
            //echo $productos;exit;
            if($productos == "NA"){
                echo "Verifique el codigo del producto para continuar";
                return;
            }
            $header = '
                <tr class="header">
                    <td style="text-align:center">C??digo</td>
                    <td style="text-align:center">Cantidad</td>
                    <td style="text-align:center">Descripcion</td>
                    <td style="text-align:center">Importe</td>
                    <td style="text-align:center">Fecha</td>
                    <td style="text-align:center">Referencia</td>
                </tr>
            ';
            for ($i = 0; $i <= count($productos) - 1; $i++) {

                if($productos[$i]->codemovement == "sale"){
                    $tableS .= '
                        <tr>
                            <td style="text-align:center">' . $productos[$i]->codeproducts . '</td>
                            <td style="text-align:center;">' . $productos[$i]->quantity . '</td>
                            <td style="text-align:center;">' . $productos[$i]->productname . '</td>
                            <td style="text-align:center;">' . $this->tec->formatMoney($productos[$i]->price) . '</td>
                            <td style="text-align:center;">' . $productos[$i]->date . '</td>
                            <td style="text-align:center;">' . $productos[$i]->description . '</td>
                        </tr>
                    ';
                }

                if($productos[$i]->codemovement == "inventory-adjust"){
                    $tableIA .= '
                        <tr>
                        <td style="text-align:center">' . $productos[$i]->codeproducts . '</td>
                            <td style="text-align:center;">' . $productos[$i]->quantity . '</td>
                            <td style="text-align:center;">' . $productos[$i]->productname . '</td>
                            <td style="text-align:center;">' . $this->tec->formatMoney($productos[$i]->price) . '</td>
                            <td style="text-align:center;">' . $productos[$i]->date . '</td>
                            <td style="text-align:center;">' . $productos[$i]->description . '</td>
                        </tr>
                    ';
                }

                if($productos[$i]->codemovement == "buy-supplier"){
                    $tableBS .= '
                        <tr>
                            <td style="text-align:center">' . $productos[$i]->codeproducts . '</td>
                            <td style="text-align:center;">' . $productos[$i]->quantity . '</td>
                            <td style="text-align:center;">' . $productos[$i]->productname . '</td>
                            <td style="text-align:center;">' . $this->tec->formatMoney($productos[$i]->price) . '</td>
                            <td style="text-align:center;">' . $productos[$i]->date . '</td>
                            <td style="text-align:center;">' . $productos[$i]->description . '</td>
                        </tr>
                    ';
                }

                if($productos[$i]->codemovement == "buy-supplier-return"){
                    $tableRS .= '
                        <tr>
                            <td style="text-align:center">' . $productos[$i]->codeproducts . '</td>
                            <td style="text-align:center;">' . $productos[$i]->quantity . '</td>
                            <td style="text-align:center;">' . $productos[$i]->productname . '</td>
                            <td style="text-align:center;">' . $this->tec->formatMoney($productos[$i]->price) . '</td>
                            <td style="text-align:center;">' . $productos[$i]->date . '</td>
                            <td style="text-align:center;">' . $productos[$i]->description . '</td>
                        </tr>
                    ';
                }

                if($productos[$i]->codemovement == "sale-return"){
                    $tableRC .= '
                        <tr>
                            <td style="text-align:center">' . $productos[$i]->codeproducts . '</td>
                            <td style="text-align:center;">' . $productos[$i]->quantity . '</td>
                            <td style="text-align:center;">' . $productos[$i]->productname . '</td>
                            <td style="text-align:center;">' . $this->tec->formatMoney($productos[$i]->price) . '</td>
                            <td style="text-align:center;">' . $productos[$i]->date . '</td>
                            <td style="text-align:center;">' . $productos[$i]->description . '</td>
                        </tr>
                    ';
                }

                if($productos[$i]->codemovement == "apart"){
                    $tableAP .= '
                        <tr>
                            <td style="text-align:center">' . $productos[$i]->codeproducts . '</td>
                            <td style="text-align:center;">' . $productos[$i]->quantity . '</td>
                            <td style="text-align:center;">' . $productos[$i]->productname . '</td>
                            <td style="text-align:center;">' . $this->tec->formatMoney($productos[$i]->price) . '</td>
                            <td style="text-align:center;">' . $productos[$i]->date . '</td>
                            <td style="text-align:center;">' . $productos[$i]->description . '</td>
                        </tr>
                    ';
                }
            }
        }

        if ($arrayfiltros[0] == "Reporte de movimientos de productos") {
            $rango = '<br><label>De la fecha "'.$arrayfiltros[2].'" a la fecha "'.$arrayfiltros[3].'"</label>';
            if($tableIA){
                $tablaExis .= '
                    <h3>E-INV</h3>
                    <table class="blueTable floatedTable" style="width:100%;text-align:center;">
                        <tbody>
                            ' . $header . '
                            ' . $tableIA . '
                        </tbody>
                    </table>
                ';
            }
            if($tableBS){
                $tablaExis .= '
                    <h3>E-COM</h3>
                    <table class="blueTable floatedTable" style="width:100%;text-align:center;">
                        <tbody>
                            ' . $header . '
                            ' . $tableBS . '
                        </tbody>
                    </table>
                ';
            }
            if($tableS){
                $tablaExis .= '
                    <h3>S-VEN</h3>
                    <table class="blueTable floatedTable" style="width:100%;text-align:center;">
                        <tbody>
                            ' . $header . '
                            ' . $tableS . '
                        </tbody>
                    </table>
                ';
            }
            if($tableRS){
                $tablaExis .= '
                    <h3>S-PRO</h3>
                    <table class="blueTable floatedTable" style="width:100%;text-align:center;">
                        <tbody>
                            ' . $header . '
                            ' . $tableRS . '
                        </tbody>
                    </table>
                ';
            }
            if($tableRC){
                $tablaExis .= '
                    <h3>E-CLI</h3>
                    <table class="blueTable floatedTable" style="width:100%;text-align:center;">
                        <tbody>
                            ' . $header . '
                            ' . $tableRC . '
                        </tbody>
                    </table>
                ';
            }

            if($tableAP){
                $tablaExis .= '
                    <h3>E-CLI</h3>
                    <table class="blueTable floatedTable" style="width:100%;text-align:center;">
                        <tbody>
                            ' . $header . '
                            ' . $tableAP . '
                        </tbody>
                    </table>
                ';
            }
            
        } else {
            $tablaExis = '
                <table class="blueTable floatedTable" style="width:100%;text-align:center;">
                    <tbody>
                        ' . $header . '
                        ' . $table . '
                    </tbody>
                </table>
            ';
        }



        $html = '
            <p>"ITALIA JOYAS" sucursal '.$tienda->name.'</p> 
            <label>'.$arrayfiltros[0].'</label>
            '.$rango.'
            <label style="margin-left:30%">Fecha de impresi??n: '.date("d-m-Y").'</label>
            <br></br>          
            <hr style="text-align:left;margin-left:0;margin-top:20px">
            <hr style="text-align:left;margin-left:0">
            <style>
                .floatedTable{
                border-collapse: collapse;
                width: 100%;
                }

                .floatedTable th, .floatedTable td {
                text-align: left;
                padding:4px;
                }

                .floatedTable tr:nth-child(even) {
                background-color: #D8D8D8;
                }
            </style>
            '.$tablaExis.'
        ';
        $dompdf = new DOMPDF();
        $dompdf->loadHtml($html);

        if ($arrayfiltros[0] == "Reporte de movimientos de productos") {
            $dompdf->set_paper("A4", "landscape"); 
        }

        $dompdf->render();

        $dompdf->stream($arrayfiltros[0] . ".pdf", array("Attachment" => 0));
        //echo $html;
    }

    public function reportssales()
    {
        $filtros = $_GET['filtros'];
        $arrayfiltros = explode(",", $filtros);
        $cantidad = 0;
        $importe = 0;
        $sales = [];
        $header = "";
        $table = "";
        $tienda = $this->site->getStoreByID($arrayfiltros[3]);
        $tipodeventa = "";

        if($arrayfiltros[0] == "Reporte de ventas por producto"){
            $sales = $this->reports_model->getallItemSales($arrayfiltros);
            $header = '
                    <tr class="header" >
                        <td style="">#</td>
                        <td style="">Clave</td>
                        <td style="">Producto</td>
                        <td style="">Fecha venta</td>
                        <td style="">Vendedor</td>
                        <td style="">Tipo de pago</td>
                        <td style="">Precio</td>
                        <td style="">Descuento</td>
                        <td style="">Cantidad</td>
                        <td style="">Importe</td>
                    </tr>
            ';
            for($i=0;$i<=count($sales)-1;$i++){
                $item = $i + 1;
                $table.='
                    <tr>
                        <td style="text-align:center;">'.$item.'</td>
                        <td style="text-align:center;">'.$sales[$i]->product_code.'</td>
                        <td style="text-align:center;">'.$sales[$i]->product_name.'</td>
                        <td style="text-align:center;">'.$sales[$i]->date.'</td>
                        <td style="text-align:center;">'.$sales[$i]->first_name.' '.$sales[$i]->last_name.'</td>
                        <td style="text-align:center;">'.$sales[$i]->tipopago.'</td>
                        <td style="text-align:center;">'.$this->tec->formatMoney($sales[$i]->unit_price).'</td>
                        <td style="text-align:center;">'.$this->tec->formatMoney($sales[$i]->discount).'</td>
                        <td style="text-align:center;">'.$this->tec->formatMoney($sales[$i]->quantity).'</td>
                        <td style="text-align:center;">'.$this->tec->formatMoney($sales[$i]->subtotal).'</td>
                    </tr>
                ';
            }
        } else if($arrayfiltros[0] == "Reporte de ventas"){
            $td = "";
            $tdd = "";
            $discount_id = "";
            $sales = $this->reports_model->getallSales($arrayfiltros);

            if($arrayfiltros[4] == "CC"){
                $td = "<td style='text-align:center;'>Cliente</td>";
                $tipodeventa = "a cr??dito";
            }
            $header = '
                    <tr class="header" >
                        <td style="text-align:center;">#</td>
                        <td style="text-align:center;">Vendedor</td>
                        '.$td.'
                        <td style="">Tipo de pago</td>
                        <td style="text-align:center;">Fecha</td>
                        <td style="text-align:center;">Descuento</td>
                        <td style="text-align:center;">Descuento Extra</td>
                        <td style="text-align:center;">Total</td>
                    </tr>
            ';
            for($i=0;$i<=count($sales)-1;$i++){
                if($arrayfiltros[4] == "CC"){
                    $tdd = "<td style='text-align:center;'>".$sales[$i]->customer_name."</td>";
                }
                $item = $i + 1;
                if($sales[$i]->order_discount_id){
                    $discount_id = '<td style="text-align:center;">'.$sales[$i]->order_discount_id.'</td>';
                } else {
                    $discount_id = '<td style="text-align:center;">0%</td>';
                }
                $table.='
                    <tr>
                        <td style="text-align:center;">'.$item.'</td>
                        <td style="text-align:center;">'.$sales[$i]->first_name.' '.$sales[$i]->last_name.'</td>
                        '.$tdd.'
                        <td style="text-align:center;">'.$sales[$i]->tipopago.'</td>
                        <td style="text-align:center;">'.$sales[$i]->date.'</td>
                        '.$discount_id.'
                        <td style="text-align:center;">'.$this->tec->formatMoney($sales[$i]->extra_discount).'</td>
                        <td style="text-align:center;">'.$this->tec->formatMoney($sales[$i]->grand_total).'</td>
                    </tr>
                ';
            }
            
        } else if($arrayfiltros[0] == "Reporte de ventas fiscal"){
            $sales = $this->reports_model->getallSalesFiscal($arrayfiltros);
            //var_dump($sales);exit;
            $header = '
                    <tr class="header" >
                        <td style="text-align:center;">#</td>
                        <td style="text-align:center;">Invoice</td>
                        <td style="text-align:center;">Vendedor</td>
                        <td style="text-align:center;">Fecha</td>
                        <td style="text-align:center;">Descuento</td>
                        <td style="text-align:center;">Total</td>
                    </tr>
            ';
            for($i=0;$i<=count($sales)-1;$i++){
                $item = $i + 1;
                $table.='
                    <tr>
                        <td style="text-align:center;">'.$item.'</td>
                        <td style="text-align:center;">'.$sales[$i]->invoice.'</td>
                        <td style="text-align:center;">'.$sales[$i]->first_name.' '.$sales[$i]->last_name.'</td>
                        <td style="text-align:center;">'.$sales[$i]->date.'</td>
                        <td style="text-align:center;">'.$this->tec->formatMoney($sales[$i]->discount).'</td>
                        <td style="text-align:center;">'.$this->tec->formatMoney($sales[$i]->grand_total).'</td>
                    </tr>
                ';
            }
            
        } else if($arrayfiltros[0] == "Reporte de ventas por comision") {
            $sales = $this->reports_model->getComisiones($arrayfiltros);

            //var_dump($sales);exit;
            $header = '
                    <tr class="header" >
                        <td style="text-align:center;">#</td>
                        <td style="text-align:center;">Vendedor</td>
                        <td style="text-align:center;">Contado</td>
                        <td style="text-align:center;">Cr??dito</td>
                        <td style="text-align:center;">Total</td>
                    </tr>
            ';
            for($i=0;$i<=count($sales)-1;$i++){
                $item = $i + 1;

                $table.='
                    <tr>
                        <td style="text-align:center;">'.$item.'</td>
                        <td style="text-align:center;">'.$sales[$i]->first_name.' '.$sales[$i]->last_name.'</td>
                        <td style="text-align:center;">'.$this->tec->formatMoney( $sales[$i]->contado).'</td>
                        <td style="text-align:center;">'.$this->tec->formatMoney( $sales[$i]->tarjeta).'</td>
                        <td style="text-align:center;">'.$this->tec->formatMoney($sales[$i]->contado + $sales[$i]->tarjeta).'</td>
                    </tr>
                ';
            }
        }
        
        $html='
            <p>"ITALIA JOYAS" sucursal '.$tienda->name.'</p>  
            <p>'.$arrayfiltros[0]. ' '.$tipodeventa.'</p>
            <p>De la fecha "'.$arrayfiltros[1].'" a la fecha "'.$arrayfiltros[2].'"</p>
            <hr style="text-align:left;margin-left:0">
            <hr style="text-align:left;margin-left:0">
            <style>
                .floatedTable{
                border-collapse: collapse;
                width: 100%;
                }

                .floatedTable th, .floatedTable td {
                text-align: left;
                padding:4px;
                }

                .floatedTable tr:nth-child(even) {
                background-color: #D8D8D8;
                }
            </style>
            <table class="blueTable floatedTable" style="width:100%;text-align:center;">
                <tbody>
                    '.$header.'
                    '.$table.'
                </tbody>
            </table>
            
        ';
        $dompdf = new DOMPDF();
        $dompdf->loadHtml($html);
        if ($arrayfiltros[0] == "Reporte de ventas por producto") {
            $dompdf->set_paper("A4", "landscape"); 
        }
        $dompdf->render();
        $dompdf->stream($arrayfiltros[0].".pdf", array("Attachment"=>0)); 
    }

    public function reportspurchase()
    {
        $filtros = $_GET['filtros'];
        $arrayfiltros = explode(",", $filtros);
        $purchase = [];
        $header = "";
        $table = "";
        $tienda = $this->site->getStoreByID(end($arrayfiltros));

        if($arrayfiltros[0] == "Reporte de compras"){
            $purchase = $this->reports_model->getallPurchases($arrayfiltros);
            $header = '
                    <tr class="header" >
                        <td style="">#</td>
                        <td style="">Clave</td>
                        <td style="">Producto</td>
                        <td style="">Fecha venta</td>
                        <td style="">Usuario</td>
                        <td style="">Cantidad</td>
                        <td style="">Costo</td>
                        <td style="">Total</td>
                    </tr>
            ';
            for($i=0;$i<=count($purchase)-1;$i++){
                $item = $i + 1;
                $table.='
                    <tr>
                        <td style="text-align:center;">'.$item.'</td>
                        <td style="text-align:center;">'.$purchase[$i]->code.'</td>
                        <td style="text-align:center;">'.$purchase[$i]->name.'</td>
                        <td style="text-align:center;">'.$purchase[$i]->date.'</td>
                        <td style="text-align:center;">'.$purchase[$i]->first_name.' '.$purchase[$i]->last_name.'</td>
                        <td style="text-align:center;">'.$this->tec->formatMoney($purchase[$i]->quantity).'</td>
                        <td style="text-align:center;">'.$this->tec->formatMoney($purchase[$i]->cost).'</td>
                        <td style="text-align:center;">'.$this->tec->formatMoney($purchase[$i]->subtotal).'</td>
                    </tr>
                ';
            }
        }
        
        $html='
            <p>"ITALIA JOYAS" sucursal '.$tienda->name.'</p> 
            <p>'.$arrayfiltros[0].'</p>
            <p>De la fecha "'.$arrayfiltros[1].'" a la fecha "'.$arrayfiltros[2].'"</p>
            <hr style="text-align:left;margin-left:0">
            <hr style="text-align:left;margin-left:0">
            <style>
                .floatedTable{
                border-collapse: collapse;
                width: 100%;
                }

                .floatedTable th, .floatedTable td {
                text-align: left;
                padding:4px;
                }

                .floatedTable tr:nth-child(even) {
                background-color: #D8D8D8;
                }
            </style>
            <table class="blueTable floatedTable" style="width:100%;text-align:center;">
                <tbody>
                    '.$header.'
                    '.$table.'
                </tbody>
            </table>
            
        ';
        $dompdf = new DOMPDF();
        $dompdf->loadHtml($html);
        $dompdf->render();
        $dompdf->stream($arrayfiltros[0].".pdf", array("Attachment"=>0)); 
    }

    public function statusAccount()
    {
        $filtros = $_GET['filtros'];
        $arrayfiltros = explode(",", $filtros);
        $sales = [];
        $itemSales = [];
        $header = "";
        $table = "";
        $headerItem = "";
        $tableItem = "";
        $masterTable = "";
        $sucursal = "";
        $itemItem=0;
        $adeudo = 0.0;
        $fechas = [];
        $cliente = $this->reports_model->getCustomerByPhone($arrayfiltros);
        $cont = 0;
        if($arrayfiltros[0] == "Estado de cuenta"){
            $sales = $this->reports_model->getStatusAccount($arrayfiltros);

            for($i=0;$i<=count($sales)-1;$i++){
                $table.='
                    <tr>
                        <td style="text-align:center;">'.$sales[$i]["compra"]->date.'</td>
                        <td style="text-align:center">Sucursal: '.$sales[$i]["compra"]->store.'</td>
                        <td style="text-align:center">'.$sales[$i]["compra"]->products_sale.'</td>
                        <td style="text-align:center;">total: $'.$this->tec->formatMoney($sales[$i]["compra"]->grand_total).'</td>
                    </tr>
                ';
                $adeudo= $adeudo + floatval( $sales[$i]["compra"]->grand_total - $sales[$i]["compra"]->paid );

                
            }

            for($i=0;$i<=count($sales)-1;$i++){

                for($j=0;$j<=count($sales[$i]["pagos"])-1;$j++){
                    if($sales[$i]["pagos"][$j]->name == ""){
                        $sucursal = '<td style="text-align:center;">Sucursal: Domicilio</td>';
                    } else {
                        $sucursal = '<td style="text-align:center;">Sucursal: '.$sales[$i]["pagos"][$j]->name.'</td>';
                    }
                    $table.='
                        <tr>
                            <td style="text-align:center;">'.$sales[$i]["pagos"][$j]->date.'</td>
                            '.$sucursal.'

                            <td style="text-align:center;"></td>
                            <td style="text-align:center;">pago de: $'.$this->tec->formatMoney($sales[$i]["pagos"][$j]->amount).'</td>
                        </tr>
                    ';
                }
                
            }
        }
        
        $html='
            <p>"ITALIA JOYAS"</p>  
            <label>'.$arrayfiltros[0].': '.$cliente[0]->name.'</label>
            <label style="margin-left:30%">Fecha de impresi??n: '.date("d-m-Y").'</label>
            <hr style="text-align:left;margin-left:0">
            <hr style="text-align:left;margin-left:0">
            <style>
                .floatedTable{
                border-collapse: collapse;
                width: 100%;
                }

                .floatedTable th, .floatedTable td {
                text-align: left;
                padding:4px;
                }

                .floatedTable tr:nth-child(even) {
                background-color: #D8D8D8;
                }
            </style>
            <table class="blueTable floatedTable" style="width:100%;text-align:center;">
            <tbody>
                '.$table.'
                <tr>
                <td style="text-align:center;">Deuda actual</td>
                <td style="text-align:center;"></td>
                <td style="text-align:center;"></td>
                <td style="text-align:center;">$'.$this->tec->formatMoney($adeudo).'</td>
            </tr>
            </tbody>
            </table>
            
        ';
        //echo $html;exit;
        $dompdf = new DOMPDF();
        $dompdf->loadHtml($html);
        if ($arrayfiltros[0] == "Reporte de ventas por producto") {
            $dompdf->set_paper("A4", "landscape"); 
        }
        $dompdf->render();
        $dompdf->stream($arrayfiltros[0].".pdf", array("Attachment"=>0)); 
    }

    public function apply_ajust_inventory(){
        $this->load->model('inventory_model');
        $items = $this->input->post('items');

        if(count($items) > 0){
            foreach($items as $item){
                $this->inventory_model->adjustProduct(
                    $this->session->userdata('store_id'),
                    $item['code'],
                    $item['quantity'],
                    $this->session->userdata('user_id')
                );
            }
        }
    }

    public function add_inventory(){
        $this->load->model('inventory_model');
        $quantity = $this->input->post('quantity');
        $product_id = $this->input->post('product_id');

        $product = $this->site->getProductByID($product_id);

        if(count($quantity) > 0 && $product){
            $this->inventory_model->adjustProduct(
                $this->session->userdata('store_id'),
                $product->code,
                $quantity,
                $this->session->userdata('user_id')
            );
        }
    }
    
    public function suggestions()
    {
        $term = $this->tec->parse_scale_barcode($this->input->get('term', true));

        $rows   = $this->reports_model->getCustomers($term);
        if ($rows) {
            foreach ($rows as $row) {

                $pr[] = ['id' => str_replace('.', '', microtime(true)), 'item_id' => $row->id, 'label' => $row->name .' '. '('. $row->phone . ')'  , 'row' => $row];
            }
            echo json_encode($pr);

        } else {
            echo json_encode([['id' => 0, 'label' => lang('no_match_found'), 'value' => $term]]);
        }
    }

    public function add_payment_credit($customer_id = null)
    {
        $this->form_validation->set_rules('amount-paid', lang('amount'), 'required');
        
        if ($this->form_validation->run() == true) {
            if ($this->Admin && $this->input->post('date')) {
                $date = $this->input->post('date');
            } else {
                $date = date('Y-m-d H:i:s');
            }

            $this->load->model('customers_model');

            $sales = $this->customers_model->getPartialSales($customer_id);
            $total_paid = (float) $this->input->post('amount-paid');
            $rest_paid = $total_paid;
            $debt = 0;

            foreach($sales as $sale){
                $debt += ((float) $sale->grand_total - (float) $sale->paid);
            }

            foreach($sales as $sale){
                $isLiquidate = (float) $sale->grand_total <= $total_paid;

                $paid = (float) $isLiquidate == true ? $sale->grand_total : $rest_paid;
                $paid_by = $this->input->post('paid_by');

                $payment = [
                    'date'        => $date,
                    'sale_id'     => $sale->id,
                    'customer_id' => $customer_id,
                    'reference'   => $this->input->post('reference'),
                    'amount'      => $paid,
                    'paid_by'     => $paid_by,
                    'cheque_no'   => $this->input->post('cheque_no'),
                    'gc_no'       => $this->input->post('gift_card_no'),
                    'cc_no'       => $this->input->post('pcc_no'),
                    'cc_holder'   => $this->input->post('pcc_holder'),
                    'cc_month'    => $this->input->post('pcc_month'),
                    'cc_year'     => $this->input->post('pcc_year'),
                    'cc_type'     => $this->input->post('pcc_type'),
                    'note'        => $this->input->post('note'),
                    'created_by'  => $this->session->userdata('user_id'),
                    'store_id'    => 99,
                ];
    
                $this->sales_model->addPayment($payment);

                $rest_paid -= $paid;

                if($rest_paid == 0) break;
            }

            
            if($ticket){
                $this->ticket_payment_credit(
                    $this->input->post('customer_id'), 
                    $date,
                    $debt, // before payment
                    $total_paid, // total payment
                    $debt - $total_paid // after payment
                );
            }else{
                $this->session->set_flashdata('message', 'Abono agregado');
                redirect('pos');
            }
        } else {
            $this->session->set_flashdata('error', 'Error al procesar el pago');
            redirect('pos');
        }
    }


    public function getPaymmentsStreet()
    {
        $filtros = $_GET['filtros'];
        $arrayfiltros = explode(",", $filtros);
        $payments = [];
        $table = "";
        $totalEfectivo = 0;
        $totalTransferencia = 0;
        $paid_by = "";

        $payments = $this->reports_model->getPaymentsStreet($arrayfiltros);

        for($i=0;$i<=count($payments)-1;$i++){
            if($payments[$i]->paid_by == "transfer"){
                $paid_by = "<td style='text-align:center;'>Transferencia</td>";
                $totalTransferencia= $totalTransferencia + floatval($payments[$i]->amount );
            } else {
                $paid_by = '<td style="text-align:center;">Efectivo</td>';
                $totalEfectivo= $totalEfectivo + floatval($payments[$i]->amount );
        }
            $table.='
                <tr>
                    <td style="text-align:center;">'.$payments[$i]->date.'</td>
                    <td style="text-align:center;">'.$payments[$i]->name.'</td>
                    '.$paid_by.'
                    <td style="text-align:center;">$'.$this->tec->formatMoney($payments[$i]->amount).'</td>
                </tr>
            ';
            
        }
        
        
        $html='
            <p>"ITALIA JOYAS"</p>  
            <label>'.$arrayfiltros[0].'</label>
            <label style="margin-left:30%">Fecha de impresi??n: '.date("d-m-Y").'</label>
            <p>De la fecha "'.$arrayfiltros[1].'" a la fecha "'.$arrayfiltros[2].'"</p>
            <hr style="text-align:left;margin-left:0">
            <hr style="text-align:left;margin-left:0">
            <style>
                .floatedTable{
                border-collapse: collapse;
                width: 100%;
                }

                .floatedTable th, .floatedTable td {
                text-align: left;
                padding:4px;
                }

                .floatedTable tr:nth-child(even) {
                background-color: #D8D8D8;
                }
            </style>
            <table class="blueTable floatedTable" style="width:100%;text-align:center;">
            <tbody>
                <tr class="header">
                    <td style="text-align:center">Fecha</td>
                    <td style="text-align:center">Cliente</td>
                    <td style="text-align:center">Tipo de pago</td>
                    <td style="text-align:center">Monto</td>
                </tr>

                '.$table.'
                <tr>
                    <td style="text-align:center;">Total efectivo</td>
                    <td style="text-align:center;"></td>
                    <td style="text-align:center;"></td>
                    <td style="text-align:center;">$'.$totalEfectivo.'</td>
                </tr>
                <tr>
                    <td style="text-align:center;">Total transferencia</td>
                    <td style="text-align:center;"></td>
                    <td style="text-align:center;"></td>
                    <td style="text-align:center;">$'.$totalTransferencia.'</td>
                </tr>
            </tr>
            </tbody>
            </table>
            
        ';
        //echo $html;exit;
        $dompdf = new DOMPDF();
        $dompdf->loadHtml($html);
        if ($arrayfiltros[0] == "Reporte de ventas por producto") {
            $dompdf->set_paper("A4", "landscape"); 
        }
        $dompdf->render();
        $dompdf->stream($arrayfiltros[0].".pdf", array("Attachment"=>0)); 
    }

    public function getPaymentsReport()
    {
        $filtros = $_GET['filtros'];
        $arrayfiltros = explode(",", $filtros);
        $payments = [];
        $total= 0;
        $payments = $this->reports_model->getPaymentsReport($arrayfiltros);
        
        for($i=0;$i<=count($payments)-1;$i++){
            $table.='
                <tr>
                    <td style="text-align:center;">'.$payments[$i]->date.'</td>
                    <td style="text-align:center;">'.$payments[$i]->store.'</td>
                    <td style="text-align:center;">'.$payments[$i]->first_name.' '.$payments[$i]->last_name.'</td>
                    <td style="text-align:center;">'.$payments[$i]->customer.'</td>
                    <td style="text-align:center;">'.$payments[$i]->tipopago.'</td>
                    <td style="text-align:center;">$'.$this->tec->formatMoney($payments[$i]->amount).'</td>
                </tr>
            ';
            $total= $total + floatval($payments[$i]->amount );
        }
        
        
        $html='
            <p>"ITALIA JOYAS"</p>  
            <label>Reporte de pagos</label>
            <label style="margin-left:55%">Fecha de impresi??n: '.date("d-m-Y").'</label>
            <p>De la fecha "'.$arrayfiltros[0].'" a la fecha "'.$arrayfiltros[1].'"</p>
            <hr style="text-align:left;margin-left:0">
            <hr style="text-align:left;margin-left:0">
            <style>
                .floatedTable{
                border-collapse: collapse;
                width: 100%;
                }

                .floatedTable th, .floatedTable td {
                text-align: left;
                padding:4px;
                }

                .floatedTable tr:nth-child(even) {
                background-color: #D8D8D8;
                }
            </style>
            <table class="blueTable floatedTable" style="width:100%;text-align:center;">
            <tbody>
                <tr class="header">
                    <td style="text-align:center">Fecha</td>
                    <td style="text-align:center;">Tienda</td>
                    <td style="text-align:center;">Cajero</td>
                    <td style="text-align:center;">Cliente</td>
                    <td style="text-align:center;">Tipo de pago</td>
                    <td style="text-align:center;">Monto</td>
                </tr>

                '.$table.'

                <tr>
                    <td style="text-align:center;"></td>
                    <td style="text-align:center;"></td>
                    <td style="text-align:center;"></td>
                    <td style="text-align:center;"> </td>
                    <td style="text-align:center">Total</td>
                    <td style="text-align:center;">$'.$total.'</td>
                </tr>
            </tbody>
            </table>
            
        ';
        //echo $html;exit;
        $dompdf = new DOMPDF();
        $dompdf->loadHtml($html);
        if ($arrayfiltros[0] == "Reporte de pagos") {
            $dompdf->set_paper("A4", "landscape"); 
        }
        $dompdf->render();
        $dompdf->stream($arrayfiltros[0].".pdf", array("Attachment"=>0)); 
    }

    public function take_inventory(){
        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang('please_select_store'));
            redirect('stores');
        }

        $this->data['page_title'] = 'Levantar inventario';

        $bc = array(array('link' => '#', 'page' => $this->data['page_title']));
        $meta = array('page_title' => $this->data['page_title'], 'bc' => $bc);

        $this->data['store'] = $this->site->getStoreByID($this->session->userdata('store_id'));
        $this->data['take_inventory'] = $this->reports_model->getCurentTakeInventory($this->data['store']->id);

        if($this->data['take_inventory']){
            $this->load->model('products_model');

            $this->data['items'] = $this->reports_model->getTakeInventory($this->data['take_inventory']->id, $this->session->userdata('user_id'));
            $this->data['products'] = $this->products_model->getAllProducts();
        }else{
            $this->data['items'] = [];
        }

        $this->page_construct('reports/take_inventory', $this->data, $meta);
    }

    public function up_take_inventory(){
        $this->reports_model->up_take_inventory($this->session->userdata('user_id'), $this->session->userdata('store_id'));
    }

    public function add_product_take_inventory(){
        $product_id = $this->input->post('product_id');
        $quantity = $this->input->post('quantity');
        $description = $this->input->post('description');
        $take_inventory = $this->reports_model->getCurentTakeInventory($this->session->userdata('store_id'));

        $this->reports_model->add_product_take_inventory($take_inventory->id, $this->session->userdata('user_id'), $product_id, $quantity, $description);
    }

    public function remove_product_take_inventory(){
        $id = $this->input->post('id');
        $this->reports_model->remove_product_take_inventory($id);
    }

    public function cancel_take_inventory(){
        $take_inventory = $this->reports_model->getCurentTakeInventory($this->session->userdata('store_id'));
        $this->reports_model->cancel_take_inventory($take_inventory->id);
    }

    public function take_inventory_report(){
        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang('please_select_store'));
            redirect('stores');
        }

        $this->data['page_title'] = 'Reporte comparativo';

        $bc = array(array('link' => '#', 'page' => $this->data['page_title']));
        $meta = array('page_title' => $this->data['page_title'], 'bc' => $bc);

        $this->data['store'] = $this->site->getStoreByID($this->session->userdata('store_id'));
        $this->data['take_inventory'] = $this->reports_model->getCurentTakeInventory($this->data['store']->id);

        if($this->data['take_inventory']){
            $this->load->model('products_model');

            $this->data['items'] = $this->reports_model->getProductsTakeInventoryReport($this->data['take_inventory']->id, $this->data['store']->id);
        }else{
            $this->data['items'] = [];
        }

        $this->page_construct('reports/take_inventory_report', $this->data, $meta);
    }

}
