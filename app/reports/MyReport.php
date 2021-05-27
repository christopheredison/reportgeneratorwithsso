<?php
namespace App\Reports;

use \koolreport\processes\Filter;
use \koolreport\processes\Sort;

class MyReport extends \koolreport\KoolReport
{
    use \koolreport\clients\Bootstrap;
    use \koolreport\laravel\Friendship;
    // By adding above statement, you have claim the friendship between two frameworks
    // As a result, this report will be able to accessed all databases of Laravel
    // There are no need to define the settings() function anymore
    // while you can do so if you have other datasources rather than those
    // defined in Laravel.
    use \koolreport\export\Exportable;
    use \koolreport\excel\ExcelExportable;
    use \koolreport\cache\FileCache;

    function __construct($params) {
        parent::__construct($params);
    }

    function settings() {
        $dataSources = [];
        if ($this->params['data_source'] === 'excel') {
            $ext = pathinfo($this->params['file_path'], PATHINFO_EXTENSION);
            $dataSources['dataSources'] = 
            [
                'excel' => [
                    "class" => "\koolreport\\excel\ExcelDataSource",
                    "filePath" => config('filesystems.disks.local.root') . '/' . $this->params['file_path'],
                    "firstRowData" => false,//Set true if first row is data and not the header
                ]
            ];
        }
        return $dataSources;
    }

    function setup()
    {
        // Let say, you have "sale_database" is defined in Laravel's database settings.
        // Now you can use that database without any further settings.
        if ($this->params['data_source'] === 'database') {
            $this->src($this->params['database'])
            ->query($this->params['query_input'])
            ->pipeIf(!empty($this->params['filters']), function($node) {
                $filters = $this->params['filters'];
                $convertedFilters = [];
                for ($i = 0; $i < sizeof($filters['filter_field']); $i++) { 
                    $convertedFilters[] = [$filters['filter_field'][$i], $filters['filter_operator'][$i], $filters['filter_value'][$i]];
                }
                return $node->pipe(new Filter($convertedFilters));
            })
            ->pipe($this->dataStore('default'));   
        }
        elseif ($this->params['data_source'] === 'excel') {
            $sort = [];
            foreach ($this->params['columns'] as $field) {
                $sort[$field] = 'asc';
            }

            $this->src('excel')
            ->pipe(new Sort($sort))
            ->pipeIf(!empty($this->params['filters']), function($node) {
                $filters = $this->params['filters'];
                $convertedFilters = [];
                for ($i = 0; $i < sizeof($filters['filter_field']); $i++) { 
                    $convertedFilters[] = [$filters['filter_field'][$i], $filters['filter_operator'][$i], $filters['filter_value'][$i]];
                }
                return $node->pipe(new Filter($convertedFilters));
            })
            ->pipe($this->dataStore('default'));
        }
             
    }

    function cacheSettings()
    {
        return array(
            "ttl"=>60,
        );
    }
}