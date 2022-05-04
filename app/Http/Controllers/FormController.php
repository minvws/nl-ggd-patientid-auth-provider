<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\FormRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class FormController extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    public function submit(FormRequest $request): void
    {
        dd($request->all());
    }
}
