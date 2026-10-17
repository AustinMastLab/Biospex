<?php

namespace App\Filament\Helpers;

class NavigationConfig
{
    /**
     * Navigation group configuration for Filament resources
     * Primary group uses custom sort order, Secondary group uses alphabetical sorting
     */
    private static array $resourceConfig = [
        // Primary Group (Core business entities) - Custom sort order
        'App\Filament\Resources\Groups\GroupResource' => ['group' => 'Primary', 'sort' => 1],
        'App\Filament\Resources\Projects\ProjectResource' => ['group' => 'Primary', 'sort' => 2],
        'App\Filament\Resources\Expeditions\ExpeditionResource' => ['group' => 'Primary', 'sort' => 3],
        'App\Filament\Resources\Subjects\SubjectResource' => ['group' => 'Primary', 'sort' => 4],
        'App\Filament\Resources\Users\UserResource' => ['group' => 'Primary', 'sort' => 5],
        'App\Filament\Resources\Profiles\ProfileResource' => ['group' => 'Primary', 'sort' => 6],
        'App\Filament\Resources\Notices\NoticeResource' => ['group' => 'Secondary', 'sort' => 7],
        'App\Filament\Resources\SiteAssets\SiteAssetResource' => ['group' => 'Primary', 'sort' => 8],
        'App\Filament\Resources\Teams\TeamResource' => ['group' => 'Primary', 'sort' => 9],
        'App\Filament\Resources\Updates\UpdateResource' => ['group' => 'Primary', 'sort' => 10],
        'App\Filament\Resources\WeDigBioEvents\WeDigBioEventResource' => ['group' => 'Primary', 'sort' => 11],

        // Secondary Group (All other resources) - Alphabetical sorting
        'App\Filament\Resources\ActorContacts\ActorContactResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\ActorExpeditions\ActorExpeditionResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\Actors\ActorResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\ActorWorkflows\ActorWorkflowResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\AmCharts\AmChartResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\ApiTokens\ApiTokenResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\Bingos\BingoResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\BingoUsers\BingoUserResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\BingoWords\BingoWordResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\Cities\CityResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\Downloads\DownloadResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\Events\EventResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\EventTeams\EventTeamResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\EventTranscriptions\EventTranscriptionResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\EventUsers\EventUserResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\ExpeditionStats\ExpeditionStatResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\ExportQueueFiles\ExportQueueFileResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\ExportQueues\ExportQueueResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\FailedJobs\FailedJobResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\FaqCategories\FaqCategoryResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\Faqs\FaqResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\GeoLocateCommunities\GeoLocateCommunityResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\GeoLocateDataSources\GeoLocateDataSourceResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\GeoLocateExports\GeoLocateExportResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\GeoLocateForms\GeoLocateFormResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\GroupInvites\GroupInviteResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\Headers\HeaderResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\ImportOccurrences\ImportOccurrenceResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\Imports\ImportResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\Metas\MetaResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\OcrQueueFiles\OcrQueueFileResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\OcrQueues\OcrQueueResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\PanoptesProjects\PanoptesProjectResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\PanoptesTranscriptions\PanoptesTranscriptionResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\ProjectAssets\ProjectAssetResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\PusherTranscriptions\PusherTranscriptionResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\Reconciles\ReconcileResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\StateCounties\StateCountyResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\TeamCategories\TeamCategoryResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\TranscriptionLocations\TranscriptionLocationResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\WeDigBioEventTranscriptions\WeDigBioEventTranscriptionResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\WeDigBioProjects\WeDigBioProjectResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\WorkflowManagers\WorkflowManagerResource' => ['group' => 'Secondary'],
        'App\Filament\Resources\Workflows\WorkflowResource' => ['group' => 'Secondary'],
    ];

    public static function getGroupForResource(string $resourceClass): string
    {
        return self::$resourceConfig[$resourceClass]['group'] ?? 'Secondary';
    }

    public static function getSortForResource(string $resourceClass): ?int
    {
        $config = self::$resourceConfig[$resourceClass] ?? [];
        $group = $config['group'] ?? 'Secondary';

        // Only return sort values for Primary group resources
        if ($group === 'Primary') {
            return $config['sort'] ?? null;
        }

        // Return null for Secondary group to use alphabetical sorting
        return null;
    }

    /**
     * Get all available navigation groups
     */
    public static function getNavigationGroups(): array
    {
        return [
            'Primary',
            'Secondary',
        ];
    }
}
