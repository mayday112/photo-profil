@extends('layouts.app')

@section('styles')
@endsection

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Add photo') }}</div>

                    <div class="card-body">
                        <form action="{{ route('photos.update', $photo->id) }}" method="POST" enctype="multipart/form-data">
                            @method('PUT')
                            @csrf

                            
                            <div class="mb-3">
                                <div class="form-label" for="id">ID</div>
                                <input type="text" name="user_id" class="form-control" placeholder="08123...." value="{{ old('user_id', $photo->user_id) }}" />
                            </div>

                            <div class="mb-3">
                                <div class="form-label" for="photo_file">Photo</div>
                                <input type="file" name="photo_file" id="photo_file" accept=".jpg, .jpeg, .png" class="form-control">
                            </div>

                            <button type="submit" class="btn btn-primary float-end">Kirim</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
@endsection
