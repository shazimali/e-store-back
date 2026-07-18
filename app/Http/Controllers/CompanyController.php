<?php

namespace App\Http\Controllers;

use App\Models\CompanyDetail;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * @OA\Get(
     *     path="/company-detail",
     *     summary="Get",
     *     tags={"Company"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    function get() {
        return response()->json(CompanyDetail::first());
    }
    /**
     * @OA\Post(
     *     path="/company-detail/update",
     *     summary="Update",
     *     tags={"Company"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="placeholder", type="string", example="value")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    function update(Request $request) {

        CompanyDetail::where('id',1)->update($request->all());
        
        return response()->json('Company details updated successfully');
    }
}
