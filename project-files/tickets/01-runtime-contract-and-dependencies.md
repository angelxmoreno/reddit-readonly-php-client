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
   Use `cuyz/valinor` as the package's mapper/validator, but keep it behind your own small abstraction.
   The goal is:
   - Reddit transport returns decoded arrays
   - an internal mapper service uses Valinor to map arrays into DTOs
   - the rest of the package depends on your DTOs and exceptions, not on Valinor directly

   Why this is the chosen path:
   - simpler than hand-writing every hydrator
   - framework-agnostic
   - gives strong typing without making the public API return raw `stdClass`
   - still leaves room to replace Valinor later if needed

4. Update `README.md` only if dependency expectations need to be explained now.

## Deliverables

- updated `composer.json`
- updated `composer.lock`
- a clear note in code or docs that Valinor is the chosen internal validation/mapping strategy

## Suggested Commit

`chore: define runtime contracts and package dependencies`

## Done When

- `composer validate --strict` passes
- `composer check` still passes
- the runtime dependencies reflect the actual architecture
