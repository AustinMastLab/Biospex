---
paths:
  - 'app/Filament/**'
---

# Filament

## Filament resource layout
Admin panel code lives in app/Filament/. Resources are split into Resource.php, Pages/, Schemas/, Tables/, and sometimes RelationManagers/. Panel setup is in app/Providers/Filament/AdminPanelProvider.php; navigation grouping uses app/Filament/Helpers/NavigationConfig.php and app/Filament/Traits/NavigationTrait.php.
