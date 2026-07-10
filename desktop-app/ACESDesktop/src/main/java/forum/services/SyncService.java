package forum.services;

/**
 * Offline-first sync engine (stub).
 *
 * Planned responsibilities, driven by the sync_log table:
 *   - push: send locally-created rows (status 'pending') to the server API,
 *           then mark them 'synced' with the returned server_id.
 *   - pull: fetch server-side changes and update the local cache.
 *   - resolve conflicts (status 'conflict') per a last-write-wins / manual policy.
 *
 * Wiring to the Laravel API comes after the token endpoints exist.
 */
public class SyncService {

    public void syncNow() {
        // TODO: push pending sync_log rows, then pull server updates.
        System.out.println("[ACES] SyncService.syncNow() — not yet implemented.");
    }
}
