<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Support\WebpEncoder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class ProjectController extends Controller
{
    public function __construct(private WebpEncoder $encoder) {}

    /**
     * Show every project, published or hidden, in display order.
     */
    public function index(): View
    {
        return view('admin.projects.index', [
            'projects' => Project::query()->ordered()->get(),
        ]);
    }

    /**
     * Show the form for adding a project, published and ordered after the others.
     */
    public function create(): View
    {
        return view('admin.projects.form', [
            'project' => new Project([
                'is_published' => true,
                'sort_order' => Project::nextSortOrder(),
                'tags' => [],
            ]),
            'tagSuggestions' => Project::tagSuggestions(),
        ]);
    }

    /**
     * Convert the uploaded image to WebP and store the new project.
     */
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $path = $this->storeImage($request->file('image'));

        try {
            $project = Project::create([
                ...$this->projectAttributes($request),
                'image_path' => $path,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);

            throw $exception;
        }

        return redirect()
            ->route('admin.projects.index')
            ->with('status', "Project “{$project->title}” added.");
    }

    /**
     * Show the form for editing the given project.
     */
    public function edit(Project $project): View
    {
        return view('admin.projects.form', [
            'project' => $project,
            'tagSuggestions' => Project::tagSuggestions(),
        ]);
    }

    /**
     * Save changes to the project, replacing its image when a new one was uploaded.
     */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $oldPath = $project->image_path;
        $newPath = $request->hasFile('image') ? $this->storeImage($request->file('image')) : null;

        try {
            $project->update([
                ...$this->projectAttributes($request),
                ...($newPath === null ? [] : ['image_path' => $newPath]),
            ]);
        } catch (Throwable $exception) {
            if ($newPath !== null) {
                Storage::disk('public')->delete($newPath);
            }

            throw $exception;
        }

        if ($newPath !== null) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()
            ->route('admin.projects.index')
            ->with('status', "Project “{$project->title}” updated.");
    }

    /**
     * Ask the owner to confirm deleting the project.
     */
    public function delete(Project $project): View
    {
        return view('admin.projects.delete', [
            'project' => $project,
        ]);
    }

    /**
     * Delete the project; the model removes its image file.
     */
    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()
            ->route('admin.projects.index')
            ->with('status', "Project “{$project->title}” deleted.");
    }

    /**
     * Get the validated project fields shared by store and update.
     *
     * @return array{title: string, description: string, sort_order: int, tags: list<string>, is_published: bool}
     */
    private function projectAttributes(StoreProjectRequest $request): array
    {
        return [
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'sort_order' => (int) $request->validated('sort_order'),
            'tags' => Project::parseTags($request->validated('tags')),
            'is_published' => $request->boolean('is_published'),
        ];
    }

    /**
     * Convert the upload to WebP and store it on the public disk under a random name.
     *
     * @throws ValidationException When the image cannot be converted.
     */
    private function storeImage(UploadedFile $file): string
    {
        try {
            $bytes = $this->encoder->encode($file->getRealPath());
        } catch (RuntimeException) {
            throw ValidationException::withMessages([
                'image' => 'This image could not be processed. Try saving it again as a JPEG or PNG.',
            ]);
        }

        $path = 'projects/'.Str::random(40).'.webp';
        Storage::disk('public')->put($path, $bytes);

        return $path;
    }
}
