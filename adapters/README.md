# Adapters Directory

This directory contains adapter packages that bridge Nexus packages with external systems.

Adapters are responsible for:
- Framework-specific implementations
- Third-party API integrations
- Database/ORM adapters

## Creating an Adapter

1. Create a new directory: `adapters/YourAdapter/`
2. Initialize with `composer init` (name: `nexus/your-adapter`)
3. Depend on required Nexus packages and framework libraries
4. Implement interfaces defined in domain packages

## Structure

```
adapters/
├── LaravelAdapter/        # Laravel framework bindings
├── SymfonyAdapter/        # Symfony framework bindings
├── EloquentAdapter/       # Eloquent ORM implementations
├── DoctrineAdapter/       # Doctrine ORM implementations
└── TwilioAdapter/         # Twilio SMS/Voice integration
```

## Guidelines

1. Adapters MAY depend on framework-specific code
2. Adapters MUST implement interfaces from domain packages
3. Adapters SHOULD be thin wrappers around framework functionality
4. Domain logic belongs in domain packages, not adapters
