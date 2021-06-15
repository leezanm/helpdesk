<div id="modal_laporan_by_category" class="modal fade" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div div class="modal-header">
                <h5 class="modal-title" id="myExtraLargeModalLabel">Laporan Mengikut Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row justify-content-md-center">
                    <div class="col-md-8">
                        <table class="table table-bordered">
                            <thead class="text-center align-middle">
                                <th>Kategori</th>
                                <th>Bilangan</th>
                            </thead>
                            <tbody class="text-center align-middle laporanTbody">
                                {{-- <tr>
                                    <td class="text-start">Acara (Event)</td>
                                    <td><a href="/laporan/mengikut-kategori">299</a></td>
                                </tr>
                                <tr>
                                    <td class="text-start">E-mel / Akaun Pengguna</td>
                                    <td><a href="/laporan/mengikut-kategori">56</a></td>
                                </tr>
                                <tr>
                                    <td class="text-start">Komputer Meja (Desktop)</td>
                                    <td><a href="/laporan/mengikut-kategori">114</a></td>
                                </tr>
                                <tr>
                                    <td class="text-start">Komputer Riba (Notebook)</td>
                                    <td><a href="/laporan/mengikut-kategori">2</a></td>
                                </tr>
                                <tr>
                                    <td class="text-start">Lain-lain</td>
                                    <td><a href="/laporan/mengikut-kategori">15</a></td>
                                </tr>
                                <tr>
                                    <td class="text-start">Pencetak / Pengimbas</td>
                                    <td><a href="/laporan/mengikut-kategori">57</a></td>
                                </tr>
                                <tr>
                                    <td class="text-start">Perisian</td>
                                    <td><a href="/laporan/mengikut-kategori">27</a></td>
                                </tr>
                                <tr>
                                    <td class="text-start">Perkakasan</td>
                                    <td><a href="/laporan/mengikut-kategori">5</a></td>
                                </tr>
                                <tr>
                                    <td class="text-start">Rangkaian</td>
                                    <td><a href="/laporan/mengikut-kategori">30</a></td>
                                </tr>
                                <tr>
                                    <td class="text-start">Sistem Aplikasi</td>
                                    <td><a href="/laporan/mengikut-kategori">13</a></td>
                                </tr> --}}
                            </tbody>
                            <tfoot class="text-center align-middle">
                                <tr>
                                    <th>Jumlah</th>
                                    <th class="laporanTotal"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer text-end">
                <a href="{{route('laporan.view-by-category')}}" target="_blank" class="btn btn-outline-secondary">Lihat Penuh</a>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
