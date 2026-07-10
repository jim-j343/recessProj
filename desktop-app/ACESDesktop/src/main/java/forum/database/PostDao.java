package forum.database;

import forum.models.Post;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.sql.Types;
import java.util.ArrayList;
import java.util.List;

/** Data-access for posts / replies in the local cache. */
public class PostDao {

    private final LocalCacheDAO cache = new LocalCacheDAO();

    public List<Post> listByTopic(long topicId) {
        String sql = """
            SELECT p.post_id, p.topic_id, p.author_id, p.parent_post_id, p.content,
                   p.is_synced, p.created_at, COALESCE(u.username, u.email) AS author
            FROM posts p LEFT JOIN users u ON u.user_id = p.author_id
            WHERE p.topic_id = ?
            ORDER BY p.created_at ASC, p.post_id ASC""";
        List<Post> out = new ArrayList<>();
        try (Connection c = SQLiteConnection.get();
             PreparedStatement ps = c.prepareStatement(sql)) {
            ps.setLong(1, topicId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) out.add(map(rs));
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return out;
    }

    /** Create a reply locally (is_synced = 0) and queue it for sync. */
    public Post create(long topicId, long authorId, Long parentPostId, String content) {
        String sql = "INSERT INTO posts(topic_id, author_id, parent_post_id, content, is_synced) VALUES(?,?,?,?,0)";
        try (Connection c = SQLiteConnection.get();
             PreparedStatement ps = c.prepareStatement(sql, Statement.RETURN_GENERATED_KEYS)) {
            ps.setLong(1, topicId);
            ps.setLong(2, authorId);
            if (parentPostId == null) ps.setNull(3, Types.INTEGER); else ps.setLong(3, parentPostId);
            ps.setString(4, content);
            ps.executeUpdate();
            long id = 0;
            try (ResultSet keys = ps.getGeneratedKeys()) {
                if (keys.next()) id = keys.getLong(1);
            }
            cache.queueForSync("posts", id);
            Post p = new Post();
            p.setPostId(id); p.setTopicId(topicId); p.setAuthorId(authorId);
            p.setParentPostId(parentPostId); p.setContent(content); p.setSynced(false);
            return p;
        } catch (SQLException e) {
            e.printStackTrace();
            return null;
        }
    }

    private Post map(ResultSet rs) throws SQLException {
        Post p = new Post();
        p.setPostId(rs.getLong("post_id"));
        p.setTopicId(rs.getLong("topic_id"));
        p.setAuthorId(rs.getLong("author_id"));
        long parent = rs.getLong("parent_post_id");
        p.setParentPostId(rs.wasNull() ? null : parent);
        p.setContent(rs.getString("content"));
        p.setSynced(rs.getInt("is_synced") == 1);
        p.setCreatedAt(rs.getString("created_at"));
        p.setAuthorName(rs.getString("author"));
        return p;
    }
}
