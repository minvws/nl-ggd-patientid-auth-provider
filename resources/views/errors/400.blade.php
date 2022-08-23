@extends('layouts.app')

@section('content')
    <section>
        <div>
            <h1>@lang('unauthenticated.header')</h1>
            <p>@lang('unauthenticated.explanation')</p>
            <p>@lang('unauthenticated.try_again')</p>
        </div>
    </section>
@endsection
