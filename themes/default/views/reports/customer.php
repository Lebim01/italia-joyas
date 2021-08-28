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
                                <select id="tipoReporte" class="form-control paid_by select2 bank" style="width:50%; display:inline-block">
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
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title"><?= lang('list_results'); ?></h3>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table id="CuData" class="table table-bordered table-hover table-striped">
                            <thead>
                                <tr>
                                    <th style="max-width:30px;"><?= lang("id"); ?></th>
                                    <th><?= lang("name"); ?></th>
                                    <th><?= lang("phone"); ?></th>
                                    <th><?= lang("email_address"); ?></th>
                                    <th><?= lang("ccf1"); ?></th>
                                    <th><?= lang("ccf2"); ?></th>
                                    <th style="width:65px;"><?= lang("actions"); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="7" class="dataTables_empty"><?= lang('loading_data_from_server') ?></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7" class="p0"><input type="text" class="form-control b0" name="search_table" id="search_table" placeholder="<?= lang('type_hit_enter'); ?>" style="width:100%;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="<?= $assets ?>plugins/bootstrap-datetimepicker/js/moment.min.js" type="text/javascript"></script>
<script src="<?= $assets ?>plugins/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js" type="text/javascript"></script>
<script type="text/javascript">
    $(document).ready(function() {
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
        
        /* $("#tipoReporte").change(function() {
            if($(this).val() != "seleccione"){
                $("#datesFilter").css("display", "inline-block");
            } else {
                $("#datesFilter").css("display", "none");
                $("#date_inicio").val("")

                $("#date_fin").val("")
            }
        }); */

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


        $( "#add_item" ).autocomplete({
            source:  base_url + 'reports/suggestions'
            });

            

    });
</script>

<style>
    .ui-autocomplete { 
        z-index: 9999999 !important; 
    }
</style>
