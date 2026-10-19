<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->text('description');
            $table->string('image_path', 255);
            $table->json('tags');
            $table->boolean('is_published')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort_order', 'id']);
        });

        DB::table('projects')->insert([
            [
                'title' => 'School Management System',
                'description' => 'Complete student information system with enrollment, grades, billing, and reporting.',
                'image_path' => 'projects/merryland.webp',
                'tags' => json_encode(['NextJS', 'React', 'PostgreSQL']),
                'is_published' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Healthcare Management System',
                'description' => 'Real-time patient monitoring system with analytics and reporting.',
                'image_path' => 'projects/healthcare-dashboard.webp',
                'tags' => json_encode(['Laravel', 'Livewire', 'PostgreSQL']),
                'is_published' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        foreach (['merryland.webp', 'healthcare-dashboard.webp'] as $name) {
            if (! Storage::disk('public')->exists("projects/{$name}")) {
                Storage::disk('public')->put("projects/{$name}", file_get_contents(database_path("data/projects/{$name}")));
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
