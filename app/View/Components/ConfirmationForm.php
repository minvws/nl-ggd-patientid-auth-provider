<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Services\OidcService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
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
     * @return \Closure|Application|Htmlable|Factory|View|string
     */
    public function render()
    {
        return view('components.confirmation-form');
    }
}
