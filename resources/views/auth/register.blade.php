@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Register</h2>

        <form method="POST" action="{{ route('register.post') }}">
            @csrf

            <div class="mb-3">
                <label>Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button class="btn btn-success">Register</button>
        </form>
    </div>
@endsection
