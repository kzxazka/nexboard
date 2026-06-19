<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'checklist' => 'nullable|array',
            'comments' => 'nullable|array',
        ]);

        // Set default order to last position
        $maxOrder = Task::where('project_id', $validated['project_id'])
            ->where('status', $validated['status'])
            ->max('order') ?? 0;

        $validated['order'] = $maxOrder + 1;

        if (!isset($validated['checklist'])) {
            $validated['checklist'] = [];
        }
        if (!isset($validated['comments'])) {
            $validated['comments'] = [];
        }

        $task = Task::create($validated);

        if ($request->wantsJson()) {
            return response()->json($task);
        }

        return redirect()->back()->with('success', 'Task created successfully.');
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'checklist' => 'nullable|array',
            'comments' => 'nullable|array',
        ]);

        $task->update($validated);

        if ($request->wantsJson()) {
            return response()->json($task->fresh());
        }

        return redirect()->back()->with('success', 'Task updated successfully.');
    }

    /**
     * Update task positions (drag-and-drop reorder).
     */
    public function updatePositions(Request $request)
    {
        $validated = $request->validate([
            'tasks' => 'required|array',
            'tasks.*.id' => 'required|exists:tasks,id',
            'tasks.*.status' => 'required|string',
            'tasks.*.order' => 'required|integer',
        ]);

        foreach ($validated['tasks'] as $taskData) {
            Task::where('id', $taskData['id'])->update([
                'status' => $taskData['status'],
                'order' => $taskData['order'],
            ]);
        }

        return response()->json(['message' => 'Positions updated.']);
    }

    public function destroy(Task $task)
    {
        $task->delete();

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Task deleted successfully.']);
        }

        return redirect()->back()->with('success', 'Task deleted successfully.');
    }
}
