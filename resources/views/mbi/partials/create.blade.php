<div id="create_mbi_modal" class="modal fade" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div div class="modal-header">
                <h5 class="modal-title" id="myExtraLargeModalLabel">Maklumat Rekod</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="mbi-form-create">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <tbody>
                                <tr>
                                    <th>Rujukan</th>
                                    <td>
                                        <input type="text" name="rujukan" class="form-control" >
                                        <p id="error-rujukan" class="text-danger collapse error"></p>
                                    </td>
                                    <th>Tarikh :</th>
                                    <td>
                                        <input type="date" name="tarikh" class="form-control" >
                                        <p id="error-tarikh" class="text-danger collapse error"></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Kepada</th>
                                    <td>
                                        <input type="text" name="kepada" class="form-control">
                                        <p id="error-kepada" class="text-danger collapse error"></p>
                                    </td>
                                    <th>Jenis</th>
                                    <td>
                                        <select class="form-select" name="jenis">
                                            <option>-Sila Pilih-</option>
                                            <option value="Sekolah">Sekolah</option>
                                            <option value="Perubatan">Perubatan</option>
                                            <option value="Sat">Kat</option>
                                        </select>
                                        <p id="error-jenis" class="text-danger collapse error"></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Perkara</th>
                                    <td colspan="3">
                                        <textarea id="elm1" name="perkara"></textarea>
                                        <p id="error-perkara" class="text-danger collapse error"></p>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer text-end">
                        <button class="btn btn-secondary" type="reset">Set Semula</button>
                        <button id="btnCreate" class="btn btn-success" type="button">Simpan</button>
                    </div>
                </form>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
