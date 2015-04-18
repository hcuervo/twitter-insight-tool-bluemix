<?php

namespace App\Http\Controllers;

use Request;
use Session;
use Storage;
use Httpful\Request as RequestClient;

class ApiController extends Controller
{
    private function array_flat($array, $prefix = '') {
        $result = array();

        foreach ($array as $key => $value)
        {
            if (!is_numeric($key)) {
                $new_key = $prefix . (empty($prefix) ? '' : '_') . $key;

                if (is_array($value))
                {
                    $result = array_merge($result, $this->array_flat($value, $new_key));
                }
                else
                {
                    $result[$new_key] = $value;
                }
            }
        }

        return $result;
    }

    public function download() {
        return app()->basePath('public');
    }

    public function finalizeResult() {
        if (!Session::has('api_settings')) {
            return array('status' => 0);
        }

        $api_settings = Session::get('api_settings');

        if ($api_settings['finalized']) {
            return array('status' => 0);
        }

        $file_path = storage_path('app') . '/' . $api_settings['file_name'];

        $fh = fopen($file_path, 'a+') or abort(404, 'File not found !');
        $stat = fstat($fh);
        ftruncate($fh, $stat['size'] - 1);
        fseek($fh, 0, SEEK_END);
        fwrite($fh, ']');
        fclose($fh);

        $download_path = app()->basePath('public') . '/download/' . $api_settings['file_name'];
        @rename($file_path, $download_path);

        $api_settings['finalized'] = true;
        Session::put('api_settings', $api_settings);

        return array('status' => 1, 'file_name' => $api_settings['file_name']);
    }

    public function init() {
        $file_name = time() . '.json';

        $api_settings = array(
            'host' => Request::input('host'),
            'username' => Request::input('username'),
            'password' => Request::input('password'),
            'query_string' => Request::input('q'),
            'file_name' => $file_name,
            'total' => 0,
            'current' => 0,
            'finalized' => false
        );

        Session::put('api_settings', $api_settings);

        try {
            $file_path = Storage::disk('local')->put($file_name, '[');
        } catch (\Exception $e) {
            return array('status' => 0);
        }

        return array('status' => 1);
    }

    public function search() {
        if (!Session::has('api_settings')) {
            return array('status' => 0);
        }

        $api_settings = Session::get('api_settings');
        $first_time = ($api_settings['current'] == 0);
        $url = sprintf("https://%s:%s@%s/api/v1/messages/search?q=%s",
                    $api_settings['username'],
                    $api_settings['password'],
                    $api_settings['host'],
                    $api_settings['query_string']);

        if (!$first_time) {
            $url .= sprintf("&from=%s&size=100", $api_settings['current']);
        }

        $http_template = RequestClient::init()->method('GET')->withoutStrictSsl()->withoutAutoParsing();
        RequestClient::ini($http_template);

        $res = RequestClient::get($url)->send();

        if (!$res->hasErrors() && $res->hasBody()) {
            $data = json_decode($res->body, true);

            if ($first_time) {
                $total = intval($data['search']['results']);
                $api_settings['total'] = $total;
            }

            $count = 0;

            foreach ($data['tweets'] as $tweet) {
                $flat = $this->array_flat($tweet);
                Storage::disk('local')->append($api_settings['file_name'], json_encode($flat, JSON_UNESCAPED_UNICODE) . ',');
                $count++;
            }

            $api_settings['current'] += $count;
            Session::put('api_settings', $api_settings);

            return array('status' => 1, 'total' => $api_settings['total'], 'current' => $api_settings['current']);
        }

        return array('status' => 0);
    }
}
