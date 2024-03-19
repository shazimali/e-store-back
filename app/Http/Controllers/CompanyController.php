<?php

namespace App\Http\Controllers;

use App\Models\CompanyDetail;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    function get() {
        return response()->json(CompanyDetail::first());
    }
    function update(Request $request) {

        CompanyDetail::where('id',1)->update($request->all());
        
        return response()->json('Company details updated successfully');
    }
}
