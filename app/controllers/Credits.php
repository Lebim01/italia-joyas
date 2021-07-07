<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Credits extends MY_Controller
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

    public function index()
    {
        $this->data['error']      = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('sales');
        $bc                       = [['link' => '#', 'page' => lang('sales')]];
        $meta                     = ['page_title' => lang('sales'), 'bc' => $bc];
        $this->page_construct('credits/index', $this->data, $meta);
    }

    public function get_credits()
    {
        $this->load->library('datatables');
        if ($this->db->dbdriver == 'sqlite3') {
            $this->datatables->select("id, strftime('%Y-%m-%d %H:%M', date) as date, customer_name, total, total_tax, total_discount, grand_total, paid, status");
        } else {
            $this->datatables->select("id, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date, customer_name, total, total_tax, total_discount, grand_total, paid, status");
        }
        $this->datatables->from('sales');
        if (!$this->Admin && !$this->session->userdata('view_right')) {
            $this->datatables->where('created_by', $this->session->userdata('user_id'));
        }
        $this->datatables->where('store_id', $this->session->userdata('store_id'));
        $this->datatables->add_column('Actions', "<div class='text-center'><div class='btn-group'><a href='" . site_url('pos/view/$1/1') . "' title='" . lang('view_invoice') . "' class='tip btn btn-primary btn-xs' data-toggle='ajax-modal'><i class='fa fa-list'></i></a> <a href='" . site_url('sales/payments/$1') . "' title='" . lang('view_payments') . "' class='tip btn btn-primary btn-xs' data-toggle='ajax'><i class='fa fa-money'></i></a> <a href='" . site_url('sales/add_payment/$1') . "' title='" . lang('add_payment') . "' class='tip btn btn-primary btn-xs' data-toggle='ajax'><i class='fa fa-briefcase'></i></a> <a href='" . site_url('pos/?edit=$1') . "' title='" . lang('edit_invoice') . "' class='tip btn btn-warning btn-xs'><i class='fa fa-edit'></i></a> <a href='" . site_url('sales/delete/$1') . "' onClick=\"return confirm('" . lang('alert_x_sale') . "')\" title='" . lang('delete_sale') . "' class='tip btn btn-danger btn-xs'><i class='fa fa-trash-o'></i></a></div></div>", 'id');

        // $this->datatables->unset_column('id');
        echo $this->datatables->generate();
    }
}
