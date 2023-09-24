# Changelog

## [Unreleased]

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
