<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<section class="content">
    <div class="row">
        <div class="col-xs-12">
            <div class="modal-body">
                <fieldset>
                    <div class="row">
                        <div class="col-md-2 col-sm-3">
                            <label class="label-control">Sucursal:</label>
                        </div>
                        <div class="col-md-10 col-sm-9">
                            <?= $store->name ?>
                        </div>
                    </div>

                    <?php if($take_inventory): ?>
                    <div class="row">
                        <div class="col-md-2 col-sm-3">
                            <label class="label-control">Levantando inventario desde:</label>
                        </div>
                        <div class="col-md-10 col-sm-9">
                            <?= $take_inventory->created_at ?>
                            <?php if($Admin): ?>
                              <button class="btn btn-sm btn-warning" id="cancel">Cancelar</button>
                            <?php endif; ?>
                        </div>
                        <hr />
                    </div>

                    <div class="row">
                      <div class="col-md-12">
                        <div class="row">
                          <div class="col-md-4">
                            <label>Producto</label>
                            <select class="form-control select2" style="width: 100%;" id="product">
                              <?php foreach($products as $prod): ?>
                                <option value="<?= $prod->id ?>"><?= $prod->name ?> (<?= $prod->code ?>)</option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="col-md-4">
                            <label class="label-control">Cantidad</label>
                            <input type="number" class="form-control" autocomplete="off" id="quantity" />
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-md-4">
                            <label class="label-control">Descripción</label>
                            <input class="form-control" autocomplete="off" id="description" />
                          </div>
                          <div class="col-md-4">
                            <br />
                            <button class="btn btn-primary" id="add">Agregar</button>
                          </div>
                        </div>
                      </div>
                      <hr />
                    </div>

                    <div class="row">
                      <div class="col-md-12">
                        <br />
                        <br />
                        <label class="label-control">Productos agregados por ti:</label>
                        <table class="table table-bordered table-response table-striped">
                          <thead>
                            <tr>
                              <th>Código</th>
                              <th>Producto</th>
                              <th>Descripción</th>
                              <th>Cantidad</th>
                              <th>Agregado</th>
                              <th></th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach($items as $item): ?>
                              <tr>
                                <td class="text-center"><?= $item->code ?></td>
                                <td class="text-center"><?= $item->name ?></td>
                                <td class="text-center"><?= $item->description ?></td>
                                <td class="text-center"><?= $item->quantity ?></td>
                                <td class="text-center"><?= $item->created_at ?></td>
                                <td class="text-center"><button id="<?= $item->id ?>" class="btn btn-sm btn-danger remove-product"><i class="fa fa-trash-o" /></button></td>
                              </tr>
                            <?php endforeach; ?>
                            <?php if(count($items) == 0): ?>
                              <tr>
                                <td colspan="5" class="text-center">No hay ningun producto agregado por ti todavia</td>
                              </tr>
                            <?php endif; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <?php endif; ?>

                    <?php if(!$take_inventory && $Admin): ?>
                      <div class="row">
                        <div class="col-md-2 col-sm-3">
                            <label class="label-control">Levantar inventario:</label>
                        </div>
                        <div class="col-md-10 col-sm-9">
                            <button class="btn btn-primary" id="up">Levantar nuevo folio</button>
                        </div>
                    </div>
                    <?php endif; ?>
                </fieldset>
            </div>
        </div>
    </div>
</section>

<script src="<?= $assets ?>plugins/bootstrap-datetimepicker/js/moment.min.js" type="text/javascript"></script>
<script src="<?= $assets ?>plugins/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js" type="text/javascript"></script>
<script type="text/javascript">
    $(document).ready(function() {
      $("#up").click(function(){
        $.ajax({
          url: base_url + `reports/up_take_inventory/`,
          method: 'POST',
          success: function (payments) {
            window.location.reload()
          }
        })
      })

      $("#cancel").click(function(){
        if(confirm('¿Estas seguro de borrar el inventario?')){
          $.ajax({
            url: base_url + `reports/cancel_take_inventory/`,
            method: 'POST',
            success: function () {
              window.location.reload()
            }
          })
        }
      })

      $("#add").click(function(){
        const product_id = $("#product").val()
        const quantity = Number($("#quantity").val())
        const description = $("#description").val()

        if(product_id && quantity > 0){
          $.ajax({
            url: base_url + `reports/add_product_take_inventory/`,
            data: {
              product_id,
              quantity,
              description
            },
            method: 'POST',
            success: function (payments) {
              window.location.reload()
            }
          })
        }else{
          alert('Favor de llenar los datos')
        }
      })

      $(".remove-product").click(function(){
        const id = $(this).attr('id')
        $.ajax({
          url: base_url + `reports/remove_product_take_inventory/`,
          data: {
            id,
          },
          method: 'POST',
          success: function () {
            window.location.reload()
          }
        })
      })
    });
</script>

<style>
    .ui-autocomplete { 
        z-index: 9999999 !important; 
    }
</style>
<style>
    .select2-search--hide {
        display: unset !important;
    }
</style>