<?php (defined('BASEPATH')) or exit('No direct script access allowed'); ?>
<script src="<?= $assets ?>dev/js/webcamjs/webcam.min.js" type="text/javascript"></script>

<script type="text/javascript">
    $(document).ready(function() {
        var in_stock = <?= isset($_POST['in_stock']) ? $_POST['in_stock'] : $Settings->default_in_stock ?>

        function ptype(x) {
            if (x == 'standard') {
                return '<?= lang('standard'); ?>';
            } else if (x == 'combo') {
                return '<?= lang('combo'); ?>';
            } else if (x == 'service') {
                return '<?= lang('service'); ?>';
            } else {
                return x;
            }
        }

        function image(n) {
            if (n !== null) {
                return '<div style="width:32px; margin: 0 auto;"><a href="<?= base_url(); ?>uploads/' + n + '" class="open-image"><img src="<?= base_url(); ?>uploads/thumbs/' + n + '" alt="" class="img-responsive"></a></div>';
            }
            return '';
        }

        function method(n) {
            return (n == 0) ? '<span class="label label-primary"><?= lang('inclusive'); ?></span>' : '<span class="label label-warning"><?= lang('exclusive'); ?></span>';
        }

        var table = $('#prTables').DataTable({
            'ajax': {
                url: '<?= site_url('products/get_products/' . $store->id . '/'); ?>' + `?in_stock=${in_stock}`,
                type: 'POST',
                "data": function(d) {
                    d.<?= $this->security->get_csrf_token_name(); ?> = "<?= $this->security->get_csrf_hash() ?>";
                }
            },
            "buttons": [{
                    extend: 'copyHtml5',
                    'footer': false,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                    }
                },
                {
                    extend: 'excelHtml5',
                    'footer': false,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                    }
                },
                {
                    extend: 'csvHtml5',
                    'footer': false,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                    }
                },
                {
                    extend: 'pdfHtml5',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    'footer': false,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                    }
                },
                {
                    extend: 'colvis',
                    text: 'Columns'
                },
            ],
            "columns": [{
                    "data": "pid",
                    "visible": false
                },
                {
                    "data": "image",
                    "searchable": false,
                    "orderable": false,
                    "render": image
                },
                {
                    "data": "code"
                },
                {
                    "data": "pname"
                },
                {
                    "data": "type",
                    "render": ptype
                },
                {
                    "data": "cname"
                },
                {
                    "data": "quantity",
                    "render": quantityFormat
                },
                {
                    "data": "tax"
                },
                {
                    "data": "tax_method",
                    "render": method
                },
                <?php if ($Admin) { ?> {
                        "data": "cost",
                        "render": currencyFormat,
                        "searchable": false
                    },
                <?php } ?> {
                    "data": "price",
                    "render": currencyFormat,
                    "searchable": false
                },
                {
                    "data": "Actions",
                    "searchable": false,
                    "orderable": false
                }
            ]

        });

        // $('#prTables tfoot th:not(:last-child, :nth-last-child(2), :nth-last-child(3))').each(function () {
        //     var title = $(this).text();
        //     $(this).html( '<input type="text" class="text_filter" placeholder="'+title+'" />' );
        // });

        $('#search_table').on('keyup change', function(e) {
            var code = (e.keyCode ? e.keyCode : e.which);
            if (((code == 13 && table.search() !== this.value) || (table.search() !== '' && this.value === ''))) {
                table.search(this.value).draw();
            }
        });

        table.columns().every(function() {
            var self = this;
            $('input', this.footer()).on('keyup change', function(e) {
                var code = (e.keyCode ? e.keyCode : e.which);
                if (((code == 13 && self.search() !== this.value) || (self.search() !== '' && this.value === ''))) {
                    self.search(this.value).draw();
                }
            });
            $('select', this.footer()).on('change', function(e) {
                self.search(this.value).draw();
            });
        });

        $("#in_stock").change(function() {
            in_stock = $(this).prop('checked') ? 1 : 0
            table.ajax.url('<?= site_url('products/get_products/' . $store->id . '/'); ?>' + `?in_stock=${in_stock}`).load()
        })

        $('#prTables').on('click', '.image', function() {
            var a_href = $(this).attr('href');
            var code = $(this).attr('id');

            $('#myModalLabel').text(code);
            $('#product_image').attr('src', a_href);
            $('#picModal').modal();
            return false;
        });
        $('#prTables').on('click', '.barcode', function() {
            var a_href = $(this).attr('href');
            var code = $(this).attr('id');

            $('#myModalLabel').text(code);
            $('#product_image').attr('src', a_href);
            $('#picModal').modal();
            return false;
        });
        $('#prTables').on('click', '.open-image', function() {
            var a_href = $(this).attr('href');
            var code = $(this).closest('tr').find('.image').attr('id');
            window.id = $(this).closest('tr').find('.image').attr('pid');

            $('#myModalLabel').text(code);
            $('#product_image').attr('src', a_href);
            $('#picModal').modal();
            return false;
        });

        $("#button_camera_capture").click(open_camera)
        $("#take_picture").click(take_snapshot)
        $("#retry_picture").click(retry_picture)
        $("#cancel_picture").click(cancel_picture)
        $("#upload_picture").click(upload_picture)

        $("#camera_capture").hide()
        $("#results").hide()

        function hide_capture_camera(){
            $("#camera_capture").hide();
        }

        function take_snapshot() {
            hide_capture_camera()
			$("#results").show()

			// take snapshot and get image data
			Webcam.snap( function(data_uri) {
				// display results in page
				document.getElementById('picture_taken').innerHTML = '<img id="picture_data" src="'+data_uri+'"/>';
			});
		}

        function retry_picture(){
            hide_results()
            close_camera()
            open_camera()
        }

        function hide_results(){
            $("#results").hide()
        }

        function open_camera(){
            $("#button_camera_capture").hide()
            $("#camera_capture").show();

            Webcam.set({
                width: 500,
                height: 350,
                image_format: 'jpeg',
                jpeg_quality: 100
            });
            Webcam.attach( '#camera_video' );
        }

        function close_camera(){
            Webcam.reset()
            $("#button_camera_capture").show()
            hide_capture_camera()
        }

        function cancel_picture(){
            close_camera()
            hide_results()
            hide_capture_camera()
            $("#button_camera_capture").show()
        }

        function upload_picture(){
			const base64image =  document.getElementById("picture_data").src;
            $.ajax({
                type: 'POST',
                url: '<?= site_url('products/update_picture/') ?>' + window.id + '/',
                dataType: 'json',
                data: {
                    base64: base64image
                },
                complete: function (data) {
                    console.log('success')
                    $('#picModal').modal('hide')
                    table.ajax.reload()
                },
            });
        }

        $('#picModal').on('shown.bs.modal', function (e) {
            
        })
        $('#picModal').on('hidden.bs.modal', function (e) {
            cancel_picture()
        })
    });
</script>
<style type="text/css">
    .table td:first-child {
        padding: 1px;
    }

    .table td:nth-child(6),
    .table td:nth-child(7),
    .table td:nth-child(8) {
        text-align: center;
    }

    .table td:nth-child(9)<?= $Admin ? ', .table td:nth-child(10)' : ''; ?> {
        text-align: right;
    }
</style>
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
                                    <option value="seleccione" selected="selected">Seleccione una opción</option>
                                    <option value="byStock">Reporte de existencia de productos</option>
                                    <option value="byMovements">Reporte de movimientos de productos</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row" style="display:none" id="linfamFilter">
                        <div class="col-xs-12">
                            <div class="form-group">
                                <input type="checkbox" id="existencia" value="existencia"> Solo productos en existencia<br><br>
                                <input type="text" id="linea" placeholder="Línea">
                                <input type="text" id="familia" placeholder="Familia">
                            </div>
                        </div>
                    </div>
                    <div class="row" style="display:none" id="datesFilter">
                        <div class="col-xs-12">
                            <div class="form-group">
                                <input id="date_inicio" type="text" class=" datepicker2" placeholder="Fecha de inicio">
                                <input id="date_fin" type="text" class=" datepicker2" placeholder="Fecha fin" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row" style="display:none" id="codeFilter">
                        <div class="col-xs-12">
                            <div class="form-group">
                                <input type="text" id="codigo" placeholder="Código">
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
                    <?php if (!$this->session->userdata('has_store_id')) { ?>
                        <div class="dropdown pull-right">
                            <button class="btn btn-primary" id="dLabel" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?= $store->name . ' (' . $store->code . ')'; ?>
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dLabel">
                                <?php
                                foreach ($stores as $st) {
                                    if ($store->id != $st->id) {
                                        echo "<li><a href='" . site_url('products/?store_id=' . $st->id) . "'>{$st->name} ({$st->code})</a></li>";
                                    }
                                }
                                ?>
                            </ul>
                        </div>
                    <?php } ?>
                    <?= form_open("products"); ?>
                    <div class="col-sm-3">
                        <label class="checkbox" for="in_stock">
                            <input type="checkbox" name="in_stock" value="1" id="in_stock" <?= (isset($_POST['in_stock']) && $_POST['in_stock'] == 1) || $Settings->default_in_stock == 1 ? 'checked="checked"' : "" ?> />
                            Mostrar solo en existencia
                        </label>
                    </div>
                    <?= form_close(); ?>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table id="prTables" class="table table-striped table-bordered table-hover" style="margin-bottom:5px;">
                            <thead>
                                <tr class="active">
                                    <th style="max-width:30px;"><?= lang("id"); ?></th>
                                    <th style="max-width:30px;"><?= lang("image"); ?></th>
                                    <th class="col-xs-1"><?= lang("code"); ?></th>
                                    <th><?= lang("name"); ?></th>
                                    <th class="col-xs-1"><?= lang("type"); ?></th>
                                    <th class="col-xs-1"><?= lang("category"); ?></th>
                                    <th class="col-xs-1"><?= lang("quantity"); ?></th>
                                    <th class="col-xs-1"><?= lang("tax"); ?></th>
                                    <th class="col-xs-1"><?= lang("method"); ?></th>
                                    <?php if ($Admin) { ?>
                                        <th class="col-xs-1"><?= lang("cost"); ?></th>
                                    <?php } ?>
                                    <th class="col-xs-1"><?= lang("price"); ?></th>
                                    <th style="width:165px;"><?= lang("actions"); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="12" class="dataTables_empty"><?= lang('loading_data_from_server'); ?></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th style="max-width:30px;"><input type="text" class="text_filter" placeholder="[<?= lang('id'); ?>]"></th>
                                    <th style="max-width:30px;"><?= lang("image"); ?></th>
                                    <th class="col-xs-1"><input type="text" class="text_filter" placeholder="[<?= lang('code'); ?>]"></th>
                                    <th><input type="text" class="text_filter" placeholder="[<?= lang('name'); ?>]"></th>
                                    <th class="col-xs-1"><input type="text" class="text_filter" placeholder="[<?= lang('type'); ?>]"></th>
                                    <th class="col-xs-1"><input type="text" class="text_filter" placeholder="[<?= lang('category'); ?>]"></th>
                                    <th class="col-xs-1"><input type="text" class="text_filter" placeholder="[<?= lang('quantity'); ?>]"></th>
                                    <th class="col-xs-1"><input type="text" class="text_filter" placeholder="[<?= lang('tax'); ?>]"></th>
                                    <th class="col-xs-1">
                                        <select class="select2 select_filter">
                                            <option value=""><?= lang("all"); ?></option>
                                            <option value="0"><?= lang("inclusive"); ?></option>
                                            <option value="1"><?= lang("exclusive"); ?></option>
                                        </select>
                                    </th>
                                    <?php if ($Admin) { ?>
                                        <th class="col-xs-1"><?= lang("cost"); ?></th>
                                    <?php } ?>
                                    <th class="col-xs-1"><?= lang("price"); ?></th>
                                    <th style="width:165px;"><?= lang("actions"); ?></th>
                                </tr>
                                <tr>
                                    <td colspan="12" class="p0"><input type="text" class="form-control b0" name="search_table" id="search_table" placeholder="<?= lang('type_hit_enter'); ?>" style="width:100%;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="modal fade" id="picModal" tabindex="-1" role="dialog" aria-labelledby="picModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i></button>
                                    <button type="button" class="close mr10" onclick="window.print();"><i class="fa fa-print"></i></button>
                                    <h4 class="modal-title" id="myModalLabel">title</h4>
                                </div>
                                <div class="modal-body text-center">
                                    <img id="product_image" src="" alt="" style="max-width: 100%;" />
                                    <div id="camera_capture" style="display: flex; flex-direction: column; align-items: center;">
                                        <div id="camera_video"></div>
                                        <button class="btn btn-primary" id="take_picture">Capturar imagen</button>
                                    </div>
                                    <div id="results">
                                        <div id="picture_taken"></div>
                                        <button class="btn btn-danger" id="cancel_picture">Cancelar</button>
                                        <button class="btn btn-warning" id="retry_picture">Tomar otra vez</button>
                                        <button class="btn btn-success" id="upload_picture">Subir</button>
                                    </div>
                                    <div>
                                        <button id="button_camera_capture">Tomar foto</button>
                                    </div>
                                </div>
                            </div>
                        </div>
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

        var options = "";

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

       /*  $("#existencia" ).change(function() {
            if($("#existencia").is(':checked')) {  
                $("#linea").css("display", "none");
                $("#familia").css("display", "none");
                setToEmpty()
            } else {
                $("#linea").css("display", "inline-block");
                $("#familia").css("display", "inline-block");
            }
            
        }); */
        
        $("#tipoReporte").change(function() {
            if($(this).val() == "byStock"){
                $("#linfamFilter").css("display", "inline-block");
                $("#codeFilter").css("display", "none");
                $("#datesFilter").css("display", "none");
                $("#familia").val("")
                $("#linea").val("")
                $("#codigo").val("")
                $("#date_inicio").val("")
                $("#date_fin").val("<?php echo date('Y-m-d'); ?>")
        
            }

            if($(this).val() == "byMovements"){
                $("#linfamFilter").css("display", "none");
                $("#codeFilter").css("display", "inline-block");
                $("#datesFilter").css("display", "inline-block");
                setToEmpty()
            }

            if($(this).val() == "seleccione") {
                $("#linfamFilter").css("display", "none");
                $("#codeFilter").css("display", "none");
                $("#datesFilter").css("display", "none");
                setToEmpty()
            }
        });

        $("#print").click(function() {
            if($("#tipoReporte").val() == "byStock"){
                if($("#existencia").is(':checked')){
                    let data = ["Reporte solo en existencia",$('#store').val()]
                    let url=  new URL(window.location.origin+"/reports/reportsproducts/");
                    url.searchParams.append('filtros', data)
                    window.open(url.toString(), '_blank')
                    clear()
                    return
                } else {
                    let data = ["Reporte de existencia de productos",$('#linea').val(),$('#familia').val(),$('#store').val()]
                    let url=  new URL(window.location.origin+"/reports/reportsproducts/");
                    url.searchParams.append('filtros', data)
                    window.open(url.toString(), '_blank')
                    clear()
                    return
                }
            }

            if($("#tipoReporte").val() == "byMovements"){
                if($('#codigo').val() && $('#date_inicio').val() && $('#date_fin').val()){
                    let data = ["Reporte de movimientos de productos",$('#codigo').val(),$('#date_inicio').val(),$('#date_fin').val(),$('#store').val()]
                    let url=  new URL(window.location.origin+"/reports/reportsproducts/");
                    url.searchParams.append('filtros', data)
                    window.open(url.toString(), '_blank')
                    clear()
                    return
                } else {
                    alert ("Llene los campos correctamente")
                }
            }
        }); 

        $(".pull-left").click(function() {
            clear()
        })

        function clear() {
            $("#linfamFilter").css("display", "none");
            $("#datesFilter").css("display", "none");
            $("#codeFilter").css("display", "none");
            $("#familia").val("")
            $("#linea").val("")
            $("#codigo").val("")
            $("#date_inicio").val("")
            $("#date_fin").val("<?php echo date('Y-m-d'); ?>")
            $('#tipoReporte').val("")
            $('#reportsModal').modal('hide');
        }

        function setToEmpty(){
            $("#familia").val("")
            $("#linea").val("")
            $("#codigo").val("")
            $("#date_inicio").val("")
            $("#date_fin").val("<?php echo date('Y-m-d'); ?>")
        }
    });
</script>