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
                        <button name="method" value="{{ $verificationType }}" type="submit">@lang('resend.button') <span aria-hidden="true">&gt;</span></button>
                        @if ($verificationType === 'sms' && $hasEmail)
                            <button name="method" value="email" type="submit" class="ghost">@lang('resend.email.button') <span aria-hidden="true">&gt;</span></button>
                        @endif
                        @if ($verificationType === 'email' && $hasPhone)
                            <button name="method" value="sms" type="submit" class="ghost">@lang('resend.sms.button') <span aria-hidden="true">&gt;</span></button>
                        @endif
                    </form>
                </div>
                <div>
                    <a class="text-button" href="{{ route('verify') }}">
                        <span aria-hidden="true">&lt;</span>
                        @lang('back')
                    </a>
                </div>
            </form>
        </div>
    </section>
@endsection
