<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create demo user
        $user = User::firstOrCreate(
            ['email' => 'admin@nexboard.dev'],
            [
                'name' => 'Admin NexBoard',
                'password' => Hash::make('password'),
            ]
        );

        // Create sample projects
        $projects = [
            ['name' => 'E-Commerce Redesign', 'client_name' => 'PT Maju Jaya', 'client_email' => 'info@majujaya.co.id', 'status' => 'in_progress', 'deadline' => now()->addDays(30), 'budget' => 15000000, 'description' => 'Full redesign of e-commerce platform with new UI/UX.'],
            ['name' => 'Company Profile Website', 'client_name' => 'CV Berkah Makmur', 'client_email' => 'contact@berkahmakmur.com', 'status' => 'review', 'deadline' => now()->addDays(7), 'budget' => 5000000, 'description' => 'Modern company profile with CMS.'],
            ['name' => 'SaaS Dashboard', 'client_name' => 'StartupXYZ', 'client_email' => 'cto@startupxyz.io', 'status' => 'in_progress', 'deadline' => now()->addDays(45), 'budget' => 25000000, 'description' => 'Admin dashboard for SaaS analytics platform.'],
            ['name' => 'Mobile App Landing', 'client_name' => 'AppWorks', 'client_email' => 'hello@appworks.id', 'status' => 'completed', 'deadline' => now()->subDays(5), 'budget' => 3500000, 'description' => 'Landing page for mobile app launch.'],
            ['name' => 'Portfolio Website', 'client_name' => 'Sarah Designer', 'client_email' => 'sarah@design.me', 'status' => 'planning', 'deadline' => now()->addDays(60), 'budget' => 4000000, 'description' => 'Personal portfolio for a UI designer.'],
        ];

        foreach ($projects as $projectData) {
            $project = Project::create(array_merge($projectData, ['user_id' => $user->id]));

            // Create tasks for each project
            $statuses = ['todo', 'in_progress', 'review', 'done'];
            $priorities = ['low', 'medium', 'high', 'urgent'];
            $taskTemplates = [
                ['title' => 'Setup project structure', 'status' => 'done'],
                ['title' => 'Design wireframes', 'status' => 'done'],
                ['title' => 'Implement homepage', 'status' => 'in_progress'],
                ['title' => 'Create responsive layout', 'status' => 'in_progress'],
                ['title' => 'Add contact form', 'status' => 'todo'],
                ['title' => 'SEO optimization', 'status' => 'todo'],
                ['title' => 'Final QA testing', 'status' => 'review'],
            ];

            foreach ($taskTemplates as $i => $task) {
                Task::create([
                    'project_id' => $project->id,
                    'title' => $task['title'],
                    'description' => 'Task for ' . $project->name,
                    'status' => $task['status'],
                    'priority' => $priorities[array_rand($priorities)],
                    'order' => $i,
                ]);
            }
        }

        // Create sample transactions
        $transactions = [
            ['type' => 'income', 'amount' => 7500000, 'description' => 'DP 50% - E-Commerce Redesign', 'category' => 'Project Payment', 'date' => now()->subDays(20)],
            ['type' => 'income', 'amount' => 5000000, 'description' => 'Full Payment - Company Profile', 'category' => 'Project Payment', 'date' => now()->subDays(10)],
            ['type' => 'income', 'amount' => 12500000, 'description' => 'DP 50% - SaaS Dashboard', 'category' => 'Project Payment', 'date' => now()->subDays(15)],
            ['type' => 'income', 'amount' => 3500000, 'description' => 'Full Payment - Mobile App Landing', 'category' => 'Project Payment', 'date' => now()->subDays(5)],
            ['type' => 'expense', 'amount' => 500000, 'description' => 'VPS Hosting Monthly', 'category' => 'Hosting', 'date' => now()->subDays(3)],
            ['type' => 'expense', 'amount' => 150000, 'description' => 'Domain .co.id renewal', 'category' => 'Domain', 'date' => now()->subDays(8)],
            ['type' => 'expense', 'amount' => 200000, 'description' => 'Figma Pro subscription', 'category' => 'Tools', 'date' => now()->subDays(1)],
            ['type' => 'income', 'amount' => 2000000, 'description' => 'DP 50% - Portfolio Website', 'category' => 'Project Payment', 'date' => now()],
        ];

        foreach ($transactions as $tx) {
            Transaction::create(array_merge($tx, ['user_id' => $user->id]));
        }
    }
}
