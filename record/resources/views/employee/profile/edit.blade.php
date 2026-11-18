@extends('layouts.employee', ['title' => 'My Profile'])

@section('content')
    <style>
        .employee-profile-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 16px; }
        @media (max-width: 620px) { .employee-profile-grid { grid-template-columns: 1fr; } }
    </style>
    <section class="card" style="max-width:760px;">
        <div class="card-header">My Profile</div>
        <form method="POST" action="{{ route('employee.profile.update') }}" class="card-body">
            @csrf
            @method('PUT')

            <div class="employee-profile-grid">
                <div class="form-group">
                    <label class="form-label" for="profile-name">Name</label>
                    <input class="form-control" id="profile-name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255">
                </div>
                <div class="form-group">
                    <label class="form-label" for="profile-email">Email</label>
                    <input class="form-control" id="profile-email" name="email" type="email" value="{{ old('email', $user->email) }}" required maxlength="255">
                </div>
                <div class="form-group">
                    <label class="form-label" for="profile-contact">Contact number</label>
                    <input class="form-control" id="profile-contact" name="contact_number" value="{{ old('contact_number', $user->contact_number) }}" maxlength="50">
                </div>
                <div class="form-group">
                    <label class="form-label" for="profile-position">Position</label>
                    <input class="form-control" id="profile-position" name="position" value="{{ old('position', $user->position) }}" maxlength="255">
                </div>
                <div class="form-group">
                    <label class="form-label" for="profile-department">Department</label>
                    <input class="form-control" id="profile-department" name="department" value="{{ old('department', $user->department) }}" maxlength="255">
                </div>
            </div>

            <div style="display:flex;gap:10px;align-items:center;margin-top:18px;">
                <button class="btn btn-primary" type="submit">Save profile</button>
                <button class="btn" type="button" data-open-password-modal aria-haspopup="dialog" aria-controls="employee-password-modal">Change password</button>
            </div>
        </form>
    </section>
@endsection
