# Security release procedure

1. Keep existing OLT and MikroTik TLS behavior as explicitly requested. Do not install prepared device certificates or change device configuration.
2. Verify the shared GitHub/server webhook secret and the restricted deployment runner. These are configured on the live host; unsigned requests must fail.
3. Run the security and selected authentication/billing suites, syntax checks and dependency audits.
4. Commit the reviewed application changes and push main. Let the GitHub webhook deploy; do not start a second SSH deployment.
5. Verify GitHub delivery, deployed commit, deploy log, successful build, no pending migrations, active PHP/nginx services and HTTP/login/device API smoke checks.

The live runner performs Git/Composer/npm/artisan operations as somitysoft, normalizes only the configured runtime directories, and gracefully reloads the configured PHP-FPM service. Its sudo entry allows only the fixed no-argument runner. Nginx upload protection is already installed and HTTP-verified.

SSH password rotation and legacy device certificate verification remain explicitly deferred. Public diagnostic certificate observations and prepared private-key material are not required for deployment. No private keys are stored in this repository.
