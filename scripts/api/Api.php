<?php

class Api
{
    public static function defaultHeaders() {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
    }

    public static function getHeaders()
    {
        self::defaultHeaders();
        header('Access-Control-Allow-Methods: GET');
    }

    public static function postHeaders()
    {
        self::defaultHeaders();
        header('Access-Control-Allow-Methods: POST');
    }

    public static function deleteHeaders() {
        self::defaultHeaders();
        header('Access-Control-Allow-Methods: DELETE');
    }

    public static function putHeaders() {
        self::defaultHeaders();
        header('Access-Control-Allow-Methods: PUT');
    }

    public static function returnJson($data, $status = 200)
    {
        http_response_code($status);
        echo json_encode($data);
    }

    public static function retunFailure($message) {
        self::returnJson(['error' => $message], 400);
    }

    public static function workDir() {
        return $WORKING_DIR = dirname(__DIR__);
    }
}