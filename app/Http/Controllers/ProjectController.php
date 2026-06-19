<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::where('user_id', Auth::id())
            ->withCount('tasks')
            ->latest()
            ->get();

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client_name' => 'required|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'description' => 'nullable|string',
            'deadline' => 'nullable|date',
            'status' => 'nullable|in:planning,in_progress,review,completed,on_hold',
            'budget' => 'nullable|numeric|min:0',
        ]);

        $validated['user_id'] = Auth::id();

        Project::create($validated);

        return redirect()->back()->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        Gate::authorize('view', $project);

        $project->load(['tasks' => fn($q) => $q->orderBy('order')]);

        $columns = [
            'todo' => $project->tasks->where('status', 'todo')->values(),
            'in_progress' => $project->tasks->where('status', 'in_progress')->values(),
            'review' => $project->tasks->where('status', 'review')->values(),
            'done' => $project->tasks->where('status', 'done')->values(),
        ];

        return Inertia::render('Projects/Show', [
            'project' => $project,
            'columns' => $columns,
        ]);
    }

    public function update(Request $request, Project $project)
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'client_name' => 'sometimes|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'description' => 'nullable|string',
            'deadline' => 'nullable|date',
            'status' => 'nullable|in:planning,in_progress,review,completed,on_hold',
            'budget' => 'nullable|numeric|min:0',
        ]);

        $project->update($validated);

        return redirect()->back()->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        Gate::authorize('delete', $project);

        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
    }
}
