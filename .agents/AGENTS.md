# Workspace Rules

## Testing & Database Safety
- **Never run tests against or clear/truncate the primary development database (`db_project_management`)**.
- Always ensure tests run in an isolated environment or dedicated test database so user development data is completely preserved.
