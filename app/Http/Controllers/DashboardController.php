<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $canAccessProduction = DB::table('bpm_role_user as ru')
            ->join('bpm_roles as r', 'r.id', '=', 'ru.bpm_role_id')
            ->where('ru.user_id', $request->user()->id)
            ->where('r.code', 'MARKETING')
            ->exists();

        return view('dashboard', compact('canAccessProduction'));
    }
}
