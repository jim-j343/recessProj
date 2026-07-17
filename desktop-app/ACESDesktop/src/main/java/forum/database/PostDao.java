package forum.database;

import forum.api.dto.PostDto;
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

    /**
     * Insert a post locally WITHOUT queuing it for sync. Used for the first
     * post of an offline-created topic: the topic push carries that content,
     * so the post must not be uploaded again as a duplicate reply.
     */
    public Post createLocalOnly(long topicId, long authorId, Long parentPostId, String content) {
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
            Post p = new Post();
            p.setPostId(id); p.setTopicId(topicId); p.setAuthorId(authorId);
            p.setParentPostId(parentPostId); p.setContent(content); p.setSynced(false);
            return p;
        } catch (SQLException e) {
            e.printStackTrace();
            return null;
        }
    }

    /** A single local post by id (used when pushing a queued reply). */
    public Post findById(long postId) {
        String sql = "SELECT p.post_id, p.topic_id, p.author_id, p.parent_post_id, p.content, "
                + "p.is_synced, p.created_at, COALESCE(u.username, u.email) AS author "
                + "FROM posts p LEFT JOIN users u ON u.user_id = p.author_id WHERE p.post_id = ?";
        try (Connection c = SQLiteConnection.get();
             PreparedStatement ps = c.prepareStatement(sql)) {
            ps.setLong(1, postId);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) return map(rs);
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return null;
    }

    /** Insert or update a post pulled from the server (keyed by server id, marked synced). */
    public void upsertFromServer(PostDto dto) {
        String update = "UPDATE posts SET topic_id = ?, author_id = ?, parent_post_id = ?, "
                + "content = ?, is_synced = 1, server_id = ?, created_at = COALESCE(?, created_at) "
                + "WHERE server_id = ?";
        try (Connection c = SQLiteConnection.get()) {
            int rows;
            try (PreparedStatement ps = c.prepareStatement(update)) {
                ps.setLong(1, dto.topic_id);
                ps.setLong(2, dto.author_id);
                if (dto.parent_post_id == null) ps.setNull(3, Types.INTEGER); else ps.setLong(3, dto.parent_post_id);
                ps.setString(4, dto.content);
                ps.setLong(5, dto.post_id);
                ps.setString(6, dto.created_at);
                ps.setLong(7, dto.post_id);
                rows = ps.executeUpdate();
            }
            if (rows == 0) {
                String insert = "INSERT OR REPLACE INTO posts"
                        + "(post_id, topic_id, author_id, parent_post_id, content, is_synced, server_id, created_at) "
                        + "VALUES(?,?,?,?,?,1,?,?)";
                try (PreparedStatement pi = c.prepareStatement(insert)) {
                    pi.setLong(1, dto.post_id);
                    pi.setLong(2, dto.topic_id);
                    pi.setLong(3, dto.author_id);
                    if (dto.parent_post_id == null) pi.setNull(4, Types.INTEGER); else pi.setLong(4, dto.parent_post_id);
                    pi.setString(5, dto.content);
                    pi.setLong(6, dto.post_id);
                    pi.setString(7, dto.created_at);
                    pi.executeUpdate();
                }
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    /** Records the server id + marks synced after a queued reply is pushed. */
    public void markSynced(long postId, long serverId) {
        try (Connection c = SQLiteConnection.get();
             PreparedStatement ps = c.prepareStatement(
                     "UPDATE posts SET server_id = ?, is_synced = 1 WHERE post_id = ?")) {
            ps.setLong(1, serverId);
            ps.setLong(2, postId);
            ps.executeUpdate();
        } catch (SQLException e) {
            e.printStackTrace();
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

    public void deleteLocally(long postId) {
        try (Connection c = SQLiteConnection.get();
             PreparedStatement ps = c.prepareStatement("DELETE FROM posts WHERE post_id = ?")) {
            ps.setLong(1, postId);
            ps.executeUpdate();
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    public void updateContentLocally(long postId, String content) {
        try (Connection c = SQLiteConnection.get();
             PreparedStatement ps = c.prepareStatement("UPDATE posts SET content = ? WHERE post_id = ?")) {
            ps.setString(1, content);
            ps.setLong(2, postId);
            ps.executeUpdate();
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }
}
