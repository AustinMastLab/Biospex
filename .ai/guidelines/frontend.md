# Frontend Build (BIOSPEX)

- This project builds assets with Vite (`vite.config.js`, `laravel-vite-plugin`) and yarn. Blade views load them with `@vite(...)`.
- `yarn build` writes `public/build`; `yarn dev` runs the dev server. If the user doesn't see a frontend change reflected in the UI, they may need to run `yarn build` or `yarn dev`. Ask them.
- The scripts are ES modules. jQuery and the libraries the pages call are globals set by `resources/js/jquery-global.js` and the `*-globals.js` files, imported first. An inline `<script>` in a Blade view that uses them while the page loads must be `type="module"`, and a CDN jQuery plugin it needs must be `defer`, so both run after the bundles.

