<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<script type="text/javascript">
    $(document).ready(function() {

        var table = $('#CuData').DataTable({

            'ajax' : { url: '<?=site_url('customers/get_customers');?>', type: 'POST', "data": function ( d ) {
                d.<?=$this->security->get_csrf_token_name();?> = "<?=$this->security->get_csrf_hash()?>";
            }},
            "buttons": [
                { extend: 'copyHtml5', 'footer': false, exportOptions: { columns: [ 0, 1, 2, 3, 4, 5 ] } },
                { extend: 'excelHtml5', 'footer': false, exportOptions: { columns: [ 0, 1, 2, 3, 4, 5 ] } },
                { extend: 'csvHtml5', 'footer': false, exportOptions: { columns: [ 0, 1, 2, 3, 4, 5 ] } },
                { extend: 'pdfHtml5', orientation: 'landscape', pageSize: 'A4', 'footer': false,
                exportOptions: { columns: [ 0, 1, 2, 3, 4, 5 ] } },
                { extend: 'colvis', text: 'Columns'},
            ],
            "columns": [
                { "data": "id", "visible": false },
                { "data": "name" },
                { "data": "phone" },
                { "data": "email" },
                { "data": "cf1" },
                { "data": "cf2" },
                { "data": "Actions", "searchable": false, "orderable": false }
            ]

        });

        $('#search_table').on( 'keyup change', function (e) {
            var code = (e.keyCode ? e.keyCode : e.which);
            if (((code == 13 && table.search() !== this.value) || (table.search() !== '' && this.value === ''))) {
                table.search( this.value ).draw();
            }
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
                                Tipo de reporte: <br>
                                <select id="tipoReporte" class="form-control paid_by bank" style="width:50%; display:inline-block">
                                    <option value="seleccione" selected>Seleccione una opción</option>
                                    <option value="statusAccount">Estado de cuenta</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom:5px;">
                                <input type="text" name="code" id="add_item" class="form-control" placeholder="Nombre del cliente" />
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
                                    <label for="louie">Crédito</label>
                                </div>
                            </div>
                        </div>
                    </div> <br>
                    <div class="row" style="display:none" id="datesFilter">
                        <div class="col-xs-12">
                            <div class="form-group">
                                <input id="date_inicio" type="text" class=" datepicker2 col-xs-5" placeholder="Fecha de inicio">
                                <input id="date_fin" type="text" class=" datepicker2 col-xs-5 col-xs-offset-1" placeholder="Fecha fin">
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
        <div class="col-xs-12">
            <div class="modal-body">
                <select class="form-control input-md select2" name="customer_id" id="customer_id" required style="width: 100%;">
                    <option value="">Seleccionar cuenta cliente</option>
                    <?php foreach($creditsClients as $order): ?>
                        <option data-row='<?= json_encode($order) ?>' value="<?= $order->customer_id ?>"><?= $order->customer ?></option>
                    <?php endforeach; ?>
                </select>
                <br />
                <br />
                <fieldset>
                    <div class="row">
                        <div class="col-sm-3">
                            <label class="label-control">Cuenta total:</label>
                        </div>
                        <div class="col-sm-9">
                            <span id="grand_total"></span>
                        </div>
                    </div>
                </fieldset>

                <br />
                <input type='number' name='amount-paid' id='amount-paid' class='form-control input-md' value='' placeholder="Monto" required>
                <br>
                <button id="makePaymentCredit" class="btn btn-primary btn-sm" style="float:right">Aceptar</button>
            </div>
        </div>
    </div>
</section>

<script src="<?= $assets ?>plugins/bootstrap-datetimepicker/js/moment.min.js" type="text/javascript"></script>
<script src="<?= $assets ?>plugins/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js" type="text/javascript"></script>
<script type="text/javascript">
    $(document).ready(function() {

        $(".select2").select2()

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

        

        $('#customer_id').change(function (e) {
            const selected_value = $(this).val()
            console.log(selected_value)
            if (selected_value) {
                console.log(selected_value,"s")
                const option = $(this).find(`option[value=${selected_value}]`)
                const data = $(option).data('row')

                $("#grand_total").html(formatMoney(parseFloat(data.grand_total)))
                $("#makePaymentCredit").attr('disabled', false)
                } else {
                $("#grand_total").html('')
                $("#makePaymentCredit").attr('disabled', true)
            }
        });

        $("#makePaymentCredit").click(function(){
            if(confirm('¿Esta seguro de aplicar los cambios? Esta accion es irreversible')){
                $("#makePaymentCredit").prop('disabled', true);
                $("#makePaymentCredit").html('Aplicando...');

                const option = $('#customer_id').val()
                const monto =  formatMoney(parseFloat($('#amount-paid').val()))

                $("input.border-success").each(function(index){
                    const code = $(this).parent().parent().find('> :first-child').html()
                    const quantity = $(this).val()
                    items.push({
                        code,
                        quantity
                    })
                })

                $.ajax({
                    url: '<?= site_url('reports/add_payment_credit/'); ?>',
                    method: 'POST',
                    data: {
                        amountpaid : monto,
                        customer_id : option
                    },
                    success: function(){
                        window.location.reload()      
                    }
                })
            }
        })

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
        
        $('.datepicker').datetimepicker({format: 'YYYY-MM-DD', showClear: true, showClose: true, useCurrent: false, widgetPositioning: {horizontal: 'auto', vertical: 'bottom'}, widgetParent: $('.dataTable tfoot')});

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
        $(document).on('click', '#print_report', function() {
            $('#reportsModal').modal({ backdrop: 'static' });
        });


        $("#print").click(function() {
            let phone = $('#add_item').val().substring($('#add_item').val().indexOf("(") + 1, $('#add_item').val().lastIndexOf(")"))
            if($("#tipoReporte").val() == "statusAccount"){
                let data = ["Estado de cuenta",phone]
                let url=  new URL(window.location.origin+"/reports/statusAccount/");
                url.searchParams.append('filtros', data)
                window.open(url.toString(), '_blank')
                $("#add_item").val("")
                $('#reportsModal').modal('hide');
            } 

        });    

            
 /*        $.ajax({
            url: "",
            type: 'POST',
            data: data,
            contentType: false,
            processData: false,
            success: function (data) {
                $(window).trigger('camera_capture.save_success', [filename]);
            },
            error: function () {
                $(window).trigger('camera_capture.save_failed');
            }
        });
 */

    });
</script>

<style>
    .ui-autocomplete { 
        z-index: 9999999 !important; 
    }
</style>
