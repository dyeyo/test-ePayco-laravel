<?php
// app/Providers/ResponseServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;

class ResponseServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Response::macro('xml', function ($data, $status = 200, array $headers = [], $xmlRoot = 'response') {
            $xml = new \SimpleXMLElement('<' . $xmlRoot . '/>');

            // Definir la función arrayToXml dentro del macro
            $arrayToXml = function ($data, \SimpleXMLElement &$xml) use (&$arrayToXml) {
                foreach ($data as $key => $value) {
                    if (is_array($value)) {
                        $subnode = $xml->addChild(is_numeric($key) ? 'item' : $key);
                        $arrayToXml($value, $subnode);
                    } else {
                        $xml->addChild(is_numeric($key) ? 'item' : $key, htmlspecialchars($value));
                    }
                }
            };

            // Convertir el array en XML
            $arrayToXml($data, $xml);

            $headers = array_merge($headers, ['Content-Type' => 'application/xml']);

            return response($xml->asXML(), $status, $headers);
        });
    }
}
