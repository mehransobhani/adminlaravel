<?php

namespace App\Http\Controllers;

use App\Classes\DeleteAllExtensionFile;
use App\Classes\GetAllSrc\GetAllSrcStrategy;
use App\Http\Requests\FaqRequest;
use App\Repository\RepositoryInterface;


class FaqController
{
    private $repository;
    private $allExtensionFile;

    public function __construct(RepositoryInterface $repository , DeleteAllExtensionFile $allExtensionFile)
    {
        $this->repository = $repository;
        $this->allExtensionFile = $allExtensionFile;
     }

    public function get_by_id($id)
    {

        return $this->repository->get_by_id($id);

    }

    public function get_all()
    {
        return $this->repository->get_all();

    }

    public function create(FaqRequest $request)
    {
       $data = $this->repository->create($request->only('question', 'answer', 'top', 'status', 'question_cats_id', 'short_answer'));
       $all=$this->repository->get_list();
        $get_src = new GetAllSrcStrategy("faq_answer");
        $arr[] = $get_src->gets($all);
        $this->allExtensionFile->delete($arr[0],"/public/Ckeditor/Faq/*");
         return $data;
    }

    public function update(FaqRequest $request)
    {
        $data = $this->repository->update($request->only('question', 'answer', 'top', 'status', 'question_cats_id', 'short_answer'), $request->id);
        $all=$this->repository->get_list();
        $get_src = new GetAllSrcStrategy("faq_answer");
        $arr[] = $get_src->gets($all);
        $this->allExtensionFile->delete($arr[0],"/public/Ckeditor/Faq/*");
        return $data;

    }
}

