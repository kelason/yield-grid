# Database Dumps

I have saved a full SQL dump of your current PostgreSQL database state.

### How to Restore
If you ever need to reset or "seed" your local environment back to this exact state, you can run the following command from the `backend/` directory:

```bash
docker exec -i yield-grid-postgres-1 psql -U yieldgrid -d yieldgrid < database/dumps/yieldgrid_current_state.sql
```
