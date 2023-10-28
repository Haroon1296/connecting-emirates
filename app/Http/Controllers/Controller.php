<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $socket_url, $options, $stripe_public_key, $stripe_secret_key;

    public function __construct()
    {
        $this->socket_url = $_SERVER['HTTP_HOST'] == 'localhost' ? 'http://localhost:3009' : '';
        
        $this->stripe_public_key  = '';
        $this->stripe_secret_key  = '';

        // $this->options = [
        //     'context' => [
        //         'ssl' => [
        //             'verify_peer'      => false,
        //             'verify_peer_name' => false
        //         ]
        //     ]
        // ];
    }
}
