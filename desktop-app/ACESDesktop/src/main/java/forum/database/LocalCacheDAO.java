package forum.database;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.SQLException;

/**
 * Generic helpers over the local cache. Feature DAOs (topics, posts, quizzes…)
 * will extend this as the offline-first data layer grows.
 */
public class LocalCacheDAO {

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
}
