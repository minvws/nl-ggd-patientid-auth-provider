@extends('layouts.app')

@section('page-title', __('resend.header.' . $verificationType))

@section('content')
    <section>
        <div>
            <h1>@lang('resend.header.' . $verificationType)</h1>

            <p>@lang('resend.explanation.' . $verificationType, ['minutes' => config('codegenerator.expiry') / 60])</p>

            <p>@lang('resend.contact_ggd')</p>

                <div>
                    <form method="POST" action="{{ route('resend.submit') }}">
                        @csrf
                        <button name="method" value="{{ $verificationType }}" type="submit">
                            @lang('resend.button')
                            <span class="icon icon-chevron-right" aria-hidden="true"></span>
                        </button>
                        @if ($verificationType === 'sms' && $hasEmail)
                            <button name="method" value="email" type="submit" class="ghost">
                                @lang('resend.email.button')
                                <span class="icon icon-chevron-right" aria-hidden="true"></span>
                            </button>
                        @endif
                        @if ($verificationType === 'email' && $hasPhone)
                            <button name="method" value="sms" type="submit" class="ghost">
                                @lang('resend.sms.button')
                                <span class="icon icon-chevron-right" aria-hidden="true"></span>
                            </button>
                        @endif
                    </form>
                </div>
                <div>
                    <a class="text-button" href="{{ route('verify') }}">
                        <span class="icon icon-chevron-left" aria-hidden="true"></span>
                        @lang('back')
                    </a>
                </div>
            </form>
        </div>
    </section>
@endsection
