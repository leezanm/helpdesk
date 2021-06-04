<div id="edit_user_modal" class="modal fade" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div div class="modal-header">
                <h5 class="modal-title" id="myExtraLargeModalLabel">Maklumat Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="#edit_user_modal_form" action="" method="post">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col"></div>
                        <div class="col-2">
                            <img id="edit-profile_path" alt="" class="avatar-lg h-auto d-block rounded ">
                        </div>
                        <div class="col"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="col-form-label">Nama</label>
                        </div>
                        <div class="col">
                            <input type="text" name="name" class="form-control">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            Jawatan
                        </div>
                        <div class="col">
                            <input type="text" name="designation" class="form-control">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            No Telefon
                        </div>
                        <div class="col">
                           <input type="text" class="form-control" name="tel_no">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            Tarikh Lahir
                        </div>
                        <div class="col">
                            <input type="date" class="form-control" name="dob">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            No Kad Pengenalan
                        </div>
                        <div class="col">
                            <input type="text" class="form-control" name="identification">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            Alamat
                        </div>
                        <div class="col">
                             <textarea  name="address" class="form-control" rows="5"> </textarea>
                        </div>
                    </div>

                </div>
                <div class="modal-footer text-end">
                    <button type="reset" class="btn btn-secondary waves-effect waves-light"><i
                            class="bx bx-reset font-size-16 align-middle me-2"></i> Set Semula</button>
                    <a href="#" class="btn btn-success waves-effect waves-light"
                        onclick="Swal.fire('Berjaya','Maklumat Pengguna dikemaskini','success');"
                        data-bs-dismiss="modal"><i class="bx bx-save font-size-16 align-middle me-2"></i> Hantar</a>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
