# Development

## Tests and static analysis

```sh
composer dev:all                 # syntax, PHPStan, PHPUnit
composer dev:phpunit-unit        # unit tests
composer dev:phpstan             # static analysis
```

Integration tests against local [Mailpit](https://mailpit.axllent.org/) instances (Docker):
plain SMTP, STARTTLS and implicit TLS with authentication.

```sh
composer dev:mailpit-up          # start instances, see util/mailpit
composer dev:phpunit-mailpit     # start instances if needed, run Mailpit tests
composer dev:mailpit-down        # stop instances
```

## Documentation

This documentation is built with [MkDocs](https://www.mkdocs.org/) and
[Material for MkDocs](https://squidfunk.github.io/mkdocs-material/).

```sh
composer dev:docs                # generate API reference and build site, open doc/Site/index.html
composer dev:docs-serve          # live preview on http://localhost:8000
composer dev:docs-api            # only generate API reference (doc/Manual/api/)
```

| Path | Content |
|------|---------|
| `util/mkdocs.yml` | configuration and navigation |
| `doc/Site/` | built HTML site, **generated** |
| `doc/Manual/` | hand-written pages (guides) and assets |
| `doc/Manual/api/` | API reference, **generated** – do not edit |
| `util/docs/generate-api.php` | generator: reads classes by reflection and their doc blocks |
| `util/docs/examples/` | examples, included into the API page of a class (same path as class) |
| `util/docs/docs.sh` | runs MkDocs: local installation, Python venv or Docker |

MkDocs is taken from the first available source:

1. `mkdocs` in `PATH` (with `mkdocs-material` installed),
2. a Python virtual environment in `util/docs/.venv` (created by `composer dev:docs-install`),
3. the Docker image `squidfunk/mkdocs-material`.

!!! tip "Documenting a class"
    Summary and description of classes and methods come from doc blocks, types from the signatures.
    To add examples to a class page, create a Markdown file in `util/docs/examples/`,
    e.g. `util/docs/examples/Transport/SMTP.md`.
