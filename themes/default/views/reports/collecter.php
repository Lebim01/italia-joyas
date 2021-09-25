<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

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
                                <input id="date_inicio" type="text" class=" datepicker2" placeholder="Fecha de inicio">
                                <input id="date_fin" type="text" class=" datepicker2" placeholder="Fecha fin" value="<?php echo date('Y-m-d'); ?>">
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
            if (selected_value) {
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
            let selected_sale = $("#customer_id").val()
            let amount = parseFloat($("#amount-paid").val()) || 0

            if (!amount > 0) {
            alert('El monto no puede ser 0')
            return;
            }

            if (!selected_sale) {
            alert('Seleccione una cuenta')
            return;
            }

            let option = $("#customer_id").find(`option[value=${selected_sale}]`)
            let sale_data = $(option).data('row')

            $.ajax({
            url: base_url + `reports/add_payment_credit/${sale_data.customer_id}`,
            method: 'POST',
            data: {
                'amount-paid': amount,
                paid_by :"cash"
            },
            success: function () {
                //$("#paymentModal").modal('hide')
                alert('Abono hecho correctamente')
                window.location.reload()
            }
            })
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
            let data = ["Reporte de abonos a domicilio",$("#date_inicio").val(),$("#date_fin").val()]

                let url=  new URL(window.location.origin+"/reports/getPaymmentsStreet/");
                url.searchParams.append('filtros', data)
                window.open(url.toString(), '_blank')
                $("#date_inicio").val("")
                $('#reportsModal').modal('hide');
            

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
