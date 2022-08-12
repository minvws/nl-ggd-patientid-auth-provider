@extends('layouts.app')

@section('content')
    <section>
        <div>
            <h1>@lang('verify.header')</h1>

            <p>@lang('verify.explanation.' . $verificationType, ['sent_to' => $sentTo])</p>

            <form method="POST" action="{{ route('verify.submit') }}">
                @csrf

                <div>
                    <label for="code">@lang('verify.code')</label>
                    <input
                        id="code"
                        name="code"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        autocomplete="off"
                        required
                        placeholder="123456"
                        aria-describedby="code_explanation"
                        {{ $errors->has('code')
                            ? 'aria-invalid=true aria-errormessage=code_error'
                        : '' }}
                        >
                        @if ($errors->has('code'))
                            <p class="error" id="code_error">{{ $errors->first('code') }}</p>
                        @else
                            <p class="explanation" id="code_explanation">@lang('verify.code_explanation')</p>
                        @endif
                </div>

                <div>
                    <button type="submit">@lang("verify.retrieve_data") <span aria-hidden="true">&gt;</span></button>
                </div>
                <div>
                    <a class="button ghost" href="{{ route('resend') }}">
                        @lang('verify.no_code.' . $verificationType)
                        <span aria-hidden="true">&gt;</span>
                    </a>
                </div>
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
