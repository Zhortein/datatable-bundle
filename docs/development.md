# Development workflow

## Install dependencies

```bash
composer install
```

With local Docker tooling:

```bash
make installdeps
```

## Run tests

```bash
composer test
```

Or:

```bash
make test
```

## Run PHPStan

```bash
composer phpstan
```

Or:

```bash
make phpstan
```

## Run PHP-CS-Fixer

Check only:

```bash
composer cs:check
```

Fix files:

```bash
composer cs:fix
```

Or:

```bash
make csfixer
```

## Run twigcs

```bash
composer twigcs
```

Or:

```bash
make twigcs
```

## Run all quality checks

```bash
composer qa
```

Or:

```bash
make qa
```

## Branch workflow

Recommended workflow:

```text
main
develop
feature/*
docs/*
```

- `main` contains stable releases.
- `develop` contains integrated development work.
- feature and documentation branches are merged into `develop` through pull requests.

## Pull requests

Every pull request should:

- target `develop`;
- pass GitHub Actions CI;
- reference an issue;
- keep scope small;
- update documentation when needed.

## AI-assisted development

AI coding agents must follow `AGENTS.md`.

Recommended issue label:

```text
ai-ready
```

An issue is AI-ready only when:

- the objective is clear;
- the scope is limited;
- out-of-scope items are explicit;
- acceptance criteria are listed;
- relevant architecture decisions are linked.
