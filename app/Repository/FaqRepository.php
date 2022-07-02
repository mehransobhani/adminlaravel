<?php

namespace App\Repository;

use App\Models\Question;

class FaqRepository extends  Repository
{
    public function model()
    {
        return Question::class;
}
}

