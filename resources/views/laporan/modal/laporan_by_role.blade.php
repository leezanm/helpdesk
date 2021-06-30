<div id="modal_laporan_by_role" class="modal fade" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div div class="modal-header">
                <h5 class="modal-title" id="myExtraLargeModalLabel">Jumlah Aduan Mengikut Peranan Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row justify-content-md-center">
                    <div class="col-md-8">
                        <table class="table table-bordered">
                            <thead class="text-center align-middle">
                                <th>Peranan</th>
                                <th>Bilangan</th>
                            </thead>
                            <tbody class="text-center align-middle laporanTbody">
                                <tr>
                                    <td class="text-start">JTK</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="text-start">PPTM</td>
                                    <td></td>
                                </tr>
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
                {{-- <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tiket</th>
                            <th>Subjek</th>
                            <th>Tarikh & Masa Aduan</th>
                            <th>Status</th>
                            <th class="text-center">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        <tr>
                            <td>1</td>
                            <td>281222020-10000</td>
                            <td>Masalah Komputer</td>
                            <td>28/12/2020</td>
                            <td>
                                <span class="badge bg-success font-size-12">Dalam Tindakan JTK</span>
                                <br>
                                <a href="">Muhammad Hazim</a>
                            </td>
                            <td class="text-center">
                                <a href="{{url('/aduan/show')}}" class="btn btn-primary waves-effect waves-light">
                                    <i class="bx bx-search-alt-2 label-icon font-size-16 align-middle me-2"></i> Lihat
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table> --}}
            </div>
            <div class="modal-footer text-end">
                <a href="{{route('laporan.view-by-role')}}" target="_blank" class="btn btn-outline-secondary">Lihat Penuh</a>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
