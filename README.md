# DependencyGraph

| `develop` |
|-----------|
| [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/DependencyGraph/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/DependencyGraph/?branch=develop) |
| [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/DependencyGraph/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/DependencyGraph/?branch=develop) |
| [![Build Status](https://scrutinizer-ci.com/g/Innmind/DependencyGraph/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/DependencyGraph/build-status/develop) |

Tool to help visualize the various dependencies between packages.

It has been created to help maintain the many packages inside this organisation.

## Installation

```sh
composer global require innmind/dependency-graph
```

## Usage

`dependency-graph from-lock`

This command will look for a `composer.lock` in the working directory and generate a file named `dependencies.svg`.

`dependency-graph of {vendor}/{package}`

This will call `packagist.org` to retrieve the last published version of the given package and generate a file named `{vendor}_{package}_dependencies.svg`.

`dependency-graph depends-on {vendor}/{package} {vendor1} {vendorX}`

This will look for all packages inside the vendors `vendor1` and `vendorX` that depend (directly or indirectly) on `{vendor}/{package}` and will generate a file named `{vendor}_{package}_dependents.svg`.

## Examples

`dependency-graph from-lock` of this repository ![](dependencies.svg)

`dependency-graph of innmind/cli` ![](innmind_cli_dependencies.svg)

`dependency-graph depends-on innmind/cli innmind baptouuuu` ![](innmind_cli_dependents.svg)
