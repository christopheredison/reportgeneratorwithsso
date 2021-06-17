<?php

namespace App\Http\Controllers;

use App\reports\MyReport;
use App\Helpers\Helper;
use App\Models\Report;
use App\Models\ReportQuery;
use App\Models\ReportExcel;
use App\Models\Database;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use \PhpOffice\PhpSpreadsheet\IOFactory;
use \PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use \koolreport\core\Utility as Util;

use Auth;

class ReportController extends BaseController
{

  /**
   * Show the application dashboard.
   *
   * @return \Illuminate\Contracts\Support\Renderable
   */
  public function index(Request $request)
  {
    $user_id = $request->user()->id;
    $data = [];
    if ($_GET) {
      $conditions = [];
      if ($request->has('title')) {
        array_push($conditions, ['title', 'LIKE', '%' . $request['title'] . '%']);
      }
      $data['filterTitle'] = $request['title'];
      $reportList = Report::where($conditions)->where('user_id', $user_id)->orderBy('id', 'DESC')->paginate(10);
    }
    else {
      $reportList = Report::where('user_id', $user_id)->orderBy('id', 'DESC')->paginate(10);
    }
    $data['reportList'] = $reportList;
    if ($request->has('action')) {
      $data['action'] = $request->action;
    }
    return view('report/index', $data);
  }

  public function export(Request $request, $id)  {
    $report = Report::find($id);
    $detail = json_decode($report->detail, true);
    $categoryFields = array_key_exists('category_fields', $detail) ? $detail['category_fields'] : [];
    $numericFields = array_key_exists('numeric_fields', $detail) ? $detail['numeric_fields'] : [];
    $reportParams = 
    [
      'title'=>$report->title,
      'data_source'=>$report->data_source,
      'visualization'=>$report->visualization,
      'columns'=>array_merge($categoryFields, $numericFields),
      'colors'=>array_key_exists('color', $detail) ? $detail['color'] : [],
      'conditions'=>array_key_exists('condition_field', $detail) 
        ? 
          [
            'condition_field' => $detail['condition_field'], 
            'condition_operator' => $detail['condition_operator'], 
            'condition_value' => $detail['condition_value'],
            'condition_type' => $detail['condition_type']
          ]
        : 
          [],
      'filters'=>array_key_exists('filter_field', $detail) 
        ?
          [
            'filter_field' => $detail['filter_field'],
            'filter_operator' => $detail['filter_operator'],
            'filter_value' => $detail['filter_value'],
          ]
        :
          [],
      'action'=>'export'
    ];
    if ($report->data_source === 'database') {
      $reportQuery = $report->reportQuery;

      $dbdata = Database::find($reportQuery->database);
      if (!$dbdata) {
        return [
          'status' => 'fail',
          'errors' => ['Database not found']
        ];
      }

      config([
          'database.connections.dynamic' => 
          [
              'driver'    => $dbdata->database_driver,
              'host'      => $dbdata->database_host,
              'port'      => $dbdata->database_port,
              'database'  => $dbdata->database_name,
              'username'  => $dbdata->database_username,
              'password'  => $dbdata->database_password,
              'charset'   => 'utf8mb4',
              'collation' => 'utf8mb4_unicode_ci',
              'options' => [
                  \PDO::ATTR_EMULATE_PREPARES => true,
              ],
          ],
      ]);

      $reportParams['database'] = 'dynamic';
      $reportParams['query_input'] = $reportQuery->query_input;
    }
    elseif ($report->data_source === 'excel') {
      $reportExcel = $report->reportExcel;
      if ($reportExcel->file_source === 'upload') {
        $reportParams['file_path'] = $reportExcel->file_path;
      }
      elseif ($reportExcel->file_source === 'onedrive') {
        if ($request->session()->has('access_token')) {
          $result = Helper::getOnedriveUrlFile($reportExcel->file_path, $request->session()->get('access_token'));
          if (isset($result['error']) && $result['error']['code'] === 'InvalidAuthenticationToken') {
            $request->session()->put('return_url', url()->full());
            return redirect()->away(config('global.onedrive.sign_in_url') . '?client_id=' . config('global.onedrive.client_id') . '&scope=files.read&response_type=token&redirect_uri=' . config('global.onedrive.redirect_uri'));
          }
          $excel = file_get_contents($result['@microsoft.graph.downloadUrl']);
          Storage::disk('local')->put('temp.xlsx', $excel);
          $reportParams['file_path'] = 'temp.xlsx';
        }
        else {
          $request->session()->put('return_url', url()->full());
          return redirect()->away(config('global.onedrive.sign_in_url') . '?client_id=' . config('global.onedrive.client_id') . '&scope=files.read&response_type=token&redirect_uri=' . config('global.onedrive.redirect_uri'));
        }
      }
    }
    $myReport = new MyReport($reportParams);
    $result = $myReport->run();
    if ($request->format === 'pdf') {
      $result->export()->pdf(array(
        "format"=>"A4",
        "orientation"=>$report->visualization === 'table' || $report->visualization === 'pie' || $report->visualization === 'donut' ? 'portrait' : 'landscape',
        "zoom"=>$report->visualization === 'table' ? '1' : '0.7',
      ))->toBrowser($report->title . ".pdf");
    }
    elseif ($request->format === 'xlsx') {   
      $result->exportToExcel()->
      toBrowser($report->title . ".xlsx");
    }
    elseif ($request->format === 'jpg') {   
      $result->export()->jpg(array(
        "width"=>"1024px",
        "height"=>"768px",
      ))->toBrowser($report->title . ".jpg");
    }
    elseif ($request->format === 'png') {   
      $result->export()->png(array(
        "width"=>"1024px",
        "height"=>"768px",
      ))->toBrowser($report->title . ".png");
    }
  }

  public function show(Request $request, $id) 
  {
    if ($request->isMethod('post')) {
      $reportParams = $request->session()->get('report_params');
      $reportParams['filters'] = request()->exists('filter_field') 
        ?
          [
            'filter_field' => $request->filter_field,
            'filter_operator' => $request->filter_operator,
            'filter_value' => $request->filter_value,
          ]
        :
          [];
      if ($reportParams['data_source'] == 'database') {
        $dbdata = Database::find($reportParams['database_id']);
        if (!$dbdata) {
          return [
            'status' => 'fail',
            'errors' => ['Database not found']
          ];
        }

        config([
            'database.connections.dynamic' => 
            [
                'driver'    => $dbdata->database_driver,
                'host'      => $dbdata->database_host,
                'port'      => $dbdata->database_port,
                'database'  => $dbdata->database_name,
                'username'  => $dbdata->database_username,
                'password'  => $dbdata->database_password,
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'options' => [
                    \PDO::ATTR_EMULATE_PREPARES => true,
                ],
            ],
        ]);

        $reportParams['database'] = 'dynamic';
      }
    }
    elseif ($request->isMethod('get')) {
      $report = Report::find($id);
      $detail = json_decode($report->detail, true);
      $categoryFields = array_key_exists('category_fields', $detail) ? $detail['category_fields'] : [];
      $numericFields = array_key_exists('numeric_fields', $detail) ? $detail['numeric_fields'] : [];
      $reportParams = 
      [
        'title'=>$report->title,
        'data_source'=>$report->data_source,
        'visualization'=>$report->visualization,
        'columns'=>array_merge($categoryFields, $numericFields),
        'category_fields'=>$categoryFields,
        'numeric_fields'=>$numericFields,
        'date_fields'=>array_key_exists('date_fields', $detail) ? $detail['date_fields'] : [],
        'colors'=>array_key_exists('color', $detail) ? $detail['color'] : [],
        'conditions'=>array_key_exists('condition_field', $detail) 
          ? 
            [
              'condition_field' => $detail['condition_field'], 
              'condition_operator' => $detail['condition_operator'], 
              'condition_value' => $detail['condition_value'],
              'condition_type' => $detail['condition_type']
            ]
          : 
            [],
        'filters'=>array_key_exists('filter_field', $detail) 
          ?
            [
              'filter_field' => $detail['filter_field'],
              'filter_operator' => $detail['filter_operator'],
              'filter_value' => $detail['filter_value'],
            ]
          :
            [],
        'action'=>'show'
      ];
      if ($report->data_source === 'database') {
        $reportQuery = $report->reportQuery;

        $dbdata = Database::find($reportQuery->database);
        if (!$dbdata) {
          return [
            'status' => 'fail',
            'errors' => ['Database not found']
          ];
        }

        config([
            'database.connections.dynamic' => 
            [
                'driver'    => $dbdata->database_driver,
                'host'      => $dbdata->database_host,
                'port'      => $dbdata->database_port,
                'database'  => $dbdata->database_name,
                'username'  => $dbdata->database_username,
                'password'  => $dbdata->database_password,
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'options' => [
                    \PDO::ATTR_EMULATE_PREPARES => true,
                ],
            ],
        ]);

        $reportParams['database'] = 'dynamic';
        $reportParams['database_id'] = $reportQuery->database;
        $reportParams['query_input'] = $reportQuery->query_input;
      }
      elseif ($report->data_source === 'excel') {
        $reportExcel = $report->reportExcel;
        $reportParams['file_source'] = $reportExcel->file_source;
        if ($reportExcel->file_source === 'upload') {
          $reportParams['file_path'] = $reportExcel->file_path;
        }
        elseif ($reportExcel->file_source === 'onedrive') {
          if ($request->session()->has('access_token')) {
            $result = Helper::getOnedriveUrlFile($reportExcel->file_path, $request->session()->get('access_token'));
            if (isset($result['error']) && $result['error']['code'] === 'InvalidAuthenticationToken') {
              $request->session()->put('return_url', url()->full());
              return redirect()->away(config('global.onedrive.sign_in_url') . '?client_id=' . config('global.onedrive.client_id') . '&scope=files.read&response_type=token&redirect_uri=' . config('global.onedrive.redirect_uri'));
            }
            $excel = file_get_contents($result['@microsoft.graph.downloadUrl']);
            Storage::disk('local')->put('temp.xlsx', $excel);
            $reportParams['file_path'] = 'temp.xlsx';
          }
          else {
            $request->session()->put('return_url', url()->full());
            return redirect()->away(config('global.onedrive.sign_in_url') . '?client_id=' . config('global.onedrive.client_id') . '&scope=files.read&response_type=token&redirect_uri=' . config('global.onedrive.redirect_uri'));
          }
        }
      }
      $request->session()->put('report_params', $reportParams);
    }
    
    $report = new MyReport($reportParams);
    $report->run();
    return view("report", ["report" => $report]);
  }

  public function create()
  {
    $databases = Database::select('id', 'name', 'database_name')->get();
    return view('report/builder', ['databases' => $databases, 'editMode' => false]);
  }

  public function preview(Request $request)
  {
    if ($request->isMethod('get')) {
      $reportParams = $request->session()->get('report_params');
      if ($reportParams['data_source'] === 'excel' && $reportParams['file_source'] === 'onedrive') {
        if ($request->session()->has('access_token')) {
          $result = Helper::getOnedriveUrlFile($reportParams['onedrive_link'], $request->session()->get('access_token'));
          if (isset($result['error']) && $result['error']['code'] === 'InvalidAuthenticationToken') {
            $request->session()->put('return_url', str_replace('preview', '', url()->full()));
            return redirect()->away(config('global.onedrive.sign_in_url') . '?client_id=' . config('global.onedrive.client_id') . '&scope=files.read&response_type=token&redirect_uri=' . config('global.onedrive.redirect_uri'));
          }
          $excel = file_get_contents($result['@microsoft.graph.downloadUrl']);
          Storage::disk('local')->put('temp.xlsx', $excel);
          $reportParams['file_path'] = 'temp.xlsx';
        }
        else {
          $request->session()->put('return_url', url()->full());
          return redirect()->away(config('global.onedrive.sign_in_url') . '?client_id=' . config('global.onedrive.client_id') . '&scope=files.read&response_type=token&redirect_uri=' . config('global.onedrive.redirect_uri'));
        }
      } else {
        $dbdata = Database::find($reportParams['database_id']);
        if (!$dbdata) {
          return [
            'status' => 'fail',
            'errors' => ['Database not found']
          ];
        }

        config([
            'database.connections.dynamic' => 
            [
                'driver'    => $dbdata->database_driver,
                'host'      => $dbdata->database_host,
                'port'      => $dbdata->database_port,
                'database'  => $dbdata->database_name,
                'username'  => $dbdata->database_username,
                'password'  => $dbdata->database_password,
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'options' => [
                    \PDO::ATTR_EMULATE_PREPARES => true,
                ],
            ],
        ]);
      }
    }
    elseif ($request->isMethod('post')) {
      if ($request->exists('refilter')) {
        $reportParams = $request->session()->get('report_params');
        $reportParams['filters'] = request()->exists('filter_field') 
          ?
            [
              'filter_field' => $request->filter_field,
              'filter_operator' => $request->filter_operator,
              'filter_value' => $request->filter_value,
            ]
          :
            [];
        if ($reportParams['data_source'] == 'database') {
          $dbdata = Database::find($reportParams['database_id']);
          if (!$dbdata) {
            return [
              'status' => 'fail',
              'errors' => ['Database not found']
            ];
          }

          config([
              'database.connections.dynamic' => 
              [
                  'driver'    => $dbdata->database_driver,
                  'host'      => $dbdata->database_host,
                  'port'      => $dbdata->database_port,
                  'database'  => $dbdata->database_name,
                  'username'  => $dbdata->database_username,
                  'password'  => $dbdata->database_password,
                  'charset'   => 'utf8mb4',
                  'collation' => 'utf8mb4_unicode_ci',
                  'options' => [
                      \PDO::ATTR_EMULATE_PREPARES => true,
                  ],
              ],
          ]);
        }
      }
      else {
        $validatedData = $request->validate([
          'database' => 'required|string|max:25',
          'query_type' => 'required|string|max:7',
          'visualization' => 'required|string|max:25'
        ]);

        $categoryFields = request()->exists('category_fields') ? $request->category_fields : [];
        $numericFields = request()->exists('numeric_fields') ? $request->numeric_fields : [];
        $reportParams = 
        [
          'title'=>$request->title,
          'data_source'=>$request->data_source,
          'file_source'=>$request->file_source,
          'onedrive_link'=>$request->onedrive_link,
          'visualization'=>$request->visualization,
          'columns'=>array_merge($categoryFields, $numericFields),
          'category_fields'=>$categoryFields,
          'numeric_fields'=>$numericFields,
          'date_fields'=>request()->exists('date_fields') ? $request->date_fields : [],
          'colors'=>request()->exists('color') ? $request->color : [],
          'conditions'=>request()->exists('condition_field') 
            ? 
              [
                'condition_field' => $request->condition_field, 
                'condition_operator' => $request->condition_operator, 
                'condition_value' => $request->condition_value,
                'condition_type' => $request->condition_type
              ]
            : 
              [],
          'filters'=>request()->exists('filter_field') 
            ?
              [
                'filter_field' => $request->filter_field,
                'filter_operator' => $request->filter_operator,
                'filter_value' => $request->filter_value,
              ]
            :
              [],
          'action'=>'show'
        ];
        if ($request->data_source === 'database') {
          $dbdata = Database::find($request->database);
          if (!$dbdata) {
            return [
              'status' => 'fail',
              'errors' => ['Database not found']
            ];
          }

          config([
              'database.connections.dynamic' => 
              [
                  'driver'    => $dbdata->database_driver,
                  'host'      => $dbdata->database_host,
                  'port'      => $dbdata->database_port,
                  'database'  => $dbdata->database_name,
                  'username'  => $dbdata->database_username,
                  'password'  => $dbdata->database_password,
                  'charset'   => 'utf8mb4',
                  'collation' => 'utf8mb4_unicode_ci',
                  'options' => [
                      \PDO::ATTR_EMULATE_PREPARES => true,
                  ],
              ],
          ]);

          $reportParams['database'] = 'dynamic';
          $reportParams['database_id'] = $request->database;
          $reportParams['query_input'] = $request->query_input;
        }
        elseif ($request->data_source === 'excel') {
          if ($request->file_source === 'upload') {
            // $reportParams['file_path'] = $request->excel->getPathName();
            Storage::putFileAs('', $request->excel, 'temp.xlsx');
            $reportParams['file_path'] = 'temp.xlsx';
          }
          elseif ($request->file_source === 'onedrive') {
            if ($request->session()->has('access_token')) {
              $result = Helper::getOnedriveUrlFile($request->onedrive_link, $request->session()->get('access_token'));
              if (isset($result['error']) && $result['error']['code'] === 'InvalidAuthenticationToken') {
                $request->session()->put('return_url', str_replace('preview', '', url()->full()));
                return redirect()->away(config('global.onedrive.sign_in_url') . '?client_id=' . config('global.onedrive.client_id') . '&scope=files.read&response_type=token&redirect_uri=' . config('global.onedrive.redirect_uri'));
              }
              $excel = file_get_contents($result['@microsoft.graph.downloadUrl']);
              Storage::disk('local')->put('temp.xlsx', $excel);
              $reportParams['file_path'] = 'temp.xlsx';
            }
            else {
              $request->session()->put('return_url', str_replace('preview', '', url()->full()));
              return redirect()->away(config('global.onedrive.sign_in_url') . '?client_id=' . config('global.onedrive.client_id') . '&scope=files.read&response_type=token&redirect_uri=' . config('global.onedrive.redirect_uri'));
            }
          }
        }
        $request->session()->put('report_params', $reportParams);
      }
    }
    $report = new MyReport($reportParams);
    $report->run();
    // dd($report);
    return view("report", ["report" => $report]);
  }

  public function store(Request $request)
  {
    //update insert user_id
    $user_id = $request->user()->id;

    $validatedData = $request->validate([
      'title' => 'required|string|unique:reports|max:150',
      'database' => 'required|string|max:25',
      'query_type' => 'required|string|max:7',
      'visualization' => 'required|string|max:25'
    ]);

    $report = new Report;
    $report->title = $request->title;
    $report->data_source = $request->data_source;
    $report->visualization = $request->visualization;
    $report->detail = json_encode($request->all());
    $report->user_id = $user_id;
    $report->save();
    if ($request->data_source === 'database') {
      $reportQuery = new ReportQuery;
      $reportQuery->report_id = $report->id;
      $reportQuery->database = $request->database;
      $reportQuery->query_type = $request->query_type;
      $reportQuery->query_input = $request->query_input;
      $reportQuery->save();
    }
    elseif ($request->data_source === 'excel') {
      if ($request->file_source === 'upload') {
        $filePath = $request->excel->store('reports');
      }
      elseif ($request->file_source === 'onedrive') {
        $filePath = $request->onedrive_link;
      }
      $reportExcel = new reportExcel;
      $reportExcel->report_id = $report->id;
      $reportExcel->file_source = $request->file_source;
      $reportExcel->file_path = $filePath;
      $reportExcel->save();
    }

    return redirect()->route('reports.index', ['action' => 'store']);
  }

  public function edit($id) 
  {
    $report = Report::find($id);
    $data = json_decode($report->detail, true);
    $data['id'] = $report->id;
    $databases = Database::select('id', 'name', 'database_name')->get();
    return view('report/builder', ['databases' => $databases, 'data' => $data, 'editMode' => true]);
  }

  public function update(Request $request, $id) 
  {
    $validatedData = $request->validate([
      'database' => 'required|string|max:25',
      'query_type' => 'required|string|max:7',
      'visualization' => 'required|string|max:25'
    ]);

    $report = Report::find($id);
    $report->title = $request->title;
    $oldDataSource = $report->data_source;
    $report->data_source = $request->data_source;
    $report->visualization = $request->visualization;
    $report->detail = json_encode($request->all());
    $report->save();

    if ($request->data_source === 'database') {
      if ($oldDataSource === 'database') {
        $reportQuery = $report->reportQuery;
      }
      elseif ($oldDataSource === 'excel') {
        $reportQuery = new ReportQuery;
        $reportExcel = $report->reportExcel;
        if ($reportExcel->file_source === 'upload') {
          Storage::disk('local')->delete($reportExcel->file_path);
        }
        $reportExcel->delete();
      }
      $reportQuery->report_id = $report->id;
      $reportQuery->database = $request->database;
      $reportQuery->query_type = $request->query_type;
      $reportQuery->query_input = $request->query_input;
      $reportQuery->save();
    }
    elseif ($request->data_source === 'excel') {
      if ($oldDataSource === 'excel') {
        $reportExcel = $report->reportExcel;
        if ($reportExcel->file_source === 'upload') {
          Storage::disk('local')->delete($reportExcel->file_path);
        }
      }
      elseif ($oldDataSource === 'database') {
        $reportExcel = new ReportExcel();
        $report->reportQuery->delete();
      }
      if ($request->file_source === 'upload') {
        $filePath = $request->excel->store('reports');
      }
      elseif ($request->file_source === 'onedrive') {
        $filePath = $request->onedrive_link;
      }
      $reportExcel->report_id = $report->id;
      $reportExcel->file_path = $filePath;
      $reportExcel->save();
    }

    return redirect()->route('reports.index', ['action' => 'update']);
  }

  public function destroy($id) 
  {
    $report = Report::find($id);
    if ($report->data_source === 'database') {
      $report->reportQuery->delete();
    }
    elseif ($report->data_source === 'excel') {
      if ($report->file_source === 'upload') {
        Storage::disk('local')->delete($report->reportExcel->file_path);
      }
      $report->reportExcel->delete();            
    }
    $report->delete();
    return redirect()->route('reports.index', ['action' => 'destroy']);
  }

  public function getTablesFields(Request $request) 
  {
    $database = Database::find($request->database);
    if (!$database) {
      return [
        'status' => 'fail',
        'errors' => ['Database not found']
      ];
    }

    config([
        'database.connections.dynamic' => 
        [
            'driver'    => $database->database_driver,
            'host'      => $database->database_host,
            'port'      => $database->database_port,
            'database'  => $database->database_name,
            'username'  => $database->database_username,
            'password'  => $database->database_password,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
    ]);

    $dbConfig = config('database.connections.dynamic');
    //$dbConfig = config("database.connections.vision_report");
    if ($dbConfig['driver'] === 'mysql') {
      
      //$query = "select * from information_schema.columns where table_schema = 'vision_report' order by table_name, ordinal_position;";
      $query = "SELECT TABLE_SCHEMA, TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA = '".$database->database_name."' ";
    }
    else if($dbConfig['driver'] === 'sqlsrv') {
      $query = "SELECT TABLE_NAME, COLUMN_NAME FROM '$request->database'.INFORMATION_SCHEMA.COLUMNS;";
    }

    $result = DB::connection('dynamic')->select($query);
    $tables = array_values(array_unique(array_map(function ($row) { 
      return $row->TABLE_NAME;
    }, $result)));
    
    $tablesFields = [];
    
    foreach ($tables as $table) {
      
      $query_column = "SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.columns where TABLE_SCHEMA = '".$database->database_name."' AND TABLE_NAME = '".$table."' order by table_name, ordinal_position;";

      //echo $query_column;

      
      $result_column = DB::connection('dynamic')->select($query_column);

      $fields = array_filter($result_column, function ($row) use ($table) {
        return $row->TABLE_NAME === $table;
      });
      
      
      $fields = array_values(array_map(function ($row) {
        return $row->COLUMN_NAME;
      }, $fields));

      //var_dump($fields);
      
      $tablesFields[$table] = $fields;
      
    }
    
    return $tablesFields;

  }

  public function getQueryFields(Request $request)
  {
    $dbdata = Database::find($request->database);
    if (!$dbdata) {
      return [
        'status' => 'fail',
        'errors' => ['Database not found']
      ];
    }

    config([
        'database.connections.dynamic' => 
        [
            'driver'    => $dbdata->database_driver,
            'host'      => $dbdata->database_host,
            'port'      => $dbdata->database_port,
            'database'  => $dbdata->database_name,
            'username'  => $dbdata->database_username,
            'password'  => $dbdata->database_password,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                \PDO::ATTR_EMULATE_PREPARES => true,
            ],
        ],
    ]);

    $dbConfig = config('database.connections.dynamic');
    if ($request->query_type === 'builder') {
      $tables = $request->tables;
      $joinFields = $request->join_fields;
      $database = $dbdata->database_name;
      $joinTypes = $request->join_types;
      $selectedFields = $request->selected_fields;
      $aliasFields = $request->alias_fields;
      $aliasValues = $request->alias_values;
      $condFields = $request->cond_fields;
      $condOperators = $request->cond_operators;
      $condValues = $request->cond_values;
      $condTypes = $request->cond_types;

      $builder = DB::connection('dynamic')->table($tables[0]);
      foreach ($selectedFields as $selectedField) {
        if ($request->has('alias_fields') && false !== $aliasIndex = array_search($selectedField, $aliasFields)) {
          $selectedField .= ' as ' . $aliasValues[$aliasIndex];
        }
        if (strpos($selectedField, 'count(') !== false || strpos($selectedField, 'max(') !== false || strpos($selectedField, 'min(') !== false || strpos($selectedField, 'avg(') !== false || strpos($selectedField, 'sum(') !== false) {
          $selectedField = Helper::generateSafeColumnName($selectedField);
          $builder->addSelect(DB::raw($selectedField));
        }
        else {
          $builder->addSelect($selectedField);
        }
      }
      if (sizeof($tables) > 1) {
        $joinedTables = [$tables[0]];
        for ($i = 0; $i < sizeof($tables); $i += 2) { 
          $joinedTable = in_array($tables[$i], $joinedTables) ? $tables[$i + 1] : $tables[$i];
          $joinedTables[] = $joinedTable;
          if ($joinTypes[$i / 2] === 'inner join') {
            $builder->join($joinedTable, $joinFields[$i], '=', $joinFields[$i + 1]);
          }
          elseif ($joinTypes[$i / 2] === 'left join') {
            $builder->leftJoin($joinedTable, $joinFields[$i], '=', $joinFields[$i + 1]);
          }
          elseif ($joinTypes[$i / 2] === 'right join') {
            $builder->rightJoin($joinedTable, $joinFields[$i], '=', $joinFields[$i + 1]);
          }
          elseif ($joinTypes[$i / 2] === 'full join') {
            $builder->crossJoin($joinedTable, $joinFields[$i], '=', $joinFields[$i + 1]);
          }
        }
      }
      if (is_array($condFields)) {
        $builder->where($condFields[0], $condOperators[0], $condValues[0]);
        for ($i = 1; $i < sizeof($condFields); $i++) { 
          if ($condTypes[$i - 1] === 'and') {
            $builder->where($condFields[$i], $condOperators[$i], $condValues[$i]);
          }
          else {
            $builder->orWhere($condFields[$i], $condOperators[$i], $condValues[$i]);
          }
        }
      }

      //for filtering data by user
      
      
      if ($dbdata->extra_query) {
        $extra_query = json_decode($dbdata->extra_query, true);
        if (
          ($extra_query['status'] ?? false) && 
          ($extra_query['identifier'] ?? false) && 
          ($extra_query['query'] ?? false)
        ) {
          $useDb2 = false;
          if ($extra_query['connection'] ?? false) {
            $dbdata2 = Database::find($extra_query['connection']);
            if ($dbdata2) {
              config([
                  'database.connections.dynamic2' => 
                  [
                      'driver'    => $dbdata2->database_driver,
                      'host'      => $dbdata2->database_host,
                      'port'      => $dbdata2->database_port,
                      'database'  => $dbdata2->database_name,
                      'username'  => $dbdata2->database_username,
                      'password'  => $dbdata2->database_password,
                      'charset'   => 'utf8mb4',
                      'collation' => 'utf8mb4_unicode_ci',
                      'options' => [
                          \PDO::ATTR_EMULATE_PREPARES => true,
                      ],
                  ],
              ]);
              $useDb2 = true;
            }
          }
          $extra_query['query'] = str_replace('%email%', $request->user()->email, $extra_query['query']);
          $result = DB::connection($useDb2 ? 'dynamic2' : 'dynamic')->select($extra_query['query']);
          $result = array_map(function ($item) {
            return (array) $item;
          }, $result);
          $builder->whereIn($extra_query['identifier'], array_merge(...$result));
        }
      }
      
      //


      if ($request->has('group_fields')) {
        foreach ($request->group_fields as $field) {
          $builder->groupBy($field);
        }
      }
      if ($request->has('having_fields')) {
        $havingFields = array_map(function ($item) {
          return Helper::generateSafeColumnName($item);
        }, $request->having_fields);
        $havingOperators = $request->having_operators;
        $havingValues = $request->having_values;
        $havingTypes = $request->having_types;
        $builder->havingRaw("$havingFields[0] $havingOperators[0] ?", [$havingValues[0]]);
        for ($i = 1; $i < sizeof($havingFields); $i++) { 
          if ($havingTypes[$i - 1] === 'and') {
            $builder->havingRaw("$havingFields[$i] $havingOperators[$i] ?", [$havingValues[$i]]);
          }
          else {
            $builder->orHavingRaw("$havingFields[$i] $havingOperators[$i] ?", [$havingValues[$i]]);
          }
        }
      }
      if ($request->has('order_fields')) {
        $orderFields = $request->order_fields;
        $orderTypes = $request->order_types;
        for ($i = 0; $i < sizeof($orderFields); $i++) { 
          $builder->orderBy($orderFields[$i], $orderTypes[$i]);
        }
      }
      if ($request->has('limit_start')) {
        $builder->offset($request->limit_start - 1);
        $builder->limit($request->limit_total);
      }
      if ($request->distinct === 'true') {
        $builder->distinct();
      }

      $query = vsprintf(str_replace(array('?'), array('\'%s\''), $builder->toSql()), $builder->getBindings());
      $result = $builder->get()->first();
      $result = (array) $result;
      foreach ($result as $index => $field) {
        if (is_numeric($field)) {
          $result[$index] = (int) $field;
        }
      }
      return ['result' => $result, 'query' => $query];
    }
    elseif ($request->query_type === 'text') {

      $query_input = $request->query_input;
      $result = array();
      if (preg_match("/^Update/i", $query_input)) {
        //$result[] = "";
        //echo "found update";
        //return $result;
      }
      else if (preg_match("/^Delete/i", $query_input)) {
        //$result[] = "";
        //return $result;
        //echo "found delete";
      } 
      else {
        $result = DB::connection('dynamic')->select($request->query_input);
        return (array) $result[0];
        //echo "is okay";
      }

      return (array) $result[0];
      
    }
  }

  public function getExcelFields(Request $request) 
  {
    if ($request->file_source === 'upload') {
      $result = $this->findExcelFields($request->excel->getPathName());
      return $result;
    }
    elseif ($request->file_source === 'onedrive') {
      if ($request->session()->has('access_token')) {
        $result = Helper::getOnedriveUrlFile($request->onedrive_link, $request->session()->get('access_token'));
        if (isset($result['error']) && $result['error']['code'] === 'InvalidAuthenticationToken') {
          $request->session()->put('return_url', $request->return_url);
          return [
            'response_code' => 2, 
            'message' => 'User needs to sign in first.', 
            'sign_in_url' => config('global.onedrive.sign_in_url') . '?client_id=' . config('global.onedrive.client_id') . '&scope=files.read&response_type=token&redirect_uri=' . config('global.onedrive.redirect_uri')
          ];
        }

        $excel = file_get_contents($result['@microsoft.graph.downloadUrl']);
        Storage::disk('local')->put('temp.xlsx', $excel);
        $result = $this->findExcelFields(Storage::disk('local')->path('temp.xlsx'));
        Storage::disk('local')->delete('temp.xlsx');

        if ($result['status_code'] === 1) {
          return 
          [
            'response_code' => 1,
            'result' => $result['result']
          ];
        }
        elseif ($result['status_code'] === 0) {
          return
          [
            'response_code' => 0,
            'message' => $result['message']
          ];
        }

      }
      else {
        $request->session()->put('return_url', $request->return_url);
        return [
          'response_code' => 2, 
          'message' => 'User needs to sign in first.', 
          'sign_in_url' => config('global.onedrive.sign_in_url') . '?client_id=' . config('global.onedrive.client_id') . '&scope=files.read&response_type=token&redirect_uri=' . config('global.onedrive.redirect_uri')
        ];
      }
    }
  }

  private function findExcelFields($filePath) {
    $inputFileType = IOFactory::identify($filePath);
    $excelReader = IOFactory::createReader($inputFileType);
    $excelObj = $excelReader->load($filePath);
    $sheet = $excelObj->getSheet(0);
    if ($sheet->getCellByColumnAndRow(1, 1)->getValue() === NULL) {
      return [
        'status_code' => 0, 
        'message' => 'Wrong table position. Make sure that the table starts from upper left. (Row 1, Column A)'
      ];
    }
    else {
      $highestRowIndex = $sheet->getHighestDataRow(); // e.g. 10
      $highestColumn = $sheet->getHighestDataColumn(); // e.g 'F'
      $highestColumnIndex = Coordinate::columnIndexFromString(
        $highestColumn); // e.g. 5

      // for ($i = 1; $i <= $highestColumnIndex; $i++) { 
      //     $cell = $sheet->getCellByColumnAndRow($i, $highestRowIndex);
      //     $value = $cell->getValue();
      //     if ($value !== NULL) {
      //         $lowestColumnIndex = $i;
      //         break;
      //     }
      // }
      // for ($i = 1; $i <= $highestRowIndex; $i++) { 
      //     $cell = $sheet->getCellByColumnAndRow($lowestColumnIndex, $i);
      //     $value = $cell->getValue();
      //     if ($value !== NULL) {
      //         $lowestRowIndex = $i;
      //         break;
      //     }
      // }

      $columnNames = [];
      $columnValues = [];

      for ($i = 1; $i <= $highestColumnIndex; $i++) { 
        $cell = $sheet->getCellByColumnAndRow($i, 1);
        $columnNames[] = $cell->getValue();
        for ($j = 2; $j < $highestRowIndex; $j++) { 
          $cell = $sheet->getCellByColumnAndRow($i, $j);
          $value = $cell->getValue();
          if ($value !== NULL) {
            $columnValues[] = $value;
            break;
          }
        }
        if (sizeof($columnNames) !== sizeof($columnValues)) {
          $columnValues[] = 'string';
        }
      }

      return [
        'status_code' => 1, 
        'result' => array_combine($columnNames, $columnValues),
        'highest_column_index' => $highestColumnIndex
      ];
    }
  }
}
