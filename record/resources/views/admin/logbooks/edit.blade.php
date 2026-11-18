@extends('layouts.admin', ['title' => 'Edit Logbook Entry'])
@section('content')
    @include('admin.logbooks.partials.form', ['action' => route('admin.logbooks.update', $entry ?? $legacyEntry->logbook_id), 'method' => 'PUT', 'entry' => $entry ?? null])
@endsection
