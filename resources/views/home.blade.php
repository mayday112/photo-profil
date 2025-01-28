@extends('layouts.app')

@section('style')

@endsection
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Dashboard') }}</div>

                    <div class="card-body">
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif


                        {{ __('You are logged in!') }}

                        <a href="{{ route('photos.create') }}">
                            <div class="btn btn-success float-end">Tambah foto</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')

@endsection
