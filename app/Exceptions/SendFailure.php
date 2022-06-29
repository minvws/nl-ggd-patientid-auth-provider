<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SendFailure extends Exception
{
}
