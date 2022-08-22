<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

class ContactInfoNotFound extends Exception
{
    /**
     * Report the exception.
     *
     * @return bool
     */
    public function report(): bool
    {
        // We don't want the exception to be shown in logs.
        return true;
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function render(Request $request): RedirectResponse
    {
        Log::warning("authcontroller: contact not found: " . $this->getMessage());

        return Redirect::route('start_auth')
            ->withInput()
            ->withErrors([
                'patient_id' => __('validation.unknown_patient_id'),
                'birthdate' => __('validation.unknown_date_of_birth'),
            ]);
    }
}
