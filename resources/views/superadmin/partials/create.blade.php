<div id="create" class="modal fade" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div div class="modal-header">
                <h5 class="modal-title" id="myExtraLargeModalLabel">Maklumat Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-2 col-form-label"><b>Gambar :</b> </div>
                    <div class="col"><input type="file" class="form-control"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-2 col-form-label"> <b>Nama Pengguna :</b> </div>
                    <div class="col"><input type="text" class="form-control"></div>
                    <div class="col-2 col-form-label"> <b>Jawatan :</b> </div>
                    <div class="col"><input type="text" class="form-control"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-2 col-form-label"> <b>No telefon :</b> </div>
                    <div class="col"><input type="text" class="form-control"></div>
                    <div class="col-2 col-form-label"> <b>Tarikh Lahir :</b> </div>
                    <div class="col"><input type="date" class="form-control"></div>
                    <div class="col-2 col-form-label"> <b>Kad pengenalan :</b> </div>
                    <div class="col"><input type="text" class="form-control"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-2 col-form-label"> <b>Alamat Pengguna :</b> </div>
                    <div class="col">
                        <form method="post">
                            <textarea id="elm2" name="area"></textarea>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer text-end">
                <button type="reset" class="btn btn-secondary waves-effect waves-light"><i class="bx bx-reset font-size-16 align-middle me-2"></i> Set Semula</button>
                <a href="#" class="btn btn-success waves-effect waves-light" onclick="Swal.fire('Berjaya','Maklumat Pengguna ditambah','success');" data-bs-dismiss="modal"><i class="bx bx-save font-size-16 align-middle me-2"></i> Hantar</a>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
