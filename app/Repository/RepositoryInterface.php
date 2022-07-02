<?php

namespace App\Repository;

interface RepositoryInterface
{
    public function model();
    public function get_by_id($id);
    public function get_list();
    public function create(array $data);
    public function update(array $data,$id);
    public function get_all();
}

