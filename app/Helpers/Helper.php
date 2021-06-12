<?php

namespace App\Helpers;

class Helper
{
    public static function getOnedriveUrlFile($fileUrl, $accessToken)
    {
        $base64Url = base64_encode($fileUrl);
        $encodedUrl = str_replace('+', '-', str_replace('/', '_', 'u!' . rtrim($base64Url, '=')));
        $authorization = "Authorization: Bearer $accessToken";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, [$authorization]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, config('global.onedrive.api_url') . '/v' . config('global.onedrive.api_version') . '/shares/' . $encodedUrl . '/driveItem');
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result, true);
        return $result;
    }

    public static function onedriveSignIn($request, $removeUrl = '') {
        if ($request->session()->has('access_token')) {
            $result = Helper::getOnedriveUrlFile($request->onedrive_link, $request->session()->get('access_token'));
            if (isset($result['error']) && $result['error']['code'] === 'InvalidAuthenticationToken') {
                $request->session()->put('return_url', str_replace($removeUrl, '', url()->full()));
                return redirect()->away(config('global.onedrive.sign_in_url') . '?client_id=' . config('global.onedrive.client_id') . '&scope=files.read&response_type=token&redirect_uri=' . config('global.onedrive.redirect_uri'));
            }
            $excel = file_get_contents($result['@microsoft.graph.downloadUrl']);
            Storage::disk('local')->put('temp.xlsx', $excel);
        }
        else {
            $request->session()->put('return_url', str_replace('preview', '', url()->full()));
            return redirect()->away(config('global.onedrive.sign_in_url') . '?client_id=' . config('global.onedrive.client_id') . '&scope=files.read&response_type=token&redirect_uri=' . config('global.onedrive.redirect_uri'));
        }
    }

    /**
     * Test database connection
     * @param  string $driver   Database driver (mysql/sqlsrv)
     * @param  string $host     Database Server host
     * @param  string $port     Database Server port
     * @param  string $database Database name
     * @param  string $username Database Account Username
     * @param  string $password Database Account Password
     * @return bool             true if valid, otherwise false
     */
    public static function testDatabaseConnection($driver, $host, $port, $database, $username, $password) : bool
    {
        return true;
    }

    /**
     * Get list table from specific connection
     * @param  string $driver   Database driver (mysql/sqlsrv)
     * @param  string $host     Database Server host
     * @param  string $port     Database Server port
     * @param  string $database Database name
     * @param  string $username Database Account Username
     * @param  string $password Database Account Password
     * @return array            array of table name
     */
    public static function getAvailableTable($driver, $host, $port, $database, $username, $password) : array
    {
        return ['apps', 'users', 'transactions'];
    }

    public static function generateSafeColumnName($selectedField)
    {
        $exploadedSelectedField = explode(' as ', $selectedField);
        if (strpos($exploadedSelectedField[0], '.')) {
            $exploadedSelectedField[0] = implode(
              '.', 
              array_map(
                function($item) {
                  if (strpos('`', $item) === FALSE) {
                    if (strpos($item, '(')) {
                      $exploaded = explode('(', $item);
                      $exploaded[1] = '`' . $exploaded[1] . '`';
                      $item = implode('(', $exploaded);
                    } elseif (strpos($item, ')')) {
                      $exploaded = explode(')', $item);
                      $exploaded[0] = '`' . $exploaded[0] . '`';
                      $item = implode(')', $exploaded);
                    } else {
                        $item = '`' . $item . '`';
                    }
                  }
                  return $item;
                }, 
                explode(
                  '.', 
                  $exploadedSelectedField[0]
                )
              )
            );
            if (count($exploadedSelectedField) > 1) {
                $exploadedSelectedField[1] = '`' . $exploadedSelectedField[1] . '`';
                $selectedField = implode(' as ', $exploadedSelectedField);
            } else {
                $selectedField = $exploadedSelectedField[0];
            }
        }
        return $selectedField;
    }
}