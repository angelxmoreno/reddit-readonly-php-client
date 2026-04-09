# 01 Runtime Contract And Dependencies

## Goal

Define the package's baseline runtime and install the interfaces and libraries the rest of the implementation will depend on.

## Why This Comes First

Every later ticket depends on these choices:

- supported PHP version
- HTTP abstraction
- cache abstraction
- validation approach

If you skip this step, later code will be built on guesses.

## PHP Refresher

- `require` is for runtime dependencies library consumers need.
- `require-dev` is only for local tooling and tests.
- In PHP libraries, depending on PSR interfaces is preferred over forcing one concrete HTTP or cache package.

## Tasks

1. Confirm the runtime floor in `composer.json`.
   The repo currently targets PHP 8.5.

2. Add runtime interface dependencies:
   - `psr/http-client`
   - `psr/http-message`
   - `psr/http-factory`
   - `psr/simple-cache`

3. Choose a validation strategy.
   Pick one and commit to it:
   - DTOs plus manual validation
   - a mapper/validator library
   - Symfony Validator

4. Update `README.md` only if dependency expectations need to be explained now.

## Deliverables

- updated `composer.json`
- updated `composer.lock`
- a clear note in code or docs about which validation approach the project will use

## Done When

- `composer validate --strict` passes
- `composer check` still passes
- the runtime dependencies reflect the actual architecture
