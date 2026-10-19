<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable(['title', 'description', 'image_path', 'tags', 'is_published', 'sort_order'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Register model events that remove a project's image file when the project is deleted.
     */
    protected static function booted(): void
    {
        static::deleted(fn (Project $project): bool => Storage::disk('public')->delete($project->image_path));
    }

    /**
     * Scope the query to projects shown on the public Projects page.
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Scope the query to the display order: lowest order first, then the project added first.
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Get the public URL of the project's image, or null when the file is missing.
     */
    public function imageUrl(): ?string
    {
        return Storage::disk('public')->exists($this->image_path)
            ? Storage::disk('public')->url($this->image_path)
            : null;
    }

    /**
     * Parse comma-separated tags, dropping blanks and repeats that differ only in letter case.
     *
     * @return list<string>
     */
    public static function parseTags(?string $input): array
    {
        $tags = [];

        foreach (explode(',', (string) $input) as $tag) {
            $tag = trim($tag);
            $key = mb_strtolower($tag);

            if ($tag !== '' && ! array_key_exists($key, $tags)) {
                $tags[$key] = $tag;
            }
        }

        return array_values($tags);
    }

    /**
     * Get every distinct tag used by any project, published or not, sorted alphabetically.
     *
     * @return list<string>
     */
    public static function tagSuggestions(): array
    {
        $tags = [];

        foreach (static::query()->pluck('tags') as $projectTags) {
            foreach ($projectTags ?? [] as $tag) {
                $tags[mb_strtolower($tag)] ??= $tag;
            }
        }

        natcasesort($tags);

        return array_values($tags);
    }

    /**
     * Get the display order to pre-fill for a new project: one after the highest, at most 999.
     */
    public static function nextSortOrder(): int
    {
        if (! static::query()->exists()) {
            return 1;
        }

        return min((int) static::query()->max('sort_order') + 1, 999);
    }
}
