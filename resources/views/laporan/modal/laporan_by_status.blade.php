<div id="modal_laporan_by_status" class="modal fade" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div div class="modal-header">
                <h5 class="modal-title" id="myExtraLargeModalLabel">Laporan Mengikut Status Tindakan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row justify-content-md-center">
                    <div class="col-md-8">
                        <table class="table table-bordered">
                            <thead class="text-center align-middle">
                                <th>Status</th>
                                <th>Bilangan</th>
                            </thead>
                            <tbody class="text-center align-middle laporanTbody">

                            </tbody>
                            {{-- <tbody class="text-center align-middle">
                                <tr>
                                    <td class="text-start">Dihantar</td>
                                    <td><a href="/laporan/mengikut-status-tindakan">16</a></td>
                                </tr>
                                <tr>
                                    <td class="text-start">Dihantar (Ambil Maklum)</td>
                                    <td><a href="/laporan/mengikut-status-tindakan">2</a></td>
                                </tr>
                                <tr>
                                    <td class="text-start">Dalam Tindakan</td>
                                    <td><a href="/laporan/mengikut-status-tindakan">2</a></td>
                                </tr>
                                <tr>
                                    <td class="text-start">Selesai</td>
                                    <td><a href="/laporan/mengikut-status-tindakan">600</a></td>
                                </tr>
                            </tbody> --}}
                            <tfoot class="text-center align-middle">
                                <tr>
                                    <th>Jumlah</th>
                                    <th class="laporanTotal"></th>
                                </tr>
                                {{-- <tr>
                                    <th>Prestasi</th>
                                    <th>97.09</th>
                                </tr> --}}
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>
            <div class="modal-footer text-end">
                <a href="{{route('laporan.view-by-status')}}" target="_blank" class="btn btn-outline-secondary">Lihat Penuh</a>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
