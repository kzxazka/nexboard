<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\User;
use App\Notifications\SystemNotification;

class TaskObserver
{
    public function created(Task $task): void
    {
        if ($task->project && $user = User::find($task->project->user_id)) {
            $user->notify(new SystemNotification(
                'New Task Added',
                "Task '{$task->title}' added to '{$task->project->name}'.",
                'info',
                route('projects.show', $task->project_id)
            ));
        }
    }

    public function updated(Task $task): void
    {
        if ($task->isDirty('status')) {
            if ($task->project && $user = User::find($task->project->user_id)) {
                $user->notify(new SystemNotification(
                    'Task Moved',
                    "Task '{$task->title}' moved to {$task->status}.",
                    'info',
                    route('projects.show', $task->project_id)
                ));
            }
        }
    }
}
