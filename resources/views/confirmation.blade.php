@extends('layouts.app')

@section('content')

    We have send you a code via email or text message. Please enter the code below. Note that
    this code is only valid for 5 minutes.

    <form method="POST" action="{{ route('confirmation.submit') }}" class="horizontal-view help">
        @csrf
        <input type="hidden" name="hash" value="{{ $hash }}">

        <fieldset>
            <legend>@lang('form.persoonsgegevens')</legend>

            <div class="required">
                <label for="code">@lang('form.code')</label>
                <input id="code" name="code" required >
                @if ($errors->has('code')) <p class="help-block">{{ $errors->first('code') }}</p> @endif
            </div>

        </fieldset>

        <div id="submit-button-container">
            <input type="submit" value=" @lang("Submit") ">
        </div>

    </form>

@endsection
