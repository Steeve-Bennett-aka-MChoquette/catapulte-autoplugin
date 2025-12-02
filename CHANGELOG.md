# Changelog

All notable changes to Catapulte-Autoplugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.1] - 2024

### Added
- DeepSeek V3.2 models to OpenRouter budget providers

### Fixed
- Security: Add sanitization callbacks and mask API keys

## [2.0.0] - 2024

### Changed
- Refactor to PHP 8.1+ with strict types and modern features
- Remove free OpenRouter models, keep only paid models

## [1.8.1] - 2024

### Fixed
- OpenRouter model IDs with correct endpoint names

## [1.8.0] - 2024

### Added
- OpenRouter as native API provider
- Expanded OpenRouter models with budget-friendly options for WordPress development
- Image input support with drag and drop files
- Anthropic Claude API support
- Google Gemini API support
- OpenAI Responses API support
- GPT-5 Codex model support via Responses API

### Changed
- Allow no text when image is attached
- Universal strip_code_fences for AI-generated files
- Complex mode for "extend theme" flow
- Differentiate Modify & Extend flows
- Multi-file UI for hooks extender

### Fixed
- Styling adjustments
- Security measures
- Whitespace fixes
- Plugin details modal
- Settings page error

## [1.7.0] - 2024

### Changed
- Break up class-ajax.php into smaller files
- Code cleanup and refactoring
- Prompt adjustments

### Fixed
- Various bug fixes and UI improvements

## [1.0.0] - Initial Release

### Added
- AI-powered plugin generation
- Plugin explanation feature
- Plugin extension feature
- Plugin fixing feature
- Theme extension via hooks
- Settings page for API configuration
- Support for OpenAI API
