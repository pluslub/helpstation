<?php

namespace App\Http\Controllers;
use App\Models\TimerRecord;
use Illuminate\Http\Request;

class TimerController extends Controller
{
    public function show()
    {
        return view('timer', ['time' => now()->format('H:i:s')]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'elapsed_ms' => 'required|integer|min:0',
        ]);

        TimerRecord::create([
            'elapsed_ms' => $request->elapsed_ms,
        ]);

        return response()->json(['status' => 'ok']);
    }
    
    public function destroy()
    {
        TimerRecord::truncate();

        return response()->json(['status' => 'ok']);
    }
}