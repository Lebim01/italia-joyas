<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require "vendor/autoload.php";

use Dompdf\Dompdf;

class Sales extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->loggedIn) {
            redirect('login');
        }
        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang('please_select_store'));
            redirect('stores');
        }
        $this->load->library('form_validation');
        $this->load->model('sales_model');

        $this->digital_file_types = 'zip|pdf|doc|docx|xls|xlsx|jpg|png|gif';
    }

    public function add_payment($id = null, $cid = null)
    {
        $ticket = $this->input->post('ticket');
        $this->load->helper('security');

        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }

        $this->form_validation->set_rules('amount-paid', lang('amount'), 'required');
        $this->form_validation->set_rules('paid_by', lang('paid_by'), 'required');
        $this->form_validation->set_rules('userfile', lang('attachment'), 'xss_clean');
        if ($this->form_validation->run() == true) {
            if ($this->Admin) {
                $date = $this->input->post('date');
                if(!$date) $date = date('Y-m-d H:i:s');
            } else {
                $date = date('Y-m-d H:i:s');
            }
            $payment = [
                'date'        => $date,
                'sale_id'     => $id,
                'customer_id' => $cid,
                'reference'   => $this->input->post('reference'),
                'amount'      => $this->input->post('amount-paid'),
                'paid_by'     => $this->input->post('paid_by'),
                'is_abono'    => $this->input->post('is_abono'),
                'cheque_no'   => $this->input->post('cheque_no'),
                'gc_no'       => $this->input->post('gift_card_no'),
                'cc_no'       => $this->input->post('pcc_no'),
                'cc_holder'   => $this->input->post('pcc_holder'),
                'cc_month'    => $this->input->post('pcc_month'),
                'cc_year'     => $this->input->post('pcc_year'),
                'cc_type'     => $this->input->post('pcc_type'),
                'note'        => $this->input->post('note'),
                'created_by'  => $this->session->userdata('user_id'),
                'store_id'    => $this->session->userdata('store_id'),
            ];

            if ($_FILES['userfile']['size'] > 0) {
                $this->load->library('upload');
                $config['upload_path']   = 'files/';
                $config['allowed_types'] = $this->digital_file_types;
                #$config['max_size']      = 2048;
                $config['overwrite']     = false;
                $config['encrypt_name']  = true;
                $this->upload->initialize($config);
                if (!$this->upload->do_upload()) {
                    $error = $this->upload->display_errors();
                    $this->session->set_flashdata('error', $error);
                    redirect($_SERVER['HTTP_REFERER']);
                }
                $photo                 = $this->upload->file_name;
                $payment['attachment'] = $photo;
            }

            // $this->tec->print_arrays($payment);
        } elseif ($this->input->post('add_payment')) {
            $this->session->set_flashdata('error', validation_errors());
            $this->tec->dd();
        }

        if ($this->form_validation->run() == true && $id = $this->sales_model->addPayment($payment)) {
            if($ticket){
                echo json_encode([$id]);
            }else{
                $this->session->set_flashdata('message', lang('payment_added'));
                redirect($_SERVER['HTTP_REFERER']);
            }
        } else {
            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $sale                = $this->sales_model->getSaleByID($id);
            $this->data['inv']   = $sale;

            $this->load->view($this->theme . 'sales/add_payment', $this->data);
        }
    }

    public function delete($id = null)
    {
        if (DEMO) {
            $this->session->set_flashdata('error', lang('disabled_in_demo'));
            redirect($_SERVER['HTTP_REFERER'] ?? 'welcome');
        }

        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }

        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('sales');
        }

        if ($this->sales_model->deleteInvoice($id)) {
            $this->session->set_flashdata('message', lang('invoice_deleted'));
            redirect('sales');
        }
    }

    public function delete_holded($id = null)
    {
        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }

        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('sales/opened');
        }

        if ($this->sales_model->deleteOpenedSale($id)) {
            $this->session->set_flashdata('message', lang('opened_bill_deleted'));
            redirect('sales/opened');
        }
    }

    public function delete_payment($id = null)
    {
        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }

        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect($_SERVER['HTTP_REFERER']);
        }

        if ($this->sales_model->deletePayment($id)) {
            $this->session->set_flashdata('message', lang('payment_deleted'));
            redirect('sales');
        }
    }

    public function edit_payment($id = null, $sid = null)
    {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect($_SERVER['HTTP_REFERER']);
        }
        $this->load->helper('security');
        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }

        $this->form_validation->set_rules('amount-paid', lang('amount'), 'required');
        $this->form_validation->set_rules('paid_by', lang('paid_by'), 'required');
        $this->form_validation->set_rules('userfile', lang('attachment'), 'xss_clean');
        if ($this->form_validation->run() == true) {
            $payment = [
                'sale_id'    => $sid,
                'reference'  => $this->input->post('reference'),
                'amount'     => $this->input->post('amount-paid'),
                'paid_by'    => $this->input->post('paid_by'),
                'cheque_no'  => $this->input->post('cheque_no'),
                'gc_no'      => $this->input->post('gift_card_no'),
                'cc_no'      => $this->input->post('pcc_no'),
                'cc_holder'  => $this->input->post('pcc_holder'),
                'cc_month'   => $this->input->post('pcc_month'),
                'cc_year'    => $this->input->post('pcc_year'),
                'cc_type'    => $this->input->post('pcc_type'),
                'note'       => $this->input->post('note'),
                'updated_by' => $this->session->userdata('user_id'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($this->Admin) {
                $payment['date'] = $this->input->post('date');
            }

            if ($_FILES['userfile']['size'] > 0) {
                $this->load->library('upload');
                $config['upload_path']   = 'files/';
                $config['allowed_types'] = $this->digital_file_types;
                #$config['max_size']      = 2048;
                $config['overwrite']     = false;
                $config['encrypt_name']  = true;
                $this->upload->initialize($config);
                if (!$this->upload->do_upload()) {
                    $error = $this->upload->display_errors();
                    $this->session->set_flashdata('error', $error);
                    redirect($_SERVER['HTTP_REFERER']);
                }
                $photo                 = $this->upload->file_name;
                $payment['attachment'] = $photo;
            }

            //$this->tec->print_arrays($payment);
        } elseif ($this->input->post('edit_payment')) {
            $this->session->set_flashdata('error', validation_errors());
            $this->tec->dd();
        }

        if ($this->form_validation->run() == true && $this->sales_model->updatePayment($id, $payment)) {
            $this->session->set_flashdata('message', lang('payment_updated'));
            redirect('sales');
        } else {
            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $payment             = $this->sales_model->getPaymentByID($id);
            if ($payment->paid_by != 'cash') {
                $this->session->set_flashdata('error', lang('only_cash_can_be_edited'));
                $this->tec->dd();
            }
            $this->data['payment'] = $payment;
            $this->load->view($this->theme . 'sales/edit_payment', $this->data);
        }
    }

    public function get_opened_list()
    {
        $this->load->library('datatables');
        if ($this->db->dbdriver == 'sqlite3') {
            $this->datatables->select("id, date, customer_name, hold_ref, (total_items || ' (' || total_quantity || ')') as items, grand_total", false);
        } else {
            $this->datatables->select("id, date, customer_name, hold_ref, CONCAT(total_items, ' (', total_quantity, ')') as items, grand_total", false);
        }
        $this->datatables->from('suspended_sales');
        if (!$this->Admin) {
            $user_id = $this->session->userdata('user_id');
            $this->datatables->where('created_by', $user_id);
        }
        $this->datatables->where('store_id', $this->session->userdata('store_id'));
        $this->datatables->add_column(
            'Actions',
            "<div class='text-center'><div class='btn-group'><a href='" . site_url('pos/?hold=$1') . "' title='" . lang('click_to_add') . "' class='tip btn btn-info btn-xs'><i class='fa fa-th-large'></i></a>
            <a href='" . site_url('sales/delete_holded/$1') . "' onClick=\"return confirm('" . lang('alert_x_holded') . "')\" title='" . lang('delete_sale') . "' class='tip btn btn-danger btn-xs'><i class='fa fa-trash-o'></i></a></div></div>",
            'id'
        )
            ->unset_column('id');

        echo $this->datatables->generate();
    }

    public function get_sales()
    {
        $this->load->library('datatables');
        if ($this->db->dbdriver == 'sqlite3') {
            $this->datatables->select("sales.id, transaction_type, strftime('%Y-%m-%d %H:%M', date) as date, CONCAT(first_name, ' ', last_name) as cashier_name, customer_name, total, total_tax, total_discount, grand_total, paid, status");
        } else {
            $this->datatables->select("sales.id, transaction_type, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date, CONCAT(first_name, ' ', last_name) as cashier_name, customer_name, total, total_tax, total_discount, grand_total, paid, status");
        }
        $this->datatables->join('users', 'users.id = sales.created_by', 'left');
        $this->datatables->from('sales');
        
        if (!$this->Admin && !$this->session->userdata('view_right')) {
            $this->datatables->where('created_by', $this->session->userdata('user_id'));
        }
        $this->datatables->where('sales.store_id', $this->session->userdata('store_id'));

        $actions = "";
        if($this->Admin){
            $actions = "
            <div class='text-center'>
                <div class='btn-group'>
                    <a href='" . site_url('pos/view/$1/1') . "' title='" . lang('view_invoice') . "' class='tip btn btn-primary btn-xs' data-toggle='ajax-modal'>
                        <i class='fa fa-list'></i>
                    </a>
                    <a href='" . site_url('sales/payments/$1') . "' title='" . lang('view_payments') . "' class='tip btn btn-primary btn-xs' data-toggle='ajax'>
                        <i class='fa fa-money'></i>
                    </a>
                    <a href='" . site_url('sales/add_payment/$1') . "' title='" . lang('add_payment') . "' class='tip btn btn-primary btn-xs status-$2' data-toggle='ajax'>
                        <i class='fa fa-briefcase'></i>
                    </a>
                    <a href='" . site_url('pos/?edit=$1&devolution=1') . "' title='Devoluci??n' class='tip btn btn-warning btn-xs'>
                        <i class='fa fa-undo'></i>
                    </a>
                    <a href='" . site_url('pos/?edit=$1') . "' title='" . lang('edit_invoice') . "' class='tip btn btn-warning btn-xs'>
                        <i class='fa fa-edit'></i>
                    </a>
                    <a href='" . site_url('sales/delete/$1') . "' onClick=\"return confirm('" . lang('alert_x_sale') . "')\" title='" . lang('delete_sale') . "' class='tip btn btn-danger btn-xs'>
                        <i class='fa fa-trash-o'></i>
                    </a>
                </div>
            </div>";
        }else{
            $actions = "
            <div class='text-center'>
                <div class='btn-group'>
                    <a href='" . site_url('pos/view/$1/1') . "' title='" . lang('view_invoice') . "' class='tip btn btn-primary btn-xs' data-toggle='ajax-modal'>
                        <i class='fa fa-list'></i>
                    </a>
                    <a href='" . site_url('sales/payments/$1') . "' title='" . lang('view_payments') . "' class='tip btn btn-primary btn-xs' data-toggle='ajax'>
                        <i class='fa fa-money'></i>
                    </a>
                    <a href='" . site_url('pos/?edit=$1&devolution=1') . "' title='Devoluci??n' class='tip btn btn-warning btn-xs'>
                        <i class='fa fa-undo'></i>
                    </a>
                    <a href='" . site_url('sales/add_payment/$1') . "' title='" . lang('add_payment') . "' class='tip btn btn-primary btn-xs status-$2' data-toggle='ajax'>
                        <i class='fa fa-briefcase'></i>
                    </a>
                </div>
            </div>";
        }
        $this->datatables->add_column('Actions', $actions, 'id, status');
        echo $this->datatables->generate();
    }

    public function index()
    {
        $this->data['error']      = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('sales');
        $this->data['transaction_types'] = $this->sales_model->enum_select('tec_sales', 'transaction_type');
        $bc                       = [['link' => '#', 'page' => lang('sales')]];
        $meta                     = ['page_title' => lang('sales'), 'bc' => $bc];
        $this->page_construct('sales/index', $this->data, $meta);
    }

    public function opened()
    {
        $this->data['error']      = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('opened_bills');
        $bc                       = [['link' => '#', 'page' => lang('opened_bills')]];
        $meta                     = ['page_title' => lang('opened_bills'), 'bc' => $bc];
        $this->page_construct('sales/opened', $this->data, $meta);
    }

    public function payment_note($id = null)
    {
        $payment                  = $this->sales_model->getPaymentByID($id);
        $inv                      = $this->sales_model->getSaleByID($payment->sale_id);
        $this->data['customer']   = $this->site->getCompanyByID($inv->customer_id);
        $this->data['inv']        = $inv;
        $this->data['payment']    = $payment;
        $this->data['page_title'] = $this->lang->line('payment_note');

        $this->load->view($this->theme . 'sales/payment_note', $this->data);
    }

    /* -------------------------------------------------------------------------------- */

    public function payments($id = null)
    {
        $this->data['payments'] = $this->sales_model->getSalePayments($id);
        $this->load->view($this->theme . 'sales/payments', $this->data);
    }

    public function get_payments($id = null)
    {
        echo json_encode($this->sales_model->getSalePayments($id));
    }

    public function get_items_sales($id = null)
    {
        echo json_encode($this->sales_model->getallItemSalesByID($id));
    }

    public function status()
    {
        if (!$this->Admin) {
            $this->session->set_flashdata('warning', lang('access_denied'));
            redirect('sales');
        }
        $this->form_validation->set_rules('sale_id', lang('sale_id'), 'required');
        $this->form_validation->set_rules('status', lang('status'), 'required');

        if ($this->form_validation->run() == true) {
            $this->sales_model->updateStatus($this->input->post('sale_id', true), $this->input->post('status', true));
            $this->session->set_flashdata('message', lang('status_updated'));
            redirect('sales');
        } else {
            $this->session->set_flashdata('error', validation_errors());
            redirect('sales');
        }
    }

    public function reports()
    {
        $filtros = $_GET['filtros'];
        $arrayfiltros = explode(",", $filtros);
        $cantidad = 0;
        $importe = 0;
        $sales = [];
        $header = "";
        $table = "";

        if($arrayfiltros[0] == "Reporte de existencia de productos"){
            $sales = $this->sales_model->getProducts($arrayfiltros);
            $header = '
                    <tr class="header" >
                        <td style="">Clave</td>
                        <td style="">Producto</td>
                        <td style="">Precio</td>
                        <td style="">Descuento</td>
                        <td style="">Cantidad</td>
                        <td style="">Importe</td>
                    </tr>
            ';
            for($i=0;$i<=count($sales)-1;$i++){
                $table.='
                    <tr>
                        <td style="text-align:center;">'.$sales[$i]->product_code.'</td>
                        <td style="text-align:center;">'.$sales[$i]->product_name.'</td>
                        <td style="text-align:center;">'.$this->tec->formatMoney($sales[$i]->unit_price).'</td>
                        <td style="text-align:center;">'.$this->tec->formatMoney($sales[$i]->discount).'</td>
                        <td style="text-align:center;">'.$this->tec->formatMoney($sales[$i]->quantity).'</td>
                        <td style="text-align:center;">'.$this->tec->formatMoney($sales[$i]->subtotal).'</td>
                    </tr>
                ';
                $cantidad += floatval($sales[$i]->quantity);
                $importe += floatval($sales[$i]->subtotal);
            }
        }
        
        $html='
            <p>"ITALIA JOYAS"</p> 
            <p>Reporte de Ventas por producto</p>
            <p>De la fecha "'.$arrayfiltros[1].'" a la fecha "'.$arrayfiltros[2].'"</p>
            <hr style="text-align:left;margin-left:0">
            <hr style="text-align:left;margin-left:0">
            
            <table class="blueTable" style="width:100%;text-align:center;">
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

    public function get_sales_refolio()
    {
        $this->load->library('datatables');
        if ($this->db->dbdriver == 'sqlite3') {
            $this->datatables->select("sales.id, invoice, transaction_type, strftime('%Y-%m-%d %H:%M', date) as date, CONCAT(first_name, ' ', last_name) as cashier_name, customer_name, total, total_tax, total_discount, grand_total, paid, status");
        } else {
            $this->datatables->select("sales.id, invoice, transaction_type, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date, CONCAT(first_name, ' ', last_name) as cashier_name, customer_name, total, total_tax, total_discount, grand_total, paid, status");
        }
        $this->datatables->join('users', 'users.id = sales.created_by', 'left');
        $this->datatables->from('sales');
        
        if (!$this->Admin && !$this->session->userdata('view_right')) {
            $this->datatables->where('created_by', $this->session->userdata('user_id'));
        }
        $this->datatables->where('sales.store_id', $this->session->userdata('store_id'));


        $actions = "
            <div class='text-center'>
                <input type='checkbox' title='Agregar al refolio' />
            </div>
        ";
        #$this->datatables->add_column('Actions', $actions, 'id, status, invoice');

        $datatable = (object) json_decode($this->datatables->generate());
        foreach($datatable->data as $row){
            if(!$row->invoice){
                $row->Actions = $actions;
            }else{
                $row->Actions = "";
            }
        }

        echo json_encode($datatable);
    }

    public function refolio()
    {
        $this->data['error']      = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = 'Refolio';
        $this->data['transaction_types'] = $this->sales_model->enum_select('tec_sales', 'transaction_type');
        $bc                       = [['link' => '#', 'page' => 'Refolio']];
        $meta                     = ['page_title' => 'Refolio', 'bc' => $bc];
        $this->page_construct('sales/refolio', $this->data, $meta);
    }
}
