<!--
Sync Impact Report:
Version: 1.0.0 (initial constitution - adapted from phpdice)
Modified Principles:
  - Updated all project references: phpdice → phpturntracker
  - Updated package installation: composer require marcowuelser/phpdice → composer require marcowuelser/phpturntracker
  - Updated GitHub repository: github.com/marcowuelser/phpdice → github.com/marcowuelser/phpturntracker
Added Sections: All core principles established (Composer Package Standards, PSR-12 Coding Standards, Test-Driven Development, PHPUnit Testing Coverage, Complete Documentation)
Removed Sections: N/A
Templates Status:
  - ✅ plan-template.md: Constitution Check section fully aligned with all 5 principles
  - ✅ spec-template.md: User scenarios and acceptance criteria format supports TDD requirements
  - ✅ tasks-template.md: Phase structure supports Red-Green-Refactor cycle and user story independence
  - ✅ checklist-template.md: Verified (standard template, no constitution-specific updates needed)
  - ✅ agent-file-template.md: Verified (standard template, no constitution-specific updates needed)
  - N/A .specify/templates/commands/: Directory does not exist (no command files to update)
Project Documentation Status:
  - ⚠ README.md: Does not exist yet (will need creation per Principle V - Complete Documentation)
  - ⚠ composer.json: Not verified (will need creation per Principle I - Composer Package Standards)
  - ⚠ phpunit.xml: Not verified (will need creation per Principle IV - PHPUnit Testing Coverage)
  - ⚠ .php-cs-fixer.php or phpcs.xml: Not verified (will need creation per Principle II - PSR-12 Coding Standards)
Follow-up TODOs:
  - Create README.md with installation instructions and quick start guide
  - Initialize composer.json with proper package metadata
  - Setup PHPUnit configuration (phpunit.xml)
  - Configure PSR-12 enforcement (.php-cs-fixer.php or phpcs.xml)
  - Setup CI/CD pipeline for automated quality checks
-->

# phpturntracker Constitution

## Core Principles

### I. Composer Package Standards

phpturntracker MUST be developed and distributed as a professionally maintained Composer package:

- Package MUST be published and consumable via Composer (packagist.org registration required)
- MUST follow Composer package best practices including proper autoloading (PSR-4)
- MUST include properly configured `composer.json` with complete metadata (name, description, license, authors, keywords)
- MUST declare all dependencies with appropriate version constraints
- MUST support semantic versioning for dependency management
- Package structure MUST enable easy installation via `composer require marcowuelser/phpturntracker`

**Rationale**: As a public library, phpturntracker must integrate seamlessly into PHP projects using the standard package manager. Professional package standards ensure reliability, discoverability, and ease of adoption.

### II. PSR-12 Coding Standards

All code MUST strictly adhere to PSR-12 Extended Coding Style:

- Code style MUST comply with PSR-12 specification (which extends PSR-1)
- Automated linting MUST enforce PSR-12 compliance (php-cs-fixer or phpcs required)
- NO code may be merged that violates PSR-12 standards
- Consistent formatting across entire codebase is NON-NEGOTIABLE
- Include `.php-cs-fixer.php` or `phpcs.xml` configuration in repository

**Rationale**: PSR-12 compliance ensures code consistency, readability, and interoperability with the broader PHP ecosystem. It reduces cognitive load and establishes professional quality standards.

### III. Test-Driven Development (NON-NEGOTIABLE)

TDD is mandatory for all feature development:

- Tests MUST be written BEFORE implementation code
- Red-Green-Refactor cycle MUST be strictly followed:
  1. Write failing test (RED)
  2. Implement minimal code to pass (GREEN)
  3. Refactor while maintaining passing tests (REFACTOR)
- NO implementation code without corresponding failing tests first
- User/stakeholder approval of test scenarios MUST occur before implementation begins
- Tests define the contract and specification for all functionality

**Rationale**: TDD ensures code is testable by design, requirements are clearly understood before coding begins, and regression protection is built-in from day one. This is fundamental to library reliability.

### IV. PHPUnit Testing Coverage

Comprehensive PHPUnit test coverage is required:

- ALL production code MUST be covered by PHPUnit tests
- Test suite MUST include:
  - Unit tests for all classes and methods
  - Integration tests for component interactions
  - Edge case and error condition coverage
- Tests MUST be executable via `composer test` or `vendor/bin/phpunit`
- Minimum code coverage target: 90% (enforced via phpunit.xml configuration)
- PHPUnit configuration (`phpunit.xml`) MUST be included in repository
- Tests MUST run in CI/CD pipeline before any merge

**Rationale**: High test coverage ensures library stability, catches regressions early, and provides confidence for refactoring. PHPUnit is the PHP community standard and enables professional testing practices.

### V. Complete Documentation

User-facing documentation MUST be comprehensive and maintained:

- MUST provide complete user documentation including:
  - README.md with quick start guide and installation instructions
  - API documentation covering all public interfaces
  - Usage examples demonstrating common use cases
  - Code examples that are tested and verified
- Documentation MUST be kept in sync with code changes
- Public API MUST include docblock comments (PHPDoc format)
- SHOULD include contribution guidelines and changelog
- Documentation updates MUST accompany feature implementations

**Rationale**: As a public library, phpturntracker's success depends on developer adoption. Complete, accurate documentation reduces support burden and accelerates user onboarding.

## Quality Assurance Standards

### Static Analysis

- MUST use static analysis tools (PHPStan or Psalm) at strict level
- Type declarations MUST be used wherever possible (strict_types=1)
- Static analysis MUST pass before merge

### Code Review

- All changes MUST undergo code review
- Reviewer MUST verify PSR-12 compliance, test coverage, and TDD adherence
- Constitution compliance MUST be explicitly checked during review

### Continuous Integration

- MUST have automated CI pipeline that runs:
  - PHPUnit test suite
  - PSR-12 style checks
  - Static analysis
  - Code coverage reporting
- All checks MUST pass before merge is allowed

## Package Distribution Requirements

### Public Repository

- Source code MUST be hosted on GitHub (github.com/marcowuelser/phpturntracker)
- MUST include proper LICENSE file (open source license required)
- MUST maintain semantic versioning (MAJOR.MINOR.PATCH)
- Tagged releases MUST be created for all versions

### Composer Registry

- MUST be registered on packagist.org
- Package metadata MUST be accurate and complete
- Version updates MUST be automatically synchronized

### Dependencies

- MUST minimize external dependencies
- All dependencies MUST be justified and documented
- MUST specify compatible PHP versions (minimum version requirement)

## Governance

This constitution supersedes all other development practices and standards for the phpturntracker project.

### Amendment Process

- Amendments require documented rationale and impact analysis
- Constitution version MUST be incremented using semantic versioning:
  - **MAJOR**: Principle removal or incompatible governance changes
  - **MINOR**: New principle additions or material expansions
  - **PATCH**: Clarifications, wording improvements, typo fixes
- All template and documentation dependencies MUST be updated to reflect amendments

### Compliance

- ALL pull requests and code reviews MUST verify compliance with this constitution
- Violations MUST be documented and justified or rejected
- Constitution check MUST be included in implementation plans (see plan-template.md)

### Versioning

Constitution follows semantic versioning aligned with governance impact.

**Version**: 1.0.0 | **Ratified**: 2025-12-03 | **Last Amended**: 2025-12-03
