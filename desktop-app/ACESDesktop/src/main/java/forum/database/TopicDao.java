package forum.database;

import forum.api.dto.TopicDto;
import forum.models.Topic;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.util.ArrayList;
import java.util.List;

/** Data-access for topics in the local cache. */
public class TopicDao {

    private final LocalCacheDAO cache = new LocalCacheDAO();

    /** Most recent topics with author name + reply count for the dashboard list. */
    public List<Topic> listRecent(int limit) {
        String sql = """
            SELECT t.topic_id, t.group_id, t.creator_id, t.title, t.category,
                   t.is_flagged, t.created_at,
                   COALESCE(u.username, u.email) AS author,
                   MAX((SELECT COUNT(*) FROM posts p WHERE p.topic_id = t.topic_id), IFNULL(t.reply_count, 0)) AS replies
            FROM topics t
            LEFT JOIN users u ON u.user_id = t.creator_id
            ORDER BY t.created_at DESC, t.topic_id DESC
            LIMIT ?""";
        List<Topic> out = new ArrayList<>();
        try (Connection c = SQLiteConnection.get();
             PreparedStatement ps = c.prepareStatement(sql)) {
            ps.setInt(1, limit);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) out.add(map(rs));
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return out;
    }

    /** Most recent topics with author name + reply count for a specific group. */
    public List<Topic> listRecentForGroup(long groupId, int limit) {
        String sql = """
            SELECT t.topic_id, t.group_id, t.creator_id, t.title, t.category,
                   t.is_flagged, t.created_at,
                   COALESCE(u.username, u.email) AS author,
                   MAX((SELECT COUNT(*) FROM posts p WHERE p.topic_id = t.topic_id), IFNULL(t.reply_count, 0)) AS replies
            FROM topics t
            LEFT JOIN users u ON u.user_id = t.creator_id
            WHERE t.group_id = ?
            ORDER BY t.created_at DESC, t.topic_id DESC
            LIMIT ?""";
        List<Topic> out = new ArrayList<>();
        try (Connection c = SQLiteConnection.get();
             PreparedStatement ps = c.prepareStatement(sql)) {
            ps.setLong(1, groupId);
            ps.setInt(2, limit);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) out.add(map(rs));
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return out;
    }

    public Topic findById(long topicId) {
        String sql = """
            SELECT t.topic_id, t.group_id, t.creator_id, t.title, t.category,
                   t.is_flagged, t.created_at,
                   COALESCE(u.username, u.email) AS author, 0 AS replies
            FROM topics t LEFT JOIN users u ON u.user_id = t.creator_id
            WHERE t.topic_id = ?""";
        try (Connection c = SQLiteConnection.get();
             PreparedStatement ps = c.prepareStatement(sql)) {
            ps.setLong(1, topicId);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) return map(rs);
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return null;
    }

    /** Create a topic locally and queue it for sync. */
    public Topic create(long groupId, long creatorId, String title, String category) {
        String sql = "INSERT INTO topics(group_id, creator_id, title, category) VALUES(?,?,?,?)";
        try (Connection c = SQLiteConnection.get();
             PreparedStatement ps = c.prepareStatement(sql, Statement.RETURN_GENERATED_KEYS)) {
            ps.setLong(1, groupId);
            ps.setLong(2, creatorId);
            ps.setString(3, title);
            ps.setString(4, category);
            ps.executeUpdate();
            long id = 0;
            try (ResultSet keys = ps.getGeneratedKeys()) {
                if (keys.next()) id = keys.getLong(1);
            }
            cache.queueForSync("topics", id);
            Topic t = findById(id);
            return t != null ? t : basic(id, groupId, creatorId, title, category);
        } catch (SQLException e) {
            e.printStackTrace();
            return null;
        }
    }

    /**
     * Insert or update a topic pulled from the server. Server rows are keyed
     * locally by their server id (so cached posts line up), matched on
     * {@code server_id} to avoid duplicating a row that was pushed from here.
     */
    public void upsertFromServer(TopicDto dto) {
        String update = "UPDATE topics SET group_id = ?, creator_id = ?, title = ?, "
                + "category = ?, created_at = COALESCE(?, created_at), reply_count = ?, server_id = ? WHERE server_id = ?";
        try (Connection c = SQLiteConnection.get()) {
            int rows;
            try (PreparedStatement ps = c.prepareStatement(update)) {
                ps.setLong(1, dto.group_id);
                ps.setLong(2, dto.creator_id);
                ps.setString(3, dto.title);
                ps.setString(4, dto.category);
                ps.setString(5, dto.created_at);
                ps.setInt(6, dto.posts_count > 0 ? dto.posts_count : dto.replies);
                ps.setLong(7, dto.topic_id);
                ps.setLong(8, dto.topic_id);
                rows = ps.executeUpdate();
            }
            if (rows == 0) {
                String insert = "INSERT OR REPLACE INTO topics"
                        + "(topic_id, group_id, creator_id, title, category, is_flagged, server_id, created_at, reply_count) "
                        + "VALUES(?,?,?,?,?,0,?,?,?)";
                try (PreparedStatement pi = c.prepareStatement(insert)) {
                    pi.setLong(1, dto.topic_id);
                    pi.setLong(2, dto.group_id);
                    pi.setLong(3, dto.creator_id);
                    pi.setString(4, dto.title);
                    pi.setString(5, dto.category);
                    pi.setLong(6, dto.topic_id);
                    pi.setString(7, dto.created_at);
                    pi.setInt(8, dto.posts_count > 0 ? dto.posts_count : dto.replies);
                    pi.executeUpdate();
                }
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    /** The server id recorded for a local topic (0 if not yet synced). */
    public long serverIdFor(long topicId) {
        try (Connection c = SQLiteConnection.get();
             PreparedStatement ps = c.prepareStatement("SELECT server_id FROM topics WHERE topic_id = ?")) {
            ps.setLong(1, topicId);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) { long v = rs.getLong(1); return rs.wasNull() ? 0 : v; }
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return 0;
    }

    /** Content of the topic's first post (used as the body when pushing a new topic). */
    public String firstPostContent(long topicId) {
        try (Connection c = SQLiteConnection.get();
             PreparedStatement ps = c.prepareStatement(
                     "SELECT content FROM posts WHERE topic_id = ? ORDER BY post_id ASC LIMIT 1")) {
            ps.setLong(1, topicId);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) return rs.getString(1);
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return null;
    }

    /** Records the server id after a locally-created topic is pushed. */
    public void setServerId(long topicId, long serverId) {
        try (Connection c = SQLiteConnection.get();
             PreparedStatement ps = c.prepareStatement("UPDATE topics SET server_id = ? WHERE topic_id = ?")) {
            ps.setLong(1, serverId);
            ps.setLong(2, topicId);
            ps.executeUpdate();
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    /** Ensures a default group + a couple of demo topics exist on first run. */
    public void seedDemoIfEmpty() {
        if (count("\"groups\"") == 0) insertDefaultGroup();
        if (count("topics") == 0) {
            Topic a = create(1, 1, "Best practices for cleaning massive datasets in Python?", "Data Science");
            Topic b = create(1, 2, "Is micro-frontend architecture still viable in 2026?", "Architecture");
            PostDao posts = new PostDao();
            if (a != null) posts.create(a.getTopicId(), 2, null,
                    "Have you tried Polars? It handled a 50GB CSV far better than pandas for me.");
            if (b != null) posts.create(b.getTopicId(), 1, null,
                    "Module Federation still works, but the overhead is real. Monolith-first is trending again.");
            System.out.println("[ACES] Seeded demo group + topics.");
        }
    }

    private int count(String table) {
        try (Connection c = SQLiteConnection.get();
             Statement st = c.createStatement();
             ResultSet rs = st.executeQuery("SELECT COUNT(*) FROM " + table)) {
            return rs.next() ? rs.getInt(1) : 0;
        } catch (SQLException e) {
            e.printStackTrace();
            return -1;
        }
    }

    private void insertDefaultGroup() {
        try (Connection c = SQLiteConnection.get(); Statement st = c.createStatement()) {
            st.executeUpdate("INSERT INTO \"groups\"(group_id, admin_id, name, description) "
                    + "VALUES(1, 3, 'General', 'Default discussion group')");
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    private Topic basic(long id, long groupId, long creatorId, String title, String category) {
        Topic t = new Topic();
        t.setTopicId(id); t.setGroupId(groupId); t.setCreatorId(creatorId);
        t.setTitle(title); t.setCategory(category);
        return t;
    }

    private Topic map(ResultSet rs) throws SQLException {
        Topic t = new Topic();
        t.setTopicId(rs.getLong("topic_id"));
        t.setGroupId(rs.getLong("group_id"));
        t.setCreatorId(rs.getLong("creator_id"));
        t.setTitle(rs.getString("title"));
        t.setCategory(rs.getString("category"));
        t.setFlagged(rs.getInt("is_flagged") == 1);
        t.setCreatedAt(rs.getString("created_at"));
        t.setAuthorName(rs.getString("author"));
        t.setReplyCount(rs.getInt("replies"));
        return t;
    }
}
