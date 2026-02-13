<?php

/*
 * Copyright (C) 2014 - 2026, Biospex
 * biospex@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Tests\Unit\Presenters;

class CoveredPresenterAnchorMethods
{
    public static function all(): array
    {
        return [
            // BingoPresenter
            'BingoPresenter::adminShowIcon',
            'BingoPresenter::showIcon',
            'BingoPresenter::editIcon',
            'BingoPresenter::editIconLrg',
            'BingoPresenter::deleteIcon',
            'BingoPresenter::deleteIconLrg',
            'BingoPresenter::twitterIcon',
            'BingoPresenter::facebookIcon',
            'BingoPresenter::contactIcon',

            // EventPresenter
            'EventPresenter::twitterIcon',
            'EventPresenter::facebookIcon',
            'EventPresenter::contactEmailIcon',
            'EventPresenter::eventShowIcon',
            'EventPresenter::eventAdminShowIcon',
            'EventPresenter::eventEditIcon',
            'EventPresenter::eventEditIconLrg',
            'EventPresenter::eventDeleteIcon',
            'EventPresenter::eventDeleteIconLrg',
            'EventPresenter::eventDownloadUsersIconLrg',
            'EventPresenter::eventDownloadDigitizationsIconLrg',

            // EventTeamPresenter
            'EventTeamPresenter::teamJoinUrlIcon',

            // ExpeditionPresenter
            'ExpeditionPresenter::expeditionShowIcon',
            'ExpeditionPresenter::expeditionShowIconLrg',
            'ExpeditionPresenter::expeditionEditIcon',
            'ExpeditionPresenter::expeditionEditIconLrg',
            'ExpeditionPresenter::expeditionDeleteIcon',
            'ExpeditionPresenter::expeditionDeleteIconLrg',
            'ExpeditionPresenter::expeditionToolsIconLrg',
            'ExpeditionPresenter::expeditionDownloadIconLrg',
            'ExpeditionPresenter::expeditionCloneIcon',
            'ExpeditionPresenter::expeditionCloneIconLrg',
            'ExpeditionPresenter::expeditionOcrBtn',
            'ExpeditionPresenter::titleLink',

            // GroupPresenter
            'GroupPresenter::groupProjectIcon',
            'GroupPresenter::groupProjectIconLrg',
            'GroupPresenter::groupShowIcon',
            'GroupPresenter::groupEditIcon',
            'GroupPresenter::groupEditIconLrg',
            'GroupPresenter::groupDeleteIcon',
            'GroupPresenter::groupDeleteIconLrg',
            'GroupPresenter::groupInviteIcon',
            'GroupPresenter::groupInviteIconLrg',

            // PanoptesProjectPresenter
            'PanoptesProjectPresenter::url',
            'PanoptesProjectPresenter::urlLrg',
            'PanoptesProjectPresenter::projectIcon',
            'PanoptesProjectPresenter::projectIconLrg',
            'PanoptesProjectPresenter::projectLink',

            // ProjectPresenter
            'ProjectPresenter::projectPageIcon',
            'ProjectPresenter::projectPageIconLrg',
            'ProjectPresenter::projectEventsIcon',
            'ProjectPresenter::projectEventsIconLrg',
            'ProjectPresenter::twitterIcon',
            'ProjectPresenter::twitterIconLrg',
            'ProjectPresenter::facebookIcon',
            'ProjectPresenter::facebookIconLrg',
            'ProjectPresenter::blogIcon',
            'ProjectPresenter::blogIconLrg',
            'ProjectPresenter::contactEmailIcon',
            'ProjectPresenter::contactEmailIconLrg',
            'ProjectPresenter::projectExpeditionsIcon',
            'ProjectPresenter::projectExpeditionsIconLrg',
            'ProjectPresenter::projectShowIcon',
            'ProjectPresenter::projectShowIconLrg',
            'ProjectPresenter::projectEditIcon',
            'ProjectPresenter::projectEditIconLrg',
            'ProjectPresenter::projectCloneIcon',
            'ProjectPresenter::projectCloneIconLrg',
            'ProjectPresenter::projectDeleteIcon',
            'ProjectPresenter::projectDeleteIconLrg',
            'ProjectPresenter::projectAdminIconLrg',
            'ProjectPresenter::organizationIcon',
            'ProjectPresenter::organizationIconLrg',
            'ProjectPresenter::projectExploreIconLrg',
            'ProjectPresenter::projectAdvertiseIconLrg',
            'ProjectPresenter::projectStatisticsIconLrg',
            'ProjectPresenter::projectImportIconLrg',
            'ProjectPresenter::projectOcrIconLrg',
            'ProjectPresenter::titleLink',

            // ProjectAssetPresenter
            'ProjectAssetPresenter::asset',

            // SiteAssetPresenter
            'SiteAssetPresenter::assetUrl',

            // UserPresenter
            'UserPresenter::email',
        ];
    }

    public static function forPresenter(string $presenter): array
    {
        return collect(self::all())
            ->filter(fn ($entry) => str_starts_with($entry, $presenter.'::'))
            ->map(fn ($entry) => str_replace($presenter.'::', '', $entry))
            ->values()
            ->all();
    }
}
