# Gemini CLI — Claude Code Context

Gemini CLI is an open-source terminal AI agent built on Google's Gemini models.
It provides a rich interactive terminal UI, scripting/headless mode, built-in
developer tools, MCP (Model Context Protocol) extensibility, and VS Code
integration.

---

## Repository Structure

This is a **monorepo** using npm workspaces with 8 packages under `packages/`:

| Package | Purpose |
|---------|---------|
| `packages/cli` | Terminal UI, React/Ink rendering, user-facing input/output |
| `packages/core` | Backend logic, Gemini API client, tool execution, prompt construction |
| `packages/sdk` | Public SDK for programmatic/embedding use |
| `packages/a2a-server` | Experimental Agent-to-Agent server |
| `packages/vscode-ide-companion` | VS Code extension paired with the CLI |
| `packages/devtools` | Development tools and utilities |
| `packages/test-utils` | Shared testing utilities across packages |

**Key top-level files/dirs:**

```
bundle/           # esbuild output (published CLI executable)
docs/             # 86+ markdown documentation files
evals/            # Model evaluation tests
integration-tests/ # End-to-end integration tests
scripts/          # Build and automation scripts
schemas/          # JSON schema definitions
esbuild.config.js # Bundle configuration
eslint.config.js  # ESLint flat config (v9)
tsconfig.json     # Base TypeScript configuration
Makefile          # Development shortcuts
GEMINI.md         # Context file for Gemini CLI itself (analogous to this file)
```

---

## Architecture Overview

### CLI Package (`packages/cli/src/`)

- `gemini.tsx` — Main entry point; bootstraps the React/Ink app
- `nonInteractiveCli.ts` — Headless/scripting mode handler (JSON output)
- `ui/` — React/Ink components (`AppContainer`, hooks, context providers)
- `commands/` — Slash commands (`/help`, `/chat`, `/clear`, etc.)
- `config/` — CLI argument parsing, settings loading, authentication
- `services/` — Session management, configuration services
- `utils/` — Cleanup, sandbox, stdin handling, window title utilities

### Core Package (`packages/core/src/`)

- `core/` — LLM client, content generation, turn management
- `tools/` — Built-in tool implementations (file ops, shell, web fetch, MCP)
- `routing/` — Model routing logic
- `policy/` — TOML-based execution policy engine with approval modes
- `agents/` — Agent scheduling and execution
- `mcp/` — Model Context Protocol client and OAuth
- `skills/` — Custom skill system (extensibility via `~/.gemini/skills/`)
- `services/` — File discovery, Git integration, shell execution
- `prompts/` — System prompts and context construction
- `telemetry/` — OpenTelemetry event logging and analytics
- `config/` — Configuration management and storage
- `safety/` — Safety checks and validation
- `billing/` — Quota and billing management
- `code_assist/` — Vertex AI Code Assist integration (OAuth)

---

## Build, Run, and Test

### Prerequisites

- Node.js >=20.0.0 (recommended: ~20.19.0)
- npm (uses npm workspaces; Google Artifact Registry configured via `.npmrc`)

### Common Commands

```bash
# Install dependencies
npm install

# Build packages (TypeScript compilation)
npm run build

# Full build: packages + sandbox + VS Code companion
npm run build:all

# Bundle CLI with esbuild (produces bundle/gemini.js)
npm run bundle

# Run in development mode
npm run start

# Run with Node.js inspector attached
npm run debug

# Clean all build artifacts
npm run clean
```

### Testing

```bash
# Run all unit tests
npm run test

# Run CI test suite
npm run test:ci

# Run a specific workspace's tests (path is relative to workspace root)
npm test -w @google/gemini-cli-core -- src/routing/modelRouterService.test.ts

# Run integration/e2e tests
npm run test:e2e
npm run test:integration:all

# Run evaluation tests
npm run test:always_passing_evals
RUN_EVALS=1 npm run test:all_evals
```

### Code Quality

```bash
# Lint check
npm run lint

# Auto-fix lint issues
npm run lint:fix

# Format with Prettier
npm run format

# Type check
npm run typecheck

# Full validation (clean → install → format → build → lint → typecheck → test)
# Only run at the END of a task; it is slow
npm run preflight
```

> **Preflight note:** Run `npm run preflight` only at the very end of a code
> implementation task. If it fails, use faster targeted commands (`npm run test`,
> `npm run lint`, workspace-specific tests) to iterate before re-running
> preflight. For non-code changes (docs, prompt updates), skip preflight and let
> PR CI validate instead.

---

## Development Conventions

### TypeScript

- **Strict mode** is enabled globally: `noImplicitAny`, `noUnusedLocals`,
  `strict`, `forceConsistentCasingInFileNames`, etc.
- Target: ES2022. Module: NodeNext. JSX: react-jsx.
- Never use `any` — use proper types or `unknown`.
- Prefer named exports; avoid default exports (ESLint warns on them).

### License Headers

All new `.ts`, `.tsx`, and `.js` source files **must** include the Apache-2.0
license header with the current year. This is enforced by ESLint:

```typescript
// Copyright 2026 Google LLC
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//     http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.
```

### Naming Conventions

- `PascalCase` for React components and classes
- `camelCase` for functions, variables, and module exports
- `UPPER_SNAKE_CASE` for module-level constants
- Prefix truly private members with `_` where needed

### File Organization

- **Collocate tests**: `feature.ts` and `feature.test.ts` live in the same
  directory
- UI components belong in `ui/` subdirectories
- Utilities belong in `utils/` subdirectories
- Each package is self-contained with its own `tsconfig.json` and
  `package.json`
- Build output goes to `dist/` inside each package; bundle output to `bundle/`
  at root

### Imports

- Use specific imports; avoid wildcard `import *` patterns
- Do not use restricted relative imports across packages (ESLint enforced)
- Import from package names (e.g., `@google/gemini-cli-core`) not relative
  paths when crossing package boundaries

### Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
feat(core): add streaming support for tool results
fix(cli): correct cursor position after resize
docs: update MCP configuration examples
chore(deps): bump @google/genai to 1.42.0
```

---

## Testing Conventions

- **Framework:** Vitest (forks pool, 60s timeout)
- **Globals enabled:** No need to import `describe`, `it`, `expect`, etc.
- **Environment variables:** Use `vi.stubEnv('NAME', 'value')` in `beforeEach`
  and `vi.unstubAllEnvs()` in `afterEach`. Never mutate `process.env` directly
  — it causes test leakage. To unset a variable, stub it with an empty string.
- **Snapshots:** Stored in `__snapshots__/` directories alongside tests
- **Coverage:** v8 provider; reports in HTML, JSON, LCOV, Cobertura
- **CI output:** JUnit XML via `junit.xml` in each package

---

## Key Architectural Patterns

1. **Tool Registry** — Tools are declared as classes extending abstract base
   types in `packages/core/src/tools/`. Each tool specifies its schema, name,
   description, and `execute()` method.

2. **Execution Policy** — TOML-based policy engine controls approval modes
   (automatic, suggest, manual) per tool category. Configured in
   `packages/core/src/policy/`.

3. **MCP Extensibility** — The CLI supports MCP servers for custom tools via
   `packages/core/src/mcp/`. Users configure MCP servers in their settings.

4. **Skills System** — Custom slash commands can be placed in
   `~/.gemini/skills/` as Markdown files. See `packages/core/src/skills/`.

5. **React/Ink UI** — Terminal rendering uses React 19 + Ink. Context providers
   (`SettingsContext`, `KeyPressContext`, etc.) are in `packages/cli/src/ui/`.

6. **Content Generation Abstraction** — `packages/core/src/core/` abstracts
   over the Gemini API with recording, logging, and replay variants.

7. **Event-Driven Communication** — Core and CLI communicate via a custom event
   system rather than direct coupling.

---

## Documentation

- All user-facing documentation lives in `docs/` (86+ Markdown files).
- Use the `docs-writer` skill when writing, editing, or reviewing docs.
- Suggest documentation updates when code changes make existing docs obsolete.
- Key docs:
  - `docs/architecture.md` — Architecture overview
  - `docs/local-development.md` — Development setup
  - `docs/integration-tests.md` — Testing guide
  - `docs/cli/` — Commands, keyboard shortcuts, sandboxing, enterprise

---

## Legacy Code Note

`packages/core/src/prompts/snippets.legacy.ts` is a snapshot of an older
system prompt. **Do not change the prompting verbiage** — it preserves
historical behavior. Structural changes for compilation or simplification are
allowed.

---

## Pull Requests

- Keep PRs small, focused, and linked to an existing GitHub issue.
- Always activate the `pr-creator` skill for PR generation, even when using
  `gh` CLI directly.
- All PRs require review; automated frontend review is available via
  `/review-frontend <PR_NUMBER>` for changes in `packages/cli`.
- Run `npm run preflight` before submitting (for code changes).

---

## CI/CD

GitHub Actions workflows are in `.github/workflows/` (18+ workflows):

- `ci.yml` — Main pipeline: lint, typecheck, unit tests, integration tests
- `chained_e2e.yml` — End-to-end integration testing
- `release-*.yml` — Release automation (patch, promote, rollback, manual)
- `smoke-test.yml` — Quick validation
- `eval.yml` / `evals-nightly.yml` — Model evaluation tests
- `deflake.yml` — Flaky test detection

---

## Key Dependencies

| Package | Role |
|---------|------|
| `@google/genai` | Gemini API client |
| `@modelcontextprotocol/sdk` | MCP server/client protocol |
| `ink` | React renderer for terminal UIs |
| `react` | UI framework (v19) |
| `google-auth-library` | Google OAuth/authentication |
| `yargs` | CLI argument parsing |
| `simple-git` | Git operations |
| `zod` | Schema validation |
| `esbuild` | Bundler for distribution |
| `vitest` | Test runner |
| `eslint` + `prettier` | Linting and formatting |
| `@opentelemetry/*` | Telemetry instrumentation |
