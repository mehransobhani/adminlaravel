<?php

namespace App\Repository;

use App\Models\QuestionCat;

class CatFaqRepository extends Repository
{
    public function model()
    {
        return QuestionCat::class;
}
}

