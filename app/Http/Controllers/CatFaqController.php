<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatFaqRequest;
use App\Repository\RepositoryInterface;

class CatFaqController extends Controller
{
    //
    private $repository;
    public function __construct(RepositoryInterface $repository)
    {
        $this->repository=$repository;
    }

    public function get_by_id($id)
    {
        return $this->repository->get_by_id($id);
}
    public function get_all()
    {
         return $this->repository->get_all();
    }
  public function get_list()
    {
         return $this->repository->get_list();
    }

    public function create(CatFaqRequest $request)
    {
        return $this->repository->create($request->only("title","status"));
    }
    public function update(CatFaqRequest $request)
    {
        return $this->repository->update($request->only("title","status"),$request->id);
    }
}

