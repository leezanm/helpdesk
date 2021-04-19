<div id="edit" class="modal fade" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div div class="modal-header">
                <h5 class="modal-title" id="myExtraLargeModalLabel">Maklumat Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col"></div>
                    <div class="col-2">
                        <img src="{{asset('images/profile/default.png')}}" alt="" class="avatar-lg h-auto d-block rounded">
                    </div>
                    <div class="col"></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <tbody>
                            <tr>
                                <th>Tahun</th>
                                <td>2020</td>
                                <th>Bil :</th>
                                <td>A882</td>
                            </tr>
                            <tr>
                                <th>Sesi</th>
                                <td>Sesi A</td>
                                <th>Tempoh</th>
                                <td>Tempoh B</td>
                            </tr>
                            <tr>
                                <th>Nama</th>
                                <td><input type="text" class="form-control" value="Muhammad Hazim bin Md Zulkeflee"></td>
                                <th>No Kad Pengenalan</th>
                                <td><input type="text" class="form-control" value="890902-02-5099"></td>
                            </tr>
                            <tr>
                                <td colspan="4"><h3>Maklumat Majikan</h3></td>
                            </tr>
                            <tr>
                                <th>Tarikh Mula Bertugas</th>
                                <td>09/01/2010</td>
                                <th>Nama Majikan</th>
                                <td><input type="text" class="form-control" value="Shamsudin Ali"></td>
                            </tr>
                            <tr>
                                <th>Alamat Majikan</th>
                                <td><input type="text" class="form-control" value="Perdana Cyberjaya"></td>
                                <th>Jawatan</th>
                                <td><input type="text" class="form-control" value="Pegawai Teknikal"></td>
                            </tr>
                            <tr>
                                <td colspan="4"><h3>Maklumat Penyelia</h3></td>
                            </tr>
                            <tr>
                                <th>Nama Penyelia</th>
                                <td><input type="text" class="form-control" value="Md Syamil Zai Zaini"></td>
                                <th>Bahagian</th>
                                <td>
                                    <select name="" id="" class="form-select">
                                        <option value="">Teknikal</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>Negeri</th>
                                <td>
                                    <select name="" id="" class="form-select">
                                        <option value="">Johor</option>
                                    </select>
                                </td>
                                <th>Projek</th>
                                <td>
                                    <select name="" id="" class="form-select">
                                        <option value="">Projek Satu</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>Email Penyelia</th>
                                <td>
                                    <input type="text" class="form-control" value="syam@gmail.com">
                                </td>
                                <th>Bidang Pengalaman</th>
                                <td><input type="text" class="form-control" value="Teknikal Mengajar"></td>
                            </tr>
                            <tr>
                                <th>Nama Kursus Seminar</th>
                                <td>
                                    <input type="text" class="form-control" value="Seminar A1">
                                </td>
                                <th>Tempat Yang Pernah Dihadiri</th>
                                <td><input type="text" class="form-control" value="Program Mengajar"></td>
                            </tr>
                            <tr>
                                <th>Tempoh Kursus/Seminar</th>
                                <td>
                                    <input type="text" class="form-control" value="2 minggu">
                                </td>
                                <th>Tempoh Pengalaman</th>
                                <td><input type="text" class="form-control" value="3 tahun"></td>
                            </tr>
                            <tr>
                                <th>Catatan Pengalaman Yang Ringkas</th>
                                <td colspan="3">
                                    <form method="post">
                                        <textarea id="elm1" name="area"></textarea>
                                    </form>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer text-end">
                <button type="reset" class="btn btn-secondary waves-effect waves-light"><i class="bx bx-reset font-size-16 align-middle me-2"></i> Set Semula</button>
                <a href="#" class="btn btn-success waves-effect waves-light" onclick="Swal.fire('Berjaya','Maklumat Pengguna dikemaskini','success');" data-bs-dismiss="modal"><i class="bx bx-save font-size-16 align-middle me-2"></i> Hantar</a>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
