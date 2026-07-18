<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Services\Roles\RolesInterface;

class RolesController extends Controller
{

    public $rolesService;
    
    public function __construct(RolesInterface $rolesService)
    {
        $this->rolesService = $rolesService;
    }

    /**
     * @OA\Get(
     *     path="/roles",
     *     summary="Index",
     *     tags={"Roles"},
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
    public function index(Request $request)
    {
        $this->authorize('role_access');

        return  $this->rolesService->getAll($request);
    }

    /**
     * @OA\Get(
     *     path="/roles/create",
     *     summary="Create",
     *     tags={"Roles"},
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
    public function create()
    {
        $this->authorize('role_create');

        return  $this->rolesService->create();
    }
    /**
     * @OA\Post(
     *     path="/roles/store",
     *     summary="Store",
     *     tags={"Roles"},
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
    public function store(StoreRoleRequest $request)
    {
        $this->authorize('role_create');

        return $this->rolesService->store($request);
    }
    /**
     * @OA\Put(
     *     path="/roles/update/{id}",
     *     summary="Update",
     *     tags={"Roles"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
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
    public function update(UpdateRoleRequest $request, $id)
    {
        $this->authorize('role_edit');
           
        return $this->rolesService->update($request,$id);
    }
    /**
     * @OA\Get(
     *     path="/roles/edit/{id}",
     *     summary="Edit",
     *     tags={"Roles"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
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
    public function edit($id)
    {
        $this->authorize('role_edit');

        return $this->rolesService->edit($id);
    }
    
    /**
     * @OA\Delete(
     *     path="/roles/delete/{id}",
     *     summary="Destroy",
     *     tags={"Roles"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
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
    public function destroy($id)
    {
        $this->authorize('role_delete');

        return $this->rolesService->destroy($id);
    }

}
