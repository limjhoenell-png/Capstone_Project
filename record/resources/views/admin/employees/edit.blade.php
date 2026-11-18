@extends('layouts.admin', ['title' => 'Edit Employee'])

@section('content')
    @include('admin.employees.partials.form', ['action' => route('admin.employees.update', $employee), 'method' => 'PUT', 'employee' => $employee])
@endsection
