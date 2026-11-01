# Repository-local agent skills

Codex discovers skills from `.agents/skills/<skill-name>/SKILL.md` in the
repository. The included directories retain their source `SKILL.md`, compiled
guides, README files, and rule references. They contain no executable scripts
or runtime dependencies.

| Skill | Source | Declared license and attribution |
| --- | --- | --- |
| `laravel-best-practices` | User-global installed skill; upstream package: [AsyrafHussin/agent-skills](https://github.com/AsyrafHussin/agent-skills) | MIT; source metadata says Laravel Community; upstream repository license names Asyraf Hussin |
| `laravel-database-optimization` | User-global installed skill; upstream package: [AsyrafHussin/agent-skills](https://github.com/AsyrafHussin/agent-skills) | MIT; source metadata says `agent-skills`; upstream repository license names Asyraf Hussin |
| `testing-best-practices` | Larissama's read-only repository reference | `SKILL.md` declares MIT and metadata author `laravel`; no standalone license file or copyright year was present in the supplied source |

The first two skills' complete directories were copied without changing their
content. Their MIT license files were added from the upstream package notice.
The testing skill's content is also unchanged; its local MIT notice records
the attribution supplied in its frontmatter and preserves the source's
missing-copyright-year limitation. Recheck provenance and license metadata
before upgrading or redistributing these copies outside this repository.

Use the local `laravel-best-practices` skill for Laravel implementation,
`laravel-database-optimization` for database and query work, and
`testing-best-practices` for test design. Root `AGENTS.md`, Temuka's approved
stack, Docker-only command requirements, and its disposable MySQL testing
environment override conflicting generic examples.
