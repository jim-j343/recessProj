package forum.database;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.util.ArrayList;
import java.util.List;

/**
 * Generic helpers over the local cache, including the offline sync queue
 * ({@code sync_log}). Feature DAOs (topics, posts…) use this to record
 * locally-created rows that still need to be pushed to the server.
 */
public class LocalCacheDAO {

    /** One pending row awaiting upload. */
    public record PendingSync(long syncId, String table, long localId) { }

    /** Records that a locally-created row needs to be pushed to the server. */
    public void queueForSync(String tableName, long localId) {
        String sql = "INSERT INTO sync_log(table_name, local_id, status) VALUES(?,?, 'pending')";
        try (Connection c = SQLiteConnection.get();
             PreparedStatement ps = c.prepareStatement(sql)) {
            ps.setString(1, tableName);
            ps.setLong(2, localId);
            ps.executeUpdate();
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    /** All rows still waiting to sync, oldest first. */
    public List<PendingSync> pending() {
        String sql = "SELECT sync_id, table_name, local_id FROM sync_log "
                + "WHERE status = 'pending' ORDER BY sync_id ASC";
        List<PendingSync> out = new ArrayList<>();
        try (Connection c = SQLiteConnection.get();
             Statement st = c.createStatement();
             ResultSet rs = st.executeQuery(sql)) {
            while (rs.next()) {
                out.add(new PendingSync(rs.getLong(1), rs.getString(2), rs.getLong(3)));
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return out;
    }

    /** Marks a queue row uploaded and records the server id it received. */
    public void markSynced(long syncId, long serverId) {
        String sql = "UPDATE sync_log SET status = 'synced', server_id = ?, "
                + "synced_at = CURRENT_TIMESTAMP WHERE sync_id = ?";
        try (Connection c = SQLiteConnection.get();
             PreparedStatement ps = c.prepareStatement(sql)) {
            ps.setLong(1, serverId);
            ps.setLong(2, syncId);
            ps.executeUpdate();
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }
}
