<div id="modal_laporan_by_user" class="modal fade" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div div class="modal-header">
                <h5 class="modal-title" id="myExtraLargeModalLabel">Jumlah Aduan Mengikut Pengguna dan Bahagian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-2">
                        <label class="col-form-label">Daripada</label>
                    </div>
                    <div class="col-md-3">
                        <div class="position-relative" id="laporan-user-from">
                            <input type="text" name="from" class="form-control text-center" data-provide="datepicker"
                                data-date-container="#laporan-user-from" data-date-autoclose="true"
                                data-date-end-date="+0d" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="col-form-label">Sehingga</label>
                    </div>
                    <div class="col-md-3">
                        <div class="position-relative" id="laporan-user-to">
                            <input type="text" name="to" class="form-control text-center" data-provide="datepicker"
                                data-date-container="#laporan-user-to" data-date-autoclose="true"
                                data-date-end-date="+0d" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-secondary">Pilih</button>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-2">
                        <label class="col-form-label">Bahagian</label>
                    </div>
                    <div class="col-md-5">
                        <select class="form-select">
                            <option value="">Semua Bahagian</option>
                            <option value="">Divisyen 1</option>
                            <option value="">Divisyen 2</option>
                        </select>
                    </div>
                </div>
                <div class="row justify-content-md-center">
                    <div class="col-md-8">
                        <table class="table table-bordered">
                            <thead class="text-center align-middle">
                                <th>Bahagian</th>
                                <th>Bilangan</th>
                            </thead>
                            <tbody class="text-center align-middle">
                                <tr>
                                    <td class="text-start">Hardware</td>
                                    <td>16</td>
                                </tr>
                                <tr>
                                    <td class="text-start">Software</td>
                                    <td>602</td>
                                </tr>
                            </tbody>
                            <tfoot class="text-center align-middle">
                                <tr>
                                    <th>Jumlah</th>
                                    <th>618</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer text-end">
                <a href="{{url('/laporan/mengikut-bahagian')}}" target="_blank" class="btn btn-outline-secondary">Lihat Penuh</a>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
