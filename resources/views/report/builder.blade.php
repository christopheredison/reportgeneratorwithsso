<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ !$editMode ? 'Create' : 'Edit' }} Report</title>
  <link rel="stylesheet" href="{{ asset('/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('/css/select2.min.css') }}">
  <link rel="stylesheet" href="{{ asset('/css/app.css') }}">
</head>
<body>
  <div class="loader"></div>
  <div class="container mt-3">
    <form id="form" action="{{ url('reports/' . ($editMode ? $data['id'] : '')) }}" method="post" enctype="multipart/form-data">
      @csrf
      @if($editMode)
        <input type="hidden" name="_method" value="PUT" id="putMethod">
      @endif
      <div class="row">
        <div class="col-md-12">
          <h2><center>{{ !$editMode ? 'Create' : 'Edit' }} Report</center></h2>
        </div>
        @if ($errors->any())
          <div class="col-md-12">
            <div class="alert alert-danger">
              <ul>
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
              </ul>
            </div>
          </div>
        @endif
        <div class="col-md-12">
          <div class="form-group">
            <label>Report title:</label>
            <input type="text" name="title" id="title" class="form-control" value="{{ !$editMode ? '' : $data['title'] }}">
          </div>
        </div>
        <div class="col-md-12">
          <div class="form-group">
            <label>Data source:</label>
            <select class="form-control" name="data_source" id="dataSource">
              <option value="database" {{ $editMode && $data['data_source'] === 'database' ? 'selected' : '' }}>Database</option>
              <option value="excel" {{ $editMode && $data['data_source'] === 'excel' ? 'selected' : '' }}>Excel</option>
            </select>
          </div>
        </div>
      </div>
      <div id="excelInputContainer">
        <div class="row">
          <div class="col-md-12">
            <div class="form-group">
              <label>File source:</label>
              <select class="form-control" name="file_source" id="fileSource">
                <option value="upload" {{ $editMode && $data['data_source'] === 'excel' && $data['file_source'] === 'upload' ? 'selected' : '' }}>Upload</option>
                <option value="onedrive" {{ $editMode && $data['data_source'] === 'excel' && $data['file_source'] === 'onedrive' ? 'selected' : '' }}>OneDrive</option>
              </select>
            </div>
          </div>
          <div class="col-md-12" id="uploadContainer">
            <div class="form-group">
              <label>Excel file:</label>
              <input class="form-control-file" type="file" name="excel" id="excel" accept=".xls,.xlsx">
            </div>
          </div>
          <div class="col-md-12" id="onedriveContainer">
            <div class="form-group">
              <label>OneDrive share link:</label>
              <input class="form-control" type="text" name="onedrive_link" id="onedriveLink" value="{{ $editMode && $data['data_source'] === 'excel' && $data['file_source'] === 'onedrive' ? $data['onedrive_link'] : '' }}">
            </div>
            <div class="form-group">
              <button type="button" class="btn btn-info btn-block" id="onedriveBtn">Submit</button>
            </div>
          </div>
        </div>
      </div>
      <div id="databaseInputContainer">
        <div class="row">
          <div class="col-md-12">
            <div class="form-group">
              <label>Database:</label>
              <select class="form-control" name="database" id="database">
                @if(!$editMode)
                  @foreach ($databases as $database)
                    <option value="{{ $database->id }}">{{ $database->name ?: $database->database_name }}</option>
                  @endforeach
                @else
                  @foreach ($databases as $database)
                    <option value="{{ $database->id }}" {{ $database->id == $data['database'] ? 'selected' : '' }}>{{ $database->name ?: $database->database_name }}</option>
                  @endforeach
                @endif
              </select>
            </div>
          </div>
          <div class="col-md-12">
            <div class="form-group">
              <label>Query type:</label>
              <select class="form-control" name="query_type" id="queryType">
                <option value="builder" {{ $editMode && $data['query_type'] === 'builder' ? 'selected' : '' }}>Builder</option>
                <option value="text" {{ $editMode && $data['query_type'] === 'text' ? 'selected' : '' }}>Text</option>
              </select>
            </div>
          </div>
        </div>
        <div id="queryBuilderContainer">
          <div class="row">
            <div class="col-md-12">
              <div class="form-group">
                <label>Table:</label>
                <select name="table" class="select2 form-control" id="table">
                </select>
              </div>
            </div>
            <div class="col-md-12">
              <div class="form-group">
                <button type="button" class="btn btn-info btn-block" id="joinTableBtn">Add join table</button>
              </div>
            </div>
          </div>
          <div id="joinTableContainer">
          </div>
          <div class="row">
            <div class="col-md-10">
              <div class="form-group">
                <label>Select fields:</label>
                <div id="select-field-container">
                  
                </div>
              </div>
            </div>
            <div class="col-md-2">
              <div class="col-md-12 form-check">
                <center>
                  <label class="form-check-label mr-4">
                    Distinct
                  </label>
                </center>
              </div>
              <div class="col-md-12 form-check mt-2">
                <center>
                  <input class="form-check-input" name="distinct" type="checkbox" value="distinct" id="distinct">
                </center>
              </div>
            </div>
            <div class="col-md-12">
              <div class="form-group">
                <button type="button" class="btn btn-primary btn-block" id="sqlClausesBtn">Hide SQL clauses</button>
              </div>
            </div>
          </div>
          <div id="sqlClauses">
            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <button type="button" class="btn btn-info btn-block" id="aggregateBtn">Add aggregate function</button>
                </div>
              </div>
            </div>
            <div id="aggregateContainer" style="display: none;">
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <select class="form-control" name="aggregate_function">
                      <option value="count">Count</option>
                      <option value="max">Max</option>
                      <option value="min">Min</option>
                      <option value="avg">Avg</option>
                      <option value="sum">Sum</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <select name="aggregate_field" class="select2 form-control">
                </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <button type="button" class="btn btn-success btn-block addBtn">Add</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <button type="button" class="btn btn-info btn-block" id="aliasBtn">Add alias</button>
                </div>
              </div>
            </div>
            <div id="aliasContainer">
            </div>
            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <button type="button" class="btn btn-info btn-block" id="conditionBtn">Add condition</button>
                </div>
              </div>
            </div>
            <div id="conditionContainer">
            </div>
            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <button type="button" class="btn btn-info btn-block" id="groupByBtn">Add group by</button>
                </div>
              </div>
            </div>
            <div id="groupByContainer">
            </div>
            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <button type="button" class="btn btn-info btn-block" id="havingBtn">Add having condition</button>
                </div>
              </div>
            </div>
            <div id="havingContainer">
            </div>
            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <button type="button" class="btn btn-info btn-block" id="orderByBtn">Add order by</button>
                </div>
              </div>
            </div>
            <div id="orderByContainer">
            </div>
            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <button type="button" class="btn btn-info btn-block" id="limitBtn">Add limit</button>
                </div>
              </div>
            </div>
            <div id="limitContainer">
            </div>
          </div>
          <div class="row">
            <div class="col-md-12">
              <div class="form-group">
                <button type="button" class="btn btn-success btn-block" id="buildQueryBtn">Run query builder</button>
              </div>
            </div>
          </div>
        </div>
        <div class="row" id="queryTextContainer">
          <div class="col-md-12">
            <div class="form-group">
              <label>Query:</label>
              <textarea class="form-control" rows="4" id="query" name="query_input" placeholder="Type your query">{{ !$editMode ? '' : $data['query_input'] }}</textarea>
            </div>
          </div>
          <div class="col-md-12">
            <div class="form-group">
              <button type="button" class="btn btn-info btn-block" id="queryBtn">Run query</button>
            </div>
          </div>
        </div>
      </div>
      <div id="classificationInputs">
        <div class="row">
          <div class="col-md-12">
            <div class="form-group">
              <label>Visualization:</label>
              <select class="form-control" name="visualization" id="visualization">
                <option value="table">Table</option>
                <option value="bar">Bar chart</option>
                <option value="column">Column chart</option>
                <option value="pie">Pie chart</option>
                <option value="donut">Donut chart</option>
                <option value="line">Line chart</option>
                <option value="spline">Spline chart</option>
                <option value="histogram">Histogram chart</option>
              </select>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-12">
            <label>Color:</label>
          </div>
          <div class="col-md-12">
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="color_option" value="default" checked>
              <label class="form-check-label">Default</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="color_option" value="custom">
              <label class="form-check-label">Custom</label>
            </div>
          </div>
          <div class="col-md-12 mt-2">
            <div class="form-group">
              <button type="button" class="btn btn-info btn-block" id="colorBtn" style="display: none;">Add color</button>
            </div>
          </div>
        </div>
        <div id="colorContainer">
        </div>
        <div class="row" id="classification">
        </div>
        <div class="row">
          <div class="col-md-12 mt-3">
            <div class="form-group">
              <button type="button" class="btn btn-info btn-block" id="filterBtn">Add Filter</button>
            </div>
          </div>
        </div>
        <div id="filterContainer">
        </div>
        <div class="row">
          <div class="col-md-12 mt-3">
            <button type="button" class="btn btn-info btn-block" id="previewBtn">Preview</button>
            <button type="button" class="btn btn-primary btn-block" id="saveBtn">Save</button>
          </div>
        </div>
      </div>
    </form>
  </div>
  <div class="row joinTableClone" style="display: none;">
    <div class="col-md-6">
      <div class="form-group">
        <select data-placeholder="Select first table" name="join_table_1[]" class="select2 form-control">
        </select>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <select data-placeholder="Select first table field" name="join_field_1[]" class="select2 form-control" disabled>
        </select>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <select data-placeholder="Select second table" name="join_table_2[]" class="select2 form-control">
        </select>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <select data-placeholder="Select second table field" name="join_field_2[]" class="select2 form-control" disabled>
        </select>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <select name="join_type[]" class="form-control">
          <option value="inner join">Inner join</option>
          <option value="left join">Left join</option>
          <option value="right join">Right join</option>
          <option value="full join">Full join</option>
        </select>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <button type="button" class="btn btn-danger btn-block deleteBtn">Delete join</button>
      </div>
    </div>
  </div>
  <div class="row aliasClone" style="display: none;">
    <div class="col-md-4">
      <div class="form-group">
        <select data-placeholder="Select column" name="alias_field[]" class="select2 form-control">
        </select>
      </div>
    </div>
    <div class="col-md-4">
      <div class="form-group">
        <input placeholder="Type alias" type="text" name="alias_value[]" class="form-control">
      </div>
    </div>
    <div class="col-md-4">
      <div class="form-group">
        <button type="button" class="btn btn-danger btn-block deleteBtn">Delete alias</button>
      </div>
    </div>
  </div>
  <div class="row conditionClone" style="display: none;">
    <div class="col-md-5">
      <div class="form-group">
        <select data-placeholder="Select field" name="condition_field[]" class="select2 form-control">
        </select>
      </div>
    </div>
    <div class="col-md-2">
      <div class="form-group">
        <input placeholder="operator" type="text" name="condition_operator[]" class="form-control">
      </div>
    </div>
    <div class="col-md-5">
      <div class="form-group">
        <input placeholder="value" type="text" name="condition_value[]" class="form-control">
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <select name="condition_type[]" class="form-control">
          <option value="and">AND</option>
          <option value="or">OR</option>
        </select>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <button type="button" class="btn btn-danger btn-block deleteBtn">Delete condition</button>
      </div>
    </div>
  </div>
  <div class="row groupByClone" style="display: none;">
    <div class="col-md-10">
      <div class="form-group">
        <select name="group_fields[]" class="select2 form-control" multiple>
        </select>
      </div>
    </div>
    <div class="col-md-2">
      <div class="form-group">
        <button type="button" class="btn btn-danger btn-block deleteBtn">Delete group by</button>
      </div>
    </div>
  </div>
  <div class="row havingClone" style="display: none;">
    <div class="col-md-5">
      <div class="form-group">
        <select name="having_field[]" class="select2 form-control">
        </select>
      </div>
    </div>
    <div class="col-md-2">
      <div class="form-group">
        <input placeholder="operator" type="text" name="having_operator[]" class="form-control">
      </div>
    </div>
    <div class="col-md-5">
      <div class="form-group">
        <input placeholder="value" type="text" name="having_value[]" class="form-control">
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <select name="having_type[]" class="form-control">
          <option value="and">AND</option>
          <option value="or">OR</option>
        </select>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <button type="button" class="btn btn-danger btn-block deleteBtn">Delete having</button>
      </div>
    </div>
  </div>
  <div class="row orderByClone" style="display: none;">
    <div class="col-md-6">
      <div class="form-group">
        <select name="order_field[]" class="select2 form-control">
        </select>
      </div>
    </div>
    <div class="col-md-3">
      <div class="form-group">
        <select name="order_type[]" class="form-control">
          <option value="asc">Ascending</option>
          <option value="desc">Descending</option>
        </select>
      </div>
    </div>
    <div class="col-md-3">
      <div class="form-group">
        <button type="button" class="btn btn-danger btn-block deleteBtn">Delete order by</button>
      </div>
    </div>
  </div>
  <div class="row limitClone" style="display: none;">
    <div class="col-md-3">
      <div class="form-group">
        <input placeholder="Starting row" type="number" name="limit_start" class="form-control">
      </div>
    </div>
    <div class="col-md-3">
      <div class="form-group">
        <input placeholder="Total rows" type="number" name="limit_total" class="form-control">
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <button type="button" class="btn btn-danger btn-block deleteBtn">Delete limit</button>
      </div>
    </div>
  </div>
  <div class="row colorClone" style="display: none;">
    <div class="col-md-6">
      <div class="form-group">
        <input type="color" name="color[]" class="form-control" />
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <button type="button" class="btn btn-danger btn-block deleteBtn">Delete color</button>
      </div>
    </div>
  </div>
  <div class="row filterClone" style="display: none;">
    <div class="col-md-4">
      <div class="form-group">
        <select name="filter_field[]" class="select2 form-control">
        </select>
      </div>
    </div>
    <div class="col-md-2">
      <div class="form-group">
        <select data-placeholder="Select operator" name="filter_operator[]" class="select2 form-control" disabled>
        </select>
      </div>
    </div>
    <div class="col-md-4">
      <div class="form-group">
        <input placeholder="Insert filter value" type="text" name="filter_value[]" class="form-control">
      </div>
    </div>
    <div class="col-md-2">
      <div class="form-group">
        <button type="button" class="btn btn-danger btn-block deleteBtn">Delete filter</button>
      </div>
    </div>
  </div>

  <input type="hidden" name="edit_mode" value="{{ $editMode }}">
  <input type="hidden" name="url_database" value="{{ url('reports/database') }}">
  <input type="hidden" name="url_query" value="{{ url('reports/query/fields') }}">
  <input type="hidden" name="url_excel" value="{{ url('reports/excel/fields') }}">
  <input type="hidden" name="url_preview" value="{{ url('reports/preview') }}">
  <input type="hidden" name="url_save" value="{{ url('reports/' . ($editMode ? $data['id'] : '')) }}">
  @if($editMode)
    <input type="hidden" name="data" value="{{ json_encode($data) }}">
  @endif

  <script type="text/javascript" src="{{ asset('/js/jquery-3.5.1.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('/js/bootstrap.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('/js/sweetalert.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('/js/select2.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('/js/builder.js') }}"></script>
</body>
</html>