<div id="password-reset" class="modal fade" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form action="{{route('account.password.update', Auth::user()->id)}}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="myExtraLargeModalLabel">Tukar Kata Laluan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="col-form-label">Kata Laluan Lama <span class="text-danger">*</span> </label>
                        </div>
                        <div class="col">
                            <input type="password" name="old_password" class="form-control">
                            <p class="text-danger collapse error error-old_password"></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="col-form-label">Kata Laluan Baru <span class="text-danger">*</span> </label>
                        </div>
                        <div class="col">
                            <input type="password" name="password" class="form-control">
                            <p class="text-danger collapse error error-password"></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="col-form-label">Pengesahan Kata Laluan Baru <span class="text-danger">*</span> </label>
                        </div>
                        <div class="col">
                            <input type="password" name="password_confirmation" class="form-control">
                            <p class="text-danger collapse error error-password_confirmation"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer text-end">
                    <button type="reset" class="btn btn-secondary waves-effect waves-light">
                        <i class="bx bx-reset font-size-16 align-middle me-2"></i> Set Semula
                    </button>
                    {{-- <a href="#" class="btn btn-primary waves-effect waves-light">
                        <i class="bx bx-save font-size-16 align-middle me-2"></i> Simpan Draf
                    </a> --}}
                    <a href="#" class="btn btn-success waves-effect waves-light updatePassword">
                        <i class="bx bx-paper-plane font-size-16 align-middle me-2"></i> Simpan
                    </a>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
