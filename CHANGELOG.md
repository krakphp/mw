# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Changed

- Add license year
- Reverted changes from 0.3.1 and 0.3.2 due to bc breaks

## [0.3.2] - 2016-12-26
### Added

- Added the CHANGELOG.md
- Added `methodInvoke`
- A bunch of documentation updates to be more clear and helpful
- Added `MwStack::on`

### Changed

- Several documentation pages to be more explicit
- Moved docs to doc

## [0.3.1] - 2016-12-19
### Added

- Added Custom Invocation into the mw system
- Added even more documentation and helpful
  images for illustration
- Added `pimpleAwareInvoke`

### Changed

- Updated the typehinting to be more forgiving to allow for Custom Invocation

## [0.3.0] - 2016-11-27
### Changed

- Better Error Handling
- Added full documentation with rtd integration
- Better typehinting
- Improvements to before/after
- More verbose function arguments

## [0.2.0] - 2016-11-23
### Changed

- Removed all http related information
- Added MwStack for powerful interface
  for creating mw stacks
- Used php5.6 feature to allow for multi
  argument middleware