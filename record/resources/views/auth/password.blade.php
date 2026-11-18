@extends('auth.simple', ['title' => 'Change password'])

@section('content')
    <h1>Change password</h1>
    <form method="POST" action="{{ route('password.change') }}">
        @csrf @method('PUT')
        <label>Current password</label>
        <input type="password" name="current_password" required>
        <label>New password</label>
        <input type="password" name="password" required>
        <label>Confirm</label>
        <input type="password" name="password_confirmation" required>
        <button type="submit">Update password</button>
    </form>
@endsection
