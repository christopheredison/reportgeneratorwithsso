<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Report</title>
    <link rel="stylesheet" href="{{ asset('/css/bootstrap.min.css') }}">
  </head>
  <body>
    <div class="container mt-5 mb-5">
      <div class="row">
        <div class="col-md-12 mt-2">  
          <h2><center>Report Management System</center></h2>
        </div>
        @isset($action)
          @if($action === 'store')
            <div class="col-md-12 mt-2">
              <div class="alert alert-info">
                Report has been created successfully.
              </div>
            </div>
          @elseif($action === 'update')
            <div class="col-md-12 mt-2">
              <div class="alert alert-info">
                Report has been updated successfully.
              </div>
            </div>
          @elseif($action === 'destroy')
            <div class="col-md-12 mt-2">
              <div class="alert alert-info">
                Report has been deleted successfully.
              </div>
            </div>
          @endif
        @endisset
        <div class="col-md-2 mt-3"><a class="btn btn-info btn-block" href="{{ url()->current() . '/create' }}" role="button">Create Report</a></div>
        <div class="col-md-8"></div>
        <div class="col-md-2 mt-3"><button class="btn btn-warning btn-block" data-toggle="modal" data-target="#filterModal">Filter</button></div>
        <div class="col-md-12 mt-3">
          <div class="table-responsive">
            <table id="reportTable" class="table table-hover table-striped">
              <thead>
                <tr>
                  <th>Id</th>
                  <th>Title</th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @foreach ($reportList as $report)
                  <tr>
                    <td style="width: 10%">{{ $report['id'] }}</td>
                    <td style="width: 60%"><p>{{ $report['title'] }}</p></td>
                    <td style="width: 10%"><button class="btn btn-success btn-block" data-toggle="modal" data-target=".exportModal{{ $report['id'] }}">Export</button></td>
                    <td style="width: 10%"><a class="btn btn-primary btn-block" href="{{ url()->current() . '/' . $report['id'] . '/edit' }}" role="button">Edit</a></td>
                    <td style="width: 10%">
                      <form action="{{ url()->current() . '/' . $report['id'] }}" method="post">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="id" value="{{ $report['id'] }}">
                        <button type="submit" class="btn btn-danger btn-block">Delete</button>
                      </form>
                    </td>
                    <td style="width: 10%"><a class="btn btn-info btn-block" href="{{ url()->current() . '/' . $report['id'] }}" target="_blank" role="button">Show</a></td>
                  </tr>
                  <div class="modal fade exportModal{{ $report['id'] }}" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-sm" role="document">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title">Export Report</h5>
                          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                          </button>
                        </div>
                        <div class="modal-body">
                          <form action="{{ url()->current() . '/' . $report['id'] . '/export' }}">
                            <div class="row">
                              @csrf
                              <div class="col-md-12 form-group">
                                <label>Format</label>
                                <select class="form-control" name="format">
                                  <option value="pdf">PDF</option>
                                  <option value="xlsx">XLSX</option>
                                  <option value="jpg">JPG</option>
                                  <option value="png">PNG</option>
                                </select>
                              </div>
                              <div class="col-md-12">
                                <button type="submit" formtarget="_blank" class="btn btn-primary btn-block">Export</button>
                              </div>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
        <div class="col-md-12">
          {{ $reportList->withQueryString()->links() }}
        </div>
      </div>
    </div>
    <div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Filter</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <form>
              <div class="row">
                @csrf
                <div class="col-md-12 form-group">
                  <label>Title</label>
                  <input type="text" name="title" class="form-control" placeholder="filter report title" value="{{ isset($filterTitle) ? $filterTitle : '' }}">
                </div>
                <div class="col-md-12">
                  <button type="submit" class="btn btn-primary btn-block">Filter</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <script type="text/javascript" src="{{ asset('/js/jquery-3.5.1.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/bootstrap.min.js') }}"></script>
  </body>
</html>
