@extends('layouts.app')

@section('content')
    <section>
        <div>
            <h1>@lang('login.header')</h1>
            <p>@lang('login.instructions', ['client_name' => $client->getName()])</p>

            <form method="POST" action="{{ route('login.submit') }}">
                @csrf
                @if ($errors->has('global'))
                    <p class="error" id="global_error">{{ $errors->first('global') }}</p>
                @endif

                <label for="patient_id">@lang('login.patientId')</label>
                <input
                    id="patient_id"
                    name="patient_id"
                    type="number"
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

                <div>
                    <button type="submit">@lang("continue") <span aria-hidden="true">&gt;</span></button>
                </div>
            </form>
        </div>
    </section>
@endsection
