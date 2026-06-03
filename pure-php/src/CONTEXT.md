# pure-php/src

```
Bootstrap/Container.php     # wires all use cases + ports
Domain/                     # → Domain/CONTEXT.md
Application/                # → Application/CONTEXT.md
Infrastructure/             # → Infrastructure/CONTEXT.md
```

## Request flow

```
HTTP/CLI → Controller → Use case → Port → Adapter (InMemory | Redis)
```

## Change checklist

1. Domain rules → `Domain/`
2. New outbound need → `Application/Port/*.php`
3. Orchestration → `Application/{Scheduling|Prescription|Expiration}/`
4. Storage/HTTP → `Infrastructure/`
5. Register in `Bootstrap/Container.php`
6. Route in `Infrastructure/Http/*Controller.php` + `bin/console`
