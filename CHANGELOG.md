# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Phan static analysis, wired into CI as a dedicated step on the coverage matrix row.
- `1.46`/PHP 8.5 experimental CI leg, alongside the existing `1.39` and `1.43` legs.
- `CHANGELOG.md` (this file).

### Changed

- CI matrix reduced to the three legs actually maintained (`1.39`/PHP 8.1, `1.43`/PHP 8.2
  coverage, `1.46`/PHP 8.5 experimental), dropping the EOL `1.35`/PHP 7.4, `1.40`, `1.41`,
  and `1.42` legs.
- `runs-on` pinned to `ubuntu-24.04`; `actions/checkout` bumped to `v6`; `codecov/codecov-action`
  bumped to `v7` with an explicit `slug` and `disable_search: true`.
- `ci.yml` now skips runs that only touch `.adoc` files.
- Bumped `mediawiki/mediawiki-codesniffer` from 43.0.0 to 48.0.0 and
  `mediawiki/mediawiki-phan-config` from 0.14.0 to 0.16.0, matching the versions
  used across the org's other extensions.
- `.env-39`/`.env-40` removed in favour of the CI matrix `env:` block and `Makefile` defaults.
- `Gruntfile.js`/`package.json` now exclude `coverage/`/`build/coverage/` from ESLint and expose a
  `test-coverage` npm script, since `make ci-coverage` calls it unconditionally.
- `MpdfAction::show()`'s filename-sanitization, mPDF-configuration parsing, and
  image-to-data-URI logic extracted into private static helpers (`sanitizeFilename()`,
  `parseMpdfConfig()`, `inlineImagesAsDataUris()`/`imageSrcToDataUri()`) so they can be
  unit tested directly; behavior is unchanged.
- `MpdfHooks::mpdftagsRender()` changed from an untyped `&$parser` parameter plus
  `func_get_args()` to a typed `Parser $parser, ...$params` signature, matching the
  pattern used by sibling parser-function hooks in this org.

### Fixed

- `Makefile`: `OS_PACKAGES`/`PHP_EXTENSIONS` were quoted in this extension's `Makefile` on
  top of `build/Makefile` already quoting them, producing doubled quotes that made the
  shell try to execute `libpng-dev` as a command — `make ci` failed before any test could
  run.
- `tests/phpunit/Unit/MpdfHooksTest.php` referenced a non-existent `MyExtensionHooks` class
  instead of `MpdfHooks`, which would have fataled on first run.
- Removed a test that passed a `stdClass` mock where `SkinTemplate` is type-hinted (a hard
  `TypeError`) and relied on `setService( 'wfMessage', ... )`/`setMwGlobals( 'wgHooks', ... )`
  under `MediaWikiUnitTestCase`, which cannot work since `wfMessage()` requires a real
  `MessageCache`. Replaced with a `MediaWikiIntegrationTestCase` covering both the
  enabled and disabled `MpdfTab` branches.
- `tests/phpunit/Unit/MpdfActionTest.php` contained tests that mocked their own return
  values instead of exercising `MpdfAction`, reimplemented production logic inline instead
  of calling it, and wrote real PDF files to disk during a unit test run. Replaced with
  tests against the extracted helper methods and a `MediaWikiIntegrationTestCase` for
  `getName()`.
- Removed the dead `wfSuppressWarnings()`/`wfRestoreWarnings()` pre-MW-1.31 fallback from
  `MpdfAction::show()`, since this extension's minimum supported version is now MW 1.39.

[Unreleased]: https://github.com/gesinn-it-pub/mediawiki-extensions-Mpdf/compare/1e5378d...HEAD
