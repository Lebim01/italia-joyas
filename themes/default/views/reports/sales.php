<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<?php
$v = "?v=1";

if ($this->input->post('customer')){
    $v .= "&customer=".$this->input->post('customer');
}
if ($this->input->post('user')){
    $v .= "&user=".$this->input->post('user');
}
if ($this->input->post('start_date')){
    $v .= "&start_date=".$this->input->post('start_date');
}
if ($this->input->post('end_date')) {
    $v .= "&end_date=".$this->input->post('end_date');
}

?>

<script type="text/javascript">
    $(document).ready(function() {

        function status(x) {
            var paid = '<?= lang('paid'); ?>';
            var partial = '<?= lang('partial'); ?>';
            var due = '<?= lang('due'); ?>';
            if (x == 'paid') {
                return '<div class="text-center"><span class="sale_status label label-success">'+paid+'</span></div>';
            } else if (x == 'partial') {
                return '<div class="text-center"><span class="sale_status label label-primary">'+partial+'</span></div>';
            } else if (x == 'due') {
                return '<div class="text-center"><span class="sale_status label label-danger">'+due+'</span></div>';
            } else {
                return '<div class="text-center"><span class="sale_status label label-default">'+x+'</span></div>';
            }
        }

        var table = $('#SLRData').DataTable({

            'ajax' : { url: '<?=site_url('reports/get_sales/'. $v);?>', type: 'POST', "data": function ( d ) {
                d.<?=$this->security->get_csrf_token_name();?> = "<?=$this->security->get_csrf_hash()?>";
            }},
            "buttons": [
            { extend: 'copyHtml5', 'footer': true, exportOptions: { columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9 ] } },
            { extend: 'excelHtml5', 'footer': true, exportOptions: { columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9 ] } },
            { extend: 'csvHtml5', 'footer': true, exportOptions: { columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9 ] } },
            { extend: 'pdfHtml5', orientation: 'landscape', pageSize: 'A4', 'footer': true,
            exportOptions: { columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9 ] } },
            { extend: 'colvis', text: 'Columns'},
            ],
            "columns": [
            { "data": "id", "visible": false },
            { "data": "date", "render": hrld },
            { "data": "customer_name" },
            { "data": "total", "render": currencyFormat },
            { "data": "total_tax", "render": currencyFormat },
            { "data": "total_discount", "render": currencyFormat },
            { "data": "grand_total", "render": currencyFormat },
            { "data": "paid", "render": currencyFormat },
            { "data": "balance", "render": currencyFormat },
            { "data": "status", "render": status }
            ],
            "footerCallback": function (  tfoot, data, start, end, display ) {
                var api = this.api(), data;
                $(api.column(3).footer()).html( cf(api.column(3).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(4).footer()).html( cf(api.column(4).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(5).footer()).html( cf(api.column(5).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(6).footer()).html( cf(api.column(6).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(7).footer()).html( cf(api.column(7).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(8).footer()).html( cf(api.column(8).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
            }

        });

        $('#search_table').on( 'keyup change', function (e) {
            var code = (e.keyCode ? e.keyCode : e.which);
            if (((code == 13 && table.search() !== this.value) || (table.search() !== '' && this.value === ''))) {
                table.search( this.value ).draw();
            }
        });

        table.columns().every(function () {
            var self = this;
            $( 'input.datepicker', this.footer() ).on('dp.change', function (e) {
                self.search( this.value ).draw();
            });
            $( 'input:not(.datepicker)', this.footer() ).on('keyup change', function (e) {
                var code = (e.keyCode ? e.keyCode : e.which);
                if (((code == 13 && self.search() !== this.value) || (self.search() !== '' && this.value === ''))) {
                    self.search( this.value ).draw();
                }
            });
            $( 'select', this.footer() ).on( 'change', function (e) {
                self.search( this.value ).draw();
            });
        });

    });
</script>

<script type="text/javascript">
    $(document).ready(function(){
        $('#form').hide();
        $('.toggle_form').click(function(){
            $("#form").slideToggle();
            return false;
        });
    });
</script>

<section class="content">
<div class="row">
        <div class="col-xs-12">
            <button type="button" class="btn btn-primary" id="print_report"><i class='fa fa-print'></i> Imprimir reportes</button>
        </div>
    </div>
    <br>
    <div class="modal" data-easein="flipYIn" id="reportsModal" tabindex="-1" role="dialog" aria-labelledby="cModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal-primary">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i></button>
                    <h4 class="modal-title" id="cModalLabel">
                       Imprimir reportes
                    </h4>
                </div>
                
                <div class="modal-body">
                    <div id="c-alert" class="alert alert-danger" style="display:none;"></div>
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="form-group">
                                Sucursal: <br>
                                <div id="selectStore">
                                    <select id="store"></select>
                                </div>
                            </div>
                            <div class="form-group">
                                Tipo de reporte: <br>
                                <select id="tipoReporte" class="form-control paid_by select2 bank" style="width:50%; display:inline-block">
                                    <option value="seleccione" selected>Seleccione una opci??n</option>
                                    <option value="bySales">Reporte de ventas</option>
                                    <option value="byProducts">Reporte de ventas por producto</option>
                                    <option value="byInvoice">Reporte de ventas fiscal</option>
                                    <option value="byComision">Reporte de comisiones de ventas</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row" style="display:none" id="paymentFilter">
                        <div class="col-xs-12">
                            <div class="form-group">
                                <div>
                                    <input type="radio" id="huey" name="pay" value="todos" checked>
                                    <label for="huey">Todos</label>
                                </div>
                                <div>
                                    <input type="radio" id="dewey" name="pay" value="cash">
                                    <label for="dewey">Contado</label>
                                </div>
                                <div>
                                    <input type="radio" id="louie" name="pay" value="CC">
                                    <label for="louie">Cr??dito</label>
                                </div>
                            </div>
                        </div>
                    </div> <br>
                    <div class="row" style="display:none" id="datesFilter">
                        <div class="col-xs-12">
                            <div class="form-group">
                                <input id="date_inicio" type="text" class=" datepicker2 col-xs-5" placeholder="Fecha de inicio">
                                <input id="date_fin" type="text" class=" datepicker2 col-xs-5 col-xs-offset-1" placeholder="Fecha fin" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="margin-top:0;">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal"> Cerrar </button>
                    <button type="submit" class="btn btn-primary" id="print">Imprimir </button>
                </div>
                <?= form_close(); ?>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="box box-primary">
                <div class="box-header">
                    <a href="#" class="btn btn-default btn-sm toggle_form pull-right"><?= lang("show_hide"); ?></a>
                    <h3 class="box-title"><?= lang('customize_report'); ?></h3>
                </div>
                <div class="box-body">
                    <div id="form" class="panel panel-warning">
                        <div class="panel-body">
                            <?= form_open("reports");?>

                            <div class="row">
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <label class="control-label" for="customer"><?= lang("customer"); ?></label>
                                        <?php
                                        $cu[0] = lang("select")." ".lang("customer");
                                        foreach($customers as $customer){
                                            $cu[$customer->id] = $customer->name;
                                        }
                                        echo form_dropdown('customer', $cu, set_value('customer'), 'class="form-control select2" style="width:100%" id="customer"'); ?>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <label class="control-label" for="user"><?= lang("user"); ?></label>
                                        <?php
                                        $us[""] = "";
                                        foreach ($users as $user) {
                                            $us[$user->id] = $user->first_name . " " . $user->last_name;
                                        }
                                        echo form_dropdown('user', $us, (isset($_POST['user']) ? $_POST['user'] : ""), 'class="form-control select2" id="user" data-placeholder="' . lang("select") . " " . lang("user") . '" style="width:100%;"');
                                        ?>
                                    </div>
                                </div>

                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <label class="control-label" for="start_date"><?= lang("start_date"); ?></label>
                                        <?= form_input('start_date', set_value('start_date'), 'class="form-control datetimepicker" id="start_date"');?>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <label class="control-label" for="end_date"><?= lang("end_date"); ?></label>
                                        <?= form_input('end_date', set_value('end_date'), 'class="form-control datetimepicker" id="end_date"');?>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <button type="submit" class="btn btn-primary"><?= lang("submit"); ?></button>
                                </div>
                            </div>
                            <?= form_close();?>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="table-responsive">
                                <table id="SLRData" class="table table-striped table-bordered table-condensed table-hover">
                                    <thead>
                                        <tr class="active">
                                            <th style="max-width:30px;"><?= lang("id"); ?></th>
                                            <th class="col-sm-2"><?= lang("date"); ?></th>
                                            <th class="col-sm-2"><?= lang("customer"); ?></th>
                                            <th class="col-sm-1"><?= lang("total"); ?></th>
                                            <th class="col-sm-1"><?= lang("tax"); ?></th>
                                            <th class="col-sm-1"><?= lang("discount"); ?></th>
                                            <th class="col-sm-2"><?= lang("grand_total"); ?></th>
                                            <th class="col-sm-1"><?= lang("paid"); ?></th>
                                            <th class="col-sm-1"><?= lang("balance"); ?></th>
                                            <th class="col-sm-1"><?= lang("status"); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="10" class="dataTables_empty"><?= lang('loading_data_from_server'); ?></td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="active">
                                            <th style="max-width:30px;"><input type="text" class="text_filter" placeholder="[<?= lang('id'); ?>]"></th>
                                            <th class="col-sm-2"><span class="datepickercon"><input type="text" class="text_filter datepicker" placeholder="[<?= lang('date'); ?>]"></span></th>
                                            <th class="col-sm-2"><input type="text" class="text_filter" placeholder="[<?= lang('customer'); ?>]"></th>
                                            <th class="col-sm-1"><?= lang("total"); ?></th>
                                            <th class="col-sm-1"><?= lang("tax"); ?></th>
                                            <th class="col-sm-1"><?= lang("discount"); ?></th>
                                            <th class="col-sm-2"><?= lang("grand_total"); ?></th>
                                            <th class="col-sm-1"><?= lang("paid"); ?></th>
                                            <th class="col-sm-1"><?= lang("balance"); ?></th>
                                            <th class="col-sm-1">
                                                <select class="select2 select_filter"><option value=""><?= lang("all"); ?></option><option value="paid"><?= lang("paid"); ?></option><option value="partial"><?= lang("partial"); ?></option><option value="due"><?= lang("due"); ?></option></select>
                                            </th>
                                        </tr>
                                        <tr>
                                            <td colspan="10" class="p0"><input type="text" class="form-control b0" name="search_table" id="search_table" placeholder="<?= lang('type_hit_enter'); ?>" style="width:100%;"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <?php if ($this->input->post('customer')) { ?>
                    <div class="row">
                        <div class="col-md-3">
                            <button class="btn bg-purple btn-lg btn-block" style="cursor:default;">
                                <strong><?= $this->tec->formatMoney($total_sales->number, 0); ?></strong>
                                <?= lang("sales"); ?>
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary btn-lg btn-block" style="cursor:default;">
                                <strong><?= $this->tec->formatMoney($total_sales->amount); ?></strong>
                                <?= lang("amount"); ?>
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-success btn-lg btn-block" style="cursor:default;">
                                <strong><?= $this->tec->formatMoney($total_sales->paid); ?></strong>
                                <?= lang("paid"); ?>
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-warning btn-lg btn-block" style="cursor:default;">
                                <strong><?= $this->tec->formatMoney($total_sales->amount-$total_sales->paid); ?></strong>
                                <?= lang("due"); ?>
                            </button>
                        </div>
                    </div>
                    <?php } ?>

                </div>
            </div>
        </div>
    </div>
</section>

<script src="<?= $assets ?>plugins/bootstrap-datetimepicker/js/moment.min.js" type="text/javascript"></script>
<script src="<?= $assets ?>plugins/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js" type="text/javascript"></script>
<script type="text/javascript">
    $(document).ready(function() {
        
        var options = "";

        $.ajax({
            type: 'get',
            url: base_url + 'reports/getStores',
            dataType: 'json',
            success: function (data) {
                for(let i = 0; i < data.length; i ++){
                   options += '<option value="'+data[i].id+'">'+data[i].name+'</option>'
                }
                $('#selectStore select').html(options);
            },
        })

        $('.datepicker').datetimepicker({
            format: 'YYYY-MM-DD',
            showClear: true,
            showClose: true,
            useCurrent: false,
            widgetPositioning: {
                horizontal: 'auto',
                vertical: 'bottom'
            },
            widgetParent: $('.dataTable tfoot')
        });

        $('.datepicker2').datetimepicker({
            format: 'YYYY-MM-DD',
            showClear: true,
            showClose: true,
            useCurrent: false,
            widgetPositioning: {
                horizontal: 'auto',
                vertical: 'bottom'
            },
        });

        //$('#date_fin').datetimepicker('setDate', 'today')

        $("#tipoReporte").change(function() {
            if($(this).val() != "seleccione"){
                $("#datesFilter").css("display", "inline-block");
            } else {
                $("#datesFilter").css("display", "none");
                $("#date_inicio").val("")

                $("#date_fin").val("<?php echo date('Y-m-d'); ?>")
            }

            if( $(this).val() != "seleccione" && $(this).val() != "byInvoice" && $(this).val() != "byComision"){
                $("#paymentFilter").css("display", "inline-block");
            } else{
                $("#paymentFilter").css("display", "none");
            }
        });

        $("#print").click(function() {
            
            if($("#tipoReporte").val() == "byProducts"){
                if($('#date_inicio').val() && $('#date_fin').val()){
                    let data = ["Reporte de ventas por producto",$('#date_inicio').val(),$('#date_fin').val(),$('#store').val(),$("input[type='radio']:checked").val()]
                    redirect(data)
                }
                else {
                    alert ("Llene los campos correctamente")
                }
            } 

            if($("#tipoReporte").val() == "bySales"){
                if($('#date_inicio').val() && $('#date_fin').val()){
                    let data = ["Reporte de ventas",$('#date_inicio').val(),$('#date_fin').val(),$('#store').val(),$("input[type='radio']:checked").val()]
                    redirect(data)
                    return
                }
                else {
                    alert ("Llene los campos correctamentes")
                }
            } 

            if($("#tipoReporte").val() == "byInvoice"){
                if($('#date_inicio').val() && $('#date_fin').val()){
                    let data = ["Reporte de ventas fiscal",$('#date_inicio').val(),$('#date_fin').val(),$('#store').val()]
                    redirect(data)
                }
                else {
                    alert ("Llene los campos correctamentes")
                }
            }
            
            if($("#tipoReporte").val() == "byComision"){
                if($('#date_inicio').val() && $('#date_fin').val()){
                    let data = ["Reporte de ventas por comision",$('#date_inicio').val(),$('#date_fin').val(),$('#store').val()]
                    redirect(data)
                }
                else {
                    alert ("Llene los campos correctamentes")
                }
            }

        });

        function redirect(data){
            let url=  new URL(window.location.origin+"/reports/reportssales/");
            url.searchParams.append('filtros', data)
            window.open(url.toString(), '_blank')
            clear()
            return
        }

        function clear() {
            $("#datesFilter").css("display", "none");
            $("#paymentFilter").css("display", "none");
            $("#date_inicio").val("")
            $("#date_fin").val("<?php echo date('Y-m-d'); ?>")
            $('#tipoReporte').val("")
            $('#reportsModal').modal('hide');
        }

        $(".pull-left").click(function() {
            clear()
        })

        $(document).on('click', '#print_report', function() {
            $('#reportsModal').modal({ backdrop: 'static' });
        });
    });
</script>
