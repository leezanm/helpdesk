<div id="user_feedback" class="modal fade" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div div class="modal-header">
                <h5 class="modal-title" id="myExtraLargeModalLabel">Laporan Mengikut Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <legend class="text-uppercase h5">Soal Selidik</legend>

                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="col-form-label">Rating</label>
                        </div>
                        <div class="col-md-4">
                            <div class="rating-star">
                                <input type="hidden" name="rating" class="rating" data-filled="mdi mdi-star text-warning" data-empty="mdi mdi-star-outline text-muted"/>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="col-form-label">Komen</label>
                        </div>
                        <div class="col-md-10">
                            <textarea name="feedback" class="form-control" rows="5" maxlength="255"></textarea>
                            <p>Komen anda adalah amat dihargai, maksimum 255 patah perkataan</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer text-end">
                    <a class="btn btn-success waves-effect waves-light submitFeedback">
                        <i class="bx bx-save font-size-16 align-middle me-2"></i>Simpan
                    </a>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
