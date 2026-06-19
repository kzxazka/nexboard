<?php

namespace App\Observers;

use App\Models\Project;
use App\Models\User;
use App\Notifications\SystemNotification;

class ProjectObserver
{
    public function created(Project $project): void
    {
        if ($user = User::find($project->user_id)) {
            $user->notify(new SystemNotification(
                'New Project Created',
                "Project '{$project->name}' has been successfully created.",
                'success',
                route('projects.show', $project->id)
            ));
        }
    }

    public function updated(Project $project): void
    {
        if ($project->isDirty('status')) {
            if ($user = User::find($project->user_id)) {
                $status = strtoupper(str_replace('_', ' ', $project->status));
                $user->notify(new SystemNotification(
                    'Project Status Updated',
                    "Project '{$project->name}' status changed to {$status}.",
                    'info',
                    route('projects.show', $project->id)
                ));
            }
        }
    }
}
