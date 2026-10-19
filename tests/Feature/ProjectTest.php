<?php

use App\Models\Project;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

test('the migration adds the two existing projects in order', function () {
    $projects = Project::query()->ordered()->get();

    expect($projects)->toHaveCount(2)
        ->and($projects->pluck('title')->all())->toBe(['School Management System', 'Healthcare Management System'])
        ->and($projects[0]->description)->toBe('Complete student information system with enrollment, grades, billing, and reporting.')
        ->and($projects[0]->tags)->toBe(['NextJS', 'React', 'PostgreSQL'])
        ->and($projects[1]->description)->toBe('Real-time patient monitoring system with analytics and reporting.')
        ->and($projects[1]->tags)->toBe(['Laravel', 'Livewire', 'PostgreSQL']);
});

test('published projects leave out hidden ones and break order ties by id', function () {
    Project::factory()->unpublished()->create(['title' => 'Secret Build', 'sort_order' => 0]);
    $first = Project::factory()->create(['title' => 'Tie A', 'sort_order' => 5]);
    $second = Project::factory()->create(['title' => 'Tie B', 'sort_order' => 5]);

    $titles = Project::query()->published()->ordered()->pluck('title')->all();

    expect($titles)->not->toContain('Secret Build')
        ->and($titles)->toBe(['School Management System', 'Healthcare Management System', $first->title, $second->title]);
});

test('tags are trimmed and de-duplicated ignoring letter case', function () {
    expect(Project::parseTags(' laravel, Tailwind, Laravel ,, '))->toBe(['laravel', 'Tailwind'])
        ->and(Project::parseTags(null))->toBe([])
        ->and(Project::parseTags(''))->toBe([]);
});

test('tag suggestions cover every project and forget tags of deleted projects', function () {
    $hidden = Project::factory()->unpublished()->create(['tags' => ['Svelte', 'postgresql']]);

    expect(Project::tagSuggestions())->toContain('Svelte')
        ->and(array_count_values(array_map('mb_strtolower', Project::tagSuggestions()))['postgresql'])->toBe(1);

    $hidden->delete();

    expect(Project::tagSuggestions())->not->toContain('Svelte');
});

test('the next display order follows the highest, capped at 999', function () {
    expect(Project::nextSortOrder())->toBe(3);

    Project::factory()->create(['sort_order' => 999]);
    expect(Project::nextSortOrder())->toBe(999);

    Project::query()->delete();
    expect(Project::nextSortOrder())->toBe(1);
});

test('the image url is null when the file is missing', function () {
    $project = Project::factory()->create();

    expect($project->imageUrl())->toBeNull();

    Storage::disk('public')->put($project->image_path, 'x');

    expect($project->imageUrl())->toEndWith($project->image_path);
});

test('deleting a project removes its image file', function () {
    $project = Project::factory()->create();
    Storage::disk('public')->put($project->image_path, 'x');

    $project->delete();

    Storage::disk('public')->assertMissing($project->image_path);
});
