<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.activity.index', [
            'activities' => ActivityLog::query()->with('user')->latest()->paginate(30),
        ]);
    }
}
