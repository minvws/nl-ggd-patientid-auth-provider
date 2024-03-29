@extends('layouts.app')

@section(
    'page-title',
    __('verify.header') . ($errors->any() ? ' (' . __('page-title.errors') . ')' : '')
)

@section('content')
    <section>
        <div>
            <h1>@lang('verify.header')</h1>

            <p>@lang('verify.explanation.' . $verificationType, ['sent_to' => $sentTo, 'minutes' => config('codegenerator.expiry') / 60])</p>

            <form method="POST" action="{{ route('verify.submit') }}">
                @csrf

                <div>
                    <label for="code">@lang('verify.code')</label>
                    <input
                        id="code"
                        name="code"
                        inputmode="numeric"
                        autocomplete="off"
                        placeholder="123456"
                        aria-describedby="code_explanation code_error"
                        {{ $errors->has('code') ? 'aria-invalid=true autofocus' : '' }}
                    >
                    @if ($errors->has('code'))
                        <p class="error" id="code_error">{{ $errors->first('code') }}</p>
                    @else
                        <p class="explanation" id="code_explanation">@lang('verify.code_explanation')</p>
                    @endif
                </div>

                <button type="submit">
                    @lang("verify.retrieve_data")
                    <span class="icon icon-chevron-right" aria-hidden="true"></span>
                </button>

                <a class="button ghost" href="{{ route('resend') }}">
                    @lang('verify.no_code.' . $verificationType)
                    <span class="icon icon-chevron-right" aria-hidden="true"></span>
                </a>
            </form>

            <div class="extra-buttons">
                <a href="{{ $cancel_uri }}" class="text-button">
                    <span class="icon icon-chevron-left" aria-hidden="true"></span>
                    @lang("cancel")
                </a>
            </div>
        </div>
    </section>
@endsection
