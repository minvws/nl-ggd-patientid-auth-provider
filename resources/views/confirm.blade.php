@extends('layouts.app')

@section('content')
    <section>
        <div>
            <h1>@lang('confirmation.header')</h1>

            <p>@lang('confirmation.explanation.' . $confirmationType, ['sent_to' => $sentTo])</p>

            <form method="POST" action="{{ route('confirmation.submit') }}">
                @csrf
                <label for="code">@lang('confirmation.code')</label>
                <input
                    id="code"
                    name="code"
                    type="number"
                    inputmode="numeric"
                    pattern="[0-9]{1-8}"
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
                    <p class="explanation" id="code_explanation">@lang('confirmation.code_explanation')</p>
                @endif

                <div>
                    <button type="submit">@lang("confirmation.retrieve_data") <span aria-hidden="true">&gt;</span></button>
                </div>
                <div>
                    <a class="button ghost" href="#">
                        @lang('confirmation.no_sms')
                        <span aria-hidden="true">&gt;</span>
                    </a>
                </div>
            </form>
        </div>
    </section>
@endsection
