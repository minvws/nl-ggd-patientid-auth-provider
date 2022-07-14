@extends('layouts.app')

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
                        pattern="[0-9]{1-8}"
                        autocomplete="off"
                        required
                        placeholder="1234567"
                        value="{{ old('patient_id') }}"
                        aria-describedby="patient_id_explanation"
                        {{ $errors->has('patient_id')
                            ? 'aria-invalid=true aria-errormessage=patient_id_error'
                            : '' }}
                    >
                    @if ($errors->has('patient_id'))
                        <p class="error" id="patient_id_error">{{ $errors->first('patient_id') }}</p>
                    @else
                        <p class="explanation" id="patient_id_explanation">@lang('login.patientId_explanation')</p>
                    @endif
                </div>

                <div>
                    <label for="birthdate">@lang('login.birthDate')</label>
                    <input
                        id="birthdate"
                        name="birthdate"
                        autocomplete="off"
                        required
                        placeholder="@lang('login.birthDate_placeholder')"
                        aria-describedby="birthdate_error"
                        value="{{ old('birthdate') }}"
                        >
                        @if ($errors->has('birthdate'))
                            <p class="error" id="birthdate_error">{{ $errors->first('birthdate') }}</p>
                        @endif
                </div>

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
                    <button id="faq-no-patient-number" aria-expanded="false">
                        @lang('faq.no-patient-number.question')
                    </button>
                    <div aria-labelledby="faq-no-patient-number">
                        {!! __('faq.no-patient-number.answer') !!}
                    </div>
                </div>
                <div>
                    <button id="faq-birthdate-unknown" aria-expanded="false">
                        @lang('faq.birthdate-unknown.question')
                    </button>
                    <div aria-labelledby="faq-birthdate-unknown">
                        {!! __('faq.birthdate-unknown.answer') !!}
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
