<?php (defined('BASEPATH')) or exit('No direct script access allowed'); ?>

<?php
$v = "?v=1";
?>

<script type="text/javascript">
    $(document).ready(function() {

        var pb = ['<?= lang('cash') ?>', '<?= lang('CC') ?>', '<?= lang('Cheque') ?>', '<?= lang('stripe') ?>', '<?= lang('gift_card') ?>'];

        function paid_by(x) {
            if (x == 'cash') {
                return pb[0];
            } else if (x == 'CC') {
                return pb[1];
            } else if (x == 'Cheque') {
                return pb[2];
            } else if (x == 'stripe') {
                return pb[3];
            } else if (x == 'gift_card') {
                return pb[4];
            } else {
                return x;
            }
        }

        var table = $('#PayRData').DataTable({
            "paging": false,
            'ajax': {
                url: '<?= site_url('reports/get_inventory/' . $v); ?>',
                type: 'POST',
                "data": function(d) {
                    d.<?= $this->security->get_csrf_token_name(); ?> = "<?= $this->security->get_csrf_hash() ?>";
                }
            },
            "buttons": [{
                    extend: 'copyHtml5',
                    'footer': true,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                },
                {
                    extend: 'excelHtml5',
                    'footer': true,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                },
                {
                    extend: 'csvHtml5',
                    'footer': true,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                },
                {
                    extend: 'pdfHtml5',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    'footer': true,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                },
                {
                    extend: 'colvis',
                    text: 'Columns'
                },
            ],
            "columns": [{
                    "data": "id",
                },
                {
                    "data": "name"
                },
                {
                    "data": "quantity"
                },
                {
                    "data": "apart"
                },
                {
                    "data": "available",
                },
            ],
        });

    });
</script>

<script type="text/javascript">
    $(document).ready(function() {
        $('#form').hide();
        $('.toggle_form').click(function() {
            $("#form").slideToggle();
            return false;
        });
    });
</script>
<style type="text/css">
    .table td:nth-child(3),
    .table td:nth-child(4),
    .table td:nth-child(5) {
        text-align: right;
    }
</style>
<section class="content">
    <div class="row">
        <div class="col-sm-12">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title"><?= lang('inventory_report'); ?>
                        <?php
                        if ($this->input->post('start_date')) {
                            echo "From " . $this->input->post('start_date') . " to " . $this->input->post('end_date');
                        }
                        ?>
                    </h3>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table id="PayRData" class="table table-bordered table-hover table-striped table-condensed reports-table">
                            <thead>
                                <tr>
                                    <th style="max-width:30px;"><?= lang("id"); ?></th>
                                    <th class="col-xs-3"><?= lang("product"); ?></th>
                                    <th class="col-xs-3"><?= lang("quantity"); ?></th>
                                    <th class="col-xs-2"><?= lang("apart"); ?></th>
                                    <th class="col-xs-2"><?= lang("available"); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="dataTables_empty"><?= lang('loading_data_from_server') ?></td>
                                </tr>
                            </tbody>
                        </table>
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
                                    <strong><?= $this->tec->formatMoney($total_sales->amount - $total_sales->paid); ?></strong>
                                    <?= lang("due"); ?>
                                </button>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>
</section>

<script src="<?= $assets ?>plugins/bootstrap-datetimepicker/js/moment.min.js" type="text/javascript"></script>
<script src="<?= $assets ?>plugins/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js" type="text/javascript"></script>
<script type="text/javascript">
    $(function() {
        $('.datetimepicker').datetimepicker({
            format: 'YYYY-MM-DD HH:mm'
        });
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
    });
</script>