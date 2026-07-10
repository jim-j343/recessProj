package forum.database;

import forum.models.Role;
import forum.models.User;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;

/** Data-access for users in the local SQLite cache. */
public class UserDao {

    public User findByEmail(String email) {
        String sql = "SELECT user_id, username, email, system_role, status FROM users WHERE email = ?";
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
                if (rs.next()) return rs.getString(1).equals(password);
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
            ps.setString(3, password);          // TODO: hash before storing
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
        return new User(
                rs.getLong("user_id"),
                rs.getString("username"),
                rs.getString("email"),
                Role.fromDb(rs.getString("system_role")),
                rs.getString("status"));
    }
}
