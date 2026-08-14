# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.1] - 2026-08-14

Fixes a fatal error that broke PDF export on MediaWiki 1.43 and newer, and resolves
outstanding dependency security alerts.

### Changed

- Resolve 17 Dependabot security alerts in development/lint tooling dependencies (no runtime impact) [`311cb0d`](https://github.com/gesinn-it-pub/mediawiki-extensions-Mpdf/commit/311cb0d)

### Fixed

- Fix a fatal error on every PDF export under MediaWiki 1.43+ (`Call to undefined function Wikimedia\suppressWarnings()`) [`0deb683`](https://github.com/gesinn-it-pub/mediawiki-extensions-Mpdf/commit/0deb683)

[Unreleased]: https://github.com/gesinn-it-pub/mediawiki-extensions-Mpdf/compare/1.0.1...HEAD
[1.0.1]: https://github.com/gesinn-it-pub/mediawiki-extensions-Mpdf/compare/1.0...1.0.1
