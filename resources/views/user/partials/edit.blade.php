<div id="edit_user_modal" class="modal fade" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
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
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <tbody>
                                <tr>
                                    <th>Nama Pengguna</th>
                                    <td colspan="2">
                                        <input type="text" class="form-control" name="name">
                                    </td>
                                    <th>Jawatan :</th>
                                    <td colspan="2">
                                        <input type="text" class="form-control" name="designation" >
                                    </td>
                                </tr>
                                <tr>
                                    <th>No telefon</th>
                                    <td>
                                        <input type="text" class="form-control" name="tel_no">
                                    </td>
                                    <th>Tarikh Lahir</th>
                                    <td>
                                        <input type="date" class="form-control" name="dob">
                                    </td>
                                    <th>No Kad Pengenalan</th>
                                    <td>
                                        <input type="text" class="form-control" name="identification">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Alamat</th>
                                    <td colspan="5">
                                        <textarea id="elm1" name="address"> </textarea>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
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
