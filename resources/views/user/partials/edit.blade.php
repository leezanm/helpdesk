<div id="edit_user_modal" class="modal fade" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div div class="modal-header">
                <h5 class="modal-title" id="myExtraLargeModalLabel">Maklumat Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="edit_user_modal_form" action="" method="post">
                <input id="edit-user_id" type="hidden" name="user_id">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col">
                           <center> <img id="edit-profile_path" alt="" class="avatar-lg h-auto d-block rounded "></center>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="col-form-label">Nama</label>
                        </div>
                        <div class="col">
                            <input type="text" name="name" class="form-control">
                            <p class="text-danger error-name error collapse"></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="col-form-label">ID Pengguna <a role="button" class="align-middle"> <i class="bx bx-info-circle align-middle font-size-16" data-bs-toggle="tooltip" data-bs-placement="right" title="ID daftar masuk komputer"></i> </a></label>
                        </div>
                        <div class="col">
                            <input type="text" name="username" class="form-control" disabled>
                            <p class="text-danger error-username error collapse"></p>
                        </div>

                        <div class="col-md-3">
                            <label class="col-form-label">Email</label>
                        </div>
                        <div class="col">
                           <input type="text" class="form-control" name="email" readonly>
                           <p class="text-danger error-email error collapse"></p>
                        </div>
                    </div>

                    <div class="row mb-3">

                        <div class="col-md-3">
                            <label class="col-form-label">No Telefon</label>
                        </div>
                        <div class="col">
                           <input type="text" class="form-control" name="tel_no">
                           <p class="text-danger error-tel_no error collapse"></p>
                        </div>

                        <div class="col-md-3">
                            <label class="col-form-label">No Kad Pengenalan</label>
                        </div>
                        <div class="col">
                            <input type="text" class="form-control" name="ic_no">
                            <p class="text-danger error-ic_no error collapse"></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="col-form-label">Jawatan</label>
                        </div>
                        <div class="col">
                            <input type="text" name="designation" class="form-control">
                            <p class="text-danger error-designation error collapse"></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="col-form-label">Alamat</label>
                        </div>
                        <div class="col">
                             <textarea  name="address" class="form-control" rows="5"> </textarea>
                             <p class="text-danger error-address error collapse"></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-3 col-form-label"> <b>Peranan:</b> </div>
                        <div class="col">
                            @foreach ($roles as $role)
                                <div class="form-check">
                                    <input class="form-check-input edit_role_check" name="roles[]" type="checkbox" id="edit_role_check-{{$role->id}}" value="{{$role->name}}">
                                    <label class="form-check-label" for="edit_role_check-{{$role->id}}">
                                        {{$role->desc}}
                                    </label>
                                </div>
                            @endforeach
                            <p class="text-danger error-roles error collapse"></p>
                        </div>
                    </div>

                </div>
                <div class="modal-footer text-end">
                    <button type="reset" class="btn btn-secondary waves-effect waves-light"><i
                            class="bx bx-reset font-size-16 align-middle me-2"></i> Set Semula</button>
                    <a href="#" class="btn btn-success waves-effect waves-light btnSubmit">
                        <i class="bx bx-save font-size-16 align-middle me-2"></i> Simpan
                    </a>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
