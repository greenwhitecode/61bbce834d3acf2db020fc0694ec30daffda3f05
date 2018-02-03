@extends('layouts.admin')
@section('title', title_case($action).' users')
@section('style')
    @if($type=='user')
        <style>
            .modal-dialog {
                max-width: 80%;
            }
        </style>
    @endif
@endsection
@section('content')
    <nav class="breadcrumb bg-white push">
        <a class="breadcrumb-item" href="{{url('/admin')}}">Admin</a>
        <span class="breadcrumb-item active">View {{title_case($action)}} Users</span>
    </nav>
    <div class="block">
        <div id="users">
            @include('admin.partials.user')
        </div>
    </div>
    <div aria-hidden="true" style="display: none;" class="modal modal-dialog-top modal-dialog-popout" id="user-modal"
         tabindex="-1" role="dialog"
         aria-labelledby="modal-normal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="block block-themed block-transparent mb-0">
                    <div class="block-header bg-primary-dark">
                        <h3 class="block-title">Edit Users</h3>
                        <div class="block-options">
                            <button type="button" class="btn-block-option" data-dismiss="modal" aria-label="Close">
                                <i class="si si-close"></i>
                            </button>
                        </div>
                    </div>
                    <div class="block-content" id="user">

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        @if($type=='admin')
        function viewEditUser(id, type) {
            var data = {
                'id': id,
                'type': type,
            };
            $.post('/admin/edit/viewadmin', data, function (result) {
                $('#user').html(result.html);
                $('#user-modal').modal('show');
            }).fail(function () {
                alert('Sorry, an error occurred');
            });
        }

        function editUser() {

            var data = {
                'id': $('#id').val(),
                'name': $('#name').val(),
                'email': $('#email').val(),
                'last_name': $('#last_name').val(),
                'first_name': $('#first_name').val(),
                'access_level': $('select[name=level]').val(),
                'for': '{{$action}}',
            };

            $.post('/admin/edit/admin', data, function (result) {
                alert(result.message);

                $('#user-modal').modal('hide');

                $('#users').fadeOut(300);
                $('#users').html(result.html);
                $('#users').fadeIn(300);
            }).fail(function () {
                $('#user-modal').modal('hide');
                alert('Sorry, an error occurred');
            });
        }

        @elseif ($type == 'user')
        function viewEditUser(id, type) {
            var data = {
                'id': id,
                'type': type,
            };
            $.post('/admin/edit/viewuser', data, function (result) {
                $('#user').html(result.html);
                $('#user-modal').modal('show');
            }).fail(function () {
                alert('Sorry, an error occurred');
            });
        }

        function editUser() {
            var data = {
                'id': $("input[name=id]").val(),
                'first_name': $("input[name=first_name]").val(),
                'last_name': $("input[name=last_name]").val(),
                'other_name': $("input[name=other_name]").val(),
                'account_number': $("input[name=account_number]").val(),
                'wallet_address': $("input[name=wallet_address]").val(),
                'private_key': $("input[name=private_key]").val(),
                'marital_status': $("input[name=marital_status]").val(),
                'gender': $("input[name=gender]").val(),
                'phone_no': $("input[name=phone_no]").val(),
                'nationality': $("input[name=nationality]").val(),
                'state': $("input[name=state]").val(),
                'lga': $("input[name=lga]").val(),
                'residential_address': $("input[name=residential_address]").val(),
                'contact_address': $("input[name=contact_address]").val(),
                'id_card_type': $("input[name=id_card_type]").val(),
                'id_card_no': $("input[name=id_card_no]").val(),
                'occupation': $("input[name=occupation]").val(),
                'bvn': $("input[name=bvn]").val(),
                'bank_name': $("input[name=bank_name]").val(),
                'bank_acc_name': $("input[name=bank_acc_name]").val(),
                'bank_acc_no': $("input[name=bank_acc_no]").val(),
                'next_of_kin': $("input[name=next_of_kin]").val(),
                'nok_relationship': $("input[name=nok_relationship]").val(),
                'nok_contact_address': $("input[name=nok_contact_address]").val(),
                'nok_gender': $("input[name=nok_gender]").val(),
                'nok_phone_no': $("input[name=nok_phone_no]").val(),
                'nok_email': $("input[name=nok_email]").val(),
                'spouse_name': $("input[name=spouse_name]").val(),
                'mother_maiden_name': $("input[name=mother_maiden_name]").val(),
                'office_phone_no': $("input[name=office_phone_no]").val(),
                'landmark': $("input[name=landmark]").val(),
                'form_location': $("input[name=form_location]").val(),
                'signature_location': $("input[name=signature_location]").val(),
                'utility_bill_location': $("input[name=utility_bill_location]").val(),
                'idcard_location': $("input[name=idcard_location]").val(),
                'passport_location': $("input[name=passport_location]").val(),
                'for': '{{$action}}',
            };

            $.post('/admin/edit/user', data, function (result) {
                alert(result.message);

                $('#user-modal').modal('hide');

                $('#users').fadeOut(300);
                $('#users').html(result.html);
                $('#users').fadeIn(300);
            }).fail(function () {
                alert('Sorry, an error occurred');
            });
        }

        @endif
        function verifyUser(id, action) {
            var data = {
                'id': id,
                'action': action,
                'for': '{{$action}}'
            };
            $.post('/admin/users/verify', data, function (result) {

                alert(result.message);

                $('#users').fadeOut(300);
                $('#users').html(result.html);
                $('#users').fadeIn(300);
            }).fail(function () {
                alert('Sorry, an error occurred');
            });
        }
    </script>
@endsection