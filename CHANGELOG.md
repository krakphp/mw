# Change Log

## Unreleased

### Added

- Invoke Library #3

## 0.5.1 - 2017-03-31

### Added

- `toTop`, `toBottom`, `get`, `has` methods
- Additional documentation

### Fixed

- Bug in Stack where unshifting would mess up the stored index values for named entries.
- Bug where removed named entries wouldn't fully be deleted.

## 0.5.0 - 2017-03-11

### Added

- `composer` func which creates composer functions that accept an array of middleware
  and compose into a handler.
- `guard` a generic middleware that will throw an exception when invoked to indicate an
  error in logic.
- `guardedComposer` decorator to automatically add guards when composing a middleware stack

### Changed

- Moved `MwStack` to simply `Stack` and simplified the API
- Removed Pimple integration in favor for the PSR container.

## 0.4.2 - 2017-01-19

### Added

- Added `withName` func to MwStack
- Made the `$name` parameter optional in the MwStack

## 0.4.1 - 2017-01-19

### Added

- Added `withContext` and `withLinkClass` options to MwStack.

## 0.4.0 - 2017-01-04

### Changed

- Made `splitArgs` a public function
- Update documentation to reflect new API and changes.
- Updated `mw\compose` algorithm to use the Link as a linked list instead of the
  nested closures.
- Re-implemented custom invocation via the new Context system.
- Re-designed the meta middleware system with the Link system and added documentation

### Added

- Added new Link and Context entities to provide further customization and features
  to middleware
- Added Context\\PimpleContext to provide better pimple integration

## 0.3.3 - 2017-01-03

### Changed

- Update license year
- Reverted changes from 0.3.1 and 0.3.2 due to bc breaks

## 0.3.2 - 2016-12-26

### Added

- Added the CHANGELOG.md
- Added `methodInvoke`
- A bunch of documentation updates to be more clear and helpful
- Added `MwStack::on`

### Changed

- Several documentation pages to be more explicit
- Moved docs to doc

## 0.3.1 - 2016-12-19

### Added

- Added Custom Invocation into the mw system
- Added even more documentation and helpful
  images for illustration
- Added `pimpleAwareInvoke`

### Changed

- Updated the typehinting to be more forgiving to allow for Custom Invocation

## 0.3.0 - 2016-11-27

### Changed

- Better Error Handling
- Added full documentation with rtd integration
- Better typehinting
- Improvements to before/after
- More verbose function arguments

## 0.2.0 - 2016-11-23

### Changed

- Removed all http related information
- Added MwStack for powerful interface
  for creating mw stacks
- Used php5.6 feature to allow for multi
  argument middleware
