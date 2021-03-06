<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Pos extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->loggedIn) {
            redirect('login');
        }
        $this->load->helper('pos');
        $this->load->model('pos_model');
        $this->load->model('sales_model');
        $this->load->library('form_validation');
    }

    public function ajaxproducts($category_id = null, $return = null, $in_stock = null)
    {
        if ($this->input->get('category_id') !== null) {
            $category_id = $this->input->get('category_id');
        }
        if ($this->input->get('per_page') == 'n') {
            $page = 0;
        } else {
            $page = $this->input->get('per_page');
        }
        if ($this->input->get('tcp') == 1) {
            $tcp = true;
        } else {
            $tcp = false;
        }
        if ($this->input->get('in_stock') !== null) {
            $in_stock = $this->input->get('in_stock');
        }

        $products = $this->pos_model->fetch_products($category_id, $this->Settings->pro_limit, $page, $in_stock);
        $pro      = 1;
        $prods    = '<div class="items-grid">';
        if ($products) {
            if ($this->Settings->bsty == 1) {
                foreach ($products as $product) {
                    $count = $product->id;
                    if ($count < 10) {
                        $count = '0' . ($count / 100) * 100;
                    }
                    if ($category_id < 10) {
                        $category_id = '0' . ($category_id / 100) * 100;
                    }
                    $prods .= '<button type="button" data-name="' . $product->name . '" id="product-' . $category_id . $count . "\" type=\"button\" value='" . $product->code . "' class=\"btn btn-name btn-default btn-flat product\">" . $product->name . '</button>';
                    $pro++;
                }
            } elseif ($this->Settings->bsty == 2) {
                foreach ($products as $product) {
                    $count = $product->id;
                    if ($count < 10) {
                        $count = '0' . ($count / 100) * 100;
                    }
                    if ($category_id < 10) {
                        $category_id = '0' . ($category_id / 100) * 100;
                    }
                    $prods .= '<button type="button" data-name="' . $product->name . '" id="product-' . $category_id . $count . "\" type=\"button\" value='" . $product->code . "' class=\"btn btn-img btn-flat product\"><img src=\"" . base_url() . 'uploads/thumbs/' . $product->image . '" alt="' . $product->name . '" style="width: 110px; height: 110px;"></button>';
                    $pro++;
                }
            } elseif ($this->Settings->bsty == 3) {
                foreach ($products as $product) {
                    $count = $product->id;
                    if ($count < 10) {
                        $count = '0' . ($count / 100) * 100;
                    }
                    if ($category_id < 10) {
                        $category_id = '0' . ($category_id / 100) * 100;
                    }
                    $prods .= '<button type="button" data-name="' . $product->name . '" id="product-' . $category_id . $count . "\" type=\"button\" value='" . $product->code . "' class=\"btn btn-both btn-flat product\"><span class=\"bg-img\"><img src=\"" . base_url() . 'uploads/thumbs/' . $product->image . '" alt="' . $product->name . '" style="width: 100px; height: 100px;"></span><div><span>' . $product->name . '</span></div></button>';
                    $pro++;
                }
            }
        } else {
            $prods .= '<h4 class="text-center text-info" style="margin-top:50px;">' . lang('category_is_empty') . '</h4>';
        }

        $prods .= '</div>';

        if (!$return) {
            if (!$tcp) {
                echo $prods;
            } else {
                $category_products = $this->pos_model->products_count($category_id, $in_stock);
                header('Content-Type: application/json');
                echo json_encode(['products' => $prods, 'tcp' => $category_products]);
            }
        } else {
            return $prods;
        }
    }

    public function close_register($user_id = null)
    {
        if (!$this->Admin) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->form_validation->set_rules('total_cash', lang('total_cash'), 'trim|required|numeric');
        $this->form_validation->set_rules('total_cheques', lang('total_cheques'), 'trim|required|numeric');
        $this->form_validation->set_rules('total_cc_slips', lang('total_cc_slips'), 'trim|required|numeric');

        if ($this->form_validation->run() == true) {
            if ($this->Admin) {
                $user_register      = $user_id ? $this->pos_model->registerData($user_id) : null;
                $rid                = $user_register ? $user_register->id : $this->session->userdata('register_id');
                $user_id            = $user_register ? $user_register->user_id : $this->session->userdata('user_id');
                $register_open_time = $user_register ? $user_register->date : $this->session->userdata('register_open_time');
                $cash_in_hand       = $user_register ? $user_register->cash_in_hand : $this->session->userdata('cash_in_hand');
                $ccsales            = $this->pos_model->getRegisterCCSales($register_open_time, $user_id);
                $cashsales          = $this->pos_model->getRegisterCashSales($register_open_time, $user_id);
                $expenses           = $this->pos_model->getRegisterExpenses($register_open_time, $user_id);
                $chsales            = $this->pos_model->getRegisterChSales($register_open_time, $user_id);
                $total_cash         = ($cashsales->paid ? ($cashsales->paid + $cash_in_hand) : $cash_in_hand);
                $total_cash -= ($expenses->total ? $expenses->total : 0);
            } else {
                $rid                = $this->session->userdata('register_id');
                $user_id            = $this->session->userdata('user_id');
                $register_open_time = $this->session->userdata('register_open_time');
                $cash_in_hand       = $this->session->userdata('cash_in_hand');
                $ccsales            = $this->pos_model->getRegisterCCSales($register_open_time);
                $cashsales          = $this->pos_model->getRegisterCashSales($register_open_time);
                $expenses           = $this->pos_model->getRegisterExpenses($register_open_time);
                $chsales            = $this->pos_model->getRegisterChSales($register_open_time);
                $total_cash         = ($cashsales->paid ? ($cashsales->paid + $cash_in_hand) : $cash_in_hand);
                $total_cash -= ($expenses->total ? $expenses->total : 0);
            }

            $data = [
                'closed_at'           => date('Y-m-d H:i:s'),
                'total_cash'               => $total_cash,
                'total_cheques'            => $chsales->total_cheques,
                'total_cc_slips'           => $ccsales->total_cc_slips,
                'total_cash_submitted'     => $this->input->post('total_cash_submitted'),
                'total_cheques_submitted'  => $this->input->post('total_cheques_submitted'),
                'total_cc_slips_submitted' => $this->input->post('total_cc_slips_submitted'),
                'note'                     => $this->input->post('note'),
                'status'                   => 'close',
                'transfer_opened_bills'    => $this->input->post('transfer_opened_bills'),
                'closed_by'                => $this->session->userdata('user_id'),
            ];

            // $this->tec->print_arrays($data);
        } elseif ($this->input->post('close_register')) {
            $this->session->set_flashdata('error', (validation_errors() ? validation_errors() : $this->session->flashdata('error')));
            redirect('pos');
        }

        if ($this->form_validation->run() == true && $this->pos_model->closeRegister($rid, $user_id, $data)) {
            $this->session->unset_userdata('register_id');
            $this->session->unset_userdata('cash_in_hand');
            $this->session->unset_userdata('register_open_time');
            $this->session->set_flashdata('message', lang('register_closed'));
            redirect('welcome');
        } else {
            if ($this->Admin) {
                $user_register                    = $user_id ? $this->pos_model->registerData($user_id) : null;
                $register_open_time               = $user_register ? $user_register->date : $this->session->userdata('register_open_time');
                $this->data['cash_in_hand']       = $user_register ? $user_register->cash_in_hand : null;
                $this->data['register_open_time'] = $user_register ? $register_open_time : null;
            } else {
                $register_open_time               = $this->session->userdata('register_open_time');
                $this->data['cash_in_hand']       = null;
                $this->data['register_open_time'] = null;
            }
            $this->data['error']           = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->data['cashinhand']       = $this->pos_model->getRegisterCashSales($register_open_time, $user_id);

            $this->data['ccsales']         = $this->pos_model->getRegisterCCSales($register_open_time, $user_id);
            $this->data['cashsales']       = $this->pos_model->getRegisterCashLiquidate($register_open_time, $user_id);
            $this->data['cashcredits']     = $this->pos_model->getRegisterCashCredits($register_open_time, $user_id);
            $this->data['terminals']       = $this->pos_model->getRegisterCardsPayments($register_open_time, $user_id);
            $this->data['cashaparts']      = $this->pos_model->getRegisterCashAparts($register_open_time, $user_id);
            $this->data['creditaparts']    = $this->pos_model->getRegisterCardAparts($register_open_time, $user_id);
            $this->data['chsales']         = $this->pos_model->getRegisterChSales($register_open_time, $user_id);
            $this->data['other_sales']     = $this->pos_model->getRegisterOtherSales($register_open_time, $user_id);
            $this->data['gcsales']         = $this->pos_model->getRegisterGCSales($register_open_time, $user_id);
            $this->data['stripesales']     = $this->pos_model->getRegisterStripeSales($register_open_time, $user_id);
            $this->data['totalsales']      = $this->pos_model->getRegisterSales($register_open_time, $user_id);
            $this->data['creditsales']     = $this->pos_model->getRegisterCreditSales($register_open_time, $user_id);
            $this->data['apartssales']     = $this->pos_model->getRegisterApartsSales($register_open_time, $user_id);
            $this->data['expenses']        = $this->pos_model->getRegisterExpenses($register_open_time);
            $this->data['users']           = $this->tec->getUsers($user_id);
            $this->data['suspended_bills'] = $this->pos_model->getSuspendedsales($user_id);
            $this->data['aparts']          = $this->pos_model->getRegisterAparts($user_id);
            $this->data['user_id']         = $user_id;
            $this->load->view($this->theme . 'pos/close_register', $this->data);
        }
    }

    public function email_receipt($sale_id = null, $to = null)
    {
        if ($this->input->post('id')) {
            $sale_id = $this->input->post('id');
        }
        if ($this->input->post('email')) {
            $to = $this->input->post('email');
        }
        if (!$sale_id || !$to) {
            die();
        }

        $this->data['error']   = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['message'] = $this->session->flashdata('message');
        $inv                   = $this->pos_model->getSaleByID($sale_id);
        $this->tec->view_rights($inv->created_by);
        $this->load->helper('text');
        $this->data['rows']       = $this->pos_model->getAllSaleItems($sale_id);
        $this->data['customer']   = $this->pos_model->getCustomerByID($inv->customer_id);
        $this->data['inv']        = $inv;
        $this->data['sid']        = $sale_id;
        $this->data['noprint']    = null;
        $this->data['page_title'] = lang('invoice');
        $this->data['modal']      = false;
        $this->data['payments']   = $this->pos_model->getAllSalePayments($sale_id);
        $this->data['created_by'] = $this->site->getUser($inv->created_by);

        $receipt = $this->load->view($this->theme . 'pos/view', $this->data, true);
        $message = preg_replace('#\<!-- start -->(.+)\<!-- end -->#Usi', '', $receipt);
        $subject = lang('email_subject') . ' - ' . $this->Settings->site_name;

        try {
            if ($this->tec->send_email($to, $subject, $message)) {
                echo json_encode(['msg' => lang('email_success')]);
            } else {
                echo json_encode(['msg' => lang('email_failed')]);
            }
        } catch (Exception $e) {
            echo json_encode(['msg' => $e->getMessage()]);
        }
    }

    public function get_product($code = null)
    {
        if ($this->input->get('code')) {
            $code = $this->input->get('code');
        }
        $combo_items = false;
        if ($product = $this->pos_model->getProductByCode($code)) {
            unset($product->cost, $product->details);
            $product->qty             = 1;
            $product->comment         = '';
            $product->discount        = '0';
            $product->price           = $product->store_price > 0 ? $product->store_price : $product->price;
            $product->real_unit_price = $product->price;
            $product->unit_price      = $product->tax ? ($product->price + (($product->price * $product->tax) / 100)) : $product->price;
            if ($product->type == 'combo') {
                $combo_items = $this->pos_model->getComboItemsByPID($product->id);
            }
            echo json_encode(['id' => str_replace('.', '', microtime(true)), 'item_id' => $product->id, 'label' => $product->name . ' (' . $product->code . ')', 'row' => $product, 'combo_items' => $combo_items]);
        } else {
            echo null;
        }
    }

    public function index($sid = null, $eid = null)
    {
        if (!$this->Settings->multi_store) {
            $this->session->set_userdata('store_id', 1);
        }
        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang('please_select_store'));
            redirect($this->Settings->multi_store ? 'stores' : 'welcome');
        }
        if ($this->input->post('devolution') && $this->input->post('devolution') == 1){
            $devolution = true;
        }
        if ($this->input->get('hold')) {
            $sid = $this->input->get('hold');
        }
        if ($this->input->get('edit')) {
            $eid = $this->input->get('edit');
            $eSale = $this->pos_model->getSaleByID($eid);
        }
        if ($this->input->post('eid')) {
            $eid = $this->input->post('eid');
            $eSale = $this->pos_model->getSaleByID($eid);
        }
        if ($this->input->post('did')) {
            $did = $this->input->post('did');
        } else {
            $did = null;
        }

        if ($eid && !$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect($_SERVER['HTTP_REFERER'] ?? 'pos');
        }

        if (!$this->Settings->default_customer) {
            $this->session->set_flashdata('warning', lang('please_update_settings'));
            redirect('settings');
        }

        /* Abrir nuevo caja */
        if (!$this->session->userdata('register_id')) {
            if ($register = $this->pos_model->registerData($this->session->userdata('user_id'))) {
                $register_data = ['register_id' => $register->id, 'cash_in_hand' => $register->cash_in_hand, 'register_open_time' => $register->date];
                $this->session->set_userdata($register_data);
            } else {
                $this->session->set_flashdata('error', lang('register_not_open'));
                redirect('pos/open_register');
            }
        }

        $suspend = $this->input->post('suspend') ? true : false;

        $this->form_validation->set_rules('customer', lang('customer'), 'trim|required');

        if ($this->form_validation->run() == true) {
            $quantity  = 'quantity';
            $product   = 'product';
            $unit_cost = 'unit_cost';
            $tax_rate  = 'tax_rate';

            $date             = $eid ? $this->input->post('date') : date('Y-m-d H:i:s');
            $split_payments   = $this->input->post('split_payments');
            $customer_id      = $this->input->post('customer_id');
            $customer_details = $this->pos_model->getCustomerByID($customer_id);
            $customer         = $customer_details->name;
            $note             = $this->tec->clear_tags($this->input->post('spos_note'));

            $total            = 0;
            $product_tax      = 0;
            $order_tax        = 0;
            $product_discount = 0;
            $order_discount   = 0;
            $percentage       = '%';
            $i                = isset($_POST['product_id']) ? sizeof($_POST['product_id']) : 0;

            for ($r = 0; $r < $i; $r++) {
                $item_id         = $_POST['product_id'][$r];
                $real_unit_price = $this->tec->formatDecimal($_POST['real_unit_price'][$r]);
                $item_quantity   = $_POST['quantity'][$r];
                $item_comment    = $_POST['item_comment'][$r];
                $item_discount   = $_POST['product_discount'][$r] ?? '0';
                $item_code       = $_POST['product_code'][$r];

                $stock = $this->site->getStockByID($item_id);

                /**
                 * Validaci??n de stock disponible (excepto concepto)
                 * Cuando estas editando solo hay que validar si existe la diferencia
                 */

                if ($item_code !== 'Concepto') {
                    if($eid){
                        $productSale = $this->pos_model->getProductSale($eid, $item_id);
                        if($productSale){
                            /**
                             * Este producto se esta editando
                             */
                            $diff = $item_quantity - $productSale->quantity;
                            if($diff > 0){
                                if(intval($stock->available) < intval($diff)){
                                    $this->session->set_flashdata('error', lang('No hay stock suficiente pata el producto ' . $_POST['product_name'][$r]));
                                    redirect($_SERVER['HTTP_REFERER']);
                                }
                            }
                        }else{
                            /**
                             * Este producto se acaba de agregar
                             */
                            if(intval($stock->available) < intval($item_quantity)){
                                $this->session->set_flashdata('error', lang('No hay stock suficiente pata el producto ' . $_POST['product_name'][$r]));
                                redirect($_SERVER['HTTP_REFERER']);
                            }
                        }
                    } else if(intval($stock->available) < intval($item_quantity)){
                        $this->session->set_flashdata('error', lang('No hay stock suficiente pata el producto ' . $_POST['product_name'][$r]));
                        redirect($_SERVER['HTTP_REFERER']);
                    }
                }

                if (isset($item_id) && isset($real_unit_price) && isset($item_quantity)) {
                    $product_details = $this->site->getProductByID($item_id);
                    if ($product_details) {
                        $product_name = $product_details->name;
                        $product_code = $product_details->code;
                        $product_cost = $product_details->cost;
                    } else {
                        $product_name = $_POST['product_name'][$r];
                        $product_code = $_POST['product_code'][$r];
                        $product_cost = 0;
                    }
                    if (!$this->Settings->overselling) {
                        if ($product_details->type == 'standard') {
                            if ($product_details->available < $item_quantity) {
                                $this->session->set_flashdata('error', lang('quantity_low') . ' (' .
                                    lang('name') . ': ' . $product_details->name . ' | ' .
                                    lang('ordered') . ': ' . $item_quantity . ' | ' .
                                    lang('available') . ': ' . $product_details->available .
                                ')');
                                redirect('pos');
                            }
                        } elseif ($product_details->type == 'combo') {
                            $combo_items = $this->pos_model->getComboItemsByPID($product->id);
                            foreach ($combo_items as $combo_item) {
                                $cpr = $this->site->getProductByID($combo_item->id);
                                if ($cpr->available < $item_quantity) {
                                    $this->session->set_flashdata('error', lang('quantity_low') . ' (' .
                                        lang('name') . ': ' . $cpr->name . ' | ' .
                                        lang('ordered') . ': ' . $item_quantity . ' x ' . $combo_item->qty . ' = ' . $item_quantity * $combo_item->qty . ' | ' .
                                        lang('available') . ': ' . $cpr->available .
                                        ') ' . $product_details->name);
                                    redirect('pos');
                                }
                            }
                        }
                    }
                    $unit_price = $real_unit_price;

                    $pr_discount = 0;
                    if (isset($item_discount)) {
                        $discount = $item_discount;
                        $dpos     = strpos($discount, $percentage);
                        if ($dpos !== false) {
                            $pds         = explode('%', $discount);
                            $pr_discount = $this->tec->formatDecimal((($unit_price * (float)($pds[0])) / 100), 4);
                        } else {
                            $pr_discount = $this->tec->formatDecimal($discount);
                        }
                    }
                    $unit_price       = $this->tec->formatDecimal(($unit_price - $pr_discount), 4);
                    $item_net_price   = $unit_price;
                    $pr_item_discount = $this->tec->formatDecimal(($pr_discount * $item_quantity), 4);
                    $product_discount += $pr_item_discount;

                    $pr_item_tax = 0;
                    $item_tax    = 0;
                    $tax         = '';
                    if (isset($product_details->tax) && $product_details->tax != 0) {
                        if ($product_details && $product_details->tax_method == 1) {
                            $item_tax = $this->tec->exlusiveTax($unit_price, $product_details->tax);
                            $tax      = $product_details->tax . '%';
                        } else {
                            $item_tax = $this->tec->inclusiveTax($unit_price, $product_details->tax);
                            $tax      = $product_details->tax . '%';
                            $item_net_price -= $item_tax;
                        }

                        $pr_item_tax = $this->tec->formatDecimal(($item_tax * $item_quantity), 4);
                    }

                    $product_tax += $pr_item_tax;
                    $subtotal = $this->tec->formatDecimal((($item_net_price * $item_quantity) + $pr_item_tax), 4);

                    $products[] = [
                        'product_id'      => $item_id,
                        'quantity'        => $item_quantity,
                        'unit_price'      => $unit_price,
                        'net_unit_price'  => $item_net_price,
                        'discount'        => $item_discount,
                        'comment'         => $item_comment,
                        'item_discount'   => $pr_item_discount,
                        'tax'             => $tax,
                        'item_tax'        => $pr_item_tax,
                        'subtotal'        => $subtotal,
                        'real_unit_price' => $real_unit_price,
                        'cost'            => $product_cost,
                        'product_code'    => $product_code,
                        'product_name'    => $product_name,
                    ];

                    $total += $this->tec->formatDecimal(($item_net_price * $item_quantity), 4);
                }
            }

            /**
             * Validar si es una venta a credito no exceda el limite de credito
             */
            if($this->input->post('transaction_type') === 'credit'){
                $this->load->model('customers_model');
                $available = $this->customers_model->getAvailableCredit($customer_id);

                if($total > $available){
                    $this->session->set_flashdata('error', "El cliente <b>$customer</b> no cuenta con el cr??dito disponible suficiente para realizar esta compra, disponible: $ {$available}");
                    redirect($_SERVER['HTTP_REFERER']);
                }
            }

            /**
             * Validar que en la validaci??n solo se pueden devolver productos que cuesten lo mismo o mas
             */
            if(isset($devolution) && $devolution){
                if($total < $eSale->grand_total){
                    $this->session->set_flashdata('error', "La devoluci??n solo puede ser un articulo del mismo o mayor precio");
                    redirect($_SERVER['HTTP_REFERER']);
                }
            }

            if (empty($products)) {
                $this->form_validation->set_rules('product', lang('order_items'), 'required');
            } else {
                krsort($products);
            }

            if ($this->input->post('order_discount')) {
                $order_discount_id = $this->input->post('order_discount');
                $opos              = strpos($order_discount_id, $percentage);
                if ($opos !== false) {
                    $ods            = explode('%', $order_discount_id);
                    $order_discount = $this->tec->formatDecimal(((($total + $product_tax) * (float)($ods[0])) / 100), 4);
                } else {
                    $order_discount = $this->tec->formatDecimal($order_discount_id);
                }
            } else {
                $order_discount_id = null;
            }
            $total_discount = $this->tec->formatDecimal(($order_discount + $product_discount), 4);

            if ($this->input->post('order_tax')) {
                $order_tax_id = $this->input->post('order_tax');
                $opos         = strpos($order_tax_id, $percentage);
                if ($opos !== false) {
                    $ots       = explode('%', $order_tax_id);
                    $order_tax = $this->tec->formatDecimal(((($total + $product_tax - $order_discount) * (float)($ots[0])) / 100), 4);
                } else {
                    $order_tax = $this->tec->formatDecimal($order_tax_id);
                }
            } else {
                $order_tax_id = null;
                $order_tax    = 0;
            }

            $datametodos = [
                'metodos'           => $this->input->post('metodos'),
                'cantidad'          => $this->input->post('cantidad'),
                'bancos'            => $this->input->post('bancos')
            ];

            $metodos = explode(",", $datametodos["metodos"]);
            $cantidad = explode(",", $datametodos["cantidad"]);
            $bancos = explode(",", $datametodos["bancos"]);
            $payment = array();
            $paid = 0.0;

            for ($r = 0; $r < count($cantidad); $r++) {
                $paid = $paid + (float) $cantidad[$r];
            }

            $total_tax   = $this->tec->formatDecimal(($product_tax + $order_tax), 4);
            $grand_total = $this->tec->formatDecimal(($total + $total_tax - $order_discount), 4);

            /**
             * Add extra discount
             */
            if($this->input->post('extra_discount')){
                $extra_discount_input = $this->input->post('extra_discount');
                if(strpos($extra_discount_input, '%') !== false){
                    $extra_discount = $grand_total * ((float) str_replace("%", "", $extra_discount_input)/100);
                }else{
                    $extra_discount = (float) $extra_discount_input;
                }

                $grand_total = $this->tec->formatDecimal(($total + $total_tax - $order_discount - $extra_discount), 4);
                $total_discount += $extra_discount;
            }else{
                $extra_discount = 0;
            }

            //$paid        = $this->input->post('amount') ? $this->input->post('amount') : 0;
            $round_total = $this->tec->roundNumber($grand_total, $this->Settings->rounding);
            $rounding    = $this->tec->formatDecimal(($round_total - $grand_total));
            
            $status = 'partial';
            if ($this->tec->formatDecimal($round_total) <= $this->tec->formatDecimal($paid)) {
                $status = 'paid';
            } elseif ($this->tec->formatDecimal($round_total) > $this->tec->formatDecimal($paid) && $paid > 0) {
                $status = 'partial';
            }

            $totalpagos = $this->input->post('total_pagos');

            $data = [
                'date'              => $date,
                'customer_id'       => $customer_id,
                'customer_name'     => $customer,
                'total'             => $this->tec->formatDecimal($total, 4),
                'product_discount'  => $this->tec->formatDecimal($product_discount, 4),
                'order_discount_id' => $order_discount_id,
                'order_discount'    => $order_discount,
                'total_discount'    => $total_discount,
                'extra_discount_id' => $this->input->post('extra_discount'),
                'extra_discount'    => $extra_discount,
                'product_tax'       => $this->tec->formatDecimal($product_tax, 4),
                'order_tax_id'      => $order_tax_id,
                'order_tax'         => $order_tax,
                'total_tax'         => $total_tax,
                'grand_total'       => $grand_total,
                'total_items'       => $this->input->post('total_items'),
                'total_quantity'    => $this->input->post('total_quantity'),
                'rounding'          => $rounding,
                'paid'              => $paid,
                'status'            => $status,
                'created_by'        => $this->input->post('created_by'),
                'note'              => $note,
                'hold_ref'          => $this->input->post('hold_ref'),
                'transaction_type'  => $this->input->post('transaction_type'),
                'split_payments'    => $split_payments,
                'delivered'         => $this->input->post('transaction_type') == 'apart' ? 0 : 1
            ];

            if($eid){
                $data['transaction_type'] = $eSale->transaction_type;
                $data['created_by'] = $eSale->created_by;
            }

            if (!$eid) {
                $data['store_id'] = $this->session->userdata('store_id');
            }

            if (!$eid && !$suspend) {

                if ($this->input->post('paying_gift_card_no')) {
                    $gc = $this->pos_model->getGiftCardByNO($this->input->post('paying_gift_card_no'));
                    if (!$gc || $gc->balance < $amount) {
                        $this->session->set_flashdata('error', lang('incorrect_gift_card'));
                        redirect('pos');
                    }
                }
                $amount  = $this->tec->formatDecimal(($paid > $grand_total ? ($paid - $grand_total) : $paid), 4);

                $data['paid'] = $amount;

                for ($r = 0; $r < count($metodos); $r++) {
                    $payment[$r] = [
                        'date'        => $date,
                        'amount'      => $cantidad[$r],
                        'banks'       => $bancos[$r],
                        'customer_id' => $customer_id,
                        'paid_by'     => $metodos[$r],
                        'cheque_no'   => $this->input->post('cheque_no'),
                        'cc_no'       => $this->input->post('cc_no'),
                        'gc_no'       => $this->input->post('paying_gift_card_no'),
                        'cc_holder'   => $this->input->post('cc_holder'),
                        'cc_month'    => $this->input->post('cc_month'),
                        'cc_year'     => $this->input->post('cc_year'),
                        'cc_type'     => $this->input->post('cc_type'),
                        'cc_cvv2'     => $this->input->post('cc_cvv2'),
                        'created_by'  => $this->input->post('created_by'),
                        'store_id'    => $this->session->userdata('store_id'),
                        'note'        => $this->input->post('payment_note'),
                        'pos_paid'    => $this->tec->formatDecimal($this->input->post('amount'), 4),
                        'pos_balance' => $this->tec->formatDecimal($this->input->post('balance_amount'), 4),
                    ];
                }
            } else {
                $payment = [];
            }
        }

        if ($this->form_validation->run() == true && !empty($products)) {
            if ($suspend) {
                unset($data['status'], $data['rounding'], $data['transaction_type'], $data['delivered']);
                if ($this->pos_model->suspendSale($data, $products, $did)) {
                    $this->session->set_userdata('rmspos', 1);
                    $this->session->set_flashdata('message', lang('sale_saved_to_opened_bill'));
                    redirect('pos');
                } else {
                    $this->session->set_flashdata('error', lang('action_failed'));
                    redirect('pos/' . $did);
                }
            } elseif ($eid) {
                unset($data['status'], $data['paid']);
                if (!$this->Admin) {
                    unset($data['date']);
                }
                $data['updated_at'] = date('Y-m-d H:i:s');
                $data['updated_by'] = $this->session->userdata('user_id');
                if ($this->pos_model->updateSale($eid, $data, $products, $devolution)) {
                    $this->session->set_userdata('rmspos', 1);
                    $this->session->set_flashdata('message', lang('sale_updated'));
                    redirect('sales');
                } else {
                    $this->session->set_flashdata('error', lang('action_failed'));
                    redirect('pos/?edit=' . $eid);
                }
            } else {
                if ($sale = $this->pos_model->addSale($data, $products, $payment, $did)) {
                    $this->session->set_userdata('rmspos', 1);
                    $msg = lang('sale_added');
                    if (!empty($sale['message'])) {
                        foreach ($sale['message'] as $m) {
                            $msg .= '<br>' . $m;
                        }
                    }
                    $this->session->set_flashdata('message', $msg);
                    $redirect_to = $this->Settings->after_sale_page ? 'pos' : 'pos/view/' . $sale['sale_id'];
                    if ($this->Settings->auto_print) {
                        if (!$this->Settings->remote_printing) {
                            $this->print_receipt($sale['sale_id'], true);
                        } elseif ($this->Settings->remote_printing == 2) {
                            $redirect_to .= '?print=' . $sale['sale_id'];
                        }
                    }
                    redirect($redirect_to);
                } else {
                    $this->session->set_flashdata('error', lang('action_failed'));
                    redirect('pos');
                }
            }
        } else {
            if (isset($sid) && !empty($sid)) {
                $suspended_sale = $this->pos_model->getSuspendedSaleByID($sid);
                $inv_items      = $this->pos_model->getSuspendedSaleItems($sid);
                krsort($inv_items);
                $c = rand(100000, 9999999);
                foreach ($inv_items as $item) {
                    $row = $this->site->getProductByID($item->product_id);
                    if (!$row) {
                        $row       = json_decode('{}');
                        $row->id   = 0;
                        $row->code = $item->product_code;
                        $row->name = $item->product_name;
                        $row->tax  = 0;
                    }
                    $row->price           = $item->net_unit_price + ($item->item_discount / $item->quantity);
                    $row->unit_price      = $item->unit_price     + ($item->item_discount / $item->quantity)     + ($item->item_tax / $item->quantity);
                    $row->real_unit_price = $item->real_unit_price;
                    $row->discount        = $item->discount;
                    $row->qty             = $item->quantity;
                    $row->comment         = $item->comment;
                    $row->ordered         = $item->quantity;
                    $combo_items          = false;
                    $ri                   = $this->Settings->item_addition ? $row->id : $c;
                    $pr[$ri]              = ['id' => $c, 'item_id' => $row->id, 'label' => $row->name . ' (' . $row->code . ')', 'row' => $row, 'combo_items' => $combo_items];
                    $c++;
                }
                $this->data['items']        = json_encode($pr);
                $this->data['sid']          = $sid;
                $this->data['suspend_sale'] = $suspended_sale;
                $this->data['message']      = lang('suspended_sale_loaded');
            }

            if (isset($eid) && !empty($eid)) {
                $sale      = $this->pos_model->getSaleByID($eid);
                $inv_items = $this->pos_model->getAllSaleItems($eid);
                krsort($inv_items);
                $c = rand(100000, 9999999);
                foreach ($inv_items as $item) {
                    $row = $this->site->getProductByID($item->product_id);
                    if (!$row) {
                        if($item->product_code === 'Concepto'){
                            $row = (object)[
                                'name' => $item->product_name,
                                'code' => 'Concepto',
                                'id' => $item->product_id,
                                'type' => 'concept',
                                'quantity' => $item->quantity,
                                'unit_price' => $item->unit_price,
                                'real_unit_price' => $item->real_unit_price
                            ];
                        }else{
                            $row = json_decode('{}');
                        }
                    }
                    $row->price           = $item->net_unit_price;
                    $row->unit_price      = $item->unit_price;
                    $row->real_unit_price = $item->real_unit_price;
                    $row->discount        = $item->discount;
                    $row->qty             = $item->quantity;
                    $row->comment         = $item->comment;
                    $combo_items          = false;
                    $row->quantity += $item->quantity;
                    if ($row->type == 'combo') {
                        $combo_items = $this->pos_model->getComboItemsByPID($row->id);
                        foreach ($combo_items as $combo_item) {
                            $combo_item->quantity += ($combo_item->qty * $item->quantity);
                        }
                    }
                    $ri      = $this->Settings->item_addition ? $row->id : $c;
                    $pr[$ri] = ['id' => $c, 'item_id' => $row->id, 'label' => $row->name . ' (' . $row->code . ')', 'row' => $row, 'combo_items' => $combo_items];
                    $c++;
                }
                $this->data['items']   = json_encode($pr);
                $this->data['eid']     = $eid;
                $this->data['sale']    = $sale;
                $this->data['message'] = lang('sale_loaded');
            }

            /**
             * Validar que la caja que esta abierta sigue valida (solo vigente el mismo dia)
             * Obligarlo a cerrarla para volver a abrirla
             */
            $datetime1 = new DateTime(substr($this->session->userdata('register_open_time'), 0, 10));
            $datetime2 = new DateTime(date('Y-m-d'));
            $interval = $datetime1->diff($datetime2);
            if($this->session->userdata('register_id') && $interval->days > 0){
                $this->session->set_flashdata('error', 'Este corte de caja es de un dia pasado, debe ser cerrado primero antes de abrir otro');
                $this->data['obligate_close'] = true;
            }

            $this->data['error']           = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
            $this->data['reference_note']  = isset($sid) && !empty($sid) ? $suspended_sale->hold_ref : (isset($eid) && !empty($eid) ? $sale->hold_ref : null);
            $this->data['sid']             = isset($sid) && !empty($sid) ? $sid : 0;
            $this->data['eid']             = isset($eid) && !empty($eid) ? $eid : 0;
            $this->data['customers']       = $this->site->getAllCustomers();
            $this->data['cashiers']        = $this->pos_model->getAllCashiers();
            $this->data['tcp']             = $this->pos_model->products_count($this->Settings->default_category, $this->Settings->default_in_stock);
            $this->data['products']        = $this->ajaxproducts($this->Settings->default_category, 1, $this->Settings->default_in_stock);
            $this->data['categories']      = $this->site->getAllCategories();
            $this->data['message']         = $this->session->flashdata('message');
            $this->data['suspended_sales'] = $this->site->getUserSuspenedSales();
            $this->data['apartsOrders']    = $this->sales_model->getAllApartsSales();
            $this->data['creditsClients']  = $this->sales_model->getAllCreditsClients();
            $this->data['user_id']         = $this->session->userdata('user_id');
            $this->data['store']           = $this->site->getStoreByID($this->session->userdata('store_id'));
            $this->data['stores']          = $this->site->getAllStores();

            $this->data['printer'] = $this->site->getPrinterByID($this->Settings->printer);
            $printers              = [];
            if (!empty($order_printers = json_decode($this->Settings->order_printers))) {
                foreach ($order_printers as $printer_id) {
                    $printers[] = $this->site->getPrinterByID($printer_id);
                }
            }
            $this->data['order_printers'] = $printers;

            if ($saleid = $this->input->get('print', true)) {
                if ($inv = $this->pos_model->getSaleByID($saleid)) {
                    if ($this->session->userdata('store_id') != $inv->store_id) {
                        $this->session->set_flashdata('error', lang('access_denied'));
                        redirect('pos');
                    }
                    $this->tec->view_rights($inv->created_by, false, 'pos');
                    $this->load->helper('text');
                    $this->data['rows']       = $this->pos_model->getAllSaleItems($saleid);
                    $this->data['customer']   = $this->pos_model->getCustomerByID($inv->customer_id);
                    $this->data['store']      = $this->site->getStoreByID($inv->store_id);
                    $this->data['inv']        = $inv;
                    $this->data['print']      = $saleid;
                    $this->data['payments']   = $this->pos_model->getAllSalePayments($saleid);
                    $this->data['created_by'] = $this->site->getUser($inv->created_by);
                }
            }

            $this->data['page_title'] = lang('pos');
            $bc                       = [['link' => '#', 'page' => lang('pos')]];
            $meta                     = ['page_title' => lang('pos'), 'bc' => $bc];
            $this->load->view($this->theme . 'pos/index', $this->data, $meta);
        }
    }

    public function language($lang = false)
    {
        if ($this->input->get('lang')) {
            $lang = $this->input->get('lang');
        }
        //$this->load->helper('cookie');
        $folder        = 'app/language/';
        $languagefiles = scandir($folder);
        if (in_array($lang, $languagefiles)) {
            $cookie = [
                'name'   => 'language',
                'value'  => $lang,
                'expire' => '31536000',
                'prefix' => 'spos_',
                'secure' => false,
            ];

            $this->input->set_cookie($cookie);
        }
        redirect($_SERVER['HTTP_REFERER']);
    }

    public function open_drawer()
    {
        $printer = $this->site->getPrinterByID($this->Settings->printer);
        $this->load->library('escpos');
        $this->escpos->load($printer);
        $this->escpos->open_drawer();
    }

    public function open_register()
    {
        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang('please_select_store'));
            redirect('stores');
        }
        $this->form_validation->set_rules('cash_in_hand', lang('cash_in_hand'), 'trim|required|numeric');

        if ($this->form_validation->run() == true) {
            $data = [
                'date'    => date('Y-m-d H:i:s'),
                'cash_in_hand' => $this->input->post('cash_in_hand'),
                'user_id'      => $this->session->userdata('user_id'),
                'store_id'     => $this->session->userdata('store_id'),
                'status'       => 'open',
            ];
        }
        if ($this->form_validation->run() == true && $this->pos_model->openRegister($data)) {
            $this->session->set_flashdata('message', lang('welcome_to_pos'));
            redirect('pos');
        } else {
            $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');

            $bc   = [['link' => base_url(), 'page' => lang('home')], ['link' => '#', 'page' => lang('open_register')]];
            $meta = ['page_title' => lang('open_register'), 'bc' => $bc];
            $this->page_construct('pos/open_register', $this->data, $meta);
        }
    }

    public function p($bo = 'order')
    {
        $date             = date('Y-m-d H:i:s');
        $customer_id      = $this->input->post('customer_id');
        $customer_details = $this->pos_model->getCustomerByID($customer_id);
        $customer         = $customer_details->name;
        $note             = $this->tec->clear_tags($this->input->post('spos_note'));

        $total            = 0;
        $product_tax      = 0;
        $order_tax        = 0;
        $product_discount = 0;
        $order_discount   = 0;
        $percentage       = '%';
        $i                = isset($_POST['product_id']) ? sizeof($_POST['product_id']) : 0;
        for ($r = 0; $r < $i; $r++) {
            $item_id         = $_POST['product_id'][$r];
            $real_unit_price = $this->tec->formatDecimal($_POST['real_unit_price'][$r]);
            $item_quantity   = $_POST['quantity'][$r];
            $item_comment    = $_POST['item_comment'][$r];
            $item_ordered    = $_POST['item_was_ordered'][$r];
            $item_discount   = $_POST['product_discount'][$r] ?? '0';

            if (isset($item_id) && isset($real_unit_price) && isset($item_quantity)) {
                $product_details = $this->site->getProductByID($item_id);
                if ($product_details) {
                    $product_name = $product_details->name;
                    $product_code = $product_details->code;
                    $product_cost = $product_details->cost;
                } else {
                    $product_name = $_POST['product_name'][$r];
                    $product_code = $_POST['product_code'][$r];
                    $product_cost = 0;
                }
                if (!$this->Settings->overselling) {
                    if ($product_details->type == 'standard') {
                        if ($product_details->quantity < $item_quantity) {
                            $this->session->set_flashdata('error', lang('quantity_low') . ' (' .
                                lang('name') . ': ' . $product_details->name . ' | ' .
                                lang('ordered') . ': ' . $item_quantity . ' | ' .
                                lang('available') . ': ' . $product_details->quantity .
                                ')');
                            redirect('pos');
                        }
                    } elseif ($product_details->type == 'combo') {
                        $combo_items = $this->pos_model->getComboItemsByPID($product->id);
                        foreach ($combo_items as $combo_item) {
                            $cpr = $this->site->getProductByID($combo_item->id);
                            if ($cpr->quantity < $item_quantity) {
                                $this->session->set_flashdata('error', lang('quantity_low') . ' (' .
                                    lang('name') . ': ' . $cpr->name . ' | ' .
                                    lang('ordered') . ': ' . $item_quantity . ' x ' . $combo_item->qty . ' = ' . $item_quantity * $combo_item->qty . ' | ' .
                                    lang('available') . ': ' . $cpr->quantity .
                                    ') ' . $product_details->name);
                                redirect('pos');
                            }
                        }
                    }
                }
                $unit_price = $real_unit_price;

                $pr_discount = 0;
                if (isset($item_discount)) {
                    $discount = $item_discount;
                    $dpos     = strpos($discount, $percentage);
                    if ($dpos !== false) {
                        $pds         = explode('%', $discount);
                        $pr_discount = $this->tec->formatDecimal((($unit_price * (float)($pds[0])) / 100), 4);
                    } else {
                        $pr_discount = $this->tec->formatDecimal($discount);
                    }
                }
                $unit_price       = $this->tec->formatDecimal(($unit_price - $pr_discount), 4);
                $item_net_price   = $unit_price;
                $pr_item_discount = $this->tec->formatDecimal(($pr_discount * $item_quantity), 4);
                $product_discount += $pr_item_discount;

                $pr_item_tax = 0;
                $item_tax    = 0;
                $tax         = '';
                if (isset($product_details->tax) && $product_details->tax != 0) {
                    if ($product_details && $product_details->tax_method == 1) {
                        $item_tax = $this->tec->formatDecimal(((($unit_price) * $product_details->tax) / 100), 4);
                        $tax      = $product_details->tax . '%';
                    } else {
                        $item_tax = $this->tec->formatDecimal(((($unit_price) * $product_details->tax) / (100 + $product_details->tax)), 4);
                        $tax      = $product_details->tax . '%';
                        $item_net_price -= $item_tax;
                    }

                    $pr_item_tax = $this->tec->formatDecimal(($item_tax * $item_quantity), 4);
                }

                $product_tax += $pr_item_tax;
                $subtotal = (($item_net_price * $item_quantity) + $pr_item_tax);

                $products[] = (object) [
                    'product_id'      => $item_id,
                    'quantity'        => $item_quantity,
                    'unit_price'      => $unit_price,
                    'net_unit_price'  => $item_net_price,
                    'discount'        => $item_discount,
                    'comment'         => $item_comment,
                    'item_discount'   => $pr_item_discount,
                    'tax'             => $tax,
                    'item_tax'        => $pr_item_tax,
                    'subtotal'        => $subtotal,
                    'real_unit_price' => $real_unit_price,
                    'cost'            => $product_cost,
                    'product_code'    => $product_code,
                    'product_name'    => $product_name,
                    'ordered'         => $item_ordered,
                ];

                $total += $item_net_price * $item_quantity;
            }
        }
        if (empty($products)) {
            $this->form_validation->set_rules('product', lang('order_items'), 'required');
        } else {
            krsort($products);
        }

        if ($this->input->post('order_discount')) {
            $order_discount_id = $this->input->post('order_discount');
            $opos              = strpos($order_discount_id, $percentage);
            if ($opos !== false) {
                $ods            = explode('%', $order_discount_id);
                $order_discount = $this->tec->formatDecimal(((($total + $product_tax) * (float)($ods[0])) / 100), 4);
            } else {
                $order_discount = $this->tec->formatDecimal($order_discount_id);
            }
        } else {
            $order_discount_id = null;
        }
        $total_discount = $this->tec->formatDecimal(($order_discount + $product_discount), 4);

        if ($this->input->post('order_tax')) {
            $order_tax_id = $this->input->post('order_tax');
            $opos         = strpos($order_tax_id, $percentage);
            if ($opos !== false) {
                $ots       = explode('%', $order_tax_id);
                $order_tax = $this->tec->formatDecimal(((($total + $product_tax - $order_discount) * (float)($ots[0])) / 100), 4);
            } else {
                $order_tax = $this->tec->formatDecimal($order_tax_id);
            }
        } else {
            $order_tax_id = null;
            $order_tax    = 0;
        }

        $total_tax   = $this->tec->formatDecimal(($product_tax + $order_tax), 4);
        $grand_total = $this->tec->formatDecimal(($this->tec->formatDecimal($total) + $total_tax - $order_discount), 4);
        $paid        = 0;
        $round_total = $this->tec->roundNumber($grand_total, $this->Settings->rounding);
        $rounding    = $this->tec->formatDecimal(($round_total - $grand_total));

        $data = (object) [
            'date' => $date,
            'customer_id'        => $customer_id,
            'customer_name'      => $customer,
            'total'              => $this->tec->formatDecimal($total),
            'product_discount'   => $this->tec->formatDecimal($product_discount, 4),
            'order_discount_id'  => $order_discount_id,
            'order_discount'     => $order_discount,
            'total_discount'     => $total_discount,
            'product_tax'        => $this->tec->formatDecimal($product_tax, 4),
            'order_tax_id'       => $order_tax_id,
            'order_tax'          => $order_tax,
            'total_tax'          => $total_tax,
            'grand_total'        => $grand_total,
            'total_items'        => $this->input->post('total_items'),
            'total_quantity'     => $this->input->post('total_quantity'),
            'rounding'           => $rounding,
            'paid'               => $paid,
            'created_by'         => $this->session->userdata('user_id'),
            'note'               => $note,
            'hold_ref'           => $this->input->post('hold_ref'),
        ];

        // $this->tec->print_arrays($data, $products);
        $store      = $this->site->getStoreByID($this->session->userdata('store_id'));
        $created_by = $this->site->getUser($this->session->userdata('user_id'));

        if ($bo == 'bill') {
            $printer = $this->site->getPrinterByID($this->Settings->printer);
            $this->load->library('escpos');
            $this->escpos->load($printer);
            $this->escpos->print_receipt($store, $data, $products, false, $created_by, false, true);
        } else {
            $order_printers = json_decode($this->Settings->order_printers);
            $this->load->library('escpos');
            foreach ($order_printers as $printer_id) {
                $printer = $this->site->getPrinterByID($printer_id);
                $this->escpos->load($printer);
                $this->escpos->print_order($store, $data, $products, $created_by);
            }
        }
    }

    public function print_receipt($id, $open_drawer = false)
    {
        $sale       = $this->pos_model->getSaleByID($id);
        $items      = $this->pos_model->getAllSaleItems($id);
        $payments   = $this->pos_model->getAllSalePayments($id);
        $store      = $this->site->getStoreByID($sale->store_id);
        $created_by = $this->site->getUser($sale->created_by);
        $printer    = $this->site->getPrinterByID($this->Settings->printer);
        $this->load->library('escpos');
        $this->escpos->load($printer);
        $this->escpos->print_receipt($store, $sale, $items, $payments, $created_by, $open_drawer);
    }

    public function print_register($re = null)
    {
        if ($this->session->userdata('register_id')) {
            $register    = $this->pos_model->registerData();
            $ccsales     = $this->pos_model->getRegisterCCSales();
            $cashsales   = $this->pos_model->getRegisterCashSales();
            $chsales     = $this->pos_model->getRegisterChSales();
            $other_sales = $this->pos_model->getRegisterOtherSales();
            $gcsales     = $this->pos_model->getRegisterGCSales();
            $stripesales = $this->pos_model->getRegisterStripeSales();
            $totalsales  = $this->pos_model->getRegisterSales();
            $expenses    = $this->pos_model->getRegisterExpenses();
            $user        = $this->site->getUser();

            $total_cash = $cashsales->paid ? ($cashsales->paid + $register->cash_in_hand) : $register->cash_in_hand;
            $total_cash -= ($expenses->total ? $expenses->total : 0);
            $info = [
                (object) ['label' => lang('opened_at'), 'value' => $this->tec->hrld($register->date)],
                (object) ['label' => lang('cash_in_hand'), 'value' => $register->cash_in_hand],
                (object) ['label' => lang('user'), 'value' => $user->first_name . ' ' . $user->last_name . ' (' . $user->email . ')'],
                (object) ['label' => lang('printed_at'),  'value' => $this->tec->hrld(date('Y-m-d H:i:s'))],
            ];

            $reg_totals = [
                (object) ['label' => lang('cash_sale'), 'value' => $this->tec->formatMoney($cashsales->paid ? $cashsales->paid : '0.00') . ' (' . $this->tec->formatMoney($cashsales->total ? $cashsales->total : '0.00') . ')'],
                (object) ['label' => lang('ch_sale'), 'value' => $this->tec->formatMoney($chsales->paid ? $chsales->paid : '0.00') . ' (' . $this->tec->formatMoney($chsales->total ? $chsales->total : '0.00') . ')'],
                (object) ['label' => lang('gc_sale'),  'value' => $this->tec->formatMoney($gcsales->paid ? $gcsales->paid : '0.00') . ' (' . $this->tec->formatMoney($gcsales->total ? $gcsales->total : '0.00') . ')'],
                (object) ['label' => lang('cc_sale'),  'value' => $this->tec->formatMoney($ccsales->paid ? $ccsales->paid : '0.00') . ' (' . $this->tec->formatMoney($ccsales->total ? $ccsales->total : '0.00') . ')'],
                (object) ['label' => lang('stripe'),  'value' => $this->tec->formatMoney($stripesales->paid ? $stripesales->paid : '0.00') . ' (' . $this->tec->formatMoney($stripesales->total ? $stripesales->total : '0.00') . ')'],
                (object) ['label' => lang('other_sale'),  'value' => $this->tec->formatMoney($other_sales->paid ? $other_sales->paid : '0.00') . ' (' . $this->tec->formatMoney($other_sales->total ? $other_sales->total : '0.00') . ')'],
                (object) ['label' => 'line',  'value' => ''],
                (object) ['label' => lang('total_sales'),  'value' => $this->tec->formatMoney($totalsales->paid ? $totalsales->paid : '0.00') . ' (' . $this->tec->formatMoney($totalsales->total ? $totalsales->total : '0.00') . ')'],
                (object) ['label' => lang('cash_in_hand'),  'value' => $this->tec->formatMoney($register->cash_in_hand)],
                (object) ['label' => lang('expenses'),  'value' => $this->tec->formatMoney($expenses->total ? $expenses->total : '0.00')],
                (object) ['label' => 'line',  'value' => ''],
                (object) ['label' => lang('total_cash'),  'value' => $this->tec->formatMoney($total_cash)],
            ];

            $data = (object) [
                'printer' => $this->Settings->local_printers ? '' : json_encode($printer),
                'logo'    => !empty($store->logo) ? base_url('uploads/' . $store->logo) : '',
                'heading' => lang('register_details'),
                'info'    => $info,
                'totals'  => $reg_totals,
            ];

            // $this->tec->print_arrays($data);
            if ($re == 1) {
                return $data;
            } elseif ($re == 2) {
                echo json_encode($data);
            } else {
                $printer = $this->site->getPrinterByID($this->Settings->printer);
                $this->load->library('escpos');
                $this->escpos->load($printer);
                $this->escpos->print_data($data);
                echo json_encode(true);
            }
        } else {
            echo json_encode(false);
        }
    }

    public function promotions()
    {
        $this->load->view($this->theme . 'promotions', $this->data);
    }

    public function receipt_img()
    {
        $data     = $this->input->post('img', true);
        $filename = date('Y-m-d-H-i-s-') . uniqid() . '.png';
        $cd       = !empty($this->input->post('cd')) ? true : false;
        $imgData  = str_replace(' ', '+', $data);
        $imgData  = base64_decode($imgData);
        file_put_contents('files/receipts/' . $filename, $imgData);
        $printer = $this->site->getPrinterByID($this->Settings->printer);
        $this->load->library('escpos');
        $this->escpos->load($printer);
        $this->escpos->print_img($filename, $cd);
        echo 'Printed Image  files/receipts/' . $filename;
        exit;
    }

    public function register_details()
    {
        $register_open_time        = $this->session->userdata('register_open_time');
        $this->data['error']       = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['ccsales']     = $this->pos_model->getRegisterCCSales($register_open_time);
        $this->data['cashsales']   = $this->pos_model->getRegisterCashSales($register_open_time);
        $this->data['chsales']     = $this->pos_model->getRegisterChSales($register_open_time);
        $this->data['other_sales'] = $this->pos_model->getRegisterOtherSales($register_open_time);
        $this->data['gcsales']     = $this->pos_model->getRegisterGCSales($register_open_time);
        $this->data['stripesales'] = $this->pos_model->getRegisterStripeSales($register_open_time);
        $this->data['totalsales']  = $this->pos_model->getRegisterSales($register_open_time);
        $this->data['expenses']    = $this->pos_model->getRegisterExpenses($register_open_time);
        $this->load->view($this->theme . 'pos/register_details', $this->data);
    }

    public function registers()
    {
        $this->data['error']     = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['registers'] = $this->pos_model->getOpenRegisters();
        $bc                      = [['link' => base_url(), 'page' => lang('home')], ['link' => site_url('pos'), 'page' => lang('pos')], ['link' => '#', 'page' => lang('open_registers')]];
        $meta                    = ['page_title' => lang('open_registers'), 'bc' => $bc];
        $this->page_construct('pos/registers', $this->data, $meta);
    }

    public function shortcuts()
    {
        $this->load->view($this->theme . 'pos/shortcuts', $this->data);
    }

    public function stripe_balance()
    {
        if (!$this->Owner) {
            return false;
        }
        $this->load->model('stripe_payments');
        return $this->stripe_payments->get_balance();
    }

    public function suggestions()
    {
        $term = $this->tec->parse_scale_barcode($this->input->get('term', true));
        if (is_array($term)) {
            $bqty   = $term['weight'] ?? null;
            $bprice = $term['price']  ?? null;
            $term   = $term['item_code'];
            $rows   = $this->pos_model->getProductNames($term, null, true);
        }
        if (!$rows) {
            $bqty   = null;
            $bprice = null;
            $term   = $this->input->get('term', true);
            $rows   = $this->pos_model->getProductNames($term);
        }
        if ($rows) {
            foreach ($rows as $row) {
                unset($row->cost, $row->details);
                $row->qty             = $bqty ?: ($bprice ? $bprice / $row->price : 1);
                $row->comment         = '';
                $row->discount        = '0';
                $row->price           = $row->store_price > 0 ? $row->store_price : $row->price;
                $row->real_unit_price = $row->price;
                $row->unit_price      = $row->tax ? ($row->price + (($row->price * $row->tax) / 100)) : $row->price;
                $combo_items          = false;
                if ($row->type == 'combo') {
                    $combo_items = $this->pos_model->getComboItemsByPID($row->id);
                }
                $pr[] = ['id' => str_replace('.', '', microtime(true)), 'item_id' => $row->id, 'label' => $row->name . ' (' . $row->code . ')', 'row' => $row, 'combo_items' => $combo_items];
            }
            echo json_encode($pr);
        } else {
            echo json_encode([['id' => 0, 'label' => lang('no_match_found'), 'value' => $term]]);
        }
    }

    public function today_sale()
    {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect($_SERVER['HTTP_REFERER']);
        }

        $this->data['error']       = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['ccsales']     = $this->pos_model->getTodayCCSales();
        $this->data['cashsales']   = $this->pos_model->getTodayCashSales();
        $this->data['chsales']     = $this->pos_model->getTodayChSales();
        $this->data['other_sales'] = $this->pos_model->getTodayOtherSales();
        $this->data['gcsales']     = $this->pos_model->getTodayGCSales();
        $this->data['stripesales'] = $this->pos_model->getTodayStripeSales();
        $this->data['totalsales']  = $this->pos_model->getTodaySales();
        // $this->data['expenses'] = $this->pos_model->getTodayExpenses();
        $this->load->view($this->theme . 'pos/today_sale', $this->data);
    }

    public function validate_gift_card($no)
    {
        if ($gc = $this->pos_model->getGiftCardByNO(urldecode($no))) {
            if ($gc->expiry) {
                if ($gc->expiry >= date('Y-m-d')) {
                    echo json_encode($gc);
                } else {
                    echo json_encode(false);
                }
            } else {
                echo json_encode($gc);
            }
        } else {
            echo json_encode(false);
        }
    }

    public function view($sale_id = null, $noprint = null)
    {
        if ($this->input->get('id')) {
            $sale_id = $this->input->get('id');
        }
        $this->data['error']   = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['message'] = $this->session->flashdata('message');
        $inv                   = $this->pos_model->getSaleByID($sale_id);
        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang('please_select_store'));
            redirect('stores');
        } elseif ($this->session->userdata('store_id') != $inv->store_id) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('welcome');
        }
        $this->tec->view_rights($inv->created_by);
        $this->load->helper('text');
        $this->data['rows']       = $this->pos_model->getAllSaleItems($sale_id);
        $this->data['customer']   = $this->pos_model->getCustomerByID($inv->customer_id);
        $this->data['store']      = $this->site->getStoreByID($inv->store_id);
        $this->data['inv']        = $inv;
        $this->data['sid']        = $sale_id;
        $this->data['noprint']    = $noprint;
        $this->data['modal']      = $noprint ? true : false;
        $this->data['payments']   = $this->pos_model->getAllSalePayments($sale_id);
        $this->data['created_by'] = $this->site->getUser($inv->created_by);
        $this->data['printer']    = $this->site->getPrinterByID($this->Settings->printer);
        $this->data['store']      = $this->site->getStoreByID($inv->store_id);
        $this->data['page_title'] = lang('invoice');
        $this->load->view($this->theme . 'pos/' . ($this->Settings->remote_printing != 1 && $this->Settings->print_img ? 'eview' : 'view'), $this->data);
    }

    public function view_bill()
    {
        $this->load->view($this->theme . 'pos/view_bill', $this->data);
    }

    public function send_email_register_open(){
        $registers = $this->pos_model->getRegisterOpen();

        if(count($registers) > 0){
            $time = date('Y-m-d H:m:i');

            $body = "";
            foreach($registers as $reg){
                $body .= "
                    <tr>
                        <td>{$reg->date}</td>
                        <td>{$reg->user}</td>
                        <td>{$reg->store}</td>
                    </tr>
                ";
            }

            $html = "
                <h4>Se notifica que a esta hora {$time} se detecto que las siguientes cajas siguen abiertas</h4>

                <table>
                    <thead>
                        <tr>
                            <th>Hora abierta</th>
                            <th>Responsable</th>
                            <th>Sucursal</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$body}
                    </tbody>
                </table>
            ";

            $result = $this->tec->send_email(
                $this->Settings->default_email, 
                "Reporte de cajas abiertas", 
                $html
            );

            echo $html;

            print_r($result);
        }
    }

    public function check_aparts_expirations(){
        /**
         * 1. Traer todos los apartados que no hayan sido liquidados
         * 2. Traer la fecha del ultimo pago de cada apartado
         * 3. Validar que la cantidad de dias no haya superado a la configuraci??n "apart_expiration_days"
         */
        $aparts = $this->pos_model->getApartsOpened();
        foreach($aparts as $sale){
            $payment = $this->pos_model->getLastPaymentSale($sale->id);

            if($payment){
                $datetime1 = new DateTime($payment->date);
                $datetime2 = new DateTime(date('Y-m-d'));
                $interval = $datetime1->diff($datetime2);
                if($interval->days > $this->Settings->apart_expiration_days){
                    /**
                     * Este apartado ya expiro
                     * El status de la venta cambia a "closed"
                     * Los items son eliminados de "apart" de la tabla "tec_product_store_qty"
                     */
                    $this->pos_model->closeSale($sale->id);
                }
            }
        }
    }

    public function view_payment_credit($payments_ids){
        $this->load->model('sales_model');

        $payments_ids = json_decode(urldecode($payments_ids));

        $payments = [];
        $total_amount = 0;

        foreach($payments_ids as $payment_id){
            $pay = $this->sales_model->getPaymentByID($payment_id);
            $payments[] = $pay;
            $total_amount += (float) $pay->amount;
        }

        $debt = $this->sales_model->getDebtBefore($payments[0]->id)->total - $this->sales_model->getDebtBefore($payments[0]->id)->paid;
        $rest = $debt - $total_amount;

        $store_id = $this->session->userdata('store_id');
        $sale = $this->pos_model->getSaleFromPayment($payments[0]->id);
        $customer = $this->pos_model->getCustomerByID($sale->customer_id);

        #$this->tec->view_rights($inv->created_by);
        $this->load->helper('text');
        $this->data['customer']   = $customer;
        $this->data['store']      = $this->site->getStoreByID($store_id);
        #$this->data['inv']        = $inv;
        $this->data['sid']        = $sale->id;
        $this->data['noprint']    = $noprint;
        $this->data['modal']      = $noprint ? true : false;
        $this->data['created_by'] = $this->site->getUser($sale->created_by);
        $this->data['printer']    = $this->site->getPrinterByID($this->Settings->printer);
        $this->data['page_title'] = "Abono de cr??dito";

        $this->data['debt'] = $debt;
        $this->data['payment'] = $total_amount;
        $this->data['paid_by'] = $payments[0]->paid_by == 'cash' ? 'Efectivo' : 'Transferencia';
        $this->data['debt_rest'] = $rest;
        $this->data['date'] = date("Y-m-d");
        $this->data['text'] = 'Abono de cr??dito';
        
        $this->load->view($this->theme . 'pos/view_payment_credit', $this->data);
    }

    public function view_payment_apart($payments_ids){
        $this->load->model('sales_model');

        $array = json_decode(urldecode($payments_ids));
        $payment_id = $array[0];
        $payment = $this->sales_model->getPaymentByID($payment_id);

        $sale = $this->pos_model->getSaleFromPayment($payment_id);

        $debt = $sale->total - $sale->paid + $payment->amount;
        $rest = $sale->total - $sale->paid;

        $store_id = $this->session->userdata('store_id');
        $customer = $this->pos_model->getCustomerByID($sale->customer_id);

        #$this->tec->view_rights($inv->created_by);
        $this->load->helper('text');
        $this->data['customer']   = $customer;
        $this->data['store']      = $this->site->getStoreByID($store_id);
        #$this->data['inv']        = $inv;
        $this->data['sid']        = $sale->id;
        $this->data['noprint']    = $noprint;
        $this->data['modal']      = $noprint ? true : false;
        $this->data['created_by'] = $this->site->getUser($sale->created_by);
        $this->data['printer']    = $this->site->getPrinterByID($this->Settings->printer);
        $this->data['page_title'] = "Abono de apartado";

        $this->data['debt'] = $debt;
        $this->data['payment'] = $payment->amount;
        $this->data['paid_by'] = $payment->paid_by == "cash" ? 'Efectivo' : 'Transferencia';
        $this->data['debt_rest'] = $rest;
        $this->data['date'] = date("Y-m-d");
        $this->data['text'] = 'Abono de apartado';
        
        $this->load->view($this->theme . 'pos/view_payment_credit', $this->data);
    }

    public function ticket_payment_credit($customer_id, $date, $before_payment, $payment, $after_payment, $paid_by){
        $sale = $this->pos_model->getSaleFromPayment($payment_id);
        $customer = $this->pos_model->getCustomerByID($customer_id);

        $this->data['error']   = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['message'] = $this->session->flashdata('message');

        $inv                   = $this->pos_model->getSaleByID($sale->id);
        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang('please_select_store'));
            redirect('stores');
        }
        $store_id = $this->session->userdata('store_id');

        $this->tec->view_rights($inv->created_by);
        $this->load->helper('text');
        $this->data['customer']   = $customer;
        $this->data['store']      = $this->site->getStoreByID($store_id);
        $this->data['inv']        = $inv;
        $this->data['sid']        = $sale->id;
        $this->data['noprint']    = $noprint;
        $this->data['modal']      = $noprint ? true : false;
        $this->data['payments']   = $this->pos_model->getAllSalePayments($sale->id);
        $this->data['created_by'] = $this->site->getUser($inv->created_by);
        $this->data['printer']    = $this->site->getPrinterByID($this->Settings->printer);
        $this->data['page_title'] = "Abono de cr??dito";

        $this->data['debt'] = $before_payment;
        $this->data['payment'] = $payment;
        $this->data['paid_by'] = $paid_by;
        $this->data['debt_rest'] = $after_payment;
        $this->data['date'] = $date;

        $this->load->view($this->theme . 'pos/view_payment_credit', $this->data);
    }

    public function add_payment_credit($customer_id = null)
    {
        $this->form_validation->set_rules('amount-paid', lang('amount'), 'required');
        $this->form_validation->set_rules('paid_by', lang('paid_by'), 'required');

        $ticket = $this->input->post('ticket');
        
        if ($this->form_validation->run() == true) {
            if ($this->Admin && $this->input->post('date')) {
                $date = $this->input->post('date');
            } else {
                $date = date('Y-m-d H:i:s');
            }

            $this->load->model('customers_model');

            $sales = $this->customers_model->getPartialSales($customer_id);
            $total_paid = (float) $this->input->post('amount-paid');
            $paid_by = $this->input->post('paid_by');
            $rest_paid = $total_paid;
            $debt = 0;

            foreach($sales as $sale){
                $debt += ((float) $sale->grand_total - (float) $sale->paid);
            }

            $new_payments = [];

            foreach($sales as $sale){
                $isLiquidate = (float) $sale->grand_total <= $total_paid;

                $paid = (float) $isLiquidate == true ? $sale->grand_total : $rest_paid;

                $payment = [
                    'date'        => $date,
                    'sale_id'     => $sale->id,
                    'customer_id' => $customer_id,
                    'reference'   => $this->input->post('reference'),
                    'amount'      => $paid,
                    'paid_by'     => $paid_by,
                    'is_abono'    => true,
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
    
                $id = $this->sales_model->addPayment($payment);
                $new_payments[] = $id;

                $rest_paid -= $paid;

                if($rest_paid == 0) break;
            }

            
            if($ticket){
                /*$this->ticket_payment_credit(
                    $this->input->post('customer_id'), 
                    $date,
                    $debt, // before payment
                    $total_paid, // total payment
                    $debt - $total_paid, // after payment
                    $paid_by
                );*/

                echo json_encode($new_payments);
            }else{
                $this->session->set_flashdata('message', 'Abono agregado');
                redirect('pos');
            }
        } else {
            $this->session->set_flashdata('error', 'Error al procesar el pago');
            redirect('pos');
        }
    }

    public function getLatePayments(){
        $phone = $this->input->get('phone');
        $latePayments = $this->pos_model->getLatePayments($phone);
        $payments = "";
        //echo $latePayments[0]->last_payment. "    ";
        //var_dump(date("Y-m-d",strtotime(date("Y-m-d")."- 15 days")));exit;
        $dateVerify = date("Y-m-d",strtotime(date("Y-m-d")."- 15 days"));
        if( $latePayments[0]->last_payment < $dateVerify){
            
            $payments = "
            <h3 style='color:red'>El cliente tiene pagos pendiente</h3>
            <p>??ltimo pago el d??a: ".$latePayments[0]->last_payment."</p>
            <p>Monto de: ".$latePayments[0]->amount."</p>
          ";
        } else {
            $payments = "<h3 style='color:green'>El cliente est?? al corriente con sus pagos</h3>";
        }

        echo json_encode($payments);
        
    }

    public function store($id){
        $this->session->set_userdata('store_id', $id);
        redirect("/pos");
    }
}
