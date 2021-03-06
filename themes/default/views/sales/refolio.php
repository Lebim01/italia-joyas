<?php (defined('BASEPATH')) or exit('No direct script access allowed'); ?>

<style>
    .status-paid {
        display: none;
    }
</style>

<script type="text/javascript">
    $(document).ready(function() {

        function status(x) {
            var paid = '<?= lang('paid'); ?>';
            var partial = '<?= lang('partial'); ?>';
            var due = '<?= lang('due'); ?>';

            if (x == 'paid') {
                return '<div class="text-center"><span class="label label-success">' + paid + '</span></div>';
            } else if (x == 'partial') {
                return '<div class="text-center"><span class="sale_status label label-primary">' + partial + '</span></div>';
            } else if (x == 'due') {
                return '<div class="text-center"><span class="sale_status label label-danger">' + due + '</span></div>';
            } else {
                return '<div class="text-center"><span class="sale_status label label-default">' + x + '</span></div>';
            }
        }

        function transactionType(x) {
            let credit = '<?= lang('credit') ?>';
            let apart = '<?= lang('apart') ?>';
            let cash = '<?= lang('liquidate') ?>';

            if (x == 'credit') {
                return '<div class="text-center"><span class="label label-danger">' + credit + '</span></div>';
            } else if (x == 'apart') {
                return '<div class="text-center"><span class="label label-primary">' + apart + '</span></div>';
            } else if (x == 'liquidate') {
                return '<div class="text-center"><span class="label label-success">' + cash + '</span></div>';
            } else {
                return '<div class="text-center"><span class="label label-default">' + x + '</span></div>';
            }
        }

        var table = $('#SLData').DataTable({

            'ajax': {
                url: '<?= site_url('sales/get_sales_refolio'); ?>',
                type: 'POST',
                "data": function(d) {
                    d.<?= $this->security->get_csrf_token_name(); ?> = "<?= $this->security->get_csrf_hash() ?>";
                }
            },
            "buttons": [{
                    extend: 'copyHtml5',
                    'footer': true,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8]
                    }
                },
                {
                    extend: 'excelHtml5',
                    'footer': true,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8]
                    }
                },
                {
                    extend: 'csvHtml5',
                    'footer': true,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8]
                    }
                },
                {
                    extend: 'pdfHtml5',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    'footer': true,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8]
                    }
                },
                {
                    extend: 'colvis',
                    text: 'Columns'
                },
            ],
            "columns": [
                {
                    "data": "id",
                },
                {
                    "data": "invoice",
                },
                {
                    "data": "date",
                    "render": hrld
                },
                {
                    "data": "transaction_type",
                    "render": transactionType
                },
                {
                    "data": "cashier_name"
                },
                {
                    "data": "customer_name"
                },
                {
                    "data": "grand_total",
                    "render": currencyFormat
                },
                {
                    "data": "paid",
                    "render": currencyFormat
                },
                {
                    "data": "status",
                    "render": status
                },
                {
                    "data": "Actions",
                    "searchable": false,
                    "orderable": false
                }
            ],
            "fnRowCallback": function(nRow, aData, iDisplayIndex) {
                nRow.id = aData.id;
                return nRow;
            },
            "footerCallback": function(tfoot, data, start, end, display) {
                var api = this.api(),
                    data;
                $(api.column(6).footer()).html(cf(api.column(6).data().reduce(function(a, b) {
                    return pf(a) + pf(b);
                }, 0)));
                $(api.column(7).footer()).html(cf(api.column(7).data().reduce(function(a, b) {
                    return pf(a) + pf(b);
                }, 0)));
            }

        });

        $('#search_table').on('keyup change', function(e) {
            var code = (e.keyCode ? e.keyCode : e.which);
            if (((code == 13 && table.search() !== this.value) || (table.search() !== '' && this.value === ''))) {
                table.search(this.value).draw();
            }
        });

        table.columns().every(function() {
            var self = this;
            $('input.datepicker', this.footer()).on('dp.change', function(e) {
                self.search(this.value).draw();
            });
            $('input:not(.datepicker)', this.footer()).on('keyup change', function(e) {
                var code = (e.keyCode ? e.keyCode : e.which);
                if (((code == 13 && self.search() !== this.value) || (self.search() !== '' && this.value === ''))) {
                    self.search(this.value).draw();
                }
            });
            $('select', this.footer()).on('change', function(e) {
                self.search(this.value).draw();
            });
        });

    });
</script>

<section class="content">
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header">

                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table id="SLData" class="table table-striped table-bordered table-condensed table-hover">
                            <thead>
                                <tr class="active">
                                    <th style="max-width:30px;"><?= lang("id"); ?></th>
                                    <th style="max-width:30px;">Refolio</th>
                                    <th class="col-xs-2"><?= lang("date"); ?></th>
                                    <th class="col-xs-1"><?= lang("transaction_type"); ?></th>
                                    <th><?= lang('cashier') ?></th>
                                    <th><?= lang("customer"); ?></th>
                                    <th class="col-xs-1"><?= lang("grand_total"); ?></th>
                                    <th class="col-xs-1"><?= lang("paid"); ?></th>
                                    <th class="col-xs-1"><?= lang("status"); ?></th>
                                    <th style="min-width:115px; max-width:115px; text-align:center;"><?= lang("actions"); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="12" class="dataTables_empty"><?= lang('loading_data_from_server'); ?></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="active">
                                    <th style="max-width:30px;"><input type="text" class="text_filter" placeholder="[<?= lang('id'); ?>]"></th>
                                    <th style="max-width:30px;"><input type="text" class="text_filter" placeholder="[invoice]"></th>
                                    <th class="col-sm-2"><span class="datepickercon"><input type="text" class="text_filter datepicker" placeholder="[<?= lang('date'); ?>]"></span></th>
                                    <th class="col-sm-1">
                                        <select class="select2 select_filter">
                                            <option value=""><?= lang("all"); ?></option>
                                            <?php foreach ($transaction_types as $type) : ?>
                                                <option value="<?= $type ?>"><?= lang($type) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </th>
                                    <th class="col-sm-2"><input type="text" class="text_filter" placeholder="[<?= lang('cashier'); ?>]"></th>
                                    <th class="col-sm-2"><input type="text" class="text_filter" placeholder="[<?= lang('customer'); ?>]"></th>
                                    <th class="col-sm-2"><?= lang("grand_total"); ?></th>
                                    <th class="col-sm-1"><?= lang("paid"); ?></th>
                                    <th class="col-sm-1">
                                        <select class="select2 select_filter">
                                            <option value=""><?= lang("all"); ?></option>
                                            <option value="paid"><?= lang("paid"); ?></option>
                                            <option value="partial"><?= lang("partial"); ?></option>
                                            <option value="due"><?= lang("due"); ?></option>
                                        </select>
                                    </th>
                                    <th class="col-sm-1"><?= lang("actions"); ?></th>
                                </tr>
                                <tr>
                                    <td colspan="12" class="p0"><input type="text" class="form-control b0" name="search_table" id="search_table" placeholder="<?= lang('type_hit_enter'); ?>" style="width:100%;"></td>
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
<?php if ($Admin) { ?>
    <div class="modal fade" id="stModal" tabindex="-1" role="dialog" aria-labelledby="stModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="fa fa-times"></i></span></button>
                    <h4 class="modal-title" id="stModalLabel"><?= lang('update_status'); ?> <span id="status-id"></span></h4>
                </div>
                <?= form_open('sales/status'); ?>
                <div class="modal-body">
                    <input type="hidden" value="" id="sale_id" name="sale_id" />
                    <div class="form-group">
                        <?= lang('status', 'status'); ?>
                        <?php $opts = array('paid' => lang('paid'), 'partial' => lang('partial'), 'due' => lang('due'))  ?>
                        <?= form_dropdown('status', $opts, set_value('status'), 'class="form-control select2 tip" id="status" required="required" style="width:100%;"'); ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= lang('close'); ?></button>
                    <button type="submit" class="btn btn-primary"><?= lang('update'); ?></button>
                </div>
                <?= form_close(); ?>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        $(document).ready(function() {
            $(document).on('click', '.sale_status', function() {
                var sale_id = $(this).closest('tr').attr('id');
                var curr_status = $(this).text();
                var status = curr_status.toLowerCase();
                $('#status-id').text('( <?= lang('sale_id'); ?> ' + sale_id + ' )');
                $('#sale_id').val(sale_id);
                $('#status').val(status);
                $('#status').select2('val', status);
                $('#stModal').modal()
            });
            $(document).on('click', '#print_report', function() {
                $('#reportsModal').modal({ backdrop: 'static' });
            });
        });
    </script>
<?php } ?>
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

        $("#tipoReporte").change(function() {
            if($(this).val() != "seleccione"){
                $("#datesFilter").css("display", "inline-block");
            } else {
                $("#datesFilter").css("display", "none");
                $("#date_inicio").val("")

                $("#date_fin").val("")
            }
        });

        $("#print").click(function() {
            if($("#tipoReporte").val() == "byProducts"){
                let data = ["Reporte de ventas por producto",$('#date_inicio').val(),$('#date_fin').val()]
                let url=  new URL(window.location.href+"/reports/");
                url.searchParams.append('filtros', data)
                window.open(url.toString(), '_blank')
                $("#datesFilter").css("display", "none");
                $("#date_inicio").val("")
                $("#date_fin").val("")
                $('#tipoReporte').val("")
                $('#reportsModal').modal('hide');
            } 

            if($("#tipoReporte").val() == "bySales"){
                let data = ["Reporte de ventas",$('#date_inicio').val(),$('#date_fin').val()]
                let url=  new URL(window.location.href+"/reports/");
                url.searchParams.append('filtros', data)
                window.open(url.toString(), '_blank')
                $("#datesFilter").css("display", "none");
                $("#date_inicio").val("")
                $("#date_fin").val("")
                $('#tipoReporte').val("")
                $('#reportsModal').modal('hide');
            } 

        });
    });
</script>