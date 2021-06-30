<div id="create_user_modal" class="modal fade" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div div class="modal-header">
                <h5 class="modal-title" id="myExtraLargeModalLabel">Maklumat Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="create_user_modal_form" action="{{route('user.store')}}" method="post" enctype="multipart/form-data">

                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-2 col-form-label"><b>Gambar :</b> </div>
                        <div class="col">
                            <input type="file" name="profile_path" class="form-control">
                            <p class="text-danger error-profile_path error collapse"></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-2 col-form-label"> <b>Nama Penuh Pengguna :</b> </div>
                        <div class="col">
                            <input type="text" name="name" class="form-control">
                            <p class="text-danger error-name error collapse"></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-2 col-form-label"> <b>Nama Pengguna (AD):</b> </div>
                        <div class="col">
                            <input type="text" name="username" class="form-control">
                            <p class="text-danger error-username error collapse"></p>
                        </div>
                        <div class="col-2 col-form-label"><b>Emel :</b> </div>
                        <div class="col">
                            <input type="text" name="email" class="form-control">
                            <p class="text-danger error-email error collapse"></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-2 col-form-label"> <b>No telefon :</b> </div>
                        <div class="col">
                            <input type="text" name="tel_no" class="form-control">
                            <p class="text-danger error-tel_no error collapse"></p>
                        </div>

                        <div class="col-2 col-form-label"> <b>Kad pengenalan :</b> </div>
                        <div class="col">
                            <input type="text" name="ic_no" class="form-control">
                            <p class="text-danger error-ic_no error collapse"></p>
                        </div>
                    </div>

                    <div class="row mb-3">

                        <div class="col-2 col-form-label"> <b>Jawatan :</b> </div>
                        <div class="col">
                            <input type="text" name="designation" class="form-control">
                            <p class="text-danger error-designation error collapse"></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-2 col-form-label"> <b>Alamat Pengguna :</b> </div>
                        <div class="col">
                            <textarea name="address" class="form-control" rows="5"></textarea>
                            <p class="text-danger error-address error collapse"></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-2 col-form-label"> <b>Peranan:</b> </div>
                        <div class="col">
                            @foreach ($roles as $role)
                                <div class="form-check">
                                    <input class="form-check-input" name="roles[]" type="checkbox" id="role_check-{{$role->id}}" value="{{$role->name}}" @if($role->name == 'pengguna') checked @endif>
                                    <label class="form-check-label" for="role_check-{{$role->id}}">
                                        {{$role->desc}}
                                    </label>
                                </div>
                            @endforeach
                            <p class="text-danger error-roles error collapse"></p>
                        </div>
                    </div>

                </div>
                <div class="modal-footer text-end">
                    <button type="reset" class="btn btn-secondary waves-effect waves-light"><i class="bx bx-reset font-size-16 align-middle me-2"></i> Set Semula</button>
                    <button class="btn btn-success btnSubmit" type="button"><i class="bx bx-save font-size-16 align-middle me-2"></i> Hantar</button>
                    {{-- <a href="#" class="btn btn-success waves-effect waves-light" onclick="Swal.fire('Berjaya','Maklumat Pengguna ditambah','success');" data-bs-dismiss="modal"><i class="bx bx-save font-size-16 align-middle me-2"></i> Hantar</a> --}}
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
