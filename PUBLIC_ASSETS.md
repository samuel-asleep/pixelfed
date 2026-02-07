# Building Frontend Assets

The compiled frontend assets (`public/css/`, `public/js/`) are not tracked in this repository. You need to build them after deployment or when working on the frontend.

## Building Assets

```bash
# Install dependencies
npm install

# Development build (with source maps)
npm run development

# Production build (minified)
npm run production
```

## For 360° Media Support

This feature adds the following libraries:
- **Pannellum** (~300KB) - 360° photo viewer
- **Video.js** (~1.2MB) - Video player
- **videojs-vr** (~800KB) - VR support for 360° videos

These are automatically included when you build the assets.

## Deployment

Make sure to run `npm run production` as part of your deployment process to generate the optimized frontend bundles.
