<?php

namespace App\Http\Controllers;

use App\Models\Sketch;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SketchController extends Controller
{
    public function index()
    {
        $sketches = Sketch::whereHas('project', fn($q) => $q->where('user_id', Auth::id()))
            ->with('project:id,name')
            ->latest()
            ->get();

        $projects = Project::where('user_id', Auth::id())
            ->select('id', 'name')
            ->get();

        return Inertia::render('Sketches/Index', [
            'sketches' => $sketches,
            'projects' => $projects,
        ]);
    }

    public function show(Sketch $sketch)
    {
        $sketch->load('project:id,name');

        return Inertia::render('Sketches/Show', [
            'sketch' => $sketch,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'nullable|string|max:255',
        ]);

        $sketch = Sketch::create($validated);

        return redirect()->route('sketches.show', $sketch)->with('success', 'Sketch created.');
    }

    /**
     * Auto-save canvas data from frontend.
     */
    public function saveCanvas(Request $request, Sketch $sketch)
    {
        $validated = $request->validate([
            'canvas_data' => 'required|array',
        ]);

        $sketch->update(['canvas_data' => $validated['canvas_data']]);

        return response()->json(['message' => 'Canvas saved.']);
    }

    public function destroy(Sketch $sketch)
    {
        $sketch->delete();

        return redirect()->route('sketches.index')->with('success', 'Sketch deleted.');
    }
}
