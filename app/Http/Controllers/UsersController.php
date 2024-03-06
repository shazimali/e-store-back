<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Services\Users\UsersInterface;



class UsersController extends Controller
{
    public $usersService;
    
    public function __construct(UsersInterface $usersService)
    {
        $this->usersService = $usersService;
    }

    public function index(Request $request){
        
        $this->authorize('user_management');
        return $this->usersService->getAll($request);

    }

    public function create()
    {
        $this->authorize('user_create');
        return $this->usersService->create();
    }

    public function store(StoreUserRequest $request)
    {
        $this->authorize('user_create');
        return $this->usersService->store($request);
    }

    public function edit($id)
    {
        $this->authorize('user_edit');
        return $this->usersService->edit($id);
    }

    public function update($id, UpdateUserRequest $request)
    {
        $this->authorize('user_edit');
        return $this->usersService->update($id,$request);
    }

    public function destroy($id)
    {
        $this->authorize('user_edit');
        return $this->usersService->destroy($id);
    }
}
