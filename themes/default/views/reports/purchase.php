<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<script type="text/javascript">
    $(document).ready(function() {

        if (get('remove_spo')) {
            if (get('spoitems')) {
                remove('spoitems');
            }
            remove('remove_spo');
        }
        <?php
        if ($this->session->userdata('remove_spo')) {
            ?>
            if (get('spoitems')) {
                remove('spoitems');
            }
            <?php
            $this->tec->unset_data('remove_spo');
        }
        ?>
        function attach(x) {
            if (x !== null) {
                return '<a href="<?=base_url();?>uploads/'+x+'" target="_blank" class="btn btn-primary btn-block btn-xs"><i class="fa fa-chain"></i></a>';
            }
            return '';
        }

        var table = $('#purData').DataTable({

            'ajax' : { url: '<?=site_url('purchases/get_purchases');?>', type: 'POST', "data": function ( d ) {
                d.<?=$this->security->get_csrf_token_name();?> = "<?=$this->security->get_csrf_hash()?>";
            }},
            "buttons": [
            { extend: 'copyHtml5', 'footer': true, exportOptions: { columns: [ 0, 1, 2, 3, 4, 5 ] } },
            { extend: 'excelHtml5', 'footer': true, exportOptions: { columns: [ 0, 1, 2, 3, 4, 5 ] } },
            { extend: 'csvHtml5', 'footer': true, exportOptions: { columns: [ 0, 1, 2, 3, 4, 5 ] } },
            { extend: 'pdfHtml5', orientation: 'landscape', pageSize: 'A4', 'footer': true,
            exportOptions: { columns: [ 0, 1, 2, 3, 4, 5 ] } },
            { extend: 'colvis', text: 'Columns'},
            ],
            "columns": [
            { "data": "id", "visible": false },
            { "data": "date", "render": hrld },
            { "data": "reference" },
            { "data": "total", "render": currencyFormat },
            { "data": "note" },
            { "data": "attachment", "render": attach, "searchable": false, "orderable": false },
            { "data": "Actions", "searchable": false, "orderable": false }
            ],
            "footerCallback": function (  tfoot, data, start, end, display ) {
                var api = this.api(), data;
                $(api.column(3).footer()).html( cf(api.column(3).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
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

<style type="text/css">.table td:nth-child(3) { text-align: right; }</style>
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
                                    <option value="seleccione" selected>Seleccione una opción</option>
                                    <option value="byPurchase">Reporte de compras</option>
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
                                    <label for="louie">Crédito</label>
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
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title"><?= lang('list_results'); ?></h3>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table id="purData" class="table table-striped table-bordered table-condensed table-hover" style="margin-bottom:5px;">
                            <thead>
                                <tr class="active">
                                    <th style="max-width:30px;"><?= lang("id"); ?></th>
                                    <th class="col-xs-2"><?= lang('date'); ?></th>
                                    <th class="col-xs-2"><?= lang('reference'); ?></th>
                                    <th class="col-xs-1"><?= lang('total'); ?></th>
                                    <th><?= lang('note'); ?></th>
                                    <th style="width:25px; padding-right:5px;"><i class="fa fa-chain"></i></th>
                                    <th style="width:75px;"><?= lang('actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="7" class="dataTables_empty"><?= lang('loading_data_from_server'); ?></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="active">
                                    <th style="max-width:30px;"><input type="text" class="text_filter" placeholder="[<?= lang('id'); ?>]"></th>
                                    <th class="col-sm-2"><span class="datepickercon"><input type="text" class="text_filter datepicker" placeholder="[<?= lang('date'); ?>]"></span></th>
                                    <th class="col-sm-2"><input type="text" class="text_filter" placeholder="[<?= lang('reference'); ?>]"></th>
                                    <th class="col-xs-1"><?= lang('total'); ?></th>
                                    <th><input type="text" class="text_filter" placeholder="[<?= lang('note'); ?>]"></th>
                                    <th style="width:25px; padding-right:5px;"><i class="fa fa-chain"></i></th>
                                    <th style="width:75px;"><?= lang('actions'); ?></th>
                                </tr>
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
        
        $('.datepicker').datetimepicker(
            {
                format: 'YYYY-MM-DD', 
                showClear: true, 
                showClose: true, 
                useCurrent: false, 
                widgetPositioning: {
                    horizontal: 'auto', 
                    vertical: 'bottom'},
                     widgetParent: $('.dataTable tfoot')
            });

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
        
        $("#tipoReporte").change(function() {
            if($(this).val() != "seleccione"){
                $("#datesFilter").css("display", "inline-block");
            } else {
                $("#datesFilter").css("display", "none");
                $("#date_inicio").val("")

                $("#date_fin").val("<?php echo date('Y-m-d'); ?>")
            }
        });

        $("#print").click(function() {

            if($("#tipoReporte").val() == "byPurchase"){
                let data = ["Reporte de compras",$('#date_inicio').val(),$('#date_fin').val(),$('#store').val()]
                let url=  new URL(window.location.origin+"/reports/reportspurchase/");
                url.searchParams.append('filtros', data)
                window.open(url.toString(), '_blank')
                $("#datesFilter").css("display", "none");
                $("#date_inicio").val("")
                $("#date_fin").val("<?php echo date('Y-m-d'); ?>")
                $('#reportsModal').modal('hide');
            } 

        });    
    });
</script>
