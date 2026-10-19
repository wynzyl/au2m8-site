<?php

use App\Models\Project;
use App\Models\User;
use App\Support\WebpEncoder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

/**
 * @return array<string, mixed>
 */
function validProjectInput(array $overrides = []): array
{
    return array_merge([
        'title' => 'New Portal',
        'description' => 'Customer portal for invoices.',
        'image' => UploadedFile::fake()->image('shot.jpg', 800, 533),
        'tags' => 'Laravel, Tailwind CSS',
        'is_published' => '1',
        'sort_order' => 3,
    ], $overrides);
}

/**
 * Get a seeded project by title, with its image file on the fake public disk.
 */
function seededProject(string $title = 'School Management System'): Project
{
    $project = Project::query()->where('title', $title)->firstOrFail();
    Storage::disk('public')->put($project->image_path, 'x');

    return $project;
}

/**
 * Get the input that re-saves a project unchanged, without a new image.
 *
 * A null value leaves the field out, as a browser does for an unticked checkbox.
 *
 * @return array<string, mixed>
 */
function unchangedProjectInput(Project $project, array $overrides = []): array
{
    return array_filter(array_merge([
        'title' => $project->title,
        'description' => $project->description,
        'tags' => implode(', ', $project->tags),
        'is_published' => $project->is_published ? '1' : null,
        'sort_order' => $project->sort_order,
    ], $overrides), fn (mixed $value): bool => $value !== null);
}

test('guests cannot reach any admin project route and nothing changes', function (string $method, string $routeName, bool $needsProject) {
    $parameters = $needsProject ? [Project::query()->first()] : [];

    $this->call($method, route($routeName, $parameters), validProjectInput())
        ->assertRedirect(route('login'));

    expect(Project::query()->count())->toBe(2);
})->with([
    'index' => ['GET', 'admin.projects.index', false],
    'create' => ['GET', 'admin.projects.create', false],
    'store' => ['POST', 'admin.projects.store', false],
    'edit' => ['GET', 'admin.projects.edit', true],
    'update' => ['PUT', 'admin.projects.update', true],
    'delete' => ['GET', 'admin.projects.delete', true],
    'destroy' => ['DELETE', 'admin.projects.destroy', true],
]);

test('the index lists published and hidden projects', function () {
    Project::factory()->unpublished()->create(['title' => 'Secret Build']);

    $this->actingAs(User::factory()->create())
        ->get(route('admin.projects.index'))
        ->assertOk()
        ->assertSee('School Management System')
        ->assertSee('Healthcare Management System')
        ->assertSeeInOrder(['Secret Build', 'Hidden'])
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

test('index thumbnails after the first are lazy loaded', function () {
    seededProject('School Management System');
    seededProject('Healthcare Management System');

    preg_match_all('/<img[^>]*>/', $this->actingAs(User::factory()->create())
        ->get(route('admin.projects.index'))
        ->getContent(), $matches);

    expect($matches[0])->toHaveCount(2)
        ->and($matches[0][0])->not->toContain('loading="lazy"')
        ->and($matches[0][1])->toContain('loading="lazy"');
});

test('the create form pre-fills the next order, published and tag suggestions', function () {
    $content = $this->actingAs(User::factory()->create())
        ->get(route('admin.projects.create'))
        ->assertOk()
        ->assertSee('Tags already in use:')
        ->getContent();

    expect($content)->toMatch('/<input[^>]*name="sort_order"[^>]*value="3"/')
        ->toMatch('/<input[^>]*name="is_published"[^>]*checked/')
        ->toMatch('/<datalist[^>]*>.*<option value="PostgreSQL">.*<\/datalist>/s');
});

test('an owner can add a project and it appears publicly', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('admin.projects.store'), validProjectInput())
        ->assertRedirect(route('admin.projects.index'))
        ->assertSessionHas('status', 'Project “New Portal” added.');

    $project = Project::query()->where('title', 'New Portal')->firstOrFail();

    expect($project->tags)->toBe(['Laravel', 'Tailwind CSS'])
        ->and($project->image_path)->toMatch('/^projects\/[A-Za-z0-9]{40}\.webp$/')
        ->and(substr(Storage::disk('public')->get($project->image_path), 0, 4))->toBe('RIFF');

    $this->get(route('projects'))->assertSee('New Portal');
});

test('a large upload is scaled down when stored', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('admin.projects.store'), validProjectInput([
            'image' => UploadedFile::fake()->image('photo.jpg', 3264, 2448),
        ]))
        ->assertSessionHasNoErrors();

    $path = Project::query()->where('title', 'New Portal')->value('image_path');
    $size = getimagesizefromstring(Storage::disk('public')->get($path));

    expect([$size[0], $size[1]])->toBe([1632, 1224]);
});

test('updating without a new image keeps the current image', function () {
    $project = seededProject();

    $this->actingAs(User::factory()->create())
        ->put(route('admin.projects.update', $project), unchangedProjectInput($project, [
            'description' => 'An updated description.',
        ]))
        ->assertRedirect(route('admin.projects.index'))
        ->assertSessionHas('status', 'Project “School Management System” updated.');

    $project->refresh();

    expect($project->description)->toBe('An updated description.')
        ->and($project->image_path)->toBe('projects/merryland.webp');
    Storage::disk('public')->assertExists('projects/merryland.webp');
});

test('updating with a new image replaces the old file', function () {
    $project = seededProject();

    $this->actingAs(User::factory()->create())
        ->put(route('admin.projects.update', $project), unchangedProjectInput($project, [
            'image' => UploadedFile::fake()->image('new.png', 600, 400),
        ]))
        ->assertSessionHasNoErrors();

    $project->refresh();

    expect($project->image_path)->not->toBe('projects/merryland.webp');
    Storage::disk('public')->assertExists($project->image_path);
    Storage::disk('public')->assertMissing('projects/merryland.webp');
});

test('the delete page asks for confirmation', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.projects.delete', seededProject()))
        ->assertOk()
        ->assertSee('Delete “School Management System”?')
        ->assertSee('This cannot be undone.');
});

test('deleting a project removes it and its image', function () {
    $project = seededProject();

    $this->actingAs(User::factory()->create())
        ->delete(route('admin.projects.destroy', $project))
        ->assertRedirect(route('admin.projects.index'))
        ->assertSessionHas('status', 'Project “School Management System” deleted.');

    expect(Project::query()->find($project->id))->toBeNull();
    Storage::disk('public')->assertMissing('projects/merryland.webp');

    $this->get(route('projects'))->assertDontSee('School Management System');
});

test('a project that no longer exists redirects to the list', function (string $method, string $routeName) {
    $this->actingAs(User::factory()->create())
        ->call($method, route($routeName, 999), validProjectInput())
        ->assertRedirect(route('admin.projects.index'))
        ->assertSessionHas('status', 'That project no longer exists.');
})->with([
    'edit' => ['GET', 'admin.projects.edit'],
    'update' => ['PUT', 'admin.projects.update'],
    'delete' => ['GET', 'admin.projects.delete'],
    'destroy' => ['DELETE', 'admin.projects.destroy'],
]);

test('invalid input is rejected and nothing is stored', function (string $field, Closure $value) {
    $this->actingAs(User::factory()->create())
        ->from(route('admin.projects.create'))
        ->post(route('admin.projects.store'), validProjectInput([$field => $value()]))
        ->assertRedirect(route('admin.projects.create'))
        ->assertSessionHasErrors($field);

    expect(Project::query()->count())->toBe(2)
        ->and(Storage::disk('public')->allFiles('projects'))->toBeEmpty();
})->with([
    'blank title' => ['title', fn () => ''],
    'title too long' => ['title', fn () => str_repeat('a', 101)],
    'blank description' => ['description', fn () => ''],
    'description too long' => ['description', fn () => str_repeat('a', 501)],
    'missing image' => ['image', fn () => null],
    'image not an image' => ['image', fn () => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')],
    'image over 2 MB' => ['image', fn () => UploadedFile::fake()->image('big.jpg')->size(2049)],
    'image over 4096 pixels' => ['image', fn () => UploadedFile::fake()->image('wide.png', 4097, 10)],
    'more than 10 tags' => ['tags', fn () => implode(', ', range(1, 11))],
    'tag too long' => ['tags', fn () => str_repeat('a', 31)],
    'negative order' => ['sort_order', fn () => -1],
    'order above 999' => ['sort_order', fn () => 1000],
    'order not a number' => ['sort_order', fn () => 'abc'],
]);

test('an invalid update leaves the project unchanged', function () {
    $project = seededProject();

    $this->actingAs(User::factory()->create())
        ->from(route('admin.projects.edit', $project))
        ->put(route('admin.projects.update', $project), unchangedProjectInput($project, ['title' => '']))
        ->assertRedirect(route('admin.projects.edit', $project))
        ->assertSessionHasErrors('title');

    expect($project->refresh()->title)->toBe('School Management System');
});

test('image errors explain the size and type limits', function (Closure $image, string $message) {
    $this->actingAs(User::factory()->create())
        ->post(route('admin.projects.store'), validProjectInput(['image' => $image()]))
        ->assertSessionHasErrors(['image' => $message]);
})->with([
    'over 2 MB' => [fn () => UploadedFile::fake()->image('big.jpg')->size(2049), 'The image must be 2 MB or less.'],
    'not an image' => [fn () => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'), 'The image must be a JPEG, PNG or WebP file.'],
]);

test('an image that cannot be converted is rejected', function () {
    $this->mock(WebpEncoder::class)
        ->shouldReceive('encode')
        ->andThrow(new RuntimeException('The image could not be read.'));

    $this->actingAs(User::factory()->create())
        ->post(route('admin.projects.store'), validProjectInput())
        ->assertSessionHasErrors('image');

    expect(Project::query()->count())->toBe(2);
});

test('a request larger than the server allows goes back with an image error', function () {
    $this->actingAs(User::factory()->create())
        ->withServerVariables(['CONTENT_LENGTH' => (string) (9 * 1024 * 1024)])
        ->from(route('admin.projects.create'))
        ->post(route('admin.projects.store'))
        ->assertRedirect(route('admin.projects.create'))
        ->assertSessionHasErrors(['image' => 'The image must be 2 MB or less.']);
});

test('a lower display order moves a project to the top of the public page', function () {
    $project = seededProject('Healthcare Management System');

    $this->actingAs(User::factory()->create())
        ->put(route('admin.projects.update', $project), unchangedProjectInput($project, ['sort_order' => 0]))
        ->assertSessionHasNoErrors();

    $this->get(route('projects'))
        ->assertSeeInOrder(['Healthcare Management System', 'School Management System']);
});

test('unticking published hides a project publicly but keeps it in the admin list', function () {
    $project = seededProject('Healthcare Management System');
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->put(route('admin.projects.update', $project), unchangedProjectInput($project, ['is_published' => null]))
        ->assertSessionHasNoErrors();

    expect($project->refresh()->is_published)->toBeFalse();

    $this->get(route('projects'))->assertDontSee('Healthcare Management System');

    $this->actingAs($owner)
        ->get(route('admin.projects.index'))
        ->assertSeeInOrder(['Healthcare Management System', 'Hidden']);
});

test('a new project with a tied order is listed after the one added first', function () {
    seededProject('Healthcare Management System');

    $this->actingAs(User::factory()->create())
        ->post(route('admin.projects.store'), validProjectInput(['sort_order' => 2]))
        ->assertSessionHasNoErrors();

    $this->get(route('projects'))
        ->assertSeeInOrder(['Healthcare Management System', 'New Portal']);
});

test('a failed update keeps published unticked when the owner unticked it', function () {
    $project = seededProject();

    $this->actingAs(User::factory()->create())
        ->from(route('admin.projects.edit', $project))
        ->put(route('admin.projects.update', $project), unchangedProjectInput($project, [
            'title' => '',
            'is_published' => null,
        ]))
        ->assertRedirect(route('admin.projects.edit', $project));

    $content = $this->get(route('admin.projects.edit', $project))
        ->assertOk()
        ->assertSee('The title field is required.')
        ->getContent();

    expect($content)->toMatch('/<input[^>]*name="is_published"/')
        ->not->toMatch('/<input[^>]*name="is_published"[^>]*checked/');
});

test('a guest sending a request larger than the server allows is sent to sign in', function () {
    $this->withServerVariables(['CONTENT_LENGTH' => (string) (9 * 1024 * 1024)])
        ->from(route('admin.projects.create'))
        ->post(route('admin.projects.store'))
        ->assertRedirect(route('login'));

    expect(Project::query()->count())->toBe(2);
});

test('every admin project screen is hidden from search engines', function (string $routeName, bool $needsProject) {
    $parameters = $needsProject ? [Project::query()->first()] : [];

    $this->actingAs(User::factory()->create())
        ->get(route($routeName, $parameters))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
})->with([
    'index' => ['admin.projects.index', false],
    'create' => ['admin.projects.create', false],
    'edit' => ['admin.projects.edit', true],
    'delete' => ['admin.projects.delete', true],
]);
