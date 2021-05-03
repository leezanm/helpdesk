@php
$user = Auth::user()->load('roles');
@endphp


<div id="edit-profile" class="modal fade" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary bg-soft">
                <h5 class="text-primary">Maklumat profil</h5>
            </div>
            <form action="{{route('account.profile.update', $user->id)}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col"></div>
                        <div class="col-lg-4 text-center mb-3">
                            <img class="img-thumbnail rounded-circle mb-3" alt=""
                                src="{{ asset($user->profile_picture) }}" height="100" width="100">
                            <input type="file" name="image" class="form-control text-center">
                            {{-- <small class="text-muted">
                            </small> --}}
                        </div>
                        <div class="col"></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-borderless mb-0">
                            <tbody class="align-middle">
                                <tr>
                                    <th>Nama Penuh :</th>
                                    <td><input type="text" name="name" class="form-control" value="{{ $user->name }}">
                                    </td>
                                    <th>No. Telefon :</th>
                                    <td><input type="text" name="tel_no" class="form-control" value="{{ $user->tel_no }}">
                                    </td>
                                </tr>
                                <tr>
                                    <th>E-mel :</th>
                                    <td><input type="text" name="email" class="form-control"
                                            value="{{ $user->email }}"></td>
                                    <th>Peranan:</th>
                                    <td><span class="badge bg-success font-size-14 text-uppercase">{{$user->roles[0]->name}}</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary m-2">Kemaskini <i class="bx bx-send"></i></button>
                    </div>
            </form>
        </div>
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
