# <Module Name>

> Copy this file to `docs/modules/<module>.md` when starting a module. Fill every section; delete this line.

## Purpose
What business problem it solves. Link the relevant caps section and quotation module #.

## Responsibilities
- Single-responsibility boundaries — what this module owns and, importantly, what it does **not**.

## Public API
| Method | Route | Role(s) | Description |
|---|---|---|---|

## Domain classes
| Class | Type | Responsibility |
|---|---|---|
| `...Action` | Action | one use case |
| `...Service` | Service | orchestration |
| `...Repository` | Contract + Eloquent impl | data access |

## Data
Tables owned, key columns, relationships (link to [DATA_MODEL](../design/DATA_MODEL.md)).

## Events
- **Emitted:** …
- **Consumed:** …
(link to [EVENTS](../design/EVENTS.md))

## Real-time channels
Channels broadcast to and who may subscribe.

## SOLID notes
How this module applies the principles — especially the extension points (where "add a new X" happens).

## How to extend
Concrete walkthrough: e.g. "To add a new manufacturing stage…".

## Tests
What's covered (feature/unit) and how to run.
