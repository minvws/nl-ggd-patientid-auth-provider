@extends('layouts.app')

@section('content')
    <section>
        <div>
            <h1>@lang('resend.header.' . $verificationType)</h1>

            <p>@lang('resend.explanation.' . $verificationType)</p>

            <p>@lang('resend.contact_ggd')</p>

                <div>
                    @if ($contact->phoneNumber)
                    <form method="POST" action="{{ route('resend.submit') }}">
                        @csrf
                        <input type="hidden" name="method" value="phone">
                        <button type="submit">@lang('resend.sms.button') <span aria-hidden="true">&gt;</span></button>
                    </form>
                    @endif
                    @if ($contact->email)
                    <form method="POST" action="{{ route('resend.submit') }}">
                        @csrf
                        <input type="hidden" name="method" value="email">
                        <button type="submit">@lang('resend.email.button') <span aria-hidden="true">&gt;</span></button>
                    </form>
                    @endif
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
