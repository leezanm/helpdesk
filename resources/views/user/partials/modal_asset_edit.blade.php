<div id="kemaskini-aset" class="modal fade" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="user_id" value="{{$user->id}}">
                <div class="modal-header">
                    <h5 class="modal-title mt-0" id="myModalLabel">Kemaskini Aset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Jenis <span class="text-danger">*</span>  </label>
                                <input class="form-control" name="type">
                                <span class="text-danger collapse error error-type"></span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="formrow-email-input" class="form-label">Jenama <span class="text-danger">*</span></label>
                                <input class="form-control" name="brand">
                                <span class="text-danger collapse error error-brand"></span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="formrow-password-input" class="form-label">Model <span class="text-danger">*</span></label>
                                <input class="form-control" name="model">
                                <span class="text-danger collapse error error-model"></span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="formrow-email-input" class="form-label">No. Siri Pendaftaran <span class="text-danger">*</span></label>
                                <input class="form-control" name="registration_serial_no">
                                <span class="text-danger collapse error error-registration_serial_no"></span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">No. Siri Pembuat <span class="text-danger">*</span></label>
                                <input class="form-control" name="supplier_serial_no">
                                <span class="text-danger collapse error error-supplier_serial_no"></span>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary waves-effect waves-light submit">Simpan</button>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
