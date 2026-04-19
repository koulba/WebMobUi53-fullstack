<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiFooController extends Controller
{
    public function show()
    {
        return response()->json(['foo' => 'bar1']);
    }

    public function store(Request $request)
    {
        $id = $request->input('id');

        return response()->json(['foo2' => 'bar2']);
    }
}
