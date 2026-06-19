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
            'status' => 'nullable|in:todo,in_progress,review,done',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
        ]);

        // Set default order to last position
        $maxOrder = Task::where('project_id', $validated['project_id'])
            ->where('status', $validated['status'] ?? 'todo')
            ->max('order') ?? 0;

        $validated['order'] = $maxOrder + 1;

        Task::create($validated);

        return redirect()->back()->with('success', 'Task created successfully.');
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:todo,in_progress,review,done',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
        ]);

        $task->update($validated);

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
            'tasks.*.status' => 'required|in:todo,in_progress,review,done',
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

        return redirect()->back()->with('success', 'Task deleted successfully.');
    }
}
