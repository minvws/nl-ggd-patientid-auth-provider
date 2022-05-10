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
        $this->access_token = (string)$service->fetchTokenFromRequest($request);

        $this->url = $url;
    }

    /**
     * @return \Closure|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Support\Htmlable|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.confirmation-form');
    }
}
