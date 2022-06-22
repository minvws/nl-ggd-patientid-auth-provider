<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CmsValidationException extends Exception
{
    public function getHttpException(Request $request, Throwable $exception): HttpException
    {
        return new HttpException(500, $exception->getMessage(), $exception);
    }
}
