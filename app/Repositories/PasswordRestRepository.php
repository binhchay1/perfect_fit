<?php

namespace App\Repositories;

use App\Models\PasswordReset;

class PasswordRestRepository extends BaseRepository
{
    public function model()
    {
        return PasswordReset::class;
    }
    
    public function index()
    {
        return $this->model->orderBy('created_at', 'desc')->get();
    }
    
    public function createPassReset($dataReset)
    {
        return $this->model->create($dataReset);
    }
    
    public function checkToken($token)
    {
        return $this->model->where('token', $token)->first();
    }
    
    public function update($dataUser, $id)
    {
        return $this->model->where('id', $id)->update($dataUser);
    }
    
    public function getInfo($dataEmail)
    {
        return $this->model->where('email', $dataEmail)->first();
    }
    
    public function destroy($token)
    {
        return $this->model->where('token', $token)->delete();
    }
    
}
