<?php

namespace Tests\Unit\Presenters;

use App\Models\Bingo;
use App\Models\Expedition;
use App\Models\Group;
use App\Models\PanoptesProject;
use App\Models\Profile;
use App\Models\Project;
use App\Models\ProjectAsset;
use App\Models\SiteAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class, PresenterTestHelpers::class);

/*
|--------------------------------------------------------------------------
| BingoPresenter Tests
|--------------------------------------------------------------------------
*/

it('generates unique aria labels for different bingos', function (string $method) {
    $bingo1 = Bingo::factory()->create(['title' => 'Bingo One']);
    $bingo2 = Bingo::factory()->create(['title' => 'Bingo Two']);

    if ($method === 'contactIcon') {
        $bingo1->update(['contact' => 'test@example.com']);
        $bingo2->update(['contact' => 'test2@example.com']);
    }

    $output1 = $bingo1->present()->$method();
    $output2 = $bingo2->present()->$method();

    $this->assertLinkRenderedAndSafe($output1, [$bingo1->uuid]);
    $this->assertLinkRenderedAndSafe($output2, [$bingo2->uuid]);

    $anchor1 = $this->extractFirstAnchor($output1);
    $anchor2 = $this->extractFirstAnchor($output2);

    $label1 = $this->getAttr($anchor1, 'aria-label');
    $label2 = $this->getAttr($anchor2, 'aria-label');

    expect($label1)->not->toBeEmpty();
    expect($label1)->not->toBe($label2);
})->with(CoveredPresenterAnchorMethods::forPresenter('BingoPresenter'));

it('safely escapes danger strings in BingoPresenter', function (string $dangerString, string $method) {
    $bingo = Bingo::factory()->create(['title' => $dangerString, 'contact' => 'test@example.com']);
    $output = $bingo->present()->$method();
    $this->assertLinkRenderedAndSafe($output);
    expect($output)->toContain(e($dangerString));
})->with('dangerStrings')->with([
    'twitterIcon', 'facebookIcon',
]);

/*
|--------------------------------------------------------------------------
| ProjectPresenter Tests
|--------------------------------------------------------------------------
*/

it('generates unique aria labels for different projects', function (string $method) {
    $project1 = Project::factory()->create(['title' => 'Project One', 'slug' => 'project-one']);
    $project2 = Project::factory()->create(['title' => 'Project Two', 'slug' => 'project-two']);

    if (str_contains($method, 'Events')) {
        $project1->setAttribute('events_count', 1);
        $project2->setAttribute('events_count', 2);
    }

    if (str_contains($method, 'twitter')) {
        $project1->twitter = 'https://twitter.com/one';
        $project2->twitter = 'https://twitter.com/two';
    }

    if (str_contains($method, 'facebook')) {
        $project1->facebook = 'https://facebook.com/one';
        $project2->facebook = 'https://facebook.com/two';
    }

    if (str_contains($method, 'blog')) {
        $project1->blog = 'https://blog.com/one';
        $project2->blog = 'https://blog.com/two';
    }

    if (str_contains($method, 'contactEmail')) {
        $project1->contact_email = 'one@example.com';
        $project2->contact_email = 'two@example.com';
    }

    if (str_contains($method, 'organization')) {
        $project1->organization_website = 'https://org1.com';
        $project2->organization_website = 'https://org2.com';
    }

    $output1 = $project1->present()->$method();
    $output2 = $project2->present()->$method();

    $requireAriaLabel = $method !== 'titleLink';
    $requiredTokens1 = $method === 'titleLink' ? [] : [$project1->slug];
    $requiredTokens2 = $method === 'titleLink' ? [] : [$project2->slug];
    $this->assertLinkEmptyOrSafe($output1, $requiredTokens1, $requireAriaLabel);
    $this->assertLinkEmptyOrSafe($output2, $requiredTokens2, $requireAriaLabel);

    if ($output1 === '' || ! $requireAriaLabel) {
        return;
    }

    $anchor1 = $this->extractFirstAnchor($output1);
    $anchor2 = $this->extractFirstAnchor($output2);

    $label1 = $this->getAttr($anchor1, 'aria-label');
    $label2 = $this->getAttr($anchor2, 'aria-label');

    expect($label1)->not->toBeEmpty();
    expect($label1)->not->toBe($label2);
    // Project uses slug in aria-label
    expect($label1)->toContain($project1->slug);
})->with(CoveredPresenterAnchorMethods::forPresenter('ProjectPresenter'));

it('safely escapes danger strings in ProjectPresenter', function (string $dangerString, string $method) {
    $project = Project::factory()->create([
        'title' => $dangerString,
        'slug' => 'test-slug',
        'twitter' => 'https://twitter.com/test',
        'facebook' => 'https://facebook.com/test',
    ]);
    $output = $project->present()->$method();

    $this->assertLinkEmptyOrSafe($output);

    if ($output === '') {
        return;
    }

    expect($output)->toContain(e($dangerString));
})->with('dangerStrings')->with(['projectPageIcon', 'twitterIcon', 'facebookIcon', 'projectShowIcon']);

/*
|--------------------------------------------------------------------------
| ExpeditionPresenter Tests
|--------------------------------------------------------------------------
*/

it('generates unique aria labels for different expeditions', function (string $method) {
    $exp1 = Expedition::factory()->create(['title' => 'Exp One']);
    $exp2 = Expedition::factory()->create(['title' => 'Exp Two']);

    $output1 = $exp1->present()->$method();
    $output2 = $exp2->present()->$method();

    $requireAriaLabel = ! in_array($method, ['expeditionOcrBtn', 'titleLink']);
    $requiredTokens1 = in_array($method, ['expeditionOcrBtn', 'titleLink']) ? [] : [$exp1->uuid];
    $requiredTokens2 = in_array($method, ['expeditionOcrBtn', 'titleLink']) ? [] : [$exp2->uuid];
    $this->assertLinkRenderedAndSafe($output1, $requiredTokens1, $requireAriaLabel);
    $this->assertLinkRenderedAndSafe($output2, $requiredTokens2, $requireAriaLabel);

    if (! $requireAriaLabel) {
        return;
    }

    $anchor1 = $this->extractFirstAnchor($output1);
    $anchor2 = $this->extractFirstAnchor($output2);

    $label1 = $this->getAttr($anchor1, 'aria-label');
    $label2 = $this->getAttr($anchor2, 'aria-label');

    expect($label1)->not->toBeEmpty();
    expect($label1)->not->toBe($label2);
    expect($label1)->toContain($exp1->uuid);
})->with(CoveredPresenterAnchorMethods::forPresenter('ExpeditionPresenter'));

/*
|--------------------------------------------------------------------------
| GroupPresenter Tests
|--------------------------------------------------------------------------
*/

it('generates unique aria labels for different groups', function (string $method) {
    $group1 = Group::factory()->create(['title' => 'Group One']);
    $group2 = Group::factory()->create(['title' => 'Group Two']);

    $output1 = $group1->present()->$method();
    $output2 = $group2->present()->$method();

    $this->assertLinkRenderedAndSafe($output1, [$group1->uuid ?: (string) $group1->id]);
    $this->assertLinkRenderedAndSafe($output2, [$group2->uuid ?: (string) $group2->id]);

    $anchor1 = $this->extractFirstAnchor($output1);
    $anchor2 = $this->extractFirstAnchor($output2);

    $label1 = $this->getAttr($anchor1, 'aria-label');
    $label2 = $this->getAttr($anchor2, 'aria-label');

    expect($label1)->not->toBeEmpty();
    expect($label1)->not->toBe($label2);
    // Group uses uuid OR id
    $expected = $group1->uuid ?: (string) $group1->id;
    expect($label1)->toContain($expected);
})->with(CoveredPresenterAnchorMethods::forPresenter('GroupPresenter'));

/*
|--------------------------------------------------------------------------
| UserPresenter Tests
|--------------------------------------------------------------------------
*/

it('generates unique aria labels for different users', function () {
    $user1 = User::factory()->create(['email' => 'user1@example.com']);
    Profile::factory()->create(['user_id' => $user1->id, 'first_name' => 'User', 'last_name' => 'One']);

    $user2 = User::factory()->create(['email' => 'user2@example.com']);
    Profile::factory()->create(['user_id' => $user2->id, 'first_name' => 'User', 'last_name' => 'Two']);

    $output1 = $user1->present()->email();
    $output2 = $user2->present()->email();

    $this->assertLinkEmptyOrSafe($output1, [$user1->email]);
    $this->assertLinkEmptyOrSafe($output2, [$user2->email]);

    if ($output1 === '') {
        return;
    }

    $anchor1 = $this->extractFirstAnchor($output1);
    $anchor2 = $this->extractFirstAnchor($output2);

    $label1 = $this->getAttr($anchor1, 'aria-label');
    $label2 = $this->getAttr($anchor2, 'aria-label');

    expect($label1)->not->toBeEmpty();
    expect($label1)->not->toBe($label2);
    expect($label1)->toContain($user1->email);
});

/*
|--------------------------------------------------------------------------
| PanoptesProjectPresenter Tests
|--------------------------------------------------------------------------
*/

it('generates unique aria labels for different panoptes projects', function (string $method) {
    $pp1 = PanoptesProject::factory()->create([
        'title' => 'Pan One',
        'panoptes_project_id' => 101,
        'panoptes_workflow_id' => 1011,
        'slug' => 'slug-one',
    ]);
    $pp2 = PanoptesProject::factory()->create([
        'title' => 'Pan Two',
        'panoptes_project_id' => 102,
        'panoptes_workflow_id' => 1022,
        'slug' => 'slug-two',
    ]);

    $output1 = $pp1->present()->$method();
    $output2 = $pp2->present()->$method();

    $required1 = in_array($method, ['url', 'urlLrg']) ? [(string) $pp1->panoptes_workflow_id] : [$pp1->slug];
    $required2 = in_array($method, ['url', 'urlLrg']) ? [(string) $pp2->panoptes_workflow_id] : [$pp2->slug];

    $this->assertLinkEmptyOrSafe($output1, $required1);
    $this->assertLinkEmptyOrSafe($output2, $required2);

    if ($output1 === '') {
        return;
    }

    $anchor1 = $this->extractFirstAnchor($output1);
    $anchor2 = $this->extractFirstAnchor($output2);

    $label1 = $this->getAttr($anchor1, 'aria-label');
    $label2 = $this->getAttr($anchor2, 'aria-label');

    expect($label1)->not->toBeEmpty();
    expect($label1)->not->toBe($label2);
})->with(CoveredPresenterAnchorMethods::forPresenter('PanoptesProjectPresenter'));

/*
|--------------------------------------------------------------------------
| ProjectAssetPresenter Tests
|--------------------------------------------------------------------------
*/

it('generates unique aria labels for project assets', function () {
    Storage::fake('s3');
    $asset1 = ProjectAsset::factory()->create(['name' => 'Asset One', 'download_path' => 'path1.txt', 'type' => 'File Download']);
    $asset2 = ProjectAsset::factory()->create(['name' => 'Asset Two', 'download_path' => 'path2.txt', 'type' => 'File Download']);

    Storage::disk('s3')->put('path1.txt', 'content');
    Storage::disk('s3')->put('path2.txt', 'content');

    $output1 = $asset1->present()->asset();
    $output2 = $asset2->present()->asset();

    $this->assertLinkEmptyOrSafe($output1, [(string) $asset1->id]);
    $this->assertLinkEmptyOrSafe($output2, [(string) $asset2->id]);

    if ($output1 === '') {
        return;
    }

    $anchor1 = $this->extractFirstAnchor($output1);
    $anchor2 = $this->extractFirstAnchor($output2);

    $label1 = $this->getAttr($anchor1, 'aria-label');
    $label2 = $this->getAttr($anchor2, 'aria-label');

    expect($label1)->not->toBeEmpty();
    expect($label1)->not->toBe($label2);
});

it('generates unique aria labels for site assets', function () {
    Storage::fake('s3');
    $asset1 = SiteAsset::factory()->create(['title' => 'Site Asset One', 'download_path' => 'site1.txt']);
    $asset2 = SiteAsset::factory()->create(['title' => 'Site Asset Two', 'download_path' => 'site2.txt']);

    Storage::disk('s3')->put('site1.txt', 'content');
    Storage::disk('s3')->put('site2.txt', 'content');

    $output1 = $asset1->present()->assetUrl();
    $output2 = $asset2->present()->assetUrl();

    $this->assertLinkEmptyOrSafe($output1, [(string) $asset1->id]);
    $this->assertLinkEmptyOrSafe($output2, [(string) $asset2->id]);

    if ($output1 === '') {
        return;
    }

    $anchor1 = $this->extractFirstAnchor($output1);
    $anchor2 = $this->extractFirstAnchor($output2);

    $label1 = $this->getAttr($anchor1, 'aria-label');
    $label2 = $this->getAttr($anchor2, 'aria-label');

    expect($label1)->not->toBeEmpty();
    expect($label1)->not->toBe($label2);
});
