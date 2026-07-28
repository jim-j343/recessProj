package forum.database;

import forum.api.dto.UserDto;
import forum.models.Role;
import forum.models.User;
import forum.util.PasswordHash;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;

/** Data-access for users in the local SQLite cache. */
public class UserDao {

    public User findByEmail(String email) {
        String sql = "SELECT user_id, username, email, system_role, status, avatar FROM users WHERE email = ?";
        try (Connection c = SQLiteConnection.get();
             PreparedStatement ps = c.prepareStatement(sql)) {
            ps.setString(1, email == null ? "" : email.trim().toLowerCase());
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) return map(rs);
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return null;
    }

    /** Returns true if the stored password hash matches (plain compare for now — TODO: BCrypt). */
    public boolean passwordMatches(String email, String password) {
        String sql = "SELECT password_hash FROM users WHERE email = ?";
        try (Connection c = SQLiteConnection.get();
             PreparedStatement ps = c.prepareStatement(sql)) {
            ps.setString(1, email == null ? "" : email.trim().toLowerCase());
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    String stored = rs.getString(1);
                    if (stored == null) return false;
                    // match a local hash, or a legacy plaintext row (backward compat)
                    return stored.equals(PasswordHash.of(password)) || stored.equals(password);
                }
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return false;
    }

    public User insert(String username, String email, String password, Role role) {
        String sql = "INSERT INTO users(username, email, password_hash, system_role, status) VALUES(?,?,?,?, 'active')";
        try (Connection c = SQLiteConnection.get();
             PreparedStatement ps = c.prepareStatement(sql, Statement.RETURN_GENERATED_KEYS)) {
            ps.setString(1, username);
            ps.setString(2, email.trim().toLowerCase());
            ps.setString(3, PasswordHash.of(password));
            ps.setString(4, role.db());
            ps.executeUpdate();
            long id = 0;
            try (ResultSet keys = ps.getGeneratedKeys()) {
                if (keys.next()) id = keys.getLong(1);
            }
            return new User(id, username, email, role, "active");
        } catch (SQLException e) {
            e.printStackTrace();
            return null;
        }
    }

    /**
     * Cache a user returned by the server into the local SQLite store.
     * Keeps the row keyed by email; records the authoritative server id in
     * {@code server_id} and stores the just-used password so the account can
     * still be validated later while offline. Returns a {@link User} whose id
     * is the SERVER id (what the sync API expects for authored content).
     */
    public User upsertFromServer(UserDto dto, String password) {
        String email = dto.email == null ? "" : dto.email.trim().toLowerCase();
        String update = "UPDATE users SET username = ?, system_role = ?, status = ?, "
                + "server_id = ?, password_hash = ?, avatar = ?, updated_at = CURRENT_TIMESTAMP WHERE email = ?";
        try (Connection c = SQLiteConnection.get()) {
            int rows;
            try (PreparedStatement ps = c.prepareStatement(update)) {
                ps.setString(1, dto.username);
                ps.setString(2, dto.system_role);
                ps.setString(3, dto.status);
                ps.setLong(4, dto.user_id);
                ps.setString(5, PasswordHash.of(password));
                ps.setString(6, dto.avatar);
                ps.setString(7, email);
                rows = ps.executeUpdate();
            }
            if (rows == 0) {
                String insert = "INSERT INTO users(username, email, password_hash, system_role, status, server_id, avatar) "
                        + "VALUES(?,?,?,?,?,?,?)";
                try (PreparedStatement pi = c.prepareStatement(insert)) {
                    pi.setString(1, dto.username);
                    pi.setString(2, email);
                    pi.setString(3, PasswordHash.of(password));
                    pi.setString(4, dto.system_role);
                    pi.setString(5, dto.status);
                    pi.setLong(6, dto.user_id);
                    pi.setString(7, dto.avatar);
                    pi.executeUpdate();
                }
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return new User(dto.user_id, dto.username, dto.email, Role.fromDb(dto.system_role), dto.status, dto.avatar);
    }

    /** Seeds a demo account per role the first time the app runs (password: "password"). */
    public void seedDemoUsers() {
        // Read the count on its own connection FIRST and close it, so the
        // inserts below (separate connections) are not blocked by a read lock.
        if (!isEmpty()) return;
        insert("Demo Student",  "student@aces.test",  "password", Role.STUDENT);
        insert("Demo Lecturer", "lecturer@aces.test", "password", Role.LECTURER);
        insert("Demo Admin",    "admin@aces.test",    "password", Role.SYSTEM_ADMIN);
        System.out.println("[ACES] Seeded demo users (password: 'password').");
    }

    private boolean isEmpty() {
        try (Connection c = SQLiteConnection.get();
             Statement st = c.createStatement();
             ResultSet rs = st.executeQuery("SELECT COUNT(*) FROM users")) {
            return rs.next() && rs.getInt(1) == 0;
        } catch (SQLException e) {
            e.printStackTrace();
            return false;
        }
    }

    private User map(ResultSet rs) throws SQLException {
        String avatar = null;
        try { avatar = rs.getString("avatar"); } catch (SQLException ignore) {}
        return new User(
                rs.getLong("user_id"),
                rs.getString("username"),
                rs.getString("email"),
                Role.fromDb(rs.getString("system_role")),
                rs.getString("status"),
                avatar);
    }
}
