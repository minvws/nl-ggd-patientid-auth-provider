<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Services\OidcService;
use Illuminate\View\Component;

class ConfirmationForm extends Component
{
    public string $access_token = "";
    public string $url;

    public function __construct(OidcService $service, string $url)
    {
        $request = request();
        $this->access_token = $service->fetchTokenFromRequest($request);

        $this->url = $url;
    }

    public function render()
    {
        return view('components.confirmation-form');
    }
}
