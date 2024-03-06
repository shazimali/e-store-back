<?php
 
 namespace App\Services\Users;

 use App\Services\Users\UserInterface;
 use App\Models\User;
 use App\Models\Role;
 use App\Http\Resources\Users\UsersListResource;
 use App\Http\Resources\Users\UserEditResource;
 use App\Http\Resources\Roles\RolesResource;

class UsersClass implements UsersInterface{
   
    public function getAll($request){
        // return UsersListResource::collection(User::with('roles')->paginate(10));
        return UsersListResource::collection(User::with('roles')->get());
    }

    public function create()
    {
        return RolesResource::collection(Role::all());
    }

    public function store($request)
    {
        $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'email_verified_at' => now()
            ]);
        $roles = $request->input('roles') ? $request->input('roles') : [];
        $user->roles()->sync($roles);

        return response()->json('user created successfully.', 200);
    }

    public function edit($id)
    {
        return new  UserEditResource(User::with('roles')->where('id',$id)->first());
    }

    public function update($id, $request)
    {
        $user = User::whereId($id)->first();
        $user->name = $request->name;
        $user->password = bcrypt($request->password);
        $user->save();
        $roles = $request->input('roles') ? $request->input('roles') : [];
        $user->roles()->sync($roles);

        return response()->json('user updated successfully.', 200);
    }

    public function destroy($id)
    {
        $user = User::with('roles')->whereId($id)->first();
        $user->roles()->detach($user->roles->pluck('id'));
        $user->delete();

        return response()->json('user deleted successfully.', 200);
    }
 }
?>