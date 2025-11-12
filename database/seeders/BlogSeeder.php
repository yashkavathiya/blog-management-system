<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        $blogs = [
            [
                'title' => 'Getting Started with Laravel',
                'description' => 'Laravel is a web application framework with expressive, elegant syntax. Learn the basics of Laravel development.',
            ],
            [
                'title' => 'Understanding REST APIs',
                'description' => 'REST APIs are the backbone of modern web applications. This guide covers everything you need to know.',
            ],
            [
                'title' => 'Database Design Best Practices',
                'description' => 'Learn how to design efficient and scalable database schemas for your applications.',
            ],
            [
                'title' => 'Introduction to Sanctum Authentication',
                'description' => 'Laravel Sanctum provides a featherweight authentication system for SPAs and mobile applications.',
            ],
            [
                'title' => 'Building APIs with Laravel',
                'description' => 'Step-by-step guide to building robust and secure APIs using Laravel framework.',
            ],
        ];

        foreach ($blogs as $blog) {
            Blog::create([
                'user_id' => $users->random()->id,
                'title' => $blog['title'],
                'description' => $blog['description'],
            ]);
        }
    }
}
