<div id="show_user_modal" class="modal fade" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div div class="modal-header">
                <h5 class="modal-title" id="myExtraLargeModalLabel">Maklumat Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col"></div>
                    <div class="col-2">
                        <img id="show-profile_path" alt="" class="avatar-lg h-auto d-block rounded">
                    </div>
                    <div class="col"></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <tbody>
                            <tr>
                                <th>Nama Pengguna : </th>
                                <td colspan="2" id="show-name"></td>
                                <th >Jawatan : </th>
                                <td colspan ="2" id="show-designation"></td>
                            </tr>
                            <tr>
                                <th>No. Telefon : </th>
                                <td id="show-tel_no"></td>
                                <th>Tarikh Lahir</th>
                                <td id="show-dob"></td>
                                <th>Kad Pengenalan</th>
                                <td id="show-identification"></td>
                            </tr>
                            <tr>
                                <th>Alamat</th>
                                <td colspan="5" id="show-address">

                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer text-end">
                <button type="reset" class="btn btn-secondary waves-effect waves-light"><i class="bx bx-reset font-size-16 align-middle me-2"></i> Set Semula</button>
                <a href="#" class="btn btn-success waves-effect waves-light" onclick="Swal.fire('Berjaya','Maklumat Pengguna dikemaskini','success');" data-bs-dismiss="modal"><i class="bx bx-save font-size-16 align-middle me-2"></i> Hantar</a>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
