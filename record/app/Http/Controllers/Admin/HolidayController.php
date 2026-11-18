<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HolidayController extends Controller
{
    public function index(): View
    {
        return view('admin.holidays.index', [
            'title' => 'Holidays',
            'holidays' => Holiday::query()->orderBy('holiday_date')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Holiday::query()->create($request->validate([
            'holiday_date' => ['required', 'date', 'unique:holidays,holiday_date'],
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', 'in:regular,special'],
        ]));

        return back()->with('status', 'Holiday saved.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        return back()->with('status', 'Holiday removed.');
    }
}
