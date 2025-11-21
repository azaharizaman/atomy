# Requirements: Manufacturing

Total Requirements: 86

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0039 | A BOM must have at least one component |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0046 | BOM components cannot reference the parent product (circular BOM prevention) |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0053 | Only one BOM per product can be active at a time |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0060 | Work order quantity completed + quantity scrapped cannot exceed quantity ordered |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0067 | Materials can only be issued to work orders in "released" or "in_production" status |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0074 | Work order cannot be completed if material allocations are not fulfilled |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0081 | Operation sequence must be sequential (operation 10 before operation 20) |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0087 | Inspection must pass before work order can be completed |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0093 | Quarantined batches cannot be used in production or sold |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0098 | Standard cost must be calculated before work order release |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0103 | MRP must consider safety stock levels when calculating net requirements |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0108 | Batch genealogy must be captured for all regulated products (pharma, food) |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0113 | Lot/serial numbers must be unique across all tenants (globally unique) |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0118 | Work center capacity cannot be exceeded without approval |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0123 | Routing operations must reference active work centers |  |  |  |  |
| `Nexus\Manufacturing` | Functional Requirement | FUN-MAN-0234 | Define Bill of Materials (BOM) |  |  |  |  |
| `Nexus\Manufacturing` | Functional Requirement | FUN-MAN-0240 | Multi-level BOM support |  |  |  |  |
| `Nexus\Manufacturing` | Functional Requirement | FUN-MAN-0246 | Create work order |  |  |  |  |
| `Nexus\Manufacturing` | Functional Requirement | FUN-MAN-0252 | Material issue (backflush vs manual) |  |  |  |  |
| `Nexus\Manufacturing` | Functional Requirement | FUN-MAN-0258 | Production reporting |  |  |  |  |
| `Nexus\Manufacturing` | Functional Requirement | FUN-MAN-0264 | Work order completion |  |  |  |  |
| `Nexus\Manufacturing` | Functional Requirement | FUN-MAN-0270 | Work order tracking dashboard |  |  |  |  |
| `Nexus\Manufacturing` | Performance Requirement | PER-MAN-0325 | BOM explosion (10-level deep, 500 components) |  |  |  |  |
| `Nexus\Manufacturing` | Performance Requirement | PER-MAN-0332 | Work order creation and material allocation |  |  |  |  |
| `Nexus\Manufacturing` | Performance Requirement | PER-MAN-0339 | Production reporting (backflush 50 components) |  |  |  |  |
| `Nexus\Manufacturing` | Performance Requirement | PER-MAN-0345 | MRP calculation (1000 SKUs, 10,000 transactions) |  |  |  |  |
| `Nexus\Manufacturing` | Performance Requirement | PER-MAN-0351 | Shop floor dashboard (100 active work orders) |  |  |  |  |
| `Nexus\Manufacturing` | Reliability Requirement | REL-MAN-0387 | All inventory transactions MUST be ACID-compliant |  |  |  |  |
| `Nexus\Manufacturing` | Reliability Requirement | REL-MAN-0393 | Production reporting MUST prevent double-counting |  |  |  |  |
| `Nexus\Manufacturing` | Reliability Requirement | REL-MAN-0399 | Work order state changes MUST be resumable after failure |  |  |  |  |
| `Nexus\Manufacturing` | Reliability Requirement | REL-MAN-0405 | BOM explosion MUST handle circular references |  |  |  |  |
| `Nexus\Manufacturing` | Security and Compliance Requirement | SEC-MAN-0439 | Tenant data isolation |  |  |  |  |
| `Nexus\Manufacturing` | Security and Compliance Requirement | SEC-MAN-0445 | Role-based access control |  |  |  |  |
| `Nexus\Manufacturing` | Security and Compliance Requirement | SEC-MAN-0451 | Production data integrity |  |  |  |  |
| `Nexus\Manufacturing` | Security and Compliance Requirement | SEC-MAN-0457 | Traceability compliance |  |  |  |  |
| `Nexus\Manufacturing` | Security and Compliance Requirement | SEC-MAN-0463 | Quality data protection |  |  |  |  |
| `Nexus\Manufacturing` | User Story | USE-MAN-0505 | As a production planner, I want to define a bill of materials (BOM) for a finished product listing all required components |  |  |  |  |
| `Nexus\Manufacturing` | User Story | USE-MAN-0512 | As a production planner, I want to create a work order specifying what to produce, quantity, and due date |  |  |  |  |
| `Nexus\Manufacturing` | User Story | USE-MAN-0519 | As a shop floor supervisor, I want to release a work order to the floor and issue raw materials to production |  |  |  |  |
| `Nexus\Manufacturing` | User Story | USE-MAN-0526 | As a machine operator, I want to report production output (quantity completed, quantity scrapped) |  |  |  |  |
| `Nexus\Manufacturing` | User Story | USE-MAN-0533 | As a machine operator, I want to record material consumption (actual qty used vs BOM standard) |  |  |  |  |
| `Nexus\Manufacturing` | User Story | USE-MAN-0539 | As a shop floor supervisor, I want to complete a work order and move finished goods to inventory |  |  |  |  |
| `Nexus\Manufacturing` | User Story | USE-MAN-0545 | As a production manager, I want to view work order status (planned, released, in production, completed) |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0039 | A BOM must have at least one component |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0046 | BOM components cannot reference the parent product (circular BOM prevention) |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0053 | Only one BOM per product can be active at a time |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0060 | Work order quantity completed + quantity scrapped cannot exceed quantity ordered |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0067 | Materials can only be issued to work orders in "released" or "in_production" status |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0074 | Work order cannot be completed if material allocations are not fulfilled |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0081 | Operation sequence must be sequential (operation 10 before operation 20) |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0087 | Inspection must pass before work order can be completed |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0093 | Quarantined batches cannot be used in production or sold |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0098 | Standard cost must be calculated before work order release |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0103 | MRP must consider safety stock levels when calculating net requirements |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0108 | Batch genealogy must be captured for all regulated products (pharma, food) |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0113 | Lot/serial numbers must be unique across all tenants (globally unique) |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0118 | Work center capacity cannot be exceeded without approval |  |  |  |  |
| `Nexus\Manufacturing` | Business Requirements | BUS-MAN-0123 | Routing operations must reference active work centers |  |  |  |  |
| `Nexus\Manufacturing` | Functional Requirement | FUN-MAN-0234 | Define Bill of Materials (BOM) |  |  |  |  |
| `Nexus\Manufacturing` | Functional Requirement | FUN-MAN-0240 | Multi-level BOM support |  |  |  |  |
| `Nexus\Manufacturing` | Functional Requirement | FUN-MAN-0246 | Create work order |  |  |  |  |
| `Nexus\Manufacturing` | Functional Requirement | FUN-MAN-0252 | Material issue (backflush vs manual) |  |  |  |  |
| `Nexus\Manufacturing` | Functional Requirement | FUN-MAN-0258 | Production reporting |  |  |  |  |
| `Nexus\Manufacturing` | Functional Requirement | FUN-MAN-0264 | Work order completion |  |  |  |  |
| `Nexus\Manufacturing` | Functional Requirement | FUN-MAN-0270 | Work order tracking dashboard |  |  |  |  |
| `Nexus\Manufacturing` | Performance Requirement | PER-MAN-0325 | BOM explosion (10-level deep, 500 components) |  |  |  |  |
| `Nexus\Manufacturing` | Performance Requirement | PER-MAN-0332 | Work order creation and material allocation |  |  |  |  |
| `Nexus\Manufacturing` | Performance Requirement | PER-MAN-0339 | Production reporting (backflush 50 components) |  |  |  |  |
| `Nexus\Manufacturing` | Performance Requirement | PER-MAN-0345 | MRP calculation (1000 SKUs, 10,000 transactions) |  |  |  |  |
| `Nexus\Manufacturing` | Performance Requirement | PER-MAN-0351 | Shop floor dashboard (100 active work orders) |  |  |  |  |
| `Nexus\Manufacturing` | Reliability Requirement | REL-MAN-0387 | All inventory transactions MUST be ACID-compliant |  |  |  |  |
| `Nexus\Manufacturing` | Reliability Requirement | REL-MAN-0393 | Production reporting MUST prevent double-counting |  |  |  |  |
| `Nexus\Manufacturing` | Reliability Requirement | REL-MAN-0399 | Work order state changes MUST be resumable after failure |  |  |  |  |
| `Nexus\Manufacturing` | Reliability Requirement | REL-MAN-0405 | BOM explosion MUST handle circular references |  |  |  |  |
| `Nexus\Manufacturing` | Security and Compliance Requirement | SEC-MAN-0439 | Tenant data isolation |  |  |  |  |
| `Nexus\Manufacturing` | Security and Compliance Requirement | SEC-MAN-0445 | Role-based access control |  |  |  |  |
| `Nexus\Manufacturing` | Security and Compliance Requirement | SEC-MAN-0451 | Production data integrity |  |  |  |  |
| `Nexus\Manufacturing` | Security and Compliance Requirement | SEC-MAN-0457 | Traceability compliance |  |  |  |  |
| `Nexus\Manufacturing` | Security and Compliance Requirement | SEC-MAN-0463 | Quality data protection |  |  |  |  |
| `Nexus\Manufacturing` | User Story | USE-MAN-0505 | As a production planner, I want to define a bill of materials (BOM) for a finished product listing all required components |  |  |  |  |
| `Nexus\Manufacturing` | User Story | USE-MAN-0512 | As a production planner, I want to create a work order specifying what to produce, quantity, and due date |  |  |  |  |
| `Nexus\Manufacturing` | User Story | USE-MAN-0519 | As a shop floor supervisor, I want to release a work order to the floor and issue raw materials to production |  |  |  |  |
| `Nexus\Manufacturing` | User Story | USE-MAN-0526 | As a machine operator, I want to report production output (quantity completed, quantity scrapped) |  |  |  |  |
| `Nexus\Manufacturing` | User Story | USE-MAN-0533 | As a machine operator, I want to record material consumption (actual qty used vs BOM standard) |  |  |  |  |
| `Nexus\Manufacturing` | User Story | USE-MAN-0539 | As a shop floor supervisor, I want to complete a work order and move finished goods to inventory |  |  |  |  |
| `Nexus\Manufacturing` | User Story | USE-MAN-0545 | As a production manager, I want to view work order status (planned, released, in production, completed) |  |  |  |  |
