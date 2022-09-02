@extends('layouts.app')

@section('page-title', __('ratelimit.header'))

@section('content')

    <section>
        <div>
            <h1>@lang('ratelimit.header')</h1>
            <p>@lang('ratelimit.content')</p>

            @if(isset($cancel_uri))
            <div class="extra-buttons">
                <a href="{{ $cancel_uri }}" class="text-button">
                    <span class="icon icon-chevron-left" aria-hidden="true"></span>
                    @lang("cancel")
                </a>
            </div>
            @endif
        </div>
    </section>

@endsection
