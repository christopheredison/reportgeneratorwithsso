<div class="modal fade" id="modal-new-database" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">New Database</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="form-new-database">
          <div class="form-group row">
            <label class="col-md-2 col-form-label">Name</label>
            <div class="col-md-10">
              <input type="text" class="form-control" name="name" placeholder="Name/Label (Optional)">
              <small class="form-text text-muted">Leave blank to use database name as label</small>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-2 col-form-label">Driver <span class="text-danger">*</span></label>
            <div class="col-md-10">
              <select class="form-control select2" name="database_driver" required>
                <option value="mysql">MySQL</option>
                <option value="sqlsrv">SQLSRV</option>
              </select>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-2 col-form-label">DB Name <span class="text-danger">*</span></label>
            <div class="col-md-10">
              <input type="text" class="form-control" name="database_name" placeholder="Database Name" required>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-2 col-form-label">DB Host <span class="text-danger">*</span></label>
            <div class="col-md-7">
              <input type="text" class="form-control" name="database_host" placeholder="Database Host" required>
            </div>
            <label class="col-md-1 col-form-label text-md-right">Port<span class="text-danger">*</span></label>
            <div class="col-md-2">
              <input type="number" class="form-control" name="database_port" placeholder="Port" required>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-2 col-form-label">Username <span class="text-danger">*</span></label>
            <div class="col-md-10">
              <input type="text" class="form-control" name="database_username" placeholder="Username" required>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-2 col-form-label">Password</label>
            <div class="col-md-10">
              <input type="password" class="form-control" name="database_password" placeholder="Password" required>
              <small class="form-text text-muted">Leave blank if no password set for this database account</small>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-2 col-form-label">Advance Filter</label>
            <div class="col-md-10">
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" name="extra_query[status]" value="1" id="advance-filter-switch" onchange="updateAdvance('new', this)">
                <label class="custom-control-label" for="advance-filter-switch">Enable/Disable</label>
              </div>
            </div>
          </div>
          <div class="advance-enabled-only">
            <div class="advance-filter-container">
            </div>
            <div class="text-center">
              <button class="btn btn-primary" type="button" onclick="addAdvanceFilter('new', this)">Add Filter</button>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" onclick="testConnection('new')">Test Connection</button>
        <button type="button" class="btn btn-primary" onclick="saveConnection('new')">Save changes</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-edit-database" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Database</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="form-new-database">
          <input type="hidden" name="id">
          <div class="form-group row">
            <label class="col-md-2 col-form-label">Name</label>
            <div class="col-md-10">
              <input type="text" class="form-control" name="name" placeholder="Name/Label (Optional)">
              <small class="form-text text-muted">Leave blank to use database name as label</small>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-2 col-form-label">Driver <span class="text-danger">*</span></label>
            <div class="col-md-10">
              <select class="form-control select2" name="database_driver">
                <option value="mysql">MySQL</option>
                <option value="sqlsrv">SQLSRV</option>
              </select>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-2 col-form-label">DB Name <span class="text-danger">*</span></label>
            <div class="col-md-10">
              <input type="text" class="form-control" name="database_name" placeholder="Database Name" required>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-2 col-form-label">DB Host <span class="text-danger">*</span></label>
            <div class="col-md-7">
              <input type="text" class="form-control" name="database_host" placeholder="Database Host" required>
            </div>
            <label class="col-md-1 col-form-label text-md-right">Port<span class="text-danger">*</span></label>
            <div class="col-md-2">
              <input type="number" class="form-control" name="database_port" placeholder="Port" required>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-2 col-form-label">Username <span class="text-danger">*</span></label>
            <div class="col-md-10">
              <input type="text" class="form-control" name="database_username" placeholder="Username" required>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-2 col-form-label">Password</label>
            <div class="col-md-10">
              <input type="password" class="form-control" name="database_password" placeholder="Password" required>
              <small class="form-text text-muted">Leave blank if no password set for this database account</small>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-2 col-form-label">Advance Filter</label>
            <div class="col-md-10">
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" name="extra_query[status]" value="1" id="advance-filter-switch-edit" onchange="updateAdvance('edit', this)">
                <label class="custom-control-label" for="advance-filter-switch-edit">Enable/Disable</label>
              </div>
            </div>
          </div>
          <div class="advance-enabled-only">
            <div class="advance-filter-container">
            </div>
            <div class="text-center">
              <button class="btn btn-primary" type="button" onclick="addAdvanceFilter('edit', this)">Add Filter</button>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" onclick="testConnection('edit')">Test Connection</button>
        <button type="button" class="btn btn-primary" onclick="saveConnection('edit')">Save changes</button>
      </div>
    </div>
  </div>
</div>