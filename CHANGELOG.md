# Changelog

## 3.7.3 - 2024-08-03

### Fixed

- Max opened files exhaustion when loading a vendor with a lot of packages

## 3.7.2 - 2024-07-28

### Fixed

- Circular dependency crashing the `depends-on` command by memory exhaustion

## 3.7.1 - 2024-07-28

### Fixed

- Circular dependency crashing the `of` command by memory exhaustion

## 3.7.0 - 2024-07-28

### Added

- `Innmind\DependencyGraph\Package::repository()`

## 3.6.0 - 2024-03-10

### Added

- Support for `innmind/operating-system:~5.0`

## 3.5.1 - 2023-12-02

### Fixed

- When `dot` is not installed it displays a message telling so instead of crashing

## 3.5.0 - 2023-11-26

### Changed

- Requires `innmind/immutable:~5.2`
- Requires `innmind/operating-system:~4.1`
- Requires `innmind/framework:~2.0`

## 3.4.1 - 2023-11-11

### Fixed

- Same relation was displayed multiple times

## 3.4.0 - 2023-09-24

### Added

- Support for `innmind/immutable:~5.0`

### Changed

- It uses asynchronous HTTP calls to fetch package details faster

### Removed

- Support for PHP `8.1`

## 3.3.0 - 2023-01-11

### Changed

- Abandoned packages dependencies are no longer displayed in the vendor graph

## 3.2.0 - 2023-01-08

### Changed

- The vendor graph no longer displays dependencies of dependencies outside the wished vendor (Graphviz had problems generating some big graphs)

## 3.1.0 - 2023-01-01

### Changed

- Abandoned packages that are still relied upon are now displayed with a dotted edge
