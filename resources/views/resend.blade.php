@extends('layouts.app')

@section('content')
    <section>
        <div>
            <h1>@lang('resend.header.' . $verificationType)</h1>

            <p>@lang('resend.explanation.' . $verificationType)</p>

            <p>@lang('resend.contact_ggd')</p>

                <div>
                    <form method="POST" action="{{ route('resend.submit') }}">
                        @csrf
                        @if ($contact->phoneNumber)
                            <button name="method" value="phone" type="submit">@lang('resend.sms.button') <span aria-hidden="true">&gt;</span></button>
                        @endif
                        @if ($contact->email)
                            <button name="method" value="email" type="submit">@lang('resend.email.button') <span aria-hidden="true">&gt;</span></button>
                        @endif
                    </form>
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
