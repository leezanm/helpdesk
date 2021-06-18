<div id="edit_status_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <form action="" id="edit_status_modal_form" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="myExtraLargeModalLabel">Kemaskini Status Aduan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-body">

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="col-form-label">Nama untuk kegunaan Sistem <span class="text-danger">*</span></label>
                                <input type="text" name="reference" class="form-control" disabled>
                                <p id="error-desc" class="text-danger collapse error"></p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="col-form-label">Nama untuk kegunaan umum <span class="text-danger">*</span></label>
                                <input type="text" name="desc" class="form-control">
                                <p id="error-desc" class="text-danger collapse error"></p>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer text-end">
                    <button class="btn btn-secondary" type="reset">Set Semula</button>
                    <button class="btn btn-success btnSubmit" type="button">Simpan</button>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
