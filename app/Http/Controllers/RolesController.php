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

    public function index(Request $request)
    {
        $this->authorize('role_access');

        return  $this->rolesService->getAll($request);
    }

    public function create()
    {
        $this->authorize('role_create');

        return  $this->rolesService->create();
    }
    public function store(StoreRoleRequest $request)
    {
        $this->authorize('role_create');

        return $this->rolesService->store($request);
    }
    public function update(UpdateRoleRequest $request, $id)
    {
        $this->authorize('role_edit');
           
        return $this->rolesService->update($request,$id);
    }
    public function edit($id)
    {
        $this->authorize('role_edit');

        return $this->rolesService->edit($id);
    }
    
    public function destroy($id)
    {
        $this->authorize('role_delete');

        return $this->rolesService->destroy($id);
    }

}
