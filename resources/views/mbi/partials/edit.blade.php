<div id="edit_mbi_modal" class="modal fade" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div div class="modal-header">
                <h5 class="modal-title" id="myExtraLargeModalLabel">Maklumat Rekod</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="mbi-form-edit">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <tbody>
                                <tr>
                                    <th>Rujukan</th>
                                    <td>
                                        <input type="text" class="form-control" name="rujukan">
                                        <p class="text-danger collapse error error-rujukan"></p>
                                    </td>
                                    <th>Tarikh :</th>
                                    <td>
                                        <input type="date" class="form-control" name="tarikh">
                                        <p class="text-danger collapse error error-tarikh"></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Kepada</th>
                                    <td>
                                        <input type="text" class="form-control" name="kepada">
                                        <p class="text-danger collapse error error-kepada"></p>
                                    </td>
                                    <th>Jenis</th>
                                    <td>
                                        <select class="form-select" name="type_id">
                                            @foreach ($types as $type)
                                                <option value="{{$type->id}}">{{$type->desc}}</option>
                                            @endforeach
                                        </select>
                                        <p class="text-danger collapse error error-type_id"></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Division</th>
                                    <td>
                                        <select class="form-select" name="division_id">
                                            @foreach ($divisions as $division)
                                                <option value="{{$division->id}}">{{$division->desc}}</option>
                                            @endforeach
                                        </select>
                                        <p class="text-danger collapse error error-division_id"></p>
                                    </td>
                                    <th></th><td></td>
                                </tr>
                                <tr>
                                    <th>Perkara</th>
                                    <td colspan="3">
                                        <textarea id="perkara" name="perkara" class="form-control"></textarea>
                                        <p class="text-danger collapse error error-perkara"></p>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer text-end">
                        <button class="btn btn-secondary " type="reset">Set Semula</button>
                        <button id="btnEdit" class="btn btn-success" type="button">Simpan</button>
                    </div>
                </form>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
