@extends('layouts.app')

@section('content')
    <section>
        <div>
            <h1>@lang('resend.header.' . $verificationType)</h1>

            <p>@lang('resend.explanation.' . $verificationType)</p>

            <p>@lang('resend.contact_ggd')</p>

            <form method="POST" action="{{ route('resend.submit') }}">
                @csrf
                <div>
                    <button type="submit">@lang('resend.button') <span aria-hidden="true">&gt;</span></button>
                </div>
                <div>
                    <a class="button ghost" href="{{ route('verify') }}">
                        <span aria-hidden="true">&lt;</span>
                        @lang('back')
                    </a>
                </div>
            </form>
        </div>
    </section>
@endsection
