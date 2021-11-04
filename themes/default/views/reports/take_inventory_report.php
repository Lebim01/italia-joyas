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
                              <button class="btn btn-sm btn-success" id="apply">Aplicar</button>
                            <?php endif; ?>
                        </div>
                        <hr />
                    </div>

                    <div class="row">
                      <div class="col-md-12">
                        <br />
                        <br />
                        <table class="table table-bordered table-response table-striped">
                          <thead>
                            <tr>
                              <th>Código</th>
                              <th>Producto</th>
                              <th>Descripción</th>
                              <th>Sistema</th>
                              <th>Fisico</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach($items as $item): ?>
                              <tr>
                                <td class="text-center"><?= $item->code ?></td>
                                <td class="text-center"><?= $item->name ?></td>
                                <td class="text-center"><?= $item->description ?></td>
                                <td class="text-center <?= $item->inventory !== $item->take_inventory ? 'text-danger' : '' ?>"><?= $item->inventory ?></td>
                                <td class="text-center <?= $item->inventory !== $item->take_inventory ? 'text-danger' : '' ?>"><?= $item->take_inventory ?></td>
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
      $("#apply").click(function(){
        if(confirm('¿Estas seguro de aplicar el inventario?')){
          $.ajax({
            url: base_url + `reports/apply_take_inventory/`,
            method: 'POST',
            success: function () {
              window.location.reload()
            }
          })
        }
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