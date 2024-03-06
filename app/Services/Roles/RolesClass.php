<?php
 
 namespace App\Services\Roles;
    
    use App\Models\User;
    use App\Models\Role;
    use App\Models\Permission;
    use App\Http\Resources\Roles\RolesListResource;
    use App\Http\Resources\Permissions\PermissionsResource;
use App\Http\Resources\Roles\RolesEditResource;
use DB;

class RolesClass implements RolesInterface{

  public function getAll($request)
    {
        return  RolesListResource::collection(Role::with('permissions')->get());
    }

    public function create()
    {
        return PermissionsResource::collection(Permission::all());
    }
    public function store($request)
    {
        $role = Role::create($request->except('permissions'));
        $role->permissions()->sync($request->permissions);
        return  response()->json('Role created successfully.');
    }
    public function edit($id)
    {   
            return new RolesEditResource(Role::with('permissions')->whereId($id)->first());
    }
    public function update($request, $id)
    {
            $role = Role::find($id);
            $data = ['name' => $request->name];
            $role->update($data);
            $role->permissions()->sync($request->permissions);
            return  response()->json('Role updated successfully.');
    }
    public function destroy($id)
    {
        $deleted_role = [];
        $role = Role::with('users')->whereId($id)->first();
        $deleted_role = $role;
        $is_role_attached_with_user = DB::table('role_user')->where('role_id',$id)->first();
        if($is_role_attached_with_user)
            return  response()->json(['status' => 201, 'message' => 'Role attached with users, can not delete.']);        
        
        $role->delete();
        return  response()->json(['status' => 200, 'message' => 'Role deleted successfully.']);

    }

}
?>