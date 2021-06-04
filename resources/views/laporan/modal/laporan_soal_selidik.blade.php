<div id="modal_laporan_soal_selidik" class="modal fade" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div div class="modal-header">
                <h5 class="modal-title" id="myExtraLargeModalLabel">Laporan Soal Selidik</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-2">
                        <label class="col-form-label">Tahun</label>
                    </div>
                    <div class="col-md-3">
                        <div class="position-relative" id="laporan-soal_selidik-year">
                            <input type="text" name="year" class="form-control text-center" data-provide="datepicker"
                                data-date-container="#laporan-soal_selidik-year" data-date-autoclose="true" data-date-format="yyyy" data-date-min-view-mode="2"
                                data-date-end-date="+0d" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="col-form-label">Bulan</label>
                    </div>
                    <div class="col-md-3">
                        <div class="position-relative" id="laporan-soal_selidik-month">
                            <input type="text" name="month" class="form-control text-center" data-provide="datepicker"
                                data-date-container="#laporan-soal_selidik-month" data-date-autoclose="true" data-date-format="MM"
                                 data-date-min-view-mode="1" data-date-max-view-mode="1" data-date-start-date="1M"
                                data-date-end-date="0M" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-secondary">Pilih</button>
                    </div>
                </div>
                <div class="row justify-content-md-center">
                    <div class="col-md-8">
                        <table class="table table-bordered">
                            <thead class="text-center align-middle">
                                <th>Status</th>
                                <th>Bilangan</th>
                            </thead>
                            <tbody class="text-center align-middle">
                                <tr>
                                    <td class="text-start">Belum Mula (Baru)</td>
                                    <td>16</td>
                                </tr>
                                <tr>
                                    <td class="text-start">Ambil Maklum</td>
                                    <td>2</td>
                                </tr>
                                <tr>
                                    <td class="text-start">Selesai</td>
                                    <td>600</td>
                                </tr>
                            </tbody>
                            <tfoot class="text-center align-middle">
                                <tr>
                                    <th>Jumlah</th>
                                    <th>618</th>
                                </tr>
                                <tr>
                                    <th>Prestasi</th>
                                    <th>97.09</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            {{-- <div class="modal-footer text-end">
                <button class="btn btn-secondary" type="reset">Set Semula</button>
                <button id="btnCreate" class="btn btn-success" type="button">Simpan</button>
            </div> --}}
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
