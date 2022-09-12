@extends('layouts.app')

@section('page-title', __('resend_throttle.header'))

@section('content')
    <section>
        <div>
            <h1>@lang('resend_throttle.header')</h1>
            <p>@lang('resend_throttle.content', ['minutes' => $retry_after->diffInMinutes(now())])</p>

            @if(isset($back_uri))
            <div class="extra-buttons">
                <a href="{{ $back_uri }}" class="text-button">
                    <span class="icon icon-chevron-left" aria-hidden="true"></span>
                    @lang("back")
                </a>
            </div>
            @endif
        </div>
    </section>
@endsection
