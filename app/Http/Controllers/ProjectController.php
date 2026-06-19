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
            'client_name' => 'nullable|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'description' => 'nullable|string',
            'deadline' => 'nullable|date',
            'status' => 'nullable|in:planning,in_progress,review,completed,on_hold',
            'budget' => 'nullable|numeric|min:0',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['client_name'] = $validated['client_name'] ?? 'Internal';
        
        // Default boards have 4 default columns: To Do, In Progress, Review, Done
        $validated['columns'] = ['To Do', 'In Progress', 'Review', 'Done'];

        $project = Project::create($validated);

        if ($request->wantsJson()) {
            return response()->json($project);
        }

        return redirect()->back()->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        Gate::authorize('view', $project);

        $project->load(['tasks' => fn($q) => $q->orderBy('order')]);

        $columnNames = $project->columns ?? ['To Do', 'In Progress', 'Review', 'Done'];

        $tasks = $project->tasks;
        $columns = [];
        foreach ($columnNames as $colName) {
            $columns[$colName] = $tasks->filter(function($task) use ($colName) {
                $status = $task->status;
                if (strtolower($status) === 'todo' && strtolower($colName) === 'to do') return true;
                if (strtolower($status) === 'in_progress' && strtolower($colName) === 'in progress') return true;
                return strtolower($status) === strtolower($colName);
            })->values();
        }

        $projects = Project::where('user_id', Auth::id())->select('id', 'name')->latest()->get();

        return Inertia::render('Projects/Show', [
            'project' => $project,
            'columns' => (object)$columns,
            'projects' => $projects,
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
            'columns' => 'nullable|array',
        ]);

        if ($request->has('columns') && $project->columns) {
            $oldColumns = $project->columns;
            $newColumns = $validated['columns'] ?? [];

            if (count($oldColumns) === count($newColumns)) {
                for ($i = 0; $i < count($oldColumns); $i++) {
                    if ($oldColumns[$i] !== $newColumns[$i]) {
                        Task::where('project_id', $project->id)
                            ->where('status', $oldColumns[$i])
                            ->update(['status' => $newColumns[$i]]);
                    }
                }
            }
        }

        $project->update($validated);

        if ($request->wantsJson()) {
            return response()->json($project->fresh());
        }

        return redirect()->back()->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        Gate::authorize('delete', $project);

        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
    }
}
