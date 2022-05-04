@extends('layouts.app')

@section('content')
    <form method="POST" action="{{ route('form.submit') }}" class="horizontal-view help">
        @csrf

        <fieldset data-collect="bsn">
            <legend>@lang('form.persoonsgegevens')</legend>

            <div class="required">
                <label for="birthDate">@lang('form.birthDate')</label>
                <input id="birthDate" name="birthdate" required data-collect-element="date_of_birth">
                @if ($errors->has('birthdate')) <p class="help-block">{{ $errors->first('birthdate') }}</p> @endif
            </div>
            <div class="required">
                <label for="patient_id">@lang('form.patientId')</label>
                <input type="text" name="patient_id" id="patient_id" pattern="[0-9]+" data-ro-validate="patient_id" data-collect-element="patient_id" autocomplete="off">
                @if ($errors->has('patient_id')) <p class="help-block">{{ $errors->first('patient_id') }}</p> @endif
            </div>

            <div class="">
                <label for="phone_nr">@lang('form.phoneNr')</label>
                <div>
                    <input type="tel" name="phone_nr" id="phone_nr" autocomplete="off">
                    @if ($errors->has('phone_nr')) <p class="help-block">{{ $errors->first('phone_nr') }}</p> @endif
                </div>
            </div>
            <div class="">
                <label for="email">@lang('form.emailAddress')</label>
                <div>
                    <input type="email" name="email" id="email" autocomplete="off">
                    @if ($errors->has('email')) <p class="help-block">{{ $errors->first('email') }}</p> @endif
                </div>
            </div>

        <div id="submit-button-container">
            <input type="submit" value=" @lang("Submit") ">
        </div>

    </form>

@endsection
