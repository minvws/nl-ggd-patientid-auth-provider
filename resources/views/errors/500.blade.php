@extends('layouts.app')

@section('content')
    <section>
        <div>
            <h1>@lang('unexpected.header')</h1>
            <p>@lang('unexpected.explanation')</p>
            <p>@lang('unexpected.try_again')</p>

            <div class="extra-buttons">
                <a href="{{ route('start_auth') }}" class="text-button">
                    <span class="icon icon-chevron-left" aria-hidden="true"></span>
                    @lang("back")
                </a>
            </div>
        </div>
    </section>
@endsection
