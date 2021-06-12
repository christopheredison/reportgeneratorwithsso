<?php
// use \koolreport\widgets\koolphp\Table;
use \koolreport\datagrid\DataTables;
use \koolreport\d3\BarChart;
use \koolreport\d3\ColumnChart;
use \koolreport\d3\PieChart;
use \koolreport\d3\DonutChart;
use \koolreport\d3\LineChart;
use \koolreport\d3\SplineChart;
use \koolreport\widgets\google\Histogram;
?>
<html>
  <head>
    <title>My Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>" />
    <style type="text/css">
      <?php if ($this->params['visualization'] === 'table' && !empty($this->params['colors'])): ?>
        .tableColor
        {
            background-color:<?php echo $this->params['colors'][0] . '!important' ?>;
        }
      <?php endif; ?>
    </style>
    <link rel="stylesheet" href="<?php echo asset('/css/select2.min.css'); ?>">
    <script type="text/javascript" src="<?php echo asset('/js/jquery-3.5.1.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo asset('/js/select2.min.js'); ?>"></script>
    <script type="text/javascript">
      $.ajaxSetup({
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
      });
    </script>
  </head>
  <body>
    <h1><center><?php echo $this->params['title']; ?></center></h1>
    <?php
      if (!empty($this->params['conditions'])) {
        $conditions = '<h4>Conditions</h4>';
        ['condition_field' => $conditionFields, 'condition_operator' => $conditionOperators, 'condition_value' => $conditionValues, 'condition_type' => $conditionTypes] = $this->params['conditions'];
        $conditions .= '<ul>';
        for ($i = 0; $i < sizeof($conditionFields); $i++) {
          $conditionType = '';
          if ($i !== sizeof($conditionFields) - 1) {
            $conditionType = $conditionTypes[$i];
          }
          $conditions .= "<li>$conditionFields[$i] $conditionOperators[$i] $conditionValues[$i] $conditionType</li>";
        }
        $conditions .= '</ul><br>';
        echo $conditions;
      }

      if (!empty($this->params['filters'])) {
        $filters = '<h4>Filters</h4>';
        ['filter_field' => $filterFields, 'filter_operator' => $filterOperators, 'filter_value' => $filterValues] = $this->params['filters'];
        $filters .= '<ul>';
        for ($i = 0; $i < sizeof($filterFields); $i++) {
          $filterType = '';
          if ($i !== sizeof($filterFields) - 1) {
            $filterType = 'and';
          }
          $filters .= "<li>$filterFields[$i] $filterOperators[$i] '$filterValues[$i]' $filterType</li>";
        }
        $filters .= '</ul><br>';
        echo $filters;
      }

      $filter = "
        <br>
        <div class='container'>
          <form id='form' method='post'>
            <input type='hidden' name='_token' value='" . csrf_token() . "' />
            <input type='hidden' name='refilter' value='true'>
            <div class='row' id='refreshBtnContainer' style='display: none;'>
              <div class='col-md-12 mt-3'>
                <div class='form-group'>
                  <button type='button' class='btn btn-info btn-block' onClick='window.location.reload();'>Refresh Data</button>
                </div>
              </div>
            </div>
            <div class='row'>
              <div class='col-md-12 mt-3'>
                <div class='form-group'>
                  <button type='button' class='btn btn-info btn-block' id='filterBtn' style='display: none;'>Add Filter</button>
                </div>
              </div>
            </div>
            <div id='filterContainer'>
            </div>
            <div class='row'>
              <div class='col-md-12 mt-3'>
                <div class='form-group'>
                  <button type='button' class='btn btn-info btn-block' id='submitBtn' style='display: none;'>Submit</button>
                </div>
              </div>
            </div>
          </form>
        </div>";
        echo $filter;

      if ($this->params['visualization'] === 'table') {
        $cssClass = [];
        if ($this->params['data_source'] === 'database') {
          if (!empty($this->params['filters'])) {
            $dataSource = $this->dataStore('default');
            $serverSide = false;
          }
          else {
            $dataSource = function() {
              \Log::debug($this->params['query_input']);
              return $this->src($this->params['database'])->query($this->params['query_input']);
            };
            $serverSide = true;
          }
        }
        elseif ($this->params['data_source'] === 'excel') {
          $dataSource = $this->dataStore('default');
          $serverSide = false;
        }
        if ($this->params['action'] === 'show') {
          $searching = true;
          $paging = true;
        }
        elseif ($this->params['action'] === 'export') {
          $searching = false;
          $paging = false;
          $serverSide = false;
        }
        if (!empty($this->params['colors'])) {
          $cssClass = 
          [
            'table' => 'tableColor',
            'td' => 'tableColor'
          ];
          $columns = [];
          foreach ($this->params['columns'] as $column) {
            $columns[$column] = ['className' => 'tableColor'];
          }
        }
        else {
          $columns = $this->params['columns'];
        }
        Datatables::create([
          'name' => 'uniqueTable',
          'Title' => $this->params['title'],
          'dataSource' => $dataSource,
          'columns' => $columns,
          'options' => 
          [
            'searching' => $searching, 
            'colReorder' => true, 
            'paging' => $paging,
            'responsive' => true
          ],
          'serverSide' => $serverSide,
          'cssClass' => $cssClass,
          'plugins' => ['Responsive']
        ]);
      }
      elseif ($this->params['visualization'] === 'bar') {
        BarChart::create([
          'Title'=>$this->params['title'],
          'dataSource'=>$this->dataStore('default'),
          'columns'=>$this->params['columns'],
          'colorScheme'=>$this->params['colors']
        ]);
      }
      elseif ($this->params['visualization'] === 'column') {
        ColumnChart::create([
          'Title'=>$this->params['title'],
          'dataSource'=>$this->dataStore('default'),
          'columns'=>$this->params['columns'],
          'colorScheme'=>$this->params['colors']
        ]);
      }
      elseif ($this->params['visualization'] === 'pie') {
        PieChart::create([
          'Title'=>$this->params['title'],
          'dataSource'=>$this->dataStore('default'),
          'columns'=>$this->params['columns'],
          'colorScheme'=>$this->params['colors']
        ]);
      }
      elseif ($this->params['visualization'] === 'donut') {
        DonutChart::create([
          'Title'=>$this->params['title'],
          'dataSource'=>$this->dataStore('default'),
          'columns'=>$this->params['columns'],
          'colorScheme'=>$this->params['colors']
        ]);
      }
      elseif ($this->params['visualization'] === 'line') {
        LineChart::create([
          'Title'=>$this->params['title'],
          'dataSource'=>$this->dataStore('default'),
          'columns'=>$this->params['columns'],
          'colorScheme'=>$this->params['colors']
        ]);
      }
      elseif ($this->params['visualization'] === 'spline') {
        SplineChart::create([
          'Title'=>$this->params['title'],
          'dataSource'=>$this->dataStore('default'),
          'columns'=>$this->params['columns'],
          'colorScheme'=>$this->params['colors']
        ]);
      }
      elseif ($this->params['visualization'] === 'histogram') {
        Histogram::create([
          'Title'=>$this->params['title'],
          'dataSource'=>$this->dataStore('default'),
          'columns'=>$this->params['columns'],
          'colorScheme'=>$this->params['colors']
        ]);
      }
    ?>

    <div class="row filterClone" style="display: none;">
      <div class="col-md-4">
        <div class="form-group">
          <select name="filter_field[]" class="form-control">
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

    <input type="hidden" name="data" value='<?php echo json_encode($this->params, JSON_HEX_QUOT | JSON_HEX_APOS); ?>'>
  </body>
  <script type="text/javascript">
    $(document).ready(function() {
      let data = JSON.parse($('input[name="data"]').val());
      if (data.data_source === 'excel' && data.file_source === 'onedrive') {
        $('#refreshBtnContainer').show();
      }
      if (data.action === 'show') {
        $('#filterBtn').show();
      }

      $('#filterBtn').click(function() {
        let categoryFields = data.category_fields; 
        let numericFields = data.numeric_fields; 
        let clone = $('.filterClone').clone();
        clone.toggleClass('filterClone filter');
        $('#filterContainer').append(clone);
        clone.slideDown('fast');
        if (categoryFields.length > 0) {
          let element = $('<optgroup />').attr('label', 'Category Fields');
          categoryFields.forEach(field => {
            element.append($('<option />').val(field).text(field));
          });
          clone.find('select[name="filter_field[]"]').append(element);
        }
        if (numericFields.length > 0) {
          let element = $('<optgroup />').attr('label', 'Numeric Fields');
          numericFields.forEach(field => {
            element.append($('<option />').val(field).text(field));
          });
          clone.find('select[name="filter_field[]"]').append(element);
        }
        clone.find('select[name="filter_field[]"]').select2({ width: '100%' });
        clone.find('select[name="filter_field[]"]').trigger('change');
        $('#submitBtn').show();
      });

      $('#filterContainer').on('change', 'select[name="filter_field[]"]', function() {
        let categoryFields = data.category_fields;
        let numericFields = data.numeric_fields;
        let dateFields = data.date_fields;
        $(this).closest('.filter').find('select[name="filter_operator[]"]').empty();
        if (categoryFields.includes($(this).val())) {
          if (dateFields.includes($(this).val())) {
            $(this).closest('.filter').find('select[name="filter_operator[]"]').append(
              '<option value="=">=</option>' +
              '<option value="!=">!=</option>' +
              '<option value=">">></option>' +
              '<option value="<"><</option>' +
              '<option value=">=">>=</option>' +
              '<option value="<="><=</option>'
            );
          }
          else {
            $(this).closest('.filter').find('select[name="filter_operator[]"]').append(
              '<option value="contain">Contain</option>' +
              '<option value="notContain">Not contain</option>' +
              '<option value="startWith">Start with</option>' +
              '<option value="notStartWith">Not start with</option>' +
              '<option value="endWith">End with</option>'
            );
          }
        }
        else if (numericFields.includes($(this).val())) {
          $(this).closest('.filter').find('select[name="filter_operator[]"]').append(
            '<option value="=">=</option>' +
            '<option value="!=">!=</option>' +
            '<option value=">">></option>' +
            '<option value="<"><</option>' +
            '<option value=">=">>=</option>' +
            '<option value="<="><=</option>'
          );
        }
        $(this).closest('.filter').find('select[name="filter_operator[]"]').select2({ width: '100%' });
        $(this).closest('.filter').find('select[name="filter_operator[]"]').attr('disabled', false);
      });

      $('#filterContainer').on('click', '.deleteBtn', function() {
        $(this).closest('.filter').slideUp('fast', function() { 
          $(this).remove();
        });
      });

      $('#submitBtn').click(function() {
        $('#form').submit();
      });
    });
  </script>
</html>