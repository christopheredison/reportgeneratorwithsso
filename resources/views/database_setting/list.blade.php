@extends('layouts.main')

@section('content')
    <div class="my-2">
        <button class="btn btn-primary" data-toggle="modal" data-target="#modal-new-database">Add new database</button>
    </div>
    <table id="table-database" class="table table-striped table-bordered" style="width:100%">
        <thead>
            <tr>
                <th>Name</th>
                <th>Driver</th>
                <th>Database Name</th>
                <th>Host</th>
                <th>Port</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
    @include('database_setting._modal')
@endsection

@section('script')
<script type="text/javascript">
    function getConnectionData(source) {
        let parentDiv;
        if (source == 'new') {
            parentDiv = '#modal-new-database';
        } else {
            parentDiv = '#modal-edit-database';
        }

        const result = {
            name              : $(`${parentDiv} [name="name"]`).val(),
            database_driver   : $(`${parentDiv} [name="database_driver"]`).val(),
            database_name     : $(`${parentDiv} [name="database_name"]`).val(),
            database_host     : $(`${parentDiv} [name="database_host"]`).val(),
            database_port     : $(`${parentDiv} [name="database_port"]`).val(),
            database_username : $(`${parentDiv} [name="database_username"]`).val(),
            database_password : $(`${parentDiv} [name="database_password"]`).val()
        }

        if (source == 'edit') {
            result.id = $(`${parentDiv} [name="id"]`).val();
        }

        return result;
    }

    function testConnection(source) {
        $.blockUI({baseZ: 2000});
        const paramsData = getConnectionData(source);

        $.get('{{url(route('database.test_connection'))}}', paramsData, function(response) {
            if (response.status == 'success') {
                swal('Great!', 'Connection can be established', 'success');
            } else {
                swal('Oops!', 'Connection can not be established. ' + (response.errors ? 'Errors: ' + response.errors.join('. ') : ''), 'error');
            }
        }).fail(function(response) {
            swal('Oops!', 'Connection can not be established. ' + (response.responseJSON?.errors ? 'Errors: ' + response.responseJSON?.errors.join('. ') : ''), 'error');
            $.unblockUI();
        });
        $.unblockUI();
    }

    function saveConnection(source) {
        $.blockUI({baseZ: 2000});
        let parentDiv;
        if (source == 'new') {
            parentDiv = '#modal-new-database';
        } else {
            parentDiv = '#modal-edit-database';
        }

        const paramsData = getConnectionData(source);
        paramsData._token = '{{csrf_token()}}';
        if (source == 'new') {
            $.post('{{url(route('database.store'))}}', paramsData, function(response) {
                if (response.status == 'success') {
                    $(parentDiv).modal('hide');
                    $(parentDiv + ' :input').val('');
                    $('#table-database').DataTable().ajax.reload(null, false);
                    swal('Great!', 'Database saved', 'success');
                } else {
                    swal('Oops!', 'Failed save database ' + (response.errors ? 'Errors: ' + response.errors.join('. ') : ''), 'error');
                }
                $.unblockUI();
            }).fail(function(response) {
                swal('Oops!', (response.responseJSON?.errors ? response.responseJSON?.errors.join('. ') : 'Something went wrong'), 'error');
                $.unblockUI();
            });
        } else {
            $.ajax({
                method: "PUT",
                url: '{{url(route('database.update', '::id::'))}}'.replace('::id::', paramsData.id),
                data: paramsData
            }).done(function(response) {
                if (response.status == 'success') {
                    $(parentDiv).modal('hide');
                    $(parentDiv + ' :input').val('');
                    $('#table-database').DataTable().ajax.reload(null, false);
                    swal('Great!', 'Database saved', 'success');
                } else {
                    swal('Oops!', 'Failed save database ' + (response.errors ? 'Errors: ' + response.errors.join('. ') : ''), 'error');
                }
                $.unblockUI();
            }).fail(function(response) {
                swal('Oops!', (response.responseJSON?.errors ? response.responseJSON?.errors.join('. ') : 'Something went wrong'), 'error');
                $.unblockUI();
            });;
        }
    }

    function editDatabase(id) {
        $.blockUI();
        $.get('{{url(route('database.show', '::id::'))}}'.replace('::id::', id), function(response) {
            if (response.status == 'success') {
                $('#modal-edit-database [name="id"]').val(response.data?.id);
                $('#modal-edit-database [name="name"]').val(response.data?.name);
                $('#modal-edit-database [name="database_driver"]').val(response.data?.database_driver);
                $('#modal-edit-database [name="database_name"]').val(response.data?.database_name);
                $('#modal-edit-database [name="database_host"]').val(response.data?.database_host);
                $('#modal-edit-database [name="database_port"]').val(response.data?.database_port);
                $('#modal-edit-database [name="database_username"]').val(response.data?.database_username);
                $('#modal-edit-database [name="database_password"]').val(response.data?.database_password);
                $('#modal-edit-database').modal('show');
            } else {
                swal('Oops!', (response.errors ? response.errors.join('. ') : 'Something went wrong'), 'error');
            }
            $.unblockUI();
        }).fail(function(response) {
            swal('Oops!', (response.responseJSON?.errors ? response.responseJSON?.errors.join('. ') : 'Something went wrong'), 'error');
            $.unblockUI();
        });
    }

    function deleteDatabase(id) {
        swal({
            title: "Are you sure?",
            text: "Once deleted, you will not be able to recover this configuration data!",
            icon: "warning",
            buttons: true,
            dangerMode: true
        })
        .then((willDelete) => {
            if (willDelete) {
                $.blockUI();
                paramsData = {
                    _token : '{{csrf_token()}}',
                };
                $.ajax({
                    method: "DELETE",
                    url: '{{url(route('database.destroy', '::id::'))}}'.replace('::id::', id),
                    data: paramsData
                }).done(function(response) {
                    if (response.status == 'success') {
                        swal('Success', 'Database deleted', 'success');
                        $('#table-database').DataTable().ajax.reload(null, false);
                    } else {
                        swal('Oops!', (response.errors ? response.errors.join('. ') : 'Something went wrong'), 'error');
                    }
                    $.unblockUI();
                }).fail(function(response) {
                    swal('Oops!', (response.responseJSON?.errors ? response.responseJSON?.errors.join('. ') : 'Something went wrong'), 'error');
                    $.unblockUI();
                });
            }
        });
    }

    $(document).ready(function() {
        $('#table-database').dataTable({
            ajax: {
                url : "{{url()->current()}}",
                type: 'GET',
                data: function (data) {
                    const info = $('#table-database').DataTable().page.info();
                    data.page = (info.start / info.length) + 1;
                },
            },
            serverSide: true,
            columns: [
                {data: 'name'},
                {data: 'database_driver'},
                {data: 'database_name'},
                {data: 'database_host'},
                {data: 'database_port'},
                {
                    data: 'id',
                    render: function(value, type, row) {
                        return `
                            <button type="button" onclick="editDatabase(${value})" class="btn btn-sm btn-info edit-btn">Edit</button>
                            <button type="button" onclick="deleteDatabase(${value})" class="btn btn-sm btn-danger delete-btn">Delete</button>
                        `;
                    },
                    orderable: false
                },
            ],
            searching: true
        });
    });
</script>
@endsection