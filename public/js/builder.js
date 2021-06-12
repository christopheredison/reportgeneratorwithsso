class Builder {

	constructor(database, tables, tablesFields) {
		this.database = database;
		this.tables = tables;
		this.tablesFields = tablesFields;
		this.selectedTables = tables[0];
    this.selectedFields = [];
	}

	getTables() {
		return this.tables;
	}

	getTableFields(table) {
		return this.tablesFields[table];
	}

	getTablesFields() {
		return this.tablesFields;
	}

	getSelectedTables() {
		return this.selectedTables;
	}

	setSelectedTables(selectedTables) {
		this.selectedTables = selectedTables;
	}

  getSelectedFields() {
    return this.selectedFields;
  }

  setSelectedFields(selectedFields) {
    this.selectedFields = selectedFields;
  }

	getQueryResponse() {
		return this.queryResponse;
	}

	setQueryResponse(queryResponse) {
		this.queryResponse = queryResponse;
	}

}

var builder;

$(document).ready(function() {

	$('#dataSource').change(function() {
    $('#classificationInputs').hide();
		if ($(this).val() === 'database') {
			$('#databaseInputContainer').show();
			$('#excelInputContainer').hide();
      $('#filterBtn, #filterContainer').hide();
		}
		else if ($(this).val() === 'excel') {
			$('#databaseInputContainer').hide();
			$('#excelInputContainer').show();
      $('#filterBtn, #filterContainer').show();
		}
	});

  $('#fileSource').change(function() {
    $('#classificationInputs').hide();
    if ($(this).val() === 'upload') {
      $('#uploadContainer').show();
      $('#onedriveContainer').hide();
    }
    else if ($(this).val() === 'onedrive') {
      $('#uploadContainer').hide();
      $('#onedriveContainer').show();
    }
  });

	$('#excel').change(function() {
		let ext = $(this).val().split('.').pop();
		if (ext !== 'xlsx' && ext !== 'xls') {
			$(this).val('');
			swal('Warning', 'Please choose an Excel file', 'warning');
			return;
		}
		let formData = new FormData();
    formData.append('excel', $(this)[0].files[0]);
		formData.append('file_source', $('#fileSource').val());
		$('.loader').show();
    $.ajax({
      url: $('input[name="url_excel"]').val(),
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      type: 'post',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        if (response.status_code === 1) {
          builder.setQueryResponse(response.result);
          generateClassification(response.result);
        }
        else if (response.status_code === 0) {
          swal('Warning', response.message, 'warning');
        }
        $('.loader').hide();
      },
      error: function(jqXHR, textStatus, errorThrown) {
        $('.loader').hide();
        swal('Warning', jqXHR.responseJSON.message, 'warning');
      }
    });
	});

  $('#onedriveBtn').click(function() {
    getExcelFieldsFromOnedrive();
  });

  $('#database').change(function() {
    initQueryBuilder();
  });

  $('#table').change(function() {
    $('#classificationInputs, #queryTextContainer').hide();
    clearQueryBuilder();
  });

  $('#queryType').change(function(event) {
    checkQueryType();
  });

  $('#joinTableBtn').click(function() {
    let clone = $('.joinTableClone').clone();
    clone.toggleClass('joinTableClone joinTable');
    $('#joinTableContainer').append(clone);
    let tables = builder.getTables();
    clone.find('select[name*="join_table_"]').append($('<option />'));
    $.each(tables, function() {
      clone.find('select[name*="join_table_"]').append($('<option />').val(this).text(this));
    });
    clone.find('.select2').select2({ width: '100%' });
    clone.slideDown('fast');
  });

  $('#joinTableContainer').on('change', 'select[name*="join_table_"]', function() {
    let index = $(this).attr('name')[11];
    let table = $(this).val();
    let fields = builder.getTableFields(table);
    let selectFields = $(this).closest('.joinTable').find('select[name="join_field_' + index + '[]"]');
    selectFields.empty();
    selectFields.append($('<option />'));
    $.each(fields, function() {
      selectFields.append($('<option />').val(table + '.' + this).text(this));
    }); 
    selectFields.prop('disabled', false);
  });

  $('#joinTableContainer').on('change', 'select[name*="join_field_"]', function() {
    let [field1, field2] = $(this).closest('.joinTable').find('select[name*="join_field_"]');
    if ($(field1).val() !== '' && $(field2).val() !== '') {
      updateFields();
    }
  });

  $('#joinTableContainer').on('click', '.deleteBtn', function() {
    $(this).closest('.joinTable').slideUp('fast', function() { 
      $(this).remove();
      updateFields();
    });
  });

  $('#sqlClausesBtn').on('click', function() {
    $('#sqlClauses').slideToggle('fast');
    if ($(this).text() === 'Show SQL clauses') {
      $(this).text('Hide SQL clauses');
    }
    else {
      $(this).text('Show SQL clauses');
    }
  });

  $('#aggregateBtn').click(function() {
    $('#aggregateContainer').slideToggle('fast', function() {
      if ($('#aggregateContainer').is(':visible')) {
        $('#aggregateContainer').find('select[name="aggregate_function"]').val('count');
        $('#aggregateContainer').find('select[name="aggregate_field"]').empty();
        let element = $('<optgroup />').attr('label', 'all').append($('<option />').val('*').text('*'));
        $('#aggregateContainer').find('select[name="aggregate_field"]').append(element);
        let selectedTables = builder.getSelectedTables();
        $.each(selectedTables, function(table, fields) {
          let element = $('<optgroup />').attr('label', table);
          $.each(fields, function() {
            element.append($('<option />').val(table + '.' + this).text(table + '.' + this));
          });
          $('#aggregateContainer').find('select[name="aggregate_field"]').append(element);
        });
        $('#aggregateContainer').find('select[name="aggregate_field"]').select2({ width: '100%' });
      }
    });
  });

  $('#aggregateContainer').find('select[name="aggregate_function"]').change(function() {
    $('#aggregateContainer').find('select[name="aggregate_field"]').empty();
    if ($('#aggregateContainer').find('select[name="aggregate_function"]').val() === 'count') {
      let element = $('<optgroup />').attr('label', 'all').append($('<option />').val('*').text('*'));
      $('#aggregateContainer').find('select[name="aggregate_field"]').append(element);
    }
    let selectedTables = builder.getSelectedTables();
    $.each(selectedTables, function(table, fields) {
      let element = $('<optgroup />').attr('label', table);
      $.each(fields, function() {
        element.append($('<option />').val(table + '.' + this).text(table + '.' + this));
      });
      $('#aggregateContainer').find('select[name="aggregate_field"]').append(element);
    });
    $('#aggregateContainer').find('select[name="aggregate_field"]').select2({ width: '100%' });
  });

  $('#aggregateContainer').on('click', '.addBtn', function() {
    let func = $('#aggregateContainer').find('select[name="aggregate_function"]').val();
    let field = $('#aggregateContainer').find('select[name="aggregate_field"]').val();
    if (!$('#select-field-container .row.row-aggregate').length) {
      $('#select-field-container').append(`
        <b>aggregates</b>
        <div class="row row-aggregate"></div>
        `);
    }
    if (!$('#select-field-container .row.row-aggregate').find(`input[value='${func}(${field})']`).length) {
      const id = Math.floor(Math.random() * 1000) + 100;
      $('#select-field-container .row.row-aggregate').append(`
        <div class="col-md-6 col-sm-12 select-column">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="${func}(${field})" name="query_fields[]" id="checkboxSelect${id}" checked>
            <label class="form-check-label" for="checkboxSelect${id}">
              ${func}(${field})
            </label>
          </div>
        </div>
      `);
      $($('#select-field-container input')[0]).change();
    }
  });

  $('#select-field-container').on('change', 'input', function() {
    const selected = [];
    $('#select-field-container input[type="checkbox"]:checked').map((index, field) => selected.push($(field).val()));
    builder.setSelectedFields(selected);
    $('#aliasContainer').empty();
  });

  $('#aliasBtn').click(function() {
    let clone = $('.aliasClone').clone();
    clone.toggleClass('aliasClone alias');
    $('#aliasContainer').append(clone);

    let selectedFields = builder.getSelectedFields();
    let queryFields = selectedFields.filter(field => !contains(field, ['count(', 'max(', 'min(', 'sum(', 'avg(']));
    let aggregateFields = selectedFields.filter(field => !queryFields.includes(field));
    let tables = Array.from(new Set(queryFields.map(field => field.split('.')[0])));
    let resultFields = {};
    for (let i = 0; i < tables.length; i++) {
      resultFields[tables[i]] = queryFields.reduce(((table, fields, field) => 
        {
          if (table === field.split('.')[0]) {
            fields.push(field.split('.')[1]);
          }
          return fields;
        }).bind(null, tables[i]), []);
    }
    if (aggregateFields.length > 0) {
      tables.push('aggregates');
      resultFields.aggregates = aggregateFields;
    }
    $.each(resultFields, function(table, fields) {
      let element = $('<optgroup />').attr('label', table);
      $.each(fields, function() {
        if (table === 'aggregates') {
          element.append($('<option />').val(this).text(this));
        }
        else {
          element.append($('<option />').val(table + '.' + this).text(table + '.' + this));
        }
      });
      clone.find('select[name="alias_field[]"]').append(element);
    });
    clone.find('.select2').select2({ width: '100%' });
    clone.slideDown('fast');
  });

  $('#aliasContainer').on('click', '.deleteBtn', function() {
    $(this).closest('.alias').slideUp('fast', function() { 
      $(this).remove();
    });
  });

  $('#conditionBtn').click(function() {
    let clone = $('.conditionClone').clone();
    clone.toggleClass('conditionClone condition');
    $('#conditionContainer').append(clone);

    let selectedTables = builder.getSelectedTables();
    $.each(selectedTables, function(table, fields) {
      let element = $('<optgroup />').attr('label', table);
      $.each(fields, function() {
        element.append($('<option />').val(table + '.' + this).text(table + '.' + this));
      });
      clone.find('select[name="condition_field[]"]').append(element);
    });
    clone.find('.select2').select2({ width: '100%' });
    clone.slideDown('fast');
  });

  $('#conditionContainer').on('click', '.deleteBtn', function() {
    $(this).closest('.condition').slideUp('fast', function() { 
      $(this).remove();
    });
  });

  $('#groupByBtn').click(function() {
    if (!$('.groupBy')[0]) {
      let clone = $('.groupByClone').clone();
      clone.toggleClass('groupByClone groupBy');
      $('#groupByContainer').append(clone);

      let selectedTables = builder.getSelectedTables();
      $.each(selectedTables, function(table, fields) {
        let element = $('<optgroup />').attr('label', table);
        $.each(fields, function() {
          element.append($('<option />').val(table + '.' + this).text(table + '.' + this));
        });
        clone.find('select[name="group_fields[]"]').append(element);
      });
      clone.find('.select2').select2({ width: '100%' });
      clone.slideDown('fast');
    }
  });

  $('#groupByContainer').on('click', '.deleteBtn', function() {
    $(this).closest('.groupBy').slideUp('fast', function() { 
      $(this).remove();
    });
  });

  $('#havingBtn').click(function() {
    let clone = $('.havingClone').clone();
    clone.toggleClass('havingClone having');
    $('#havingContainer').append(clone);

    let selectedFields = builder.getSelectedFields();
    let aggregateFields = selectedFields.filter(field => contains(field, ['count(', 'max(', 'min(', 'sum(', 'avg(']));
    if (aggregateFields.length > 0) {
      let element = $('<optgroup />').attr('label', 'aggregates');
      $.each(aggregateFields, function() {
        element.append($('<option />').val(this).text(this));
      });
      clone.find('select[name="having_field[]"]').append(element).select2({ width: '100%' });
      clone.slideDown('fast');
    }
    else {
      swal('warning', 'No aggregate field selected', 'warning');
    }
  });

  $('#havingContainer').on('click', '.deleteBtn', function() {
    $(this).closest('.having').slideUp('fast', function() { 
      $(this).remove();
    });
  });

  $('#orderByBtn').click(function() {
    let clone = $('.orderByClone').clone();
    clone.toggleClass('orderByClone orderBy');
    $('#orderByContainer').append(clone);

    let selectedTables = builder.getSelectedTables();
    $.each(selectedTables, function(table, fields) {
      let element = $('<optgroup />').attr('label', table);
      $.each(fields, function() {
        element.append($('<option />').val(table + '.' + this).text(table + '.' + this));
      });
      clone.find('select[name="order_field[]"]').append(element);
    });
    clone.find('.select2').select2({ width: '100%' });
    clone.slideDown('fast');
  });

  $('#orderByContainer').on('click', '.deleteBtn', function() {
    $(this).closest('.orderBy').slideUp('fast', function() { 
      $(this).remove();
    });
  });

  $('#limitBtn').click(function() {
    if (!$('.limit')[0]) {
      let clone = $('.limitClone').clone();
      clone.toggleClass('limitClone limit');
      $('#limitContainer').append(clone);
      clone.slideDown('fast');
    }
  });

  $('#limitContainer').on('click', '.deleteBtn', function() {
    $(this).closest('.limit').slideUp('fast', function() { 
      $(this).remove();
    });
  });

  $('#buildQueryBtn').click(function() {
    getQueryBuilderFields();
  });

  $('#queryBtn').click(function() {
    getQueryTextFields();
  });

  $('#visualization').change(function() {
    generateClassification(builder.getQueryResponse());
  });

  $('input[type=radio][name=color_option]').change(function() {
    if ($(this).val() === 'default') {
      $('#colorBtn').hide();
      $('#colorContainer').hide();
      $('#colorContainer').empty();
    }
    else if ($(this).val() === 'custom') {
      $('#colorBtn').show();
      $('#colorContainer').show();
    }
  });

  $('#colorBtn').click(function() {
    let clone = $('.colorClone').clone();
    clone.toggleClass('colorClone color');
    $('#colorContainer').append(clone);
    clone.slideDown('fast');
  });

  $('#colorContainer').on('click', '.deleteBtn', function() {
    $(this).closest('.color').slideUp('fast', function() { 
      $(this).remove();
    });
  });

  $('#filterBtn').click(function() {
    let clone = $('.filterClone').clone();
    clone.toggleClass('filterClone filter');
    $('#filterContainer').append(clone);
    clone.slideDown('fast');
    let categoryFields = $('#classificationInputs').find('input[name="category_fields[]"]').map(function () {
      return this.value;
    }).get();
    let numericFields = $('#classificationInputs').find('input[name="numeric_fields[]"]').map(function () {
      return this.value;
    }).get();

    if (categoryFields.length > 0) {
      let element = $('<optgroup />').attr('label', 'Category field');
      $.each(categoryFields, function() {
        element.append($('<option />').val(this).text(this));
      });
      clone.find('select[name="filter_field[]"]').append(element);
    }

    if (numericFields.length > 0) {
      let element = $('<optgroup />').attr('label', 'Numeric field');
      $.each(numericFields, function() {
        element.append($('<option />').val(this).text(this));
      });
      clone.find('select[name="filter_field[]"]').append(element);
    }
    clone.find('select[name="filter_field[]"]').select2({ width: '100%' });
    clone.find('select[name="filter_field[]"]').trigger('change');

  });

  $('#filterContainer').on('change', 'select[name="filter_field[]"]', function() {
    let categoryFields = $('#classificationInputs').find('input[name="category_fields[]"]').map(function () {
      return this.value;
    }).get();
    let numericFields = $('#classificationInputs').find('input[name="numeric_fields[]"]').map(function () {
      return this.value;
    }).get();
    $(this).closest('.filter').find('select[name="filter_operator[]"]').empty();
    if (categoryFields.includes($(this).val())) {
      $(this).closest('.filter').find('select[name="filter_operator[]"]').append(
        '<option value="contain">Contain</option>' +
        '<option value="notContain">Not contain</option>' +
        '<option value="startWith">Start with</option>' +
        '<option value="notStartWith">Not start with</option>' +
        '<option value="endWith">End with</option>'
      );
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

  $('#previewBtn').on('click', function() {
    if (!validateVisualizationFields()) {
    	return;
    }
    $('#form').attr('target', '_blank');
    $('#form').attr('action', $('input[name="url_preview"]').val());
    if ($('input[name="edit_mode"]').val() === '1') {
      $('#putMethod').removeAttr('name');
    }
    $('#form').submit();
  });

  $('#saveBtn').on('click', function() {
  	if (!validateVisualizationFields()) {
    	return;
    }
    $('#form').attr('target', '_self');
    $('#form').attr('action', $('input[name="url_save"]').val());
    if ($('input[name="edit_mode"]').val() === '1') {
      $('#putMethod').attr('name', '_method');
    }
    $('#form').submit();
  });

  if (sessionStorage.getItem('onedrive_link') !== null) {
    $('#dataSource').val('excel');
    $('#fileSource').val('onedrive');
    $('#onedriveLink').val(sessionStorage.getItem('onedrive_link'));
    $('#title').val(sessionStorage.getItem('title'));
    sessionStorage.removeItem('onedrive_link');
    sessionStorage.removeItem('title');
    $('#fileSource').val('onedrive');
  }
  $('#dataSource').trigger('change');
  $('#fileSource').trigger('change');
  $('#database').select2({ width: '100%' });
  $('#visualization').select2({ width: '100%' });

  initQueryBuilder().then(() => {
    if ($('input[name="data"]')[0]) {
      let data = JSON.parse($('input[name="data"]').val());
      if ($('#dataSource').val() === 'database') {
        //Load saved report data to query builder inputs
        if ($('#queryType').val() === 'builder') {
          $('#table').val(data.table).trigger('change');
          if ('join_table_1' in data) {
            for (let i = 0; i < data.join_table_1.length; i++) {
              $('#joinTableBtn').trigger('click');
              $($('#joinTableContainer').find('.joinTable')[i]).find('select[name="join_table_1[]"]').val(data.join_table_1[i]).trigger('change');
              $($('#joinTableContainer').find('.joinTable')[i]).find('select[name="join_field_1[]"]').val(data.join_field_1[i]).trigger('change');
              $($('#joinTableContainer').find('.joinTable')[i]).find('select[name="join_table_2[]"]').val(data.join_table_2[i]).trigger('change');
              $($('#joinTableContainer').find('.joinTable')[i]).find('select[name="join_field_2[]"]').val(data.join_field_2[i]).trigger('change');
            }
          }
          $('#queryFields').val(data.query_fields).trigger('change');
          builder.setSelectedFields(data.query_fields);
          if ('distinct' in data) {
            $('#distinct').prop('checked', true);
          }
          if ('alias_field' in data) {
            for (let i = 0; i < data.alias_field.length; i++) {
              $('#aliasBtn').trigger('click');
              $($('#aliasContainer').find('.alias')[i]).find('select[name="alias_field[]"]').val(data.alias_field[i]).trigger('change');
              $($('#aliasContainer').find('.alias')[i]).find('input[name="alias_value[]"]').val(data.alias_value[i]);
            }
          }
          if ('condition_field' in data) {
            for (let i = 0; i < data.condition_field.length; i++) {
              $('#conditionBtn').trigger('click');
              $($('#conditionContainer').find('.condition')[i]).find('select[name="condition_field[]"]').val(data.condition_field[i]).trigger('change');
              $($('#conditionContainer').find('.condition')[i]).find('input[name="condition_operator[]"]').val(data.condition_operator[i]);
              $($('#conditionContainer').find('.condition')[i]).find('input[name="condition_value[]"]').val(data.condition_value[i]);
              $($('#conditionContainer').find('.condition')[i]).find('select[name="condition_type[]"]').val(data.condition_type[i]);
            }
          }
          if ('group_fields' in data) {
            $('#groupByBtn').trigger('click');
            $('#groupByContainer').find('select[name="group_fields[]"]').val(data.group_fields).trigger('change');
          }
          if ('having_field' in data) {
            for (let i = 0; i < data.having_field.length; i++) {
              $('#havingBtn').trigger('click');
              $($('#havingContainer').find('.having')[i]).find('select[name="having_field[]"]').val(data.having_field[i]).trigger('change');
              $($('#havingContainer').find('.having')[i]).find('input[name="having_operator[]"]').val(data.having_operator[i]);
              $($('#havingContainer').find('.having')[i]).find('input[name="having_value[]"]').val(data.having_value[i]);
              $($('#havingContainer').find('.having')[i]).find('select[name="having_type[]"]').val(data.having_type[i]);
            }
          }
          if ('order_field' in data) {
            for (let i = 0; i < data.order_field.length; i++) {
              $('#orderByBtn').trigger('click');
              $($('#orderByContainer').find('.orderBy')[i]).find('select[name="order_field[]"]').val(data.order_field[i]).trigger('change');
              $($('#orderByContainer').find('.orderBy')[i]).find('select[name="order_type[]"]').val(data.order_type[i]);
            }
          }
          if ('limit_start' in data) {
            $('#limitBtn').trigger('click');
            $('#limitContainer').find('input[name="limit_start"]').val(data.limit_start);
            $('#limitContainer').find('input[name="limit_total"]').val(data.limit_total);
          }
          
          getQueryBuilderFields().then(() => {
            fillClassificationInputs(data);
          });
        }
        else if ($('#queryType').val() === 'text') {
          getQueryTextFields().then(() => {
            fillClassificationInputs(data);
          });
        }
      }
      else if ($('#dataSource').val() === 'excel') {
        if ($('#fileSource').val() === 'onedrive') {
          getExcelFieldsFromOnedrive().then(() => {
            fillClassificationInputs(data);
          });
        }
      }
    }
  });

});

async function initQueryBuilder() {
  $('.loader').show();
  return await $.ajax({
    url: $('input[name="url_database"]').val() + '/' + $('#database').val() + '/tables/fields',
    type: 'get',
    success: function(response) {
      let tables = Object.keys(response);
      $('#table').empty();
      $.each(tables, function() {
        $('#table').append($('<option />').val(this).text(this));
      });
      $('#table').select2(({ width: '100%' }));
      builder = new Builder($('#database').val(), tables, response);
      clearQueryBuilder();
      checkQueryType();
      $('.loader').hide();
    },
    error: function(jqXHR, textStatus, errorThrown) {
      $('.loader').hide();
      swal('Error', jqXHR.responseJSON.message, 'error');
    },
  });
}

function clearQueryBuilder() {
  $('#joinTableContainer').empty();
  updateFields();
}

function checkQueryType() {
  $('#classificationInputs').hide();
  if ($('#queryType').val() === 'builder') {
    $('#queryTextContainer').hide();
    $('#queryBuilderContainer').show();
    $('#query').prop('readonly', true);
    $('#queryBtn').hide();
  }
  else {
    $('#queryTextContainer').show();
    $('#queryBuilderContainer').hide();
    $('#query').prop('readonly', false);
    $('#queryBtn').show();
  }
}

function updateFields() {
  $('#select-field-container').html('');
  let selectedTables = {};
  if ($('.joinTable')[0]) {
    let tables = $('#joinTableContainer').find('select[name*=join_table_]');
    tables = Array.from(new Set(tables)).filter(table => $(table).val() !== '');
    $.each(tables, function() {
      selectedTables[$(this).val()] = builder.getTableFields($(this).val());
    });
  }
  else {
    selectedTables[$('#table').val()] = builder.getTableFields($('#table').val());
  }
  builder.setSelectedTables(selectedTables);
  const html = [];
  $.each(selectedTables, function(table, fields) {
    html.push(`<b>${table}</b><div class="row">`);
    $.each(fields, function(id, field) {
      html.push(`
        <div class="col-md-3 col-sm-4 select-column">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="${table}.${field}" name="query_fields[]" id="checkboxSelect${id}">
            <label class="form-check-label" for="checkboxSelect${id}">
              ${field}
            </label>
          </div>
        </div>
      `);
    });
    html.push(`</div>`);
  });
  $('#select-field-container').html(html.join(''));
  if ($('input[name="data"]')[0]) {
    let data = JSON.parse($('input[name="data"]').val());
    if ('query_fields' in data) {
      let queryFields = data.query_fields;
      let aggregateFields = [];
      queryFields.forEach(field => {
        if (contains(field, ['count(', 'max(', 'min(', 'sum(', 'avg('])) {
          aggregateFields.push(field);
        } else {
          $(`input[type="checkbox"][value="${field}"]`).prop('checked', true);
        }
      });
      if (aggregateFields.length > 0) {
        if (!$('#select-field-container .row.row-aggregate').length) {
          $('#select-field-container').append(`
            <b>aggregates</b>
            <div class="row row-aggregate"></div>
            `);
        }
        aggregateFields.forEach(field => {
          if (!$('#select-field-container .row.row-aggregate').find(`input[value='${field}']`).length) {
            const id = Math.floor(Math.random() * 1000) + 100;
            $('#select-field-container .row.row-aggregate').append(`
              <div class="col-md-6 col-sm-12 select-column">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" value="${field}" name="query_fields[]" id="checkboxSelect${id}" checked>
                  <label class="form-check-label" for="checkboxSelect${id}">
                    ${field}
                  </label>
                </div>
              </div>
            `);
            $($('#select-field-container input')[0]).change();
          }
        })
        let element = $('<optgroup />').attr('label', 'aggregates');
        aggregateFields.forEach(field => {
          element.append($('<option />').val(field).text(field));
        });
        $('#queryFields').append(element);
      }
    }
  }

  $('#aliasContainer').empty();
  $('#conditionContainer').empty();
	$('#groupByContainer').empty();
	$('#orderByContainer').empty();
	$('#limitContainer').empty();
}

async function getQueryBuilderFields() {
  //validate inputs
  let isValid = true;

  $.each($('#joinTableContainer').find('.joinTable'), function() {
    if ($(this).find('select[name="join_table_1[]"]').val() === '' ||
      $(this).find('select[name="join_table_2[]"]').val() === '' ||
      $(this).find('select[name="join_field_1[]"]').val() === '' ||
      $(this).find('select[name="join_field_2[]"]').val() === '') {
      swal('warning', 'Please fill all join table inputs', 'warning');
      isValid = false;
    }
  });

  const selectedFields = Object.values($('input[name="query_fields[]"]:checked').map((idx,dom) => $(dom).val())).filter(item => typeof(item) === 'string');
  // let selectedFields = $('input[name="query_fields[]"]').val();
  if (!selectedFields.length) {
    swal('Warning', 'Please select fields', 'warning');
    isValid = false;
  }
  if (hasDuplicates(selectedFields.map(field => field.includes('(') ? field : field.split('.')[1])) && !$('#aliasContainer').find('.alias').length) {
    swal('Warning', 'Field contain duplicates. Please use alias', 'warning');
    isValid = false;
  }

  $.each($('#aliasContainer').find('.alias'), function() {
    if ($(this).find('input[name="alias_value[]"]').val() === '') {
      swal('warning', 'Please fill all alias inputs', 'warning');
      isValid = false;
    }
  });

  let aliasFields = [];
  $.each($('#aliasContainer').find('.alias'), function() {
    aliasFields.push($(this).find('select[name="alias_field[]"]').val());
  });
  if (hasDuplicates(aliasFields)) {
    swal('warning', 'Alias fields contain duplicates', 'warning');
    isValid = false;
  }

  let aliasValues = [];
  $.each($('#aliasContainer').find('.alias'), function() {
    aliasValues.push($(this).find('input[name="alias_value[]"]').val());
  });
  if (hasDuplicates(aliasValues)) {
    swal('warning', 'Alias values contain duplicates', 'warning');
    isValid = false;
  }

  // Check if aggregates have aliases.
  let aggregateFields = selectedFields.filter(field => contains(field, ['count(', 'max(', 'min(', 'sum(', 'avg(']));
  let aggregateAliases = aliasFields.filter(field => contains(field, ['count(', 'max(', 'min(', 'sum(', 'avg(']));
  if (aggregateFields.length !== aggregateAliases.length) {
    swal('warning', 'All aggregate fields must have aliases', 'warning');
    isValid = false;
  }

  $.each($('#conditionContainer').find('.condition'), function() {
    if ($(this).find('input[name="condition_operator[]"]').val() === '' ||
      $(this).find('input[name="condition_value[]"]').val() === '') {
      swal('warning', 'Please fill all condition inputs', 'warning');
      isValid = false;
    }
  });

  if ($('#groupByContainer').find('select[name="group_fields[]"]')[0] && 
    $('#groupByContainer').find('select[name="group_fields[]"]').val().length === 0) {
    swal('warning', 'Please fill group by input', 'warning');
    isValid = false;
  }

  $.each($('#havingContainer').find('.having'), function() {
    if ($(this).find('input[name="having_operator[]"]').val() === '' ||
      $(this).find('input[name="having_value[]"]').val() === '') {
      swal('warning', 'Please fill all having inputs', 'warning');
      isValid = false;
    }
  });

  if ($('#limitContainer').find('input[name="limit_start"]')[0] && 
    ($('#limitContainer').find('input[name="limit_start"]').val() === '' ||
    $('#limitContainer').find('input[name="limit_total"]').val() === '')) {
    swal('warning', 'Please fill limit input', 'warning');
    isValid = false;
  }

  if (!isValid) {
    return false;
  }

  let tables = $('#joinTableContainer').find('select[name*="join_table_"]').map(function () {
    return this.value;
  }).get();
  if (!$('.joinTable')[0]) {
    tables.push($('#table').val());
  }
  let joinFields = $('#joinTableContainer').find('select[name*="join_field_"]').map(function () {
    return this.value;
  }).get();
  let joinTypes = $('#joinTableContainer').find('select[name*="join_type"]').map(function () {
    return this.value;
  }).get();
  let distinct = $('#distinct:checked').length > 0;
  let condFields = $('#conditionContainer').find('select[name="condition_field[]"]').map(function () {
    return this.value;
  }).get();
  let condOperators = $('#conditionContainer').find('input[name="condition_operator[]"]').map(function () {
    return this.value;
  }).get();
  let condValues = $('#conditionContainer').find('input[name="condition_value[]"]').map(function () {
    return this.value;
  }).get();
  let condTypes = $('#conditionContainer').find('select[name="condition_type[]"]').map(function () {
    return this.value;
  }).get();

  let queryType = $('#queryType').val();
  let database = $('#database').val();

  let data = {
    database: database,
    tables: tables,
    join_fields: joinFields,
    join_types: joinTypes,
    selected_fields: selectedFields,
    distinct: distinct,
    alias_fields: aliasFields,
    alias_values: aliasValues,
    cond_fields: condFields,
    cond_operators: condOperators,
    cond_values: condValues,
    cond_types: condTypes,
    query_type: queryType
  };

  if ($('#groupByContainer').find('.groupBy')[0]) {
    data.group_fields = $('#groupByContainer').find('select[name="group_fields[]"]').val();
  }

  if ($('#havingContainer').find('.having')[0]) {
    data.having_fields = $('#havingContainer').find('select[name="having_field[]"]').map(function () {
      return this.value;
    }).get();
    data.having_operators = $('#havingContainer').find('input[name="having_operator[]"]').map(function () {
      return this.value;
    }).get();
    data.having_values = $('#havingContainer').find('input[name="having_value[]"]').map(function () {
      return this.value;
    }).get();
    data.having_types = $('#havingContainer').find('select[name="having_type[]"]').map(function () {
      return this.value;
    }).get();
  }

  if ($('#orderByContainer').find('.orderBy')[0]) {
    data.order_fields = $('#orderByContainer').find('select[name="order_field[]"]').map(function () {
      return this.value;
    }).get();
    data.order_types = $('#orderByContainer').find('select[name="order_type[]"]').map(function () {
      return this.value;
    }).get();
  }

  if ($('#limitContainer').find('.limit')[0]) {
    data.limit_start = $('#limitContainer').find('input[name="limit_start"]').val();
    data.limit_total = $('#limitContainer').find('input[name="limit_total"]').val();
  }

  $('.loader').show();
  return await $.ajax({
    url: $('input[name="url_query"]').val(),
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    type: 'post',
    data: data,
    success: function(response) {
      if (response.result.length === 0) {
        $('.loader').hide();
        $('#classificationInputs').hide();
        swal('Warning', 'Data is empty. Please check your query.', 'warning');
      }
      else {
        builder.setQueryResponse(response.result);
        $('#query').val(response.query);
        $('#queryTextContainer').slideDown('fast');
        generateClassification(response.result);
        $('.loader').hide();
      }
    },
    error: function(jqXHR, textStatus, errorThrown) {
      $('.loader').hide();
      if (jqXHR.responseJSON.message === 'Undefined offset: 0') {
        $('#classificationInputs').hide();
        swal('Warning', 'Data is empty. Please check your query.', 'warning');
      }
      else {
        swal('Warning', jqXHR.responseJSON.message, 'warning');
      }
    }
  });
}

async function getQueryTextFields() {
  $('.loader').show();
  return await $.ajax({
    url: $('input[name="url_query"]').val(),
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    type: 'post',
    data: {
      database: $('#database').val(),
      query_input: $('#query').val(),
      query_type: $('#queryType').val()
    },
    success: function(response) {
      builder.setQueryResponse(response);
      generateClassification(response);
      $('.loader').hide();
    },
    error: function(jqXHR, textStatus, errorThrown) {
      $('.loader').hide();
      if (jqXHR.responseJSON.message === 'Undefined offset: 0') {
        $('#classificationInputs').hide();
        swal('Warning', 'Data is empty. Please check your query.', 'warning');
      }
      else {
        swal('Warning', jqXHR.responseJSON.message, 'warning');
      }
    }
  });
}

async function getExcelFieldsFromOnedrive() {
  $('.loader').show();
  return await $.ajax({
    url: $('input[name="url_excel"]').val(),
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    type: 'post',
    data: {
      onedrive_link: $('#onedriveLink').val(),
      file_source: $('#fileSource').val(),
      return_url: window.location.href
    },
    success: function(response) {
      if (response.response_code === 2) {
        sessionStorage.setItem('onedrive_link', $('#onedriveLink').val());
        sessionStorage.setItem('title', $('#title').val());
        window.location.replace(response.sign_in_url);
      }
      else if (response.response_code === 1) {
        builder.setQueryResponse(response.result);
        generateClassification(response.result);
      }
      else if (response.response_code === 0) {
        swal('Warning', response.message, 'warning');
        $('#classificationInputs').hide();
      }
      $('.loader').hide();
    },
    error: function(jqXHR, textStatus, errorThrown) {
      $('.loader').hide();
      if (jqXHR.responseJSON.message === 'Undefined offset: 0') {
        $('#classificationInputs').hide();
        swal('Warning', 'Data is empty. Please check your query.', 'warning');
      }
      else if (jqXHR.responseJSON.message === 'Undefined index: @microsoft.graph.downloadUrl') {
        swal('Warning', 'Invalid link. Please try again.', 'warning');
      }
      else {
        swal('Warning', jqXHR.responseJSON.message, 'warning');
      }
    }
  });
}

function validateVisualizationFields() {
	let visualization = $('#visualization').val();
	let categoryFields = $('#classificationInputs').find('input:checked[name="category_fields[]"]').map(function () {
    return this.value;
  }).get();
  let numericFields = $('#classificationInputs').find('input:checked[name="numeric_fields[]"]').map(function () {
    return this.value;
  }).get();
	if (categoryFields.length + numericFields.length === 0) {
  	swal('warning', 'No field is selected', 'warning');
  	return false;
  }
  if (visualization !== 'table') {
  	if (categoryFields.length === 0 && $('#visualization').val() !== 'histogram') {
  		swal('warning', 'Please select a category field', 'warning');
  		return false;
  	}
  	if (numericFields.length === 0) {
			swal('warning', 'Please select a numeric field', 'warning');
  		return false;
  	}
  }
  return true;
}

function generateClassification(queryResponse) {
  let fields = Object.keys(queryResponse);
  let numericFields = [];
  let categoryFields = [];
  let dateFields = [];
  for (let i = 0; i < fields.length; i++) {
    if (typeof queryResponse[fields[i]] === 'number') {
      numericFields.push(fields[i]);
    }
    else if (typeof queryResponse[fields[i]] === 'string') {
      if (isNumeric(queryResponse[fields[i]])) {
        numericFields.push(fields[i]);
      }
      else {
        categoryFields.push(fields[i]); 
      }
      if (isValidDate(queryResponse[fields[i]])) {
        dateFields.push(fields[i]);
      }
    }
  }

  let categoryLabel = categoryFields.length > 0 ? '<label>Category field:</label>' : '';
  let numericLabel = numericFields.length > 0 ? '<label>Numeric field:</label>' : '';
  let type = $('#visualization').val() === 'table' ? 'checkbox' : 'radio';
  let classification = '';
  if ($('#visualization').val() !== 'histogram') {
    classification += "<div class='col-md-6'>" + categoryLabel;
    for (let i = 0; i < categoryFields.length; i++) {
      classification += "<div class='form-check'>" +
      "<input class='form-check-input' type='" + type + "' name='category_fields[]' value='"+ categoryFields[i] + "'>" +
      "<label class='form-check-label'>" + categoryFields[i] + "</label>" +
      "</div>";
    }
    classification += "</div>";
  }
  classification += "<div class='col-md-6'>" + numericLabel;
  for (let i = 0; i < numericFields.length; i++) {
    classification += "<div class='form-check'>" +
    "<input class='form-check-input' type='checkbox' name='numeric_fields[]' value='"+ numericFields[i] + "'>" +
    "<label class='form-check-label'>" + numericFields[i] + "</label>" +
    "</div>";
  }
  for (let i = 0; i < dateFields.length; i++) {
    classification += "<input type='hidden' name='date_fields[]' value='"+ dateFields[i] + "'>";
  }
  classification += "</div>";

  $('#classification').empty();
  $('#classification').append(classification);
  $('#colorContainer').empty();
  $('#classificationInputs').show();
}

function fillClassificationInputs(data) {
  $('#visualization').val(data.visualization).trigger('change');
  $('#classificationInputs input[name="color_option"]').filter('[value="' + data.color_option + '"]').attr('checked', true).trigger('change');
  if ('color' in data) {
    for (let i = 0; i < data.color.length; i++) {
      $('#colorBtn').trigger('click');
      $($('#colorContainer').find('.color')[i]).find('input[name="color[]"]').val(data.color[i]);
    }        
  }
  $('#classificationInputs input[name="category_fields[]"]').each(function(index) {
    if (data.category_fields.includes($(this).val())) {
      $(this).attr('checked', true);
    }
  });
  $('#classificationInputs input[name="numeric_fields[]"]').each(function(index) {
    if (data.numeric_fields.includes($(this).val())) {
      $(this).attr('checked', true);
    }
  });
  if ('filter_field' in data) {
    for (let i = 0; i < data.filter_field.length; i++) {
      $('#filterBtn').trigger('click');
      $($('#filterContainer').find('.filter')[i]).find('select[name="filter_field[]"]').val(data.filter_field[i]).trigger('change');
      $($('#filterContainer').find('.filter')[i]).find('select[name="filter_operator[]"]').val(data.filter_operator[i]);
      $($('#filterContainer').find('.filter')[i]).find('input[name="filter_value[]"]').val(data.filter_value[i]);
    }        
  }
}

function hasDuplicates(w) {
    return new Set(w).size !== w.length 
}

function contains(target, pattern) {
    var value = 0;
    pattern.forEach(function(word){
      value = value + target.includes(word);
    });
    return (value === 1)
}

function isNumeric(str) {
  if (typeof str != "string") return false // we only process strings!  
  return !isNaN(str) && // use type coercion to parse the _entirety_ of the string (`parseFloat` alone does not do this)...
         !isNaN(parseFloat(str)) // ...and ensure strings of whitespace fail
}

function isValidDate(dateObject){
    return new Date(dateObject).toString() !== 'Invalid Date';
}