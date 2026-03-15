<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ZplController extends Controller
{
    public function createZpl(Request $request)
    {
        $request->validate([
            "Barcode" => "required|string",
        ]);

        
    }

    public function CreateLogZplResponse(Request $request){
        $request->validate([
        ]);

    }
}
