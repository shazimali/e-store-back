<?php
 namespace App\Services\Users;

Interface UsersInterface{
   
    public function getAll($request);
    public function create();
    public function store($request);
    public function edit($id);
    public function update($id,$request);
    public function destroy($id);
 
}
?>