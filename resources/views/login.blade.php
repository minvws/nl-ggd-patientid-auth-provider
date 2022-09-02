@extends('layouts.app')

@section(
    'page-title',
    __('login.header') . ($errors->any() ? ' (' . __('page-title.errors') . ')' : '')
)

@section('content')
    <section>
        <div>
            <h1>@lang('login.header')</h1>
            <p>@lang('login.instructions', ['client_name' => $client_name])</p>

            <form method="POST" action="{{ route('login.submit') }}">
                @csrf
                @if ($errors->has('global'))
                    <p class="error" id="global_error">{{ $errors->first('global') }}</p>
                @endif

                <div>
                    <label for="patient_id">@lang('login.patientId')</label>
                    <input
                        id="patient_id"
                        name="patient_id"
                        inputmode="numeric"
                        autocomplete="off"
                        value="{{ old('patient_id') }}"
                        aria-describedby="patient_id_explanation patient_id_error"
                        {{ $errors->has('patient_id') ? 'aria-invalid=true autofocus' : '' }}
                    >
                    @if ($errors->has('patient_id'))
                        <p class="error" id="patient_id_error">{{ $errors->first('patient_id') }}</p>
                    @else
                        <p class="explanation" id="patient_id_explanation">@lang('login.patientId_explanation')</p>
                    @endif
                </div>

                <fieldset class="birthdate">
                    <legend>@lang('login.birthDate')</legend>
                    <div>
                        <div>
                            <label for="birth_day">@lang('login.day')</label>
                            <input
                                id="birth_day"
                                name="birth_day"
                                autocomplete="off"
                                inputmode="numeric"
                                maxlength="2"
                                value="{{ old('birth_day') }}"
                                aria-describedby="birthdate_error"
                                {{ $errors->has('birthdate') ? 'aria-invalid=true autofocus' : '' }}
                            >
                        </div>
                        <div>
                            <label for="birth_month">@lang('login.month')</label>
                            <input
                                id="birth_month"
                                name="birth_month"
                                autocomplete="off"
                                inputmode="numeric"
                                maxlength="2"
                                value="{{ old('birth_month') }}"
                                aria-describedby="birthdate_error"
                                {{ $errors->has('birthdate') ? 'aria-invalid=true autofocus' : '' }}
                            >
                        </div>
                        <div>
                            <label for="birth_year">@lang('login.year')</label>
                            <input
                                id="birth_year"
                                name="birth_year"
                                autocomplete="off"
                                inputmode="numeric"
                                maxlength="4"
                                value="{{ old('birth_year') }}"
                                aria-describedby="birthdate_error"
                                {{ $errors->has('birthdate') ? 'aria-invalid=true autofocus' : '' }}
                            >
                        </div>
                    </div>
                    @if ($errors->has('birthdate'))
                        <p class="error" id="birthdate_error">{{ $errors->first('birthdate') }}</p>
                    @endif
                </fieldset>

                <button type="submit">
                    @lang("continue")
                    <span class="icon icon-chevron-right" aria-hidden="true"></span>
                </button>
            </form>

            <div class="extra-buttons">
                <a href="{{ $cancel_uri }}" class="text-button">
                    <span class="icon icon-chevron-left" aria-hidden="true"></span>
                    @lang("cancel")
                </a>
            </div>

            <div class="accordion">
                <div>
                    <button
                        aria-expanded="false"
                        aria-controls="faq-no-patient-number"
                    >
                    @lang('faq.no-patient-number.question') <span aria-hidden="true"></span>
                    </button>
                    <div id="faq-no-patient-number">
                        {!! __('faq.no-patient-number.answer') !!}
                    </div>
                </div>
                <div>
                    <button
                        aria-expanded="false"
                        aria-controls="faq-birthdate-unknown"
                    >
                        @lang('faq.birthdate-unknown.question') <span aria-hidden="true"></span>
                    </button>
                    <div id="faq-birthdate-unknown">
                        {!! __('faq.birthdate-unknown.answer') !!}
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
