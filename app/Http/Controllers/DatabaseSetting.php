<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Database;
use App\Http\Requests\DatabaseSetting\Store;
use App\Http\Requests\DatabaseSetting\Update;
use Helper;
use Illuminate\Support\Facades\Crypt;

class DatabaseSetting extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->for_select) {
            return Database::select('id', 'name', 'database_name')->get();
        }
        if ($request->wantsJson()) {
            $databases = (new Database)->newQuery();

            $totalData = null;

            if ($keyword = ($request->search['value'] ?? null)) {
                $searchable = [
                    'name',
                    'database_name',
                    'database_host',
                ];
                $totalData = $databases->count();
                $databases->where(function($query) use ($searchable, $keyword) {
                    foreach ($searchable as $column) {
                        $query->orWhere($column, 'like', "%$keyword%");
                    }
                });
            }

            if (is_array($orders = $request->order)) {
                $columns = [
                    'name',
                    'database_driver',
                    'database_name',
                    'database_host',
                    'database_port',
                ];

                foreach ($orders as $column) {
                    if ($colname = ($columns[$column['column']] ?? false)) {
                        $databases->orderBy($colname, $column['dir']);
                    }
                }
            }
            $databases->orderBy('databases.id', $column['dir'] ?? 'ASC');

            $data = $databases->paginate($request->length ?: 15)->toArray();
            $data['recordsFiltered'] = $data['total'];
            $data['recordsTotal'] = $totalData ?: $data['total'];
            $data['draw'] = $request->draw;

            return $data;
        }

        $data = [
            'title' => 'Database Setting',
        ];
        return view('database_setting.list', $data);
    }

    /**
     * Check connection input
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function testConnection(Request $request)
    {
        $result = Helper::testDatabaseConnection(
            $request->database_driver,
            $request->database_host,
            $request->database_port,
            $request->database_name,
            $request->database_username,
            $request->database_password
        );

        if ($result) {
            return ['status' => 'success'];
        } else {
            return [
                'status' => 'fail',
                'errors' => ['Something went wrong']
            ];
        }
    }

    /**
     * Check connection input
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getAvailableTable(Request $request)
    {
        $result = Helper::getAvailableTable(
            $request->database_driver,
            $request->database_host,
            $request->database_port,
            $request->database_name,
            $request->database_username,
            $request->database_password
        );
        return [
            'status' => 'success',
            'data' => $result
        ];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Store $request)
    {
        $testResult = Helper::testDatabaseConnection(
            $request->database_driver,
            $request->database_host,
            $request->database_port,
            $request->database_name,
            $request->database_username,
            $request->database_password
        );

        if (!$testResult) {
            return [
                'status' => 'fail',
                'errors' => ['Cannot connect to database']
            ];
        }
        $toCreate = $request->all();
        if ($toCreate['database_password'] ?? false) {
            $toCreate['database_password'] = Crypt::encryptString($toCreate['database_password']);
        }
        if ($toCreate['extra_query']['status'] ?? false) {
            $toCreate['extra_query'] = json_encode($toCreate['extra_query']);
        }

        $database = Database::create($toCreate);
        if ($database) {
            return [
                'status' => 'success',
                'data'   => $database
            ];
        } else {
            return [
                'status' => 'fail',
                'errors' => ['Failed insert data']
            ];
        }
    }

    /**
     * Show specific resource
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function show(Database $database)
    {
        $database->load('databaseFilter');
        return [
            'status' => 'success',
            'data' => $database
        ];
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $database
     * @return \Illuminate\Http\Response
     */
    public function update(Update $request, Database $database)
    {
        $testResult = Helper::testDatabaseConnection(
            $request->database_driver,
            $request->database_host,
            $request->database_port,
            $request->database_name,
            $request->database_username,
            $request->database_password
        );

        if (!$testResult) {
            return [
                'status' => 'fail',
                'errors' => ['Cannot connect to database']
            ];
        }

        $toUpdate = $request->all();
        if ($toUpdate['database_password'] ?? false) {
            $toUpdate['database_password'] = Crypt::encryptString($toUpdate['database_password']);
        }

        if ($toUpdate['extra_query']) {
            foreach ($toUpdate['extra_query'] as $extra) {
                if (($extra['identifier'] ?? false) && ($extra['query'] ?? false)) {
                    $database->databaseFilter()->create([
                        'column_identifier' => $extra['identifier'],
                        'connection' => $extra['connection'] ?: null,
                        'query' => $extra['query']
                    ]);
                }
            }
        }

        $update = $database->update($toUpdate);
        if ($update) {
            return [
                'status' => 'success',
                'data' => $database
            ];
        } else {
            return [
                'status' => 'success',
                'errors' => ['Failed update']
            ];
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $database
     * @return \Illuminate\Http\Response
     */
    public function destroy(Database $database)
    {
        $delete = $database->delete();
        if ($delete) {
            return [
                'status' => 'success'
            ];
        } else {
            return [
                'status' => 'success',
                'errors' => ['Failed delete']
            ];
        }
    }
}
