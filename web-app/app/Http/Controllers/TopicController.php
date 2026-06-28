<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TopicController extends Controller
{
    public function index()
    {
        return view('forum.index');
    }

    public function create()
    {
        return view('topics.create');
    }

    public function store(Request $request)
    {
        return redirect()->route('dashboard');
    }

    public function show($topic)
    {
        return view('forum.show');
    }

    public function exportPdf($topic)
    {
        return redirect()->back();
    }
}