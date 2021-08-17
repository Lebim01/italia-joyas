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
            paging: false,
            ajax: {
                url: '<?= site_url('reports/get_inventory/' . $v); ?>',
                type: 'POST',
                "data": function(d) {
                    d.<?= $this->security->get_csrf_token_name(); ?> = "<?= $this->security->get_csrf_hash() ?>";
                }
            },
            buttons: [{
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
            columns: [{
                    "data": "code",
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
                {
                    "data": "Fisico",
                },
            ],
        });

        $("#PayRData").delegate('.physical-inv', 'change', function(e){
            const val = $(this).val()
            if(val){
                $(this).addClass('border-success')
            }else{
                $(this).removeClass('border-success')
            }
            $("#apply").show()
        })

        $("#apply").click(function(){
            if(confirm('¿Esta seguro de aplicar los cambios? Esta accion es irreversible')){
                $("#apply").prop('disabled', true);
                $("#apply").html('Aplicando...');

                const items = []

                $("input.border-success").each(function(index){
                    const code = $(this).parent().parent().find('> :first-child').html()
                    const quantity = $(this).val()
                    items.push({
                        code,
                        quantity
                    })
                })

                $.ajax({
                    url: '<?= site_url('reports/aplpy_ajust_inventory/'); ?>',
                    method: 'POST',
                    data: {
                        items,
                        "<?= $this->security->get_csrf_token_name(); ?>": "<?= $this->security->get_csrf_hash() ?>"
                    },
                    success: function(){
                        window.location.reload()      
                    }
                })
            }
        })

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
    .border-success {
        border: 1px solid #28a745 !important;
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
                                    <th style="max-width:30px;"><?= lang("code"); ?></th>
                                    <th class="col-xs-3"><?= lang("product"); ?></th>
                                    <th class="col-xs-3"><?= lang("quantity"); ?></th>
                                    <th class="col-xs-2"><?= lang("apart"); ?></th>
                                    <th class="col-xs-2"><?= lang("available"); ?></th>
                                    <th class="col-xs-2">Fisico</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="dataTables_empty"><?= lang('loading_data_from_server') ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-sm-12 text-right">
                        <button class="btn btn-primary" id="apply" style="display: none">
                            Aplicar
                        </button>
                    </div>
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