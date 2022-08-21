<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NoPatientHashInSessionException extends Exception
{
    /**
     * Report the exception.
     *
     * @return bool
     */
    public function report(): bool
    {
        return false;
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function render(Request $request): RedirectResponse
    {
        return redirect()->route('start_auth');
    }
}
