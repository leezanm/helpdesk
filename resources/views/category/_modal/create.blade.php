<div id="create_type_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <form action="{{route('category.store')}}" id="create_type_modal_form" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="myExtraLargeModalLabel">Tambah Kategori Aduan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="col-form-label">Nama <span class="text-danger">*</span></label>
                            <input type="text" name="desc" class="form-control">
                            <p id="error-desc" class="text-danger collapse error"></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="col-form-label">Imej <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control">
                            <p id="error-file" class="text-danger collapse error"></p>
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
